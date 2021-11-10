<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class InstallmentResponseOnline extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $orderId = $this->getRequest()->getParam('merchant_reference');
        $order = $this->getOrderById($orderId);
        $responseParams = $this->getRequest()->getParams();
        
        $helper = $this->getHelper();
        $integrationType = $helper::INTEGRATION_TYPE_REDIRECTION;
        $success = $helper->handleFortResponse($responseParams, $integrationType);
        
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
}
