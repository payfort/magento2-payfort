<?php

namespace Amazonpaymentservices\Fort\Controller\Subscription;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    private $resultPageFactory;
    public $_storeManager;
    /**
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    protected $_messageManager;

    protected \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManager $storeManager,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Amazonpaymentservices\Fort\Helper\Data $helper
    ) {
        $this->_messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context);
        $this->_helper = $helper;
    }

    public function execute()
    {
        // $newOrder = $this->_helper->getOrderById('006000051');
        // $a = $newOrder->getPayment()->getExtensionAttributes()->getVaultPaymentToken()->getEntityId();
        // print_r($a); exit();

        $customerSession = ObjectManager::getInstance()->create('Magento\Customer\Model\Session');

        if (!($customerId = $customerSession->getId())) {
            $url = $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
            $ref_login_url = $this->_url->getUrl('customer/account/login', ['referer' => base64_encode($url)]);
            $this->_redirect($ref_login_url);
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('My Subscriptions'));
        return $resultPage;
    }
}
