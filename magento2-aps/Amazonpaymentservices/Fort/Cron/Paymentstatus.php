<?php

namespace Amazonpaymentservices\Fort\Cron;

use Amazonpaymentservices\Fort\Helper\Data;
use Amazonpaymentservices\Fort\Model\Config\Source\OrderOptions;

class Paymentstatus
{
    protected $_orderCollectionFactory;

    protected $_logger;

    protected $_helper;

    protected $_order;

    protected $_methodCodes = [
        \Amazonpaymentservices\Fort\Model\Method\Vault::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Cc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Naps::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Knet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Apple::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Installment::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Valu::CODE,
        \Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE,
        \Amazonpaymentservices\Fort\Model\Method\OmanNet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Benefit::CODE
    ];

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        Data $helper,
        \Magento\Sales\Model\Order $order
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_order = $order;
    }

    public function execute()
    {
        $this->_logger->debug('APS Cron');

        $cronConfig = (int)$this->_helper->getConfig('payment/aps_fort_cron/interval');

        $date = date("Y-m-d H:i:s");
        $time = strtotime($date);
        $time = $time - ($cronConfig * 60);
        $date = date("Y-m-d H:i:s", (int)$time);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $this->_order
            ->getCollection()
            ->addFieldToFilter(
                'status',
                [
                    ['eq' => 'pending'],
                    ['eq' => 'holded'],
                ]
            )

            ->addFieldToFilter('created_at', ['lteq' => $date])
        ;
        foreach ($orders->getItems() as $order) {
            if (in_array($order->getPayment()->getMethod(), $this->_methodCodes)) {
                $this->orderUpdate($order);
            }
        }

        return $this;
    }

    private function orderUpdate($order)
    {
        $this->_logger->debug('APS Cron pending order : '.$order->getIncrementId());
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getIncrementId();
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Valu::CODE) {
            $orderId = $this->_helper->getApsValuRefFromOrderParams($order->getId(), null);
        }
        $response = $this->_helper->checkOrderStatus($orderId, $paymentMethod);
        $this->_logger->debug('APS CHECK_VERIFY_CARD_STATUS Response : '.json_encode($response));

        $transactionCode = $response['transaction_code'] ?? '';

        if (
            ( ($response['response_code'] ?? '') === '12000') &&
            ( $transactionCode === Data::PAYMENT_METHOD_AUTH_SUCCESS_STATUS
                || $transactionCode === Data::PAYMENT_METHOD_PURCHASE_SUCCESS_STATUS)
        ) {
            $this->_helper->log('process order 2');
            $this->_helper->handleSendingInvoice($order, $response);

            $order->setState($order::STATE_PROCESSING)->save();
            $order->setStatus($order::STATE_PROCESSING)->save();

            $order->addStatusToHistory($order::STATE_PROCESSING, 'APS :: Order status changed.', true);
            $order->save();
            $this->_logger->debug('APS order status changed '.$order->getId());
        } elseif (
            ($response['status'] ?? '') === '12'
            && !$this->_helper->isOrderResponseOnHold($transactionCode)
            && $this->_helper->canCancelOrder($order)
        ) {
            $orderAfterPayment = $this->_helper->getMainConfigData('orderafterpayment');
            if ($orderAfterPayment === OrderOptions::DELETE_ORDER) {
                $this->_helper->deleteOrder($order);

                $this->_logger->debug('APS order status changed ' . $order->getId() . '. Deleted because of unrecognized response code.');
            } else {
                $this->_helper->cancelOrder($order, 'You have cancelled the payment, please try again.');

                $this->_logger->debug('APS order status changed ' . $order->getId() . '. Cancelled because of unrecognized response code.');
            }
        }
    }
}
