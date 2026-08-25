<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class GetOtpVerifyData extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    use \Amazonpaymentservices\Fort\Controller\FormKeyCsrfTrait;

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
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helperFort;
    }
    
    public function execute()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        $mobileNumber = $this->getRequest()->getParam('mobileNumber');
        $otp = $this->getRequest()->getParam('otp');

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (!$this->_helper->isValidValuMobileNumber($mobileNumber)) {
            $resultJson->setData([
                'response_code' => '',
                'response_message' => __('Mobile number is not valid.'),
            ]);
            return $resultJson;
        }

        $data = $this->_helper->merchantOtpVerifyValuFort($order, $mobileNumber, $otp);

        $resultJson->setData($data);
        return $resultJson;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
