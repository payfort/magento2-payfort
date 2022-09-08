<?php

namespace Amazonpaymentservices\Fort\Controller\Subscription;

use \Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    private $resultPageFactory;
    public $_storeManager;

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
            $this->_redirect($this->_storeManager->getStore()->getBaseUrl());
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $subId = $this->getRequest()->getParam('sub_id');

        if ($subId) {

            $date_now = date('Y-m-d H:i:s', strtotime('now'));
            
            $collections = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionsFactory')->create()->getCollection()
                    ->addFieldToFilter('id', ['eq' => $subId])
                    ->addFieldToFilter('customer_id', ['eq' => $customerSession->getId()]);

            if (!empty($collections->getData())) {
                foreach ($collections as $item) {
                    $item->setSubscriptionStatus(0);
                    $item->setUpdatedAt($date_now);
                }
                
                if ($collections->save()) {
                    $this->messageManager->addSuccess(__('Subscription status is changed successfully.'));
                } else {
                    $this->messageManager->addError(__('Subscription status change has been failed.'));
                }
            } else {
                $this->messageManager->addError(__('Subscription status change has been failed.'));
            }
            return $resultRedirect->setPath('apsfort/subscription/index');
        }
        return $resultRedirect->setPath('apsfort/subscription/index');
    }
}
