<?php
/**
 * APS Visa Checkout Response
 * php version 7.3.*
 *
 * @category APS
 * @package  APS
 * @author   APS <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     APS
 **/
namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Amazonpaymentservices Visa Checkout Page Response
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class VisaCheckout extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $order = $this->_checkoutSession->getLastRealOrder();
        $responseParams = $this->getRequest()->getParams();
        $helper = $this->getHelper();
        $helper->log('Checkout Session Data1:'.$this->_checkoutSession->getLastSuccessQuoteId());
        $response = $helper->visaCheckoutPageNotifyFort($responseParams, $order);
        $dataArr['success'] = true;
        $dataArr['params'] = $response;
        
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($dataArr);
        return $jsonResult;
    }
}
