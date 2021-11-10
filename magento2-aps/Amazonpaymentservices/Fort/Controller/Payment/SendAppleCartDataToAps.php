<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;

class SendAppleCartDataToAps extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $responseParams = $this->getRequest()->getParams();
        $jsonData = json_decode($responseParams['data']);
        $jsonData = $jsonData->data;

        $shipData = json_decode($responseParams['shipData']);
        $shipData = $shipData->shipData;

        $helper = $this->getHelper();
        $response = $helper->applePayCartResponse($jsonData, $shipData);
        $success = $response['success'];
        $order = $response['order'];

        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
            $this->_checkoutSession->clearQuote();
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $returnUrl = $helper->getUrl('checkout/onepage/success');
            } else {
                $returnUrl = $helper->getUrl('checkout/cart');
            }
        }
        
        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        
        $this->orderRedirect($returnUrl);
    }
}
