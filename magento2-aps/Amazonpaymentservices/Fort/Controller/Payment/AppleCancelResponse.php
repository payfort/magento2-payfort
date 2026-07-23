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
class AppleCancelResponse extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $returnUrl = $helper->getUrl('checkout/cart');

        if (!$order->getId() || $order->getQuoteId() != $this->_checkoutSession->getQuoteId()) {
            $this->orderRedirect($returnUrl);
            return;
        }

        $this->messageManager
        ->addError(__('You have cancelled the payment, please try again.'));

        $orderAfterPayment = $helper->getMainConfigData('orderafterpayment');

        $responseParams = $this->getRequest()->getParams();
        if ($orderAfterPayment === OrderOptions::DELETE_ORDER && !$helper->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
            $helper->deleteOrder($order);
        }
        
        $this->orderRedirect($returnUrl);
    }
}
