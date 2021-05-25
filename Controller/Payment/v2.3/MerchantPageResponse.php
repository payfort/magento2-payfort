<?php

namespace Payfort\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class MerchantPageResponse extends \Payfort\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        
        $paymentMethod  = $order->getPayment()->getMethod();
        if ($paymentMethod  == $helper::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS) {
            $integrationType = $helper->getConfig('payment/payfort_fort_installments/integration_type');
        }
        else {
            $integrationType = $helper->getConfig('payment/payfort_fort_cc/integration_type');
        }
                 
        $success = $helper->handleFortResponse($responseParams, 'online', $integrationType);
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        }
        else {
            $returnUrl = $this->getHelper()->getUrl('checkout/cart');
        }
        $this->orderRedirect($order, $returnUrl);
    }

}
