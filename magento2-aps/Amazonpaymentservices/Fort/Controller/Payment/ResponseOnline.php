<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Amazonpaymentservices\Fort\Model\Config\Source\OrderOptions;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Amazonpaymentservices\Fort\Model\Payment;
use Magento\Framework\Controller\ResultFactory;

class ResponseOnline extends \Amazonpaymentservices\Fort\Controller\Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $helper->log('Checkout Session Data2:'.$this->_checkoutSession->getLastSuccessQuoteId());
        $helper->log('Checkout Session order Id Data2:'.$this->_checkoutSession->getLastRealOrderId());
        $integrationType = $helper::INTEGRATION_TYPE_REDIRECTION;
        $success = $helper->handleFortResponse($responseParams, 'online', $integrationType);

        if ($success) {
            $returnUrl = $helper->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $returnUrl = $helper->getUrl('checkout/onepage/success');
            } else {
                $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $result->setPath('checkout/cart');

                $orderAfterPayment = $helper->getMainConfigData('orderafterpayment');

                if ($orderAfterPayment === OrderOptions::DELETE_ORDER && !$helper->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
                    $helper->deleteOrder($order);
                }

                return $result;
            }
        }

        $this->_checkoutSession->setLastOrderId($order->getId());
        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

        $this->orderRedirect($returnUrl);
    }
}
