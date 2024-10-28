<?php
/**
 * Amazonpaymentservices Payment Helper
 * php version 8.2.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Helper;

use Amazonpaymentservices\Fort\Model\Method\Stc;
use Amazonpaymentservices\Fort\Model\Method\Vault;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;

/**
 * Amazonpaymentservices Payment Helper
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Cron extends \Magento\Payment\Helper\Data
{
    protected $_helper;

    protected $_shippingRate;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Resource Connection
     */
    protected $_connection;

    protected $_storeManager;

    protected $_product;

    protected $_formkey;

    protected $quote;

    protected $quoteManagement;

    protected $customerFactory;

    protected $customerRepository;

    protected $orderService;

    /**
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param Store $storeManager
     * @param Product $product
     * @param FormKey $formkey
     * @param QuoteFactory $quote ,
     * @param QuoteManagement $quoteManagement
     * @param CustomerFactory $customerFactory ,
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderService $orderService ,
     * @param Data $helper
     * @param ResourceConnection $connect
     * @param Rate $shippingRate
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context                     $context,
        LayoutFactory               $layoutFactory,
        Factory                     $paymentMethodFactory,
        Emulation                   $appEmulation,
        Config                      $paymentConfig,
        Initial                     $initialConfig,
        Store                       $storeManager,
        Product                     $product,
        FormKey                     $formkey,
        QuoteFactory                $quote,
        QuoteManagement             $quoteManagement,
        CustomerFactory             $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderService                $orderService,
        Data                        $helper,
        ResourceConnection          $connect,
        Rate                        $shippingRate,
        ObjectManagerInterface      $objectManager
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory,
            $appEmulation, $paymentConfig, $initialConfig);

        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->_helper = $helper;
        $this->_connection = $connect;
        $this->_shippingRate = $shippingRate;
        $this->_objectManager = $objectManager;
    }

    /**
     * Create Order On Your Store
     *
     * @param $qty
     * @param $subscriptionOrderId
     * @param $orderIncrementedId
     * @param $itemId
     *
     * @return void
     *
     * @throws Exception
     */
    public function createCronOrder($qty, $subscriptionOrderId, $orderIncrementedId, $itemId)
    {
        $order = null;
        try {
            $order = $this->_helper->getOrderById($orderIncrementedId);
            $this->_helper->log('OrderID:'.$order->getId());

            $paymentMethod = $order->getPayment()->getMethod();

            $orderStcRef = null;
            if ($paymentMethod == Stc::CODE) {
                $orderStcRef = $this->_helper->getApsStcRefFromOrderParams(null, $orderIncrementedId);
            }

            if (
                empty($order->getPayment()->getExtensionAttributes()->getVaultPaymentToken())
                || ($paymentMethod == Stc::CODE && empty($orderStcRef))
            ) {
                $this->_helper->log('Payment Data not found. Failed to create order');
                $this->_helper->cancelSubscription($subscriptionOrderId);

                return;
            }

            $tokenName = $order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()->getGatewayToken();
            $tokenId = $order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()->getEntityId();
            $remoteIp = $order->getRemoteIp();

            $store = $this->_storeManager->load($order->getStoreId());
            $store->setCurrentCurrencyCode($order->getOrderCurrencyCode());

            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($order->getStore()->getWebsiteId());
            $customer->loadByEmail($order->getCustomerEmail());

            $quote = $this->quote->create();
            $quote->setStore($store);

            $customer = $this->customerRepository->getById($customer->getEntityId());
            $quote->setCurrency();
            $quote->setQuoteCurrencyCode($order->getOrderCurrencyCode());
            $quote->assignCustomer($customer);

            foreach ($order->getAllItems() as $item) {
                if($item->getItemId() == $itemId) {
                    //@codingStandardsIgnoreStart
                    $product = $this->_product->load($item['product_id']);
                    //@codingStandardsIgnoreEnd
                    $product->setPrice($item['base_price']);
                    $quote->addProduct(
                        $product,
                        (int) $qty
                    );
                }
            }

            if(empty($quote->getAllItems()))
                throw new Exception('APS :: Order Item not found for subcription.');

            $quote->getBillingAddress()->addData($order->getBillingAddress()->getData());

            $quote->getShippingAddress()->addData($order->getShippingAddress()->getData());

            $quote->getShippingAddress()
                ->setShippingMethod($order->getShippingMethod())
                ->setCollectShippingRates(true);

            $this->_shippingRate->setCode($order->getShippingMethod())->getPrice(1);

            $quote->getShippingAddress()->addShippingRate($this->_shippingRate);

            $quote->setPaymentMethod(Vault::CODE);
            $quote->setInventoryProcessed(true);
            $quote->save();

            $quote->getPayment()->importData(['method' => Vault::CODE]);
            $quote->collectTotals()->save();

            $newOrder = $this->quoteManagement->submit($quote);
            $newOrder->setEmailSent(0);
            $increment_id = $newOrder->getRealOrderId();

            foreach ($newOrder->getAllItems() as $item) {
                $this->updateSubscriptionOrder($subscriptionOrderId, $item, $newOrder,
                    $tokenId, $tokenName, $remoteIp, $order);
            }
        } catch (Exception $e) {
            if ($order) {
                $order->addStatusHistoryComment('APS :: Failed to create child order.', true);
                $order->save();
            }
            $this->_helper->cancelSubscription($subscriptionOrderId);
            $this->_helper->log("Cron Job failed for Order:".$order->getId());
            $this->_helper->log($e->getMessage());

            return;
        }
    }

    private function updateSubscriptionOrder($subscriptionOrderId, $item, $newOrder,
                                             $tokenId, $tokenName, $remoteIp, $order)
    {
        // is the Recurring Product feature enabled?
        $isRecurringEnabled = (int)$this->_helper->getConfig('payment/aps_recurring/active') === 1;

        $connection = $prodApsSubEnabled = null;
        if ($isRecurringEnabled) {
            $connection = $this->_connection->getConnection();
            /* @isSubscriptionProduct */
            $query = $connection->select()
                ->from(['table' => 'eav_attribute'], ['attribute_id'])
                ->where('table.attribute_code=?', 'aps_sub_enabled');
            $apsSubEnabled = $connection->fetchRow($query);

            $query = $connection->select()
                ->from(['table' => 'catalog_product_entity_int'], ['value'])
                ->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])
                ->where('table.entity_id=?', $item->getProductId());
            $prodApsSubEnabled = $connection->fetchRow($query);
        }

        if ($isRecurringEnabled && !empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {

            $this->_helper->log('New ORderNUmber:'.$newOrder->getRealOrderId());

            $apsPaymentResponse = $this->_helper
                ->apsSubscriptionPaymentApi($newOrder, $tokenName, $order, $remoteIp);

            if ($apsPaymentResponse['response_code'] == '14000') {
                $newOrder->setState($newOrder::STATE_PROCESSING)->save();
                $newOrder->setStatus($newOrder::STATE_PROCESSING)->save();
                $newOrder->addStatusToHistory($newOrder::STATE_PROCESSING, 'APS :: Order has been paid.', true);
                $newOrder->save();
                $this->_helper->log('OrderData:'.json_encode($newOrder->getData()));

                $connection->insert(
                    $this->_connection->getTableName('vault_payment_token_order_payment_link'),
                    [
                        'order_payment_id' => $newOrder->getPayment()->getEntityId(),
                        'payment_token_id' => $tokenId
                    ]
                );

                $this->_helper->log('Order status is chenged from PENDING to PROCESSING.');
                $this->_helper->apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, 1, $order);
            } else {
                $newOrder->cancel();
                $newOrder->addStatusToHistory(
                    $newOrder::STATE_CANCELED,
                    $apsPaymentResponse['response_message'], false);
                $newOrder->save();

                $this->_helper->log('Order status is chenged from PENDING to CANCELED.');
                $this->_helper->apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, 0, $order);
            }
        } else {
            $newOrder
                ->cancel()
                ->save()
            ;
            $this->_helper->log('Order status is chenged from PENDING to CANCELED.');
            $this->_helper->apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, 0, $order);
            $order->addStatusHistoryComment('Product is not enabled for subscription', true)->save();
        }
    }
}
