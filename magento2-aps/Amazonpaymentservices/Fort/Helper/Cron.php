<?php
/**
 * Amazonpaymentservices Payment Helper
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Helper;

/**
 * Amazonpaymentservices Payment Helper
 * php version 7.3.*
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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Resource Connection
     */
    protected $_connection;

    /**
     * @param Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\Store $storeManager
     * @param Magento\Catalog\Model\Product $product
     * @param Magento\Framework\Data\Form\FormKey $formKey $formkey,
     * @param Magento\Quote\Model\Quote $quote,
     * @param Magento\Customer\Model\CustomerFactory $customerFactory,
     * @param Magento\Sales\Model\Service\OrderService $orderService,
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Store\Model\Store $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $connect,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
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
     * @param array $orderData
     * @return array
     */
    public function createCronOrder($qty, $subscriptionOrderId, $orderIncrementedId)
    {
        try {
            $order = $this->_helper->getOrderById($orderIncrementedId);
            $this->_helper->log('OrderID:'.$order->getId());
            
            $newOrder = '';
            $paymentMethod = $order->getPayment()->getMethod();
            
            if (empty($order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()) || ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Stc::CODE && empty($order->getApsStcRef()))) {
                $this->log('Payment Data not found. Failed to create order');
                $this->_helper->cancelSubscription($subscriptionOrderId);
                return false;
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
                //@codingStandardsIgnoreStart
                $product = $this->_product->load($item['product_id']);
                //@codingStandardsIgnoreEnd
                $product->setPrice($item['base_price']);
                $quote->addProduct(
                    $product,
                    (int) $qty
                );
                
            }
            
            $quote->getBillingAddress()->addData($order->getBillingAddress()->getData());
            
            $quote->getShippingAddress()->addData($order->getShippingAddress()->getData());
            
            $quote->getShippingAddress()
                ->setShippingMethod($order->getShippingMethod())
                ->setCollectShippingRates(true);

            $this->_shippingRate->setCode($order->getShippingMethod())->getPrice(1);

            $quote->getShippingAddress()->addShippingRate($this->_shippingRate);

            $quote->setPaymentMethod(\Amazonpaymentservices\Fort\Model\Method\Vault::CODE);
            $quote->setInventoryProcessed(true);
            $quote->save();
            
            $quote->getPayment()->importData(['method' => \Amazonpaymentservices\Fort\Model\Method\Vault::CODE]);
            $quote->collectTotals()->save();
            
            $newOrder = $this->quoteManagement->submit($quote);
            $newOrder->setEmailSent(0);
            $increment_id = $newOrder->getRealOrderId();

            foreach ($newOrder->getAllItems() as $item) {
                $this->updateSubscriptionOrder($subscriptionOrderId, $item, $newOrder, $tokenId, $tokenName, $remoteIp, $order);
            }
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('APS :: Failed to create child order.', true);
            $order->save();
            $this->_helper->cancelSubscription($subscriptionOrderId);
            $this->_helper->log("Cron Job failed for Order:".$order->getId());
            $this->_helper->log($e->getMessage());
            
            return false;
        }
    }

    private function updateSubscriptionOrder($subscriptionOrderId, $item, $newOrder, $tokenId, $tokenName, $remoteIp, $order)
    {
        $connection = $this->_connection->getConnection();
        /* @isSubscriptionProduct */
        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
        $apsSubEnabled = $connection->fetchRow($query);

        $query = $connection->select()->from(['table'=>'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $item->getProductId());
        $prodApsSubEnabled = $connection->fetchRow($query);

        if (!empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {

            $this->_helper->log('New ORderNUmber:'.$newOrder->getRealOrderId());
            
            $apsPaymentResponse = $this->_helper->apsSubscriptionPaymentApi($newOrder, $tokenName, $order, $remoteIp);
            
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
                $newOrder->setState($newOrder::STATE_CANCELED)->save();
                $newOrder->setStatus($newOrder::STATE_CANCELED)->save();

                $this->_helper->log('Order status is chenged from PENDING to CANCELED.');
                $this->_helper->apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, 0, $order);
                $newOrder->addStatusToHistory($newOrder::STATE_CANCELED, $apsPaymentResponse['response_message'], false)->save();
            }
        } else {
            $newOrder->setState($newOrder::STATE_CANCELED)->save();
            $newOrder->setStatus($newOrder::STATE_CANCELED)->save();

            $this->_helper->log('Order status is chenged from PENDING to CANCELED.');
            $this->_helper->apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, 0, $order);
            $order->addStatusHistoryComment('Product is not enabled for subscription', true)->save();
        }
    }
}
