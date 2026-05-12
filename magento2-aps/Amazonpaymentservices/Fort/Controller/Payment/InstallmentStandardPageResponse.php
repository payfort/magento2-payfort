<?php
/**
 * Amazonpaymentservices Installment Standard Page Response
 * php version 8.2.*
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
 * Amazonpaymentservices Installment Standard Page Response
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class InstallmentstandardPageResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $response = $helper->installmentPageNotifyFort($responseParams, $order);
        if (!empty($response['response_code']) && $response['response_code'] == \Amazonpaymentservices\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK && isset($response['3ds_url'])) {

            $redirectURL =  '<script>window.top.location.href = "'.$response['3ds_url'].'"</script>';
                $response = $this->_resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                $response->setContents($redirectURL);
                return $response;
        } elseif (isset($response['fort_id'])) {
            $responseParams = $response;
        }
        $integrationType = $helper->getConfig('payment/aps_installment/integration_type');
        $success = $helper->handleFortResponse($responseParams, 'online', $integrationType);
        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $returnUrl = $helper->getUrl('checkout/onepage/success');
            } else {
                $returnUrl = $helper->getUrl('checkout/cart');

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
        
        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

        if ($integrationType == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::STANDARD) {
            $redirectURL =  '<script>window.top.location.href = "'.$returnUrl.'"</script>';
            $response = $this->_resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $response->setContents($redirectURL);
            return $response;
        } else {
            $this->orderRedirect($returnUrl);
        }

        return false;
    }
}
