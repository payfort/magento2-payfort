<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Response extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $valu = $this->getRequest()->getParam('payment_option');
        $responseParams     = $this->getRequest()->getParams();
        $orderId = $this->checkOrderId($valu, $responseParams);
        
        $order = $this->getOrderById($orderId);
        
        $responseCode = isset($responseParams['response_code']) ? $responseParams['response_code'] : '';
        $helper = $this->getHelper();
        
        $integrationType    = $helper::INTEGRATION_TYPE_REDIRECTION;
        $paymentMethod      = $order->getPayment()->getMethod();
        if ($paymentMethod == $helper::PAYMENT_METHOD_CC) {
            $integrationType = $helper->getConfig('payment/aps_fort_cc/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_INSTALLMENT) {
            $integrationType = $helper->getConfig('payment/aps_installment/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_VALU) {
            $integrationType = $helper->getConfig('payment/aps_fort_valu/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_VAULT) {
            $integrationType = $helper->getConfig('payment/aps_fort_vault/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_NAPS) {
            $integrationType = $helper->getConfig('payment/aps_fort_naps/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_KNET) {
            $integrationType = $helper->getConfig('payment/aps_knet/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_APPLE) {
            $integrationType = $helper->getConfig('payment/aps_apple/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_VISACHECKOUT) {
            $integrationType = $helper->getConfig('payment/aps_fort_visaco/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_OMANNET) {
            $integrationType = $helper->getConfig('payment/aps_omannet/integration_type');
        } elseif ($paymentMethod == $helper::PAYMENT_METHOD_BENEFIT) {
            $integrationType = $helper->getConfig('payment/aps_benefit/integration_type');
        }
        
        $success = $helper->handleFortResponse($responseParams, 'offline', $integrationType);
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
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

    private function checkOrderId($valu, $responseParams)
    {
        $orderId = '';
        if ($valu == 'VALU') {
            $sessionData = $this->_customerSession->getCustomValue();
            $orderId = $sessionData['orderId'];
            $orderId = $this->getOrderId($orderId);
        } else {
            $orderId = $responseParams['merchant_reference'];
        }
        return $orderId;
    }

    private function getOrderId($orderId)
    {
        $orderId = '';
        if (empty($orderId)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $orderCollectionFactory = $objectManager->get('\Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
            $collections = $orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('aps_valu_ref', ['eq'=>$this->getRequest()->getParam('merchant_reference')]);
            foreach ($collections as $collection) {
                $orderId = $collection->getIncrementId();
            }
        }
        return $orderId;
    }
}
