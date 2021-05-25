<?php

namespace Payfort\Fort\Controller\Payment;

class Response extends \Payfort\Fort\Controller\Checkout
{
    
    public function execute()
    {
        
        $orderId            = $this->getRequest()->getParam('merchant_reference');
        $order              = $this->getOrderById($orderId);
        $responseParams     = $this->getRequest()->getParams();          
        $helper = $this->getHelper();  
        
        $integrationType    = $helper::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
        $paymentMethod      = $order->getPayment()->getMethod();

        if($paymentMethod == $helper::PAYFORT_FORT_PAYMENT_METHOD_CC) {
            $integrationType = $helper->getConfig('payment/payfort_fort_cc/integration_type');
        }
        elseif($paymentMethod == $helper::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS) {
            $integrationType = $helper->getConfig('payment/payfort_fort_installments/integration_type');
        }
        
        $success = $helper->handleFortResponse($responseParams, 'offline', $integrationType);
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        }
        else {
            $returnUrl = $this->getHelper()->getUrl('checkout/cart');
        }
        $this->orderRedirect($order, $returnUrl);
    }

}