<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;

class CreateToken extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     *
     * @var \Amazonpaymentservices\Fort\Model\Payment
     */
    protected $_apsModel;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @var
     */
    protected $_resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Sales\Model\Order\Config $orderConfig,
     * @param \Amazonpaymentservices\Fort\Model\Payment $apsModel,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Amazonpaymentservices\Fort\Model\Payment $apsModel,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;

        $this->_helper = $helperFort;

        $this->_apsModel = $apsModel;
        $this->_resultJsonFactory  = $resultJsonFactory;
    }
    
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
        $order_is_ok = true;
        $order_error_message = '';
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!($order = $this->_checkoutSession->getLastRealOrder())) {
            $order_error_message = __('Couldn\'t extract order information.');
        } elseif ($order->getState() != Order::STATE_NEW) {
            $order_error_message = __('Order was already processed or session information expired.');
        } elseif (!($additional_info = $order->getPayment()->getAdditionalInformation()) || !is_array($additional_info)) {
            $order_error_message = __('Couldn\'t extract payment information from order.');
        }
        if (!empty($order_error_message)) {
            $order_is_ok = false;
        }
        $form_data  = '';
        $form_url   = '';
        if ($order_is_ok) {
            $helper = $this->_helper;
            $orderId = $order->getRealOrderId();
            $url = $helper->getGatewayUrl();
            $baseCurrency = $helper->getBaseCurrency();
            $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
            $currency = $helper->getFortCurrency($baseCurrency, $orderCurrency);
            
            $postData = [
                'service_command' => 'CREATE_TOKEN',
                'access_code' => $helper->getMainConfigData('merchant_identifier'),
                'merchant_identifier' => $helper->getMainConfigData('access_code'),
                'merchant_reference' => $orderId,
                'language' => $helper->getLanguage(),
                'card_number' => $responseParams['card_number'],
                'expiry_date' => $responseParams['expiryDate'],
                'return_url' => $helper->getReturnUrl('amazonpaymentservicesfort/payment/responseOnline'),
                'currency' => $currency,
                'token_name' => 'TKN000001',
                'card_holder_name' => $responseParams['holderName']
            ];
            $postData['signature'] = $helper->calculateSignature($postData);
            $response = $helper->callApi($postData, $url);
            
            $form_data = $arrPaymentPageData['params'];
            $form_url = $arrPaymentPageData['url'];
        
            $paymentMethod= $order->getPayment()->getMethod();
            
            $order->addStatusHistoryComment('Aps :: Token Created: '.$paymentMethod);

            $order->save();
            $result = [
                'success' => true,
                'error_message' => $order_error_message,
                'order_id'  => $order->getIncrementId(),
                'params'  => $form_data,
                'url'  => $form_url,
            ];
        } else {
            $result = [
                    'success' => false,
                    'error_message' => $order_error_message,
            ];
        }
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($result);
        return $jsonResult;
    }
}
