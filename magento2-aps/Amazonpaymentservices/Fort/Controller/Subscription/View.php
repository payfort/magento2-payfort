<?php

namespace Amazonpaymentservices\Fort\Controller\Subscription;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    private $resultPageFactory;
    public $_storeManager;
    protected $_messageManager;
    protected $currentCustomer;


    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManager $storeManager,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    ) {
        $this->_messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context);
    }

    public function execute()
    {
        
        $customerSession = ObjectManager::getInstance()->create('Magento\Customer\Model\Session');

        if (!($customerId = $customerSession->getId())) {
            $url = $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
            $ref_login_url = $this->_url->getUrl('customer/account/login', ['referer' => base64_encode($url)]);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($ref_login_url);
            return $resultRedirect;
        }

        // Validate order_id parameter exists
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Invalid subscription.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('apsfort/subscription/index');
        }

        // Verify the subscription belongs to the authenticated customer
        $resource = ObjectManager::getInstance()->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('aps_subscriptions');

        $query = $connection->select()
            ->from($tableName)
            ->where('id = ?', (int)$orderId)
            ->where('customer_id = ?', (int)$customerId);
        $subscription = $connection->fetchRow($query);

        if (!$subscription) {
            $this->messageManager->addErrorMessage(__('You do not have permission to view this subscription.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('apsfort/subscription/index');
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('apsfort/subscription/index');
        }
        $resultPage->getConfig()->getTitle()->set(__('Order View'));
        return $resultPage;
    }
}
