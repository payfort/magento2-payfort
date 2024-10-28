<?php
/**
 * Amazonpaymentservices Apple Failed Response
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
 * Amazonpaymentservices Apple Failed Response
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class AppleFailedResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $helper = $this->getHelper();
        $integrationType = $helper->getConfig('payment/aps_installment/integration_type');
        $r = $helper->cancelOrder($order, 'You have cancelled the payment, please try again.');
        
        if ($r) {
            $helper->restoreQuote();
            $this->messageManager->addError('You have cancelled the payment, please try again.');
        }
        
        $returnUrl = $helper->getUrl('checkout/cart');

        $orderAfterPayment = $helper->getMainConfigData('orderafterpayment');

        $responseParams = $this->getRequest()->getParams();
        if ($orderAfterPayment === OrderOptions::DELETE_ORDER && !$helper->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
            $helper->deleteOrder($order);
        }
        
        $this->orderRedirect($returnUrl);
    }
}
