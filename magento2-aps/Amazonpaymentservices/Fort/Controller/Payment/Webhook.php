<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
        $responseParams = $this->getRequest()->getContent();
        
        if (!empty($responseParams)) {
            $responseParams = json_decode($responseParams, 1);
            if (empty($responseParams)) {
                $responseParams = $this->getRequest()->getParams();
            }
        }
        $this->_helper->log('WebHook Data:'.json_encode($responseParams));

        $responseCode = isset($responseParams['response_code']) ? $responseParams['response_code'] : '';
        $this->_helper->log('WebHook Data:'.$responseCode);
        if ($responseCode == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_CAPTURE_STATUS) {
            $this->_helper->captureAuthorize($responseParams);
        } elseif ($responseCode == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_VOID_STATUS) {
            $this->_helper->captureAuthorize($responseParams);
        } elseif ($responseCode == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_REFUND_STATUS) {
            $this->_helper->refundAps($responseParams);
        } else{
            $this->_helper->handleFortResponse($responseParams, 'offline');
        }
        $result = [];
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($result);
        return $jsonResult;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
