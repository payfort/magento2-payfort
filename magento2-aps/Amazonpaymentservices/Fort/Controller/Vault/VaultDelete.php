<?php

namespace Amazonpaymentservices\Fort\Controller\Vault;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;

class VaultDelete extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
    protected $_jsonFactory;

    /**
     *
     * @var \Magento\Vault\Model\ResourceModel\PaymentToken
     */
    protected $paymentToken;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $modelSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     * @param \Magento\Framework\Controller\Result\JsonFactory,
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Vault\Model\ResourceModel\PaymentToken $paymentToken,
        \Magento\Customer\Model\Session $modelSession
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_isScopePrivate = true;
        $this->_helper = $helperFort;
        $this->_jsonHelper = $jsonFactory;
        $this->paymentToken = $paymentToken;
        $this->modelSession = $modelSession;
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
        $publicHash = $this->getRequest()->getParam('publicHash');
        
        $customerId = $this->modelSession->getCustomer()->getId();
        $tokenData = $this->paymentToken->getByPublicHash($publicHash, $customerId);
        $data = [];
        if (!empty($tokenData)) {
            $details = json_decode($tokenData['details']);
            $data = $this->_helper->tokenChangeStatus($tokenData['gateway_token'], $details->orderId, 'INACTIVE');
        }
        $resultJson = $this->_jsonHelper->create();
        return $resultJson->setData($data);
    }
}
