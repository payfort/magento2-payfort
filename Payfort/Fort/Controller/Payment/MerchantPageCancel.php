<?php

namespace Payfort\Fort\Controller\Payment;

class MerchantPageCancel extends \Payfort\Fort\Controller\Checkout
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