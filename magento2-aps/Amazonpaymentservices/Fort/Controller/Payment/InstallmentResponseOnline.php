<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Amazonpaymentservices\Fort\Model\Config\Source\OrderOptions;
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

                // Only perform destructive order operations (delete) if the response
                // signature is valid. This prevents forged callback requests with
                // invalid signatures from triggering order deletion.
                $notIncludedParams = ['signature', 'aps_fort', 'integration_type', 'form_key'];
                $responseGatewayParams = array_diff_key($responseParams, array_flip($notIncludedParams));
                $calculatedSignature = $helper->calculateSignature($responseGatewayParams, 'response');
                $isSignatureValid = strtolower($calculatedSignature) === strtolower($responseParams['signature'] ?? '');

                $orderAfterPayment = $helper->getMainConfigData('orderafterpayment');

                if ($isSignatureValid && $orderAfterPayment === OrderOptions::DELETE_ORDER && !$helper->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
                    $helper->deleteOrder($order);
                }
            }
        }

        if ($success && $order->getQuoteId() == $this->_checkoutSession->getQuoteId()) {
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        }
        
        $this->orderRedirect($returnUrl);
    }
}
