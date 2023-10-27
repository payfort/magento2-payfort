<?php

namespace Amazonpaymentservices\Fort\Block\Adminhtml\Order\View;

class View extends \Magento\Backend\Block\Template
{
    /**
     * @var string[]
     */
    protected $_methodCodes = [
        \Amazonpaymentservices\Fort\Model\Method\Vault::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Cc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Naps::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Knet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Apple::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Installment::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Valu::CODE,
        \Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Stc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Tabby::CODE
    ];

    public function isApsPaymentMethod($paymentMethod)
    {
        if (in_array($paymentMethod, $this->_methodCodes)) {
            return true;
        }
        return false;
    }

    public function myFunction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderRepository = $objectManager->get('\Magento\Sales\Api\OrderRepositoryInterface');
        $order = $orderRepository->get($orderId);
        $payment = $order->getPayment();
        $data = $payment->getAdditionalData();
        $sendData['additionalData'] = [];
        if (!empty($data)) {
            $sendData['additionalData'] = json_decode($data, true);
        }
        $sendData['payment'] = $order->getPayment()->toArray();

        return $sendData;
    }

    public function fetchAllQuery($query)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        //@codingStandardsIgnoreStart
        $queryResponse = $connection->fetchAll($query);
        //@codingStandardsIgnoreEnd
        return $queryResponse;
    }
}
