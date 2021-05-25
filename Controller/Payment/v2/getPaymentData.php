<?php

namespace Payfort\Fort\Controller\Payment;
use Magento\Sales\Model\Order;
class getPaymentData extends \Magento\Framework\App\Action\Action
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
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Sales\Model\Order\Config $orderConfig,
     * @param \Magento\Framework\App\Http\Context $httpContext,
     * @param \Payfort\Fort\Model\Payment $payfortModel,
     * @param \Payfort\Fort\Helper\Data $helperFort,
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Payfort\Fort\Model\Payment $payfortModel,
        \Payfort\Fort\Helper\Data $helperFort,
        array $data = []
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_helper = $helperFort;
        $this->_payfortModel = $payfortModel;
    }
    
    public function execute()
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
                $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE);
            }
            elseif($this->_helper->isMerchantPageMethod2($order)) {
                $arrPaymentPageData = $this->_helper->getPaymentRequestParams($order, $helper::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2);
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
        else{
            $result = array(
                    'success' => false, 
                    'error_message' => $order_error_message,
            );
            echo json_encode($result);
            exit;
        }
        $result = array(
            'success' => true,
            'error_message' => $order_error_message,
            'order_id'  => $order->getIncrementId(),
            'params'  => $form_data,
            'url'  => $form_url,
        );
        echo json_encode($result);
        exit;
    }
}