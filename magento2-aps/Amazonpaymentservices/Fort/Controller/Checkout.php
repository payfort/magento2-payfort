<?php
/**
 * Amazonpaymentservices Checkout
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Controller;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\ResultFactory;

/**
 * Amazonpaymentservices Checkout
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
abstract class Checkout extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    //protected $_quote = false;
    
    /**
     *
     * @var \Amazonpaymentservices\Fort\Model\Payment
     */
    protected $_apsModel;
    
    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderModel;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $_resultFactory;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    protected $resultRedirectFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Amazonpaymentservices\Fort\Model\Payment $apsModel
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Amazonpaymentservices\Fort\Model\Payment $apsModel,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory
    ) {
        $this->_pageFactory = $resultPageFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_apsModel = $apsModel;
        $this->_helper = $helper;
        $this->_orderModel = $order;
        $this->_resultFactory = $resultFactory;
        $this->checkoutHelper = $checkoutHelper;
        $this->messageManager = $messageManager;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
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

    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */
    protected function _cancelCurrenctOrderPayment($errorMsg = '')
    {
        $gotoSection = false;
        $this->_helper->cancelCurrentOrder($errorMsg);
        if ($this->_checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }

        return $gotoSection;
    }
    
    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */
    protected function _cancelPayment($order, $errorMsg = '')
    {
        return $this->_helper->cancelOrder($order, $errorMsg);
    }
    
    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrderById($order_id)
    {
        $order_info = $this->_orderModel->loadByIncrementId($order_id);
        return $order_info;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    protected function getCustomerSession()
    {
        return $this->_customerSession;
    }

    protected function getApsModel()
    {
        return $this->_apsModel;
    }

    protected function getHelper()
    {
        return $this->_helper;
    }
    
    public function orderRedirect($returnUrl)
    {
        $this->getResponse()->setRedirect($returnUrl);
    }
}
