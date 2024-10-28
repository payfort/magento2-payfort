<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Amazonpaymentservices\Fort\Helper\Data;
use Amazonpaymentservices\Fort\Model\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Config;

class GetStcPaymentData extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
     * @param Context $context ,
     * @param Session $checkoutSession ,
     * @param Config $orderConfig ,
     * @param Payment $apsModel ,
     * @param Data $helperFort
     * @param JsonFactory $resultJsonFactory
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
        $form_url   = '';
        if ($order_is_ok) {
            $helper = $this->_helper;
            $arrPaymentPageData = $this->_helper->getStcPaymentRequestParams($order, $responseParams);
            $form_url = $arrPaymentPageData['url'];

            $result = [
                'success' => true,
                'error_message' => $order_error_message,
                'order_id'  => $order->getIncrementId(),
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
