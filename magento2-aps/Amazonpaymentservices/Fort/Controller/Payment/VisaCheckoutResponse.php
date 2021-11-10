<?php
/**
 * Amazonpaymentservices Visa Checkout Response
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Amazonpaymentservices Visa Checkout Page Response
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class VisaCheckoutResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $helper->log('Checkout Session Data2:'.$this->_checkoutSession->getLastSuccessQuoteId());
        $helper->log('Checkout Session order Id Data2:'.$this->_checkoutSession->getLastRealOrderId());
        
        $orderId = $this->getRequest()->getParam('merchant_reference');
        $order = $this->getOrderById($orderId);
        $responseParams = $this->getRequest()->getParams();
        $helper->log('Response:'.json_encode($responseParams));
        if (isset($responseParams['form_key'])) {
            unset($responseParams['form_key']);
        }
        
        $integrationType = $helper->getConfig('payment/aps_fort_visaco/integration_type');
        $success = '';
        if (!empty($responseParams['response_code']) && $responseParams['response_code'] == \Amazonpaymentservices\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK && isset($responseParams['3ds_url'])) {
            $success = $helper->handleFortResponse($responseParams, 'online', $integrationType, 'h2h');
        } else {
            $success = $helper->handleFortResponse($responseParams, 'offline', $integrationType);
        }
        
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
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
        
        $helper->log('Checkout Session Data3:'.$this->_checkoutSession->getLastSuccessQuoteId());
        $helper->log('Checkout Session order Id Data3:'.$this->_checkoutSession->getLastRealOrderId());
        $this->orderRedirect($returnUrl);
    }
}
