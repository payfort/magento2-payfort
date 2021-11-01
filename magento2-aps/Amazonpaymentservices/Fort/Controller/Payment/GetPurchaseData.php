<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;

class GetPurchaseData extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;
    
    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonHelper;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     * @param \Magento\Framework\Controller\Result\JsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $checkoutSession;
        $this->_isScopePrivate = true;
        $this->_helper = $helperFort;
        $this->_jsonHelper = $jsonFactory;
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
        
        $order = $this->_checkoutSession->getLastRealOrder();

        $mobileNumber = $this->getRequest()->getParam('mobileNumber');
        $otp = $this->getRequest()->getParam('otp');
        $tenure = $this->getRequest()->getParam('tenure');
        $valuTenureAmount = $this->getRequest()->getParam('valu_tenure_amount');
        $valuTenureInterest = $this->getRequest()->getParam('valu_tenure_interest');
        $data = $this->_helper->merchantPurchaseValuFort($order, $mobileNumber, $otp, $tenure, $valuTenureAmount, $valuTenureInterest);

        $resultJson = $this->_jsonHelper->create();
        return $resultJson->setData($data);
    }
}
