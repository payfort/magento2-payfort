<?php
namespace Amazonpaymentservices\Fort\Block\Subscription;

use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class SubscriptionList extends \Magento\Framework\View\Element\Template
{
    protected $_orderCollectionFactory;
    protected $_customerSession;
    protected $_orderConfig;
    protected $orders;
    private $orderCollectionFactory;
    protected $_helper;
    protected $_countryFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        array $data = []
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_orderConfig = $orderConfig;
        $this->_helper = $helper;
        $this->_countryFactory = $countryFactory;
        parent::__construct($context, $data);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }
    
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }
        return $this->orderCollectionFactory;
    }

    public function getConnection()
    {
        $resource = ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        return $resource->getConnection();
    }
    
    public function getOrders()
    {
        $customerSession = ObjectManager::getInstance()->create('Magento\Customer\Model\Session');
        $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\Customer')->load($customerSession->getId());

        if (!($customerId = $customerSession->getId())) {
            return false;
        }
        if (!$this->orders) {
            $this->orders = $this->getOrderCollectionFactory()->create($customerId)->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'status',
                ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
            )->setOrder(
                'created_at',
                'desc'
            );
        }
        return $this->orders->getData();
    }
    
    public function getSubscriptionOrderDetail($orderIncrementId)
    {
        $connection = $this->getConnection();
        $subscriptionOrder = null;
        $subOrderIds = [];

        $query = $connection->select()->from(['table'=>'aps_subscription_orders'])->where('table.order_increment_id=?', $orderIncrementId);
        $subscriptionItems = $this->_helper->fetchAllQuery($query);
        
        foreach ($subscriptionItems as $subItems) {
            $subOrderIds[] = $subItems['aps_subscription_id'];
        }

        $query = $connection->select()->from(['table'=>'aps_subscriptions'])->where('table.id IN(?)', $subOrderIds);
        $subscriptionOrder = $this->_helper->fetchAllQuery($query);

        return $subscriptionOrder;
    }

    public function getRelatedOrders($orderIncrementId)
    {
        $connection = $this->getConnection();
        
        $query = $connection->select()->from(['table'=>'aps_subscription_orders'])->where('table.order_increment_id=?', $orderIncrementId);
        $subOrderDetail = $this->_helper->fetchAllQuery($query);
        
        if (!empty($subOrderDetail)) {
            $parentID = [];
            foreach ($subOrderDetail as $subOrder) {
                $parentIds[] = $subOrder['aps_subscription_id'];
            }
            
            $query = $connection->select()->from(['table'=>'aps_subscription_orders'])->where('table.aps_subscription_id IN(?)', $parentIds)->order(['table.created_at DESC']);
            $subOrderDetails = $this->_helper->fetchAllQuery($query);
            
            return $subOrderDetails;
        } else {
            return [];
        }
    }

    public function getSubscriptionItemDetail($subOrderId)
    {
        $connection = $this->getConnection();
        $subscriptionOrder = null;
        
        $query = $connection->select()->from(['table'=>'aps_subscriptions'])->where('table.id=?', $subOrderId);
        $subscriptionOrder = $this->_helper->fetchAllQuery($query);
        
        return $subscriptionOrder;
    }

    public function getRelatedItems($subOrderId)
    {
        $connection = $this->getConnection();
        $subOrderDetails = null;

        $query = $connection->select()->from(['table'=>'aps_subscription_orders'])->where('table.aps_subscription_id=?', $subOrderId)->order(['table.created_at DESC']);
        $subOrderDetails = $this->_helper->fetchAllQuery($query);

        return $subOrderDetails;
    }

    public function getOrder($orderId)
    {
        $order = ObjectManager::getInstance()->get('Magento\Sales\Api\Data\OrderInterface');
        $this->order = $order;
        $order = $this->order->load($orderId);  // pass your order id here
        return $order;
    }

    public function getBillingAddress($orderId)
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress()->getData();
        return $billingAddress;
    }

    public function getShippingAddress($orderId)
    {
        $order = $this->getOrder($orderId);
        $shippingAddress = $order->getShippingAddress()->getData();
        return $shippingAddress;
    }

    public function getPaymentMethod($orderId)
    {
        $order = $this->getOrder($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        return $methodTitle;
    }

    public function fetchAllQuery($query)
    {
        $queryResponse = $this->_helper->fetchAllQuery($query);
        return $queryResponse;
    }

    public function fetchGetParams()
    {
        $urlParams = $this->getRequest()->getParams();
        return $urlParams;
    }
    
    // protected function _prepareLayout()
    // {
    //     parent::_prepareLayout();
    //     if ($this->getOrders()) {
    //         $pager = $this->getLayout()->createBlock(
    //             \Magento\Theme\Block\Html\Pager::class,
    //             'sales.order.history.pager'
    //         )->setCollection(
    //             $this->getOrders()
    //         );
    //         $this->setChild('pager', $pager);
    //         $this->getOrders()->load();
    //     }
    //     return $this;
    // }
    
    // public function getPagerHtml()
    // {
    //     return $this->getChildHtml('pager');
    // }
    
    public function getViewUrl($orderId)
    {
        return $this->getUrl('apsfort/subscription/view', ['order_id' => $orderId]);
    }
    
    public function getCancelUrl($subId)
    {
        return $this->getUrl('apsfort/subscription/cancel', ['sub_id' => $subId]);
    }
    
    public function getMainOrderlUrl()
    {
        return $this->getUrl('sales/order/view/');
    }
    
    public function getBackUrl()
    {
        return $this->getUrl('apsfort/subscription/index');
    }

    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
