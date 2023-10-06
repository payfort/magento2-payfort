<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class Stcpayotp extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_cart;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Checkout\Model\Cart $cart, 
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement, 
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->quoteManagement = $quoteManagement;
        $this->_helper = $helperFort;
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
        
        $quote = $this->_cart->getQuote();
        $this->_helper->log(json_encode($quote->getData()));
        $quote->reserveOrderId()->save();
        $orderId = $quote->getReservedOrderId();

        $mobileNumber = $this->getRequest()->getParam('mobileNumber');
        $data = [];
        $data = $this->_helper->stcPayRequestOtp($orderId, $mobileNumber);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);
        return $resultJson;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
