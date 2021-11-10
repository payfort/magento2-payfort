<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class OrderCancel extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $helper = $this->getHelper();
        
        $order = $this->_checkoutSession->getLastRealOrder();
        if ($order->getState() != $order::STATE_PROCESSING) {
            $success = $helper->orderFailed($order, 'Payment cancelled by user', '');
            $helper->restoreQuote();
            $this->messageManager->addError(__('Payment cancelled by user'));
        }
        $returnUrl = $helper->getUrl('checkout/cart');
        
        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        
        $this->orderRedirect($returnUrl);
    }
}
