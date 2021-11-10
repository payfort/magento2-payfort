<?php

namespace Amazonpaymentservices\Fort\Cron;

class Paymentstatus
{
    protected $_orderCollectionFactory;

    protected $_logger;

    protected $_helper;

    protected $_order;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
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

        $cronConfig = $this->_helper->getConfig('payment/aps_fort_cron/interval');

        $date = date("Y-m-d H:i:s");
        $time = strtotime($date);
        $time = $time - ($cronConfig * 60);
        $date = date("Y-m-d H:i:s", $time);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $this->_order->getCollection()->addFieldToFilter('status', ['eq' => 'pending'])->addFieldToFilter('created_at', ['lteq' => $date]);
        foreach ($orders->getItems() as $order) {
            $this->Orderupdate($order);
        };
        
        return $this;
    }

    private function Orderupdate($order)
    {
        $this->_logger->debug('APS Cron pending order : '.$order->getIncrementId());
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getIncrementId();
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Valu::Code) {
            $orderId = $order->getApsValuRef();
        }
        $response = $this->_helper->checkOrderStatus($orderId, $paymentMethod);
        $this->_logger->debug('APS CHECK_VERIFY_CARD_STATUS Response : '.json_encode($response));

        if (!empty($response['response_code']) && $response['response_code'] === '12000') {
            $order->setState($order::STATE_PROCESSING)->save();
            $order->setStatus($order::STATE_PROCESSING)->save();
            
            $order->addStatusToHistory($order::STATE_PROCESSING, 'APS :: Order status changed.', true);
            $order->save();
            $this->_logger->debug('APS order status changed '.$order->getId());
        } else {
            $order->setState($order::STATE_CANCELED)->save();
            $order->setStatus($order::STATE_CANCELED)->save();
            
            $order->addStatusToHistory($order::STATE_CANCELED, 'APS :: Order status changed.', true);
            $order->save();
            $this->_logger->debug('APS order status changed '.$order->getId());
        }
    }
}
