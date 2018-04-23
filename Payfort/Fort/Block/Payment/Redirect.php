<?php

namespace Payfort\Fort\Block\Payment;

use Magento\Customer\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
//use Payfort\Fort\Model\Payment;
use Payfort\Fort\Helper\Data;

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
     *
     * @var \Payfort\Fort\Model\Payment
     */
    protected $_payfortModel;

    /**
     * Helper
     *
     * @var \Payfort\Fort\Helper\Data
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
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Payfort\Fort\Model\Payment $payfortModel,
        \Payfort\Fort\Helper\Data $helperFort,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;

        $this->_helper = $helperFort;

        $this->_payfortModel = $payfortModel;
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
        if( !($order = $this->_checkoutSession->getLastRealOrder()) )
            $order_error_message = __( 'Couldn\'t extract order information.' );

        elseif( $order->getState() != Order::STATE_NEW )
            $order_error_message = __( 'Order was already processed or session information expired.' );

        elseif( !($additional_info = $order->getPayment()->getAdditionalInformation())
             or !is_array( $additional_info ) )
            $order_error_message = __( 'Couldn\'t extract payment information from order.' );

        if( !empty( $order_error_message ) )
            $order_is_ok = false;

        $form_data  = '';
        $form_url   = '';
        if( $order_is_ok )
        {
            $helper = $this->_helper;
            if($this->_helper->isMerchantPageMethod($order)) {
                $this->_template = 'merchant-page.phtml';
                $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE);
            }
            else {
                $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION);
            }
            
            $form_data = $arrPaymentPageData['params'];
            $form_url = $arrPaymentPageData['url'];
        
            $paymentMethod= $order->getPayment()->getMethod();
            
            $order->addStatusHistoryComment( 'PayfortFort :: redirecting to payment page with Method: '.$paymentMethod );

            $order->save();
        }
        
        $this->addData(
            [
                'order_ok' => $order_is_ok,
                'error_message' => $order_error_message,
                'order_id'  => $order->getIncrementId(),
                'form_data'  => $form_data,
                'form_url'  => $form_url,
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
}
