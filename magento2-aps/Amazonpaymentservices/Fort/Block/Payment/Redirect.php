<?php

namespace Amazonpaymentservices\Fort\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Amazonpaymentservices\Fort\Helper\Data;

class Redirect extends \Magento\Framework\View\Element\Template
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
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'redirect.phtml';

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->_helper = $helperFort;
        $this->_isScopePrivate = true;
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order_is_ok = true;
        $order_error_message = '';
        $order = '';
        if (!($order = $this->_checkoutSession->getLastRealOrder())) {
            $order_error_message = __('Couldn\'t extract order information.');
        } elseif ($order->getState() != Order::STATE_NEW) {
            $order_error_message = __('Order was already processed or session information expired.');
        } elseif (!($additional_info = $order->getPayment()->getAdditionalInformation())
             || !is_array($additional_info)) {
            $order_error_message = __('Couldn\'t extract payment information from order.');
        }
        if (!empty($order_error_message)) {
            $order_is_ok = false;
        }
        $form_data  = '';
        $form_url   = '';
        $arrPaymentPageData = [];
        $this->_helper->log('Redirect 2');
        $this->_helper->log(json_encode($order));
        $this->_helper->log($order_is_ok);
        $this->_helper->log($order->getState());
        $this->_helper->log($order_error_message);
        if ($order_is_ok) {
            $helper = $this->_helper;
            $paymentMethod= $order->getPayment()->getMethod();
            if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Installment::CODE) {
                if ($this->_helper->isStandardMethod($order)) {
                    $this->_template = 'merchant-page.phtml';
                    $arrPaymentPageData = $this->_helper->getInstallmentRequestParams($order, $helper::INTEGRATION_TYPE_STANDARD);
                } else {
                    $arrPaymentPageData = $this->_helper->getInstallmentRequestParams($order, $helper::INTEGRATION_TYPE_REDIRECTION);
                }
            } elseif ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Stc::CODE) {
                $arrPaymentPageData = $this->_helper->getStcRequestParams($order, $helper::INTEGRATION_TYPE_REDIRECTION);
            } elseif ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Tabby::CODE) {
                $arrPaymentPageData = $this->_helper->getTabbyRequestParams($order, $helper::INTEGRATION_TYPE_REDIRECTION);
            } else {
                if ($this->_helper->isStandardMethod($order)) {
                    $this->_template = 'merchant-page.phtml';
                    $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::INTEGRATION_TYPE_STANDARD);
                } else {
                    $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::INTEGRATION_TYPE_REDIRECTION);
                }
            }
            $form_data = $arrPaymentPageData['params'];
            $form_url = $arrPaymentPageData['url'];
            $order->addStatusHistoryComment('AmazonpaymentservicesFort :: redirecting to payment page with Method: '.$paymentMethod);
            $order->save();
        }

        $this->addData(
            [
                'order_ok' => $order_is_ok,
                'error_message' => $order_error_message,
                'order_id'  => $order->getIncrementId(),
                'form_data'  => $form_data,
                'form_url'  => $form_url
            ]
        );
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Get Url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('amazonpaymentservicesfort/payment/orderCancel');
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
