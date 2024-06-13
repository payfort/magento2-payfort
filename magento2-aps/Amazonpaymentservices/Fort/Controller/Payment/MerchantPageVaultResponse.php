<?php
/**
 * Amazonpaymentservices Merchant Page Response
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

use Amazonpaymentservices\Fort\Model\Config\Source\OrderOptions;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Amazonpaymentservices Merchant Page Response
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class MerchantPageVaultResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $paymentMethod = $order->getPayment()->getMethod();
        $integrationType = $helper->getConfig('payment/aps_fort_cc/integration_type');
        $success = '';
        if ($responseParams['response_code'] == \Amazonpaymentservices\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK && isset($responseParams['3ds_url'])) {
            $success = $helper->handleFortResponse($responseParams, 'online', $integrationType, 'h2h');
            if (isset($success['redirect']) && $success['redirect'] == true) {
                $redirectURL =  '<script>window.top.location.href = "'.$success['url'].'"</script>';
                $response = $this->_resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                $response->setContents($redirectURL);
                return $response;
            }
        } elseif ($paymentMethod == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_CC || $paymentMethod == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_INSTALLMENT || $paymentMethod == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_VAULT) {
            $success = $helper->handleFortResponse($responseParams, 'offline', $integrationType);
            if (isset($success['redirect']) && $success['redirect'] == true) {
                $redirectURL =  '<script>window.top.location.href = "'.$success['url'].'"</script>';
                $response = $this->_resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                $response->setContents($redirectURL);
                return $response;
            }
        }
            
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $returnUrl = $helper->getUrl('checkout/onepage/success');
            } else {
                $returnUrl = $helper->getUrl('checkout/cart');

                $orderAfterPayment = $helper->getMainConfigData('orderafterpayment');

                if ($orderAfterPayment === OrderOptions::DELETE_ORDER && !$helper->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
                    $helper->deleteOrder($order);
                }
            }
        }
        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

        $this->orderRedirect($returnUrl);
    }
}
