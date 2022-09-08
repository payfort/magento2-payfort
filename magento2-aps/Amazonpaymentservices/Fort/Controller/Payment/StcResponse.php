<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Amazonpaymentservices\Fort\Model\Payment;

class StcResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    public function execute()
    {
        $paymentOption = $this->getRequest()->getParam('payment_option');
        $responseParams     = $this->getRequest()->getParams();
        $orderId = $this->checkOrderId($responseParams);
        $order = $this->getOrderById($orderId);
        $helper = $this->getHelper();
        $helper->log('Request Param:'.json_encode($responseParams));
        
        $connection = $helper->_connection->getConnection();

        $integrationType = $helper::INTEGRATION_TYPE_REDIRECTION;
        $success = $helper->handleFortResponse($responseParams, 'online', $integrationType);
        if ($success) {
            $this->stcSaveCard($connection, $order, $responseParams);
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $this->stcSaveCard($connection, $order, $responseParams);
                $returnUrl = $helper->getUrl('checkout/onepage/success');
            } else {
                $returnUrl = $this->getHelper()->getUrl('checkout/cart');
            }
        }

        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        
        $this->orderRedirect($returnUrl);
    }

    private function checkOrderId($responseParams)
    {
        $orderId = '';
        $sessionData = $this->_customerSession->getCustomValue();
        $orderId = $sessionData['orderId'];
        $orderId = $this->getOrderId($orderId);
        return $orderId;
    }

    private function getOrderId($orderId)
    {
        if (empty($orderId)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $orderCollectionFactory = $objectManager->get('\Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
            $collections = $orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('aps_stc_ref', ['eq'=>$this->getRequest()->getParam('merchant_reference')]);
            foreach ($collections as $collection) {
                $orderId = $collection->getIncrementId();
            }
        }
        return $orderId;
    }

    private function stcSaveCard($connection, $order, $responseParams)
    {
        if ($this->getHelper()->getConfig('payment/aps_fort_stc/token') == 1) {
            $query = $connection->select()->from(['table'=>'aps_stc_relation'], ['id'])->where('table.token_name=?', $responseParams['token_name']);
            $stcTokenData = $connection->fetchRow($query);
            if (empty($stcTokenData)) {
                $connection->insert(
                    $connection->getTableName('aps_stc_relation'),
                    [
                        'customer_id' => $order->getCustomerId(),
                        'order_increment_id' => $order->getIncrementId(),
                        'token_name' => $responseParams['token_name'],
                        'phone_number' => $responseParams["phone_number"],
                        'added_date' => date('Y-m-d H:i:s'),
                    ]
                );
            }
            $connection->insert(
                $connection->getTableName('aps_stc_token_order_relation'),
                [
                    'order_increment_id' => $order->getIncrementId(),
                    'token_name' => $responseParams['token_name']
                ]
            );
        }
    }
}
