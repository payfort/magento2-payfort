<?php
/**
 * Amazonpaymentservices APS Subscription cron
 * php version 8.2.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Cron;

use Amazonpaymentservices\Fort\Helper\Cron;
use Amazonpaymentservices\Fort\Helper\Data;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Amazonpaymentservices APS Subscription cron
 * php version 8.2.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class PlaceSubscriptionOrder
{
    protected $_orderCollectionFactory;

    protected $_logger;

    protected $_helper;

    protected $_order;

    protected $_connection;

    protected $_cronHelper;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param Cron $cronHelper
     * @param Order $order
     * @param ResourceConnection $connect
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        LoggerInterface $logger,
        Data $helper,
        Cron $cronHelper,
        Order $order,
        ResourceConnection $connect
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_cronHelper = $cronHelper;
        $this->_order = $order;
        $this->_connection = $connect;
    }

    /**
     * Run the APS Subscription Job
     *
     * @return PlaceSubscriptionOrder
     */
    public function execute()
    {
        $this->_helper->log('APS Subscription Cron');

        try {
            $connection = $this->_connection->getConnection();

            /** Select current date subscription order */
            $dateNow = date('Y-m-d');
            $this->_helper->log('Date Now: ' . $dateNow);

            $query = $connection->select()
                ->from(['table' => 'aps_subscriptions'])
                ->where('table.next_payment_date=?', $dateNow)
                ->where('table.subscription_status = 1');
            $getAllSubscriptionOrders = $this->_helper->fetchAllQuery($query);

            /** Prepare and Place subscription orders */
            foreach ($getAllSubscriptionOrders as $subscriptionOrder) {
                $this->_helper->log('Subscription OrderPicked:' . $subscriptionOrder['order_increment_id']);
                $this->_cronHelper
                    ->createCronOrder($subscriptionOrder['qty'], $subscriptionOrder['id'],
                        $subscriptionOrder['order_increment_id'], $subscriptionOrder['item_id']);
            }
        } catch (Exception $e) {
            $this->_helper->log('APS Subscription Cron FAILED with error: ' . $e->getMessage());
        }

        return $this;
    }
}
