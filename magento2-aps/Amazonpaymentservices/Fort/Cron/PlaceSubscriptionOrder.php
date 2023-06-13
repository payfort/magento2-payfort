<?php

namespace Amazonpaymentservices\Fort\Cron;

class PlaceSubscriptionOrder
{
    protected $_orderCollectionFactory;

    protected $_logger;

    protected $_helper;

    protected $_order;

    protected $_connection;

    protected $_cronHelper;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        \Amazonpaymentservices\Fort\Helper\Cron $cronHelper,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\ResourceConnection $connect
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_cronHelper = $cronHelper;
        $this->_order = $order;
        $this->_connection = $connect;
    }

    public function execute()
    {
        $this->_helper->log('APS Subscription Cron');

        $connection = $this->_connection->getConnection();

        /** Select current date subscription order */
        $dateNow = date('Y-m-d');
        $this->_helper->log('Date Now: '.$dateNow);

        $query = $connection->select()->from(['table'=>'aps_subscriptions'])->where('table.next_payment_date=?', $dateNow)->where('table.subscription_status = 1');
        $getAllSubscriptionOrders = $this->_helper->fetchAllQuery($query);

        /** Prepare and Place subscription orders */
        foreach ($getAllSubscriptionOrders as $subscriptionOrder) {
            $order = [];
            $this->_helper->log('Subscription OrderPicked:'.$subscriptionOrder['order_increment_id']);
            $this->_cronHelper->createCronOrder($subscriptionOrder['qty'], $subscriptionOrder['id'], $subscriptionOrder['order_increment_id'], $subscriptionOrder['item_id']);
        }
        
        return $this;
    }
}
