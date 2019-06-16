<?php

namespace Payfort\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class MerchantPageCancel extends \Payfort\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    public function execute()
    {
        $this->_cancelCurrenctOrderPayment('User has cancel the payment');
        $this->_checkoutSession->restoreQuote();
        
        $message = __('You have canceled the payment.');
        $this->messageManager->addError( $message );            
        $returnUrl = $this->getHelper()->getUrl('checkout/cart');
        $this->getResponse()->setRedirect($returnUrl);
    }
}

?>