<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;

class GetVaultPaymentData extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
     * @var \Magento\Vault\Model\ResourceModel\PaymentToken
     */
    protected $_paymentToken;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

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
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Vault\Model\ResourceModel\PaymentToken $paymentToken,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;

        $this->_helper = $helperFort;

        $this->_apsModel = $apsModel;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_paymentToken = $paymentToken;
        $this->_customerSession = $customerSession;
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

        $customerId = $this->_customerSession->getCustomer()->getId();
        $tokenData = $this->_paymentToken->getByPublicHash($responseParams['publicHash'], $customerId);
        $tokenName = '';
        if (!empty($tokenData)) {
            $details = json_decode($tokenData['details']);
            $tokenName = $tokenData['gateway_token'];
        } else {
            $order_is_ok = false;
        }
        if ($order_is_ok) {
            $helper = $this->_helper;
            if ($this->_helper->getConfig('payment/aps_installment/integration_type') == \Amazonpaymentservices\Fort\Helper\Data::INTEGRATION_TYPE_EMBEDED && isset($responseParams['issuer_code'])) {
                $arrPaymentPageData = $this->_helper->getInstallmentVault($order, $tokenName, $responseParams);
            } else {
                if ($this->_helper->isStandardMethod($order)) {
                    
                    $arrPaymentPageData = $this->_helper->getVaultPaymentRequestParams($order, $helper::INTEGRATION_TYPE_STANDARD, $tokenName, $responseParams['cvv']);
                } elseif ($this->_helper->isHostedMethod($order)) {
                    $arrPaymentPageData = $this->_helper->getVaultPaymentRequestParams($order, $helper::INTEGRATION_TYPE_HOSTED, $tokenName, $responseParams['cvv']);
                } else {
                    $arrPaymentPageData = $this->_helper->getVaultPaymentRequestParams($order, $helper::INTEGRATION_TYPE_REDIRECTION, $tokenName, '');
                }
            }
            $form_data = $arrPaymentPageData['params'];
            $form_url = $arrPaymentPageData['url'];
        
            $paymentMethod= $order->getPayment()->getMethod();
            
            $order->addStatusHistoryComment('Aps :: redirecting to payment page with Method: '.$paymentMethod);

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
    public function getCacheLifetime()
    {
        return null;
    }
}
