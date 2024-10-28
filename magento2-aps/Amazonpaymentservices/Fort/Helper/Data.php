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

use Amazonpaymentservices\Fort\Model\Config\Source\OrderOptions;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\Message\Manager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Controller\ResultFactory;
use Amazonpaymentservices\Fort\Model\Payment;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;

/**
 * Amazonpaymentservices Payment Helper
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Data extends \Magento\Payment\Helper\Data
{
    protected $_code;
    private $_gatewayHost          = 'https://checkout.payfort.com/';
    private $_gatewaySandboxHost   = 'https://sbcheckout.payfort.com/';
    private $_gatewaySandboxNotify = 'https://sbpaymentservices.payfort.com/';
    private $_gatewayNotify        = 'https://paymentservices.payfort.com/';

    protected $_gatewayParams;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory
     */
    protected $_paymentCaptureFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_custmerSession;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     *
     * @var type
     */
    protected $_checkoutSession;
    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\CatalogInventory\Api\StockManagementInterface
     */
    protected $_stockManagement;

    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $_stockIndexerProcessor;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $_priceIndexer;

    /**
     * @var \Magento\CatalogInventory\Observer\ProductQty
     */
    protected $_productQty;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $_resultFactory;

    protected $searchCriteriaBuilder;

    protected $_curl;

    protected $_order;

    protected $_cart;

    protected $invoiceService;

    protected $transaction;


    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $refundOrder;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory
     */
    protected $itemCreationFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var Resource Connection
     */
    public $_connection;

    private $registry;

    protected $_date;

    protected $creditmemoSender;
    protected $creditmemoLoader;

    protected $resultRedirectFactory;
    protected $_product;
    protected $_catalogCategory;
    protected $_encryptorInterface;
    protected $_paymentToken;
    protected $_modelPaymentToken;
    protected $_paymentTokenInterface;
    protected $_salesCollectionFactory;
    protected $_orderInterface;
    protected $_moduleResourceInterface;

    const INTEGRATION_TYPE_REDIRECTION = 'redirection';
    const INTEGRATION_TYPE_STANDARD = 'standard';
    const INTEGRATION_TYPE_HOSTED = 'hosted';
    const INTEGRATION_TYPE_EMBEDED = 'embeded';
    const PAYMENT_METHOD_CC = 'aps_fort_cc';
    const PAYMENT_METHOD_VAULT = 'aps_fort_vault';
    const PAYMENT_METHOD_NAPS = 'aps_fort_naps';
    const PAYMENT_METHOD_KNET = 'aps_knet';
    const PAYMENT_METHOD_OMANNET = 'aps_omannet';
    const PAYMENT_METHOD_APPLE = 'aps_apple';
    const PAYMENT_METHOD_INSTALLMENT = 'aps_installment';
    const PAYMENT_METHOD_VALU = 'aps_fort_valu';
    const PAYMENT_METHOD_VISACHECKOUT = 'aps_fort_visaco';
    const PAYMENT_METHOD_STC = 'aps_fort_stc';
    const PAYMENT_METHOD_TABBY = 'aps_fort_tabby';
    const VALU_API_FAILED_STATUS  = '15777';
    const VALU_API_FAILED_RESPONSE_CODE  = '15777';
    const PAYMENT_METHOD_CAPTURE_STATUS = '04000';
    const PAYMENT_METHOD_VOID_STATUS = '08000';
    const PAYMENT_METHOD_REFUND_STATUS = '06000';
    const PAYMENT_METHOD_PURCHASE_SUCCESS_STATUS = '14000';
    const PAYMENT_METHOD_AUTH_SUCCESS_STATUS = '02000';
    const PAYMENT_TRANSACTION_DECLINED = '13666';
    const INSTALLMENTS_PLAN_CARD = 'BIN';
    const INSTALLMENTS_PLAN_TOKEN = 'TOKEN';
    const PAYMENT_METHOD_BENEFIT = 'aps_benefit';

    const PAYMENT_METHOD = [
        \Amazonpaymentservices\Fort\Model\Method\Vault::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Cc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Naps::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Knet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Apple::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Installment::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Valu::CODE,
        \Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Stc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\OmanNet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Benefit::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Tabby::CODE
    ];

    const APS_ONHOLD_RESPONSE_CODES = [
        '15777',
        '15778',
        '15779',
        '15780',
        '15781',
        '00006',
        '01006',
        '02006',
        '03006',
        '04006',
        '05006',
        '06006',
        '07006',
        '08006',
        '09006',
        '11006',
        '13006',
        '17006',
    ];

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $session,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer,
        \Magento\CatalogInventory\Observer\ProductQty $productQty,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Message\Manager $mesageManager,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory $paymentCaptureFactory,
        \Magento\Sales\Model\RefundOrder $refundOrder,
        \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory $itemCreationFactory,
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditmemoSender,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface,
        \Magento\Vault\Model\ResourceModel\PaymentToken $paymentToken,
        \Magento\Vault\Model\PaymentToken $modelPaymentToken,
        \Magento\Vault\Api\Data\PaymentTokenInterface $paymentTokenInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesCollectionFactory,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Framework\Module\ResourceInterface $moduleResourceInterface,
        \Magento\Framework\App\ResourceConnection $connect,
        InvoiceService $invoiceService,
        Registry $registry,
        transactionFactory $transaction
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_storeManager = $storeManager;
        $this->session = $session;
        $this->_logger = $context->getLogger();
        $this->_localeResolver = $localeResolver;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_objectManager = $objectManager;
        $this->_stockManagement = $stockManagement;
        $this->_stockIndexerProcessor = $stockIndexerProcessor;
        $this->_priceIndexer = $priceIndexer;
        $this->_productQty = $productQty;
        $this->_productMetadata = $productMetadata;
        $this->_curl = $curl;
        $this->_order = $order;
        $this->_orderSender = $orderSender;
        $this->_invoiceSender = $invoiceSender;
        $this->_messageManager = $mesageManager;
        $this->_remoteAddress = $remoteAddress;
        $this->_resultFactory = $resultFactory;
        $this->_checkoutSession = $session;
        $this->_cart = $cart;
        $this->_custmerSession = $customerSession;
        $this->_paymentCaptureFactory = $paymentCaptureFactory;
        $this->refundOrder = $refundOrder;
        $this->itemCreationFactory = $itemCreationFactory;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->_date = $date;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quoteManagement = $quoteManagement;
        $this->_product = $product;
        $this->_catalogCategory = $catalogCategory;
        $this->_encryptorInterface = $encryptorInterface;
        $this->_paymentToken = $paymentToken;
        $this->_modelPaymentToken = $modelPaymentToken;
        $this->_paymentTokenInterface = $paymentTokenInterface;
        $this->_salesCollectionFactory = $salesCollectionFactory;
        $this->_orderInterface = $orderInterface;
        $this->_moduleResourceInterface = $moduleResourceInterface;
        $this->_connection = $connect;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->registry = $registry;

        $this->apsCookieUpdate();
    }

    public function setMethodCode($code)
    {
        $this->_code = $code;
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMainConfigData($config_field)
    {
        return $this->scopeConfig->getValue(
            ('payment/aps_fort/'.$config_field),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isApsPaymentMethod($paymentMethod)
    {
        if (preg_match('#^aps\_#', $paymentMethod)) {
            return true;
        }
        return false;
    }

    public function getPaymentRequestParams($order, $integrationType = self::INTEGRATION_TYPE_REDIRECTION, $postData = [])
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getRealOrderId();
        $language = $this->getLanguage();

        $this->_gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $orderId,
            'language'            => $language,
        ];
        if ($paymentMethod == self::PAYMENT_METHOD_STC) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $this->_gatewayParams['currency']       = strtoupper($currency);
            $this->_gatewayParams['amount']         = $amount;
            $this->_gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $this->_gatewayParams['command']        = $this->getMainConfigData('command');
            $this->_gatewayParams['digital_wallet'] = 'STCPAY';
            $this->_gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/stcResponseOnline');
            $this->_gatewayParams['token_name']     = $postData['stcToken'];

            if ($this->getConfig('payment/aps_fort_stc/token') == 1){
                $this->_gatewayParams['remember_me'] = 'YES';
            }
        } elseif ($paymentMethod == self::PAYMENT_METHOD_TABBY) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $this->_gatewayParams['currency']       = strtoupper($currency);
            $this->_gatewayParams['amount']         = $amount;
            $this->_gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $this->_gatewayParams['command']        = $this->getMainConfigData('command');
            $this->_gatewayParams['payment_option'] = 'TABBY';
            $this->_gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/tabbyResponseOnline');

            $ip = $this->getVisitorIp();
            $this->_gatewayParams['customer_ip'] = $ip;
            $this->_gatewayParams['order_description'] = $orderId;
            $this->_gatewayParams['phone_number'] = $order->getBillingAddress()->getTelephone();

        }elseif ($integrationType == self::INTEGRATION_TYPE_REDIRECTION) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $this->_gatewayParams['currency']       = strtoupper($currency);
            $this->_gatewayParams['amount']         = $amount;
            $this->_gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $this->_gatewayParams['command']        = $this->getMainConfigData('command');
            $this->_gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/responseOnline');
            $this->setPaymentMethodParams($paymentMethod, $orderId);
            $this->_gatewayParams = array_merge($this->_gatewayParams, $this->pluginParams());
        } elseif (($paymentMethod == self::PAYMENT_METHOD_INSTALLMENT) && $integrationType == self::INTEGRATION_TYPE_HOSTED) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $this->_gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/installmentStandardPageResponse');
            $this->_gatewayParams['service_command'] = 'TOKENIZATION';

            $sessionData['issuer_code'] = $postData['issuer_code'];
            $sessionData['plan_code'] = $postData['plan_code'];
            $sessionData['installment_amount'] = $postData['installment_amount'];
            $sessionData['installment_interest'] = $postData['installment_interest'];

            $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApsorderparamsFactory')->create();
            $model->setOrderId($order->getId());
            $model->setOrderIncrementId($orderId);
            $model->setApsParams(json_encode($sessionData));
            $model->setCreatedAt(date('Y-m-d H:i:s'));
            $model->setUpdatedAt(date('Y-m-d H:i:s'));
            $model->save();

            $this->_custmerSession->setCustomValue($sessionData);

        } elseif (($paymentMethod == self::PAYMENT_METHOD_CC) && $this->getConfig('payment/aps_installment/integration_type') == self::INTEGRATION_TYPE_EMBEDED && isset($postData['issuer_code'])) {

            $this->_gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/installmentStandardPageResponse');
            $this->_gatewayParams['service_command'] = 'TOKENIZATION';

            $sessionData['issuer_code'] = $postData['issuer_code'];
            $sessionData['plan_code'] = $postData['plan_code'];
            $sessionData['installment_amount'] = $postData['installment_amount'];
            $sessionData['installment_interest'] = $postData['installment_interest'];

            $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApsorderparamsFactory')->create();
            $model->setOrderId($order->getId());
            $model->setOrderIncrementId($orderId);
            $model->setApsParams(json_encode($sessionData));
            $model->setCreatedAt(date('Y-m-d H:i:s'));
            $model->setUpdatedAt(date('Y-m-d H:i:s'));
            $model->save();

            $this->_custmerSession->setCustomValue($sessionData);

        } elseif ($integrationType == self::INTEGRATION_TYPE_STANDARD || $integrationType == self::INTEGRATION_TYPE_HOSTED) {
            $this->_gatewayParams['service_command'] = 'TOKENIZATION';
            $this->_gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/merchantPageResponse');
        }

        $signature = $this->calculateSignature($this->_gatewayParams, 'request');
        $this->_gatewayParams['signature'] = $signature;
        if ($paymentMethod == self::PAYMENT_METHOD_INSTALLMENT && $integrationType == self::INTEGRATION_TYPE_HOSTED) {
            $this->_gatewayParams['remember_me'] = $postData['rememberMe'] ?? 'NO';
        }
        if ($paymentMethod == self::PAYMENT_METHOD_CC && $integrationType == self::INTEGRATION_TYPE_HOSTED && $this->getConfig('payment/aps_installment/integration_type') == self::INTEGRATION_TYPE_EMBEDED) {
            $this->_gatewayParams['remember_me'] = $postData['rememberMe'] ?? 'NO';
        }

        $gatewayUrl = $this->getGatewayUrl();
        $logMsg = "Request Params for payment method ($paymentMethod) \n\n" . json_encode($this->_gatewayParams, 1);
        $this->log($logMsg);

        return ['url' => $gatewayUrl, 'params' => $this->_gatewayParams];
    }

    private function setPaymentMethodParams($paymentMethod, $orderId)
    {
        if ($paymentMethod == self::PAYMENT_METHOD_NAPS) {
            $this->_gatewayParams['payment_option']    = 'NAPS';
            $this->_gatewayParams['command']        = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
            $this->_gatewayParams['order_description'] = $orderId;
        } elseif ($paymentMethod == self::PAYMENT_METHOD_KNET) {
            $this->_gatewayParams['payment_option']    = 'KNET';
            $this->_gatewayParams['command']        = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
        } elseif ($paymentMethod == self::PAYMENT_METHOD_OMANNET) {
            $this->_gatewayParams['payment_option']    = 'OMANNET';
            $this->_gatewayParams['command']        = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
        } elseif ($paymentMethod == self::PAYMENT_METHOD_VALU) {
            $this->_gatewayParams['payment_option']    = 'VALU';
            $this->_gatewayParams['order_description'] = $orderId;
        } elseif ($paymentMethod == self::PAYMENT_METHOD_VISACHECKOUT) {
            $this->_gatewayParams['digital_wallet']    = 'VISA_CHECKOUT';
        } elseif ($paymentMethod == self::PAYMENT_METHOD_BENEFIT) {
            $this->_gatewayParams['payment_option']    = 'BENEFIT';
            $this->_gatewayParams['command']        = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
        }
    }

    public function getVaultPaymentRequestParams($order, $integrationType = self::INTEGRATION_TYPE_REDIRECTION, $tokenName = '', $cvv = '')
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getRealOrderId();
        $language = $this->getLanguage();

        $gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $orderId,
            'language'            => $language,
        ];
        if ($integrationType == self::INTEGRATION_TYPE_REDIRECTION) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $gatewayParams['currency']       = strtoupper($currency);
            $gatewayParams['amount']         = $amount;
            $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $gatewayParams['command']        = $this->getMainConfigData('command');
            $gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/responseOnline');
            $gatewayParams['token_name']      = $tokenName;

            $gatewayParams = array_merge($gatewayParams, $this->pluginParams());
            $signature = $this->calculateSignature($gatewayParams, 'request');
            $gatewayParams['signature'] = $signature;

        } elseif ($integrationType == self::INTEGRATION_TYPE_STANDARD || $integrationType == self::INTEGRATION_TYPE_HOSTED) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);

            $ip = $this->getVisitorIp();
            $gatewayParams['customer_ip']    = $ip;
            $gatewayParams['eci']            = 'ECOMMERCE';
            $gatewayParams['currency']       = strtoupper($currency);
            $gatewayParams['amount']         = $amount;
            $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $gatewayParams['command']        = $this->getMainConfigData('command');

            $gatewayParams['card_security_code']     = $cvv;
            $gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/merchantPageVaultResponse');
            $gatewayParams['token_name']      = $tokenName;

            $gatewayParams = array_merge($gatewayParams, $this->pluginParams());

            $signature = $this->calculateSignature($gatewayParams, 'request');
            $gatewayParams['signature'] = $signature;

            $gatewayUrl = $this->getGatewayUrl('notificationApi');
            $response = $this->callApi($gatewayParams, $gatewayUrl);
            $logMsg = "Request Params for payment method ($paymentMethod) \n\n" . json_encode($gatewayParams, 1);
            $this->log($logMsg);
            $logMsg = "Repose for payment method ($paymentMethod) \n\n" . json_encode($response, 1);
            $this->log($logMsg);
            return ['url' => $gatewayParams['return_url'], 'params' => $response];
        }
        //$gatewayParams['token_name']      = $tokenName;

        //$signature = $this->calculateSignature($gatewayParams, 'request');
        //$gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl();
        //$response = $this->callApi($gatewayUrl, $gatewayParams);
        $logMsg = "Request Params for payment method ($paymentMethod) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);

        return ['url' => $gatewayUrl, 'params' => $gatewayParams];
    }

    public function getInstallmentVault($order, $tokenName, $postParams)
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getRealOrderId();
        $language = $this->getLanguage();

        $gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $orderId,
            'language'            => $language,
        ];
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmount($order, $currency);

        $ip = $this->getVisitorIp();
        $gatewayParams['customer_ip']    = $ip;
        $gatewayParams['eci']            = 'ECOMMERCE';
        $gatewayParams['currency']       = strtoupper($currency);
        $gatewayParams['amount']         = $amount;
        $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
        $gatewayParams['command']        = 'PURCHASE';
        $gatewayParams['token_name']     = trim($tokenName);
        $gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/merchantPageVaultResponse');
        $gatewayParams['issuer_code']    = $postParams['issuer_code'];
        $gatewayParams['plan_code']      = $postParams['plan_code'];
        $gatewayParams['card_security_code'] = $postParams['cvv'];
        $gatewayParams['installments']   = 'HOSTED';

        $sessionData['installment_amount'] = $postParams['installment_amount'];
        $sessionData['installment_interest'] = $postParams['installment_interest'];

        $this->_custmerSession->setCustomValue($sessionData);
        $gatewayParams = array_merge($gatewayParams, $this->pluginParams());

        $signature = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $response = $this->callApi($gatewayParams, $gatewayUrl);
        $logMsg = "Request Params for payment method ($paymentMethod) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);
        $logMsg = "Response for payment method ($paymentMethod) \n\n" . json_encode($response, 1);
        $this->log($logMsg);
        return ['url' => $gatewayParams['return_url'], 'params' => $response];
    }

    /**
     * Call Customer Verify API for Valu PayFort
     *
     * @param string $mobileNumber
     * @return array
     */
    public function merchantVerifyValuFort($mobileNumber)
    {
        $refId = strtoupper(substr(uniqid(), 1, 9));
        $language = $this->getLanguage();
        $postData = [
            'service_command'     => 'CUSTOMER_VERIFY',
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $refId,
            'language'            => $language,
            'payment_option'      => 'VALU',
            'phone_number'        => $mobileNumber
        ];

        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');

        $this->log("Fort Valu Customer Verify Request Params for payment method (PayFort Valu) : " . json_encode($postData, 1));

        $response = $this->callApi($postData, $gatewayUrl);
        $responseData = [];
        if (!$response) {
            $responseData['status'] = self::VALU_API_FAILED_STATUS;
            $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
            $responseData['response_message'] = __('Failed to generate OTP');
        } elseif ($response['response_code'] == '00160') {
            $responseData['status'] = $response['status'];
            $responseData['response_code'] = $response['response_code'];
            $responseData['response_message'] = __('Customer does not exist.');
        } else {
            $responseData['status'] = $response['status'];
            $responseData['response_code'] = $response['response_code'];
            $responseData['response_message'] = $response['response_message'];
        }

        $this->_custmerSession->setCustomValue(['refId' => $refId]);

        $debugMsg = "Fort Valu Customer Verify Response Parameters for payment method (PayFort Valu)"."\n".json_encode($response, true);
        $this->log($debugMsg);

        return $responseData;
    }

    /**
     * Call Otp Generate API for Valu PayFort
     *
     * @param object $order
     * @param string $mobileNumber
     * @return array
     */
    public function execGenOtp($order, $mobileNumber, $downPayment, $wallet_amount, $cashback_amount)
    {
        $orderId = $order->getRealOrderId();

        $sessionData = $this->_custmerSession->getCustomValue();
        if (!empty($sessionData) && !empty($sessionData['refId'])) {
            $cart = $this->_order->loadByIncrementId($orderId);

            $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApsorderparamsFactory')->create();
            $model->setOrderId($order->getId());
            $model->setOrderIncrementId($orderId);
            $model->setApsValuRef($sessionData['refId']);
            $model->setCreatedAt(date('Y-m-d H:i:s'));
            $model->setUpdatedAt(date('Y-m-d H:i:s'));
            $model->save();

            $discountAmount = (float)$order->getBaseDiscountAmount();
            $orderData = $cart;
            $shippingAmount = (float)$orderData->getShippingAmount();

            $baseCurrency  = $this->getBaseCurrency();
            $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
            $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount        = $this->convertFortAmount($order, $currency);
            $downPaymentAmount = $downPayment > 0 ? $downPayment*100 : 0;
            $wallet_amount = $wallet_amount > 0 ? $wallet_amount*100 : 0;
            $cashback_amount = $cashback_amount > 0 ? $cashback_amount*100 : 0;

            $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();
            $items = $cart->getAllItems();
            $gatewayUrl = $this->getGatewayUrl('notificationApi');
            $taxAmount = (float)$order->getTaxAmount();
            $products = $this->getValuProductsArr($items, $shippingAmount, $taxAmount, $discountAmount);
            $language = $this->getLanguage();
            $include_installments = 'YES';
            $postData = [
                'service_command'     => 'OTP_GENERATE',
                'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
                'access_code'         => $this->getMainConfigData('access_code'),
                'merchant_reference'  => $sessionData['refId'],
                'language'            => $language,
                'payment_option'      => 'VALU',
                'merchant_order_id'   => $orderId,
                'phone_number'        => $mobileNumber,
                'amount'              => round($amount),
                'currency'            => $currency,
                'products'            => $products,
                'total_downpayment'   => $downPaymentAmount,
                'include_installments' => $include_installments,
                'wallet_amount'        => $wallet_amount,
                'cashback_wallet_amount' => $cashback_amount
            ];
            $signature = $this->calculateSignature($postData, 'request');
            $postData['signature'] = $signature;
            $this->log("Fort Valu Generate OTP Request Params for payment method (PayFort Valu) : " . json_encode($postData, 1));

            $response = $this->callApi($postData, $gatewayUrl);
            $debugMsg = "Fort Valu Generate OTP Response Parameters for payment method (PayFort Valu)"."\n".json_encode($response, true);
            $this->log($debugMsg);

            $responseData = [];
            if (!$response) {
                $responseData['status'] = self::VALU_API_FAILED_STATUS;
                $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
                $responseData['response_message'] = __('Failed to generate OTP');
            } elseif ($response['status'] == '88') {
                $this->_custmerSession->setCustomValue(['orderId' => $orderId, 'refId' => $sessionData['refId'], 'transaction_id' => $response['merchant_order_id']]);
                $responseData['status'] = $response['status'];
                $responseData['response_code'] = $response['response_code'];
                $responseData['response_message'] = $response['response_message'];
                $responseData['installment_detail'] = $response['installment_detail'];

            } else {
                $responseData['status'] = $response['status'];
                $responseData['response_code'] = $response['response_code'];
                $responseData['response_message'] = $response['response_message'];
            }
        } else {
            $responseData['status'] = self::VALU_API_FAILED_STATUS;
            $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
            $responseData['response_message'] = __('Session expired please reload cart page again.');
        }
        return $responseData;
    }

    /**
     * Get Valu Product Arr to get product array
     *
     * @param array $items
     * @param float $shippingAmount
     * @param float $taxAmount
     * @param float $discountAmount
     * @return array
     */
    private function getValuProductsArr($items, $shippingAmount, $taxAmount, $discountAmount)
    {
        $products = [];
        $totalItems = 0;
        foreach ($items as $_item) {
            $totalItems += $_item->getQtyOrdered();
        }

        $products[] = [
            'product_name'     => '',
            'product_price'    => 0,
            'product_category' => ''
        ];

        foreach ($items as $_item) {
            $product = $this->getProduct($_item->getProductId());
            $categoryIds = $product->getCategoryIds();
            $categoryName = '';
            if (!empty($categoryIds)) {
                $category = $this->getCategory($categoryIds[0]);
                $categoryName = $category->getName();
            } else {
                $categoryName = $_item->getName();
            }

            $tax = ($taxAmount / $totalItems ) * $_item->getQtyOrdered();
            $shipping = (($shippingAmount / $totalItems ) * $_item->getQtyOrdered());

            $itemTotalPrice = round((($_item->getPrice() * $_item->getQtyOrdered()) + $tax + $shipping) * 100);

            //Removing special characters
            $itemName = preg_replace("/[^a-z0-9]+/i", "", $_item->getName());
            $categoryName = preg_replace("/[^a-z0-9]+/i", "", $categoryName);

            $products[0]['product_name'] .= $itemName;
            $products[0]['product_price'] += $itemTotalPrice ;
            $products[0]['product_category'] .= $categoryName;
        }
        $products[0]['product_name'] = substr($products[0]['product_name'], 0, 50);
        $products[0]['product_category'] = substr($products[0]['product_category'], 0, 50);
        $products[0]['product_category'] = empty($products[0]['product_category']) ? 'Uncategorized' : $products[0]['product_category'];
        $products[0]['product_price'] = $products[0]['product_price'] + ($discountAmount * 100);
        return $products;
    }

    private function getProduct($productId)
    {
        return $this->_product->load($productId);
    }

    private function getCategory($categoryId)
    {
        return $this->_catalogCategory->load($categoryId);
    }

    /**
     * Call Otp Verify API for Valu PayFort
     *
     * @param object $order
     * @param string $mobileNumber
     * @param string $otp
     * @return array
     */
    public function merchantOtpVerifyValuFort($order, $mobileNumber, $otp)
    {
        $orderId = $order->getRealOrderId();
        $sessionData = $this->_custmerSession->getCustomValue();
        if (!empty($sessionData) && !empty($sessionData['refId'])) {
            $baseCurrency  = $this->getBaseCurrency();
            $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
            $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount        = $this->convertFortAmount($order, $currency);

            $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

            $language = $this->getLanguage();
            $postData = [
                'service_command'     => 'OTP_VERIFY',
                'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
                'access_code'         => $this->getMainConfigData('access_code'),
                'merchant_reference'  => $sessionData['refId'],
                'language'            => $language,
                'payment_option'      => 'VALU',
                'phone_number'        => $mobileNumber,
                'amount'              => round($amount),
                'currency'            => $currency,
                'merchant_order_id'   => $orderId,
                'otp'                 => $otp,
                'total_downpayment'   => 0
            ];
            //calculate request signature
            $signature = $this->calculateSignature($postData, 'request');
            $postData['signature'] = $signature;

            $gatewayUrl = $this->getGatewayUrl('notificationApi');

            $this->log("Fort Valu OTP Verify Request Params for payment method (PayFort Valu) : " . json_encode($postData, 1));

            $response = $this->callApi($postData, $gatewayUrl);
            $debugMsg = "Fort Valu OTP Verify Response Parameters for payment method (PayFort Valu)"."\n".json_encode($response, true);
            $this->log($debugMsg);
            $responseData = [];
            if (!$response) {
                $responseData['status'] = self::VALU_API_FAILED_STATUS;
                $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
                $responseData['response_message'] = __('Failed to Verify OTP');
            } else {
                $responseData = $response;
            }
        } else {
            $responseData['status'] = self::VALU_API_FAILED_STATUS;
            $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
            $responseData['response_message'] = __('Session expired please reload cart page again.');
        }
        return $responseData;
    }

    /**
     * Call Purchase API for Valu PayFort
     *
     * @param object $order
     * @param string $mobileNumber
     * @param string $otp
     * @param string $tenure
     * @return array
     */
    public function merchantPurchaseValuFort($order, $mobileNumber, $otp, $tenure, $valuTenureAmount, $valuTenureInterest, $downPayment,  $wallet_amount, $cashback_amount)
    {
        $orderId = $order->getRealOrderId();
        $sessionData = $this->_custmerSession->getCustomValue();
        if (!empty($sessionData) && !empty($sessionData['refId'])) {
            $baseCurrency  = $this->getBaseCurrency();
            $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
            $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount        = $this->convertFortAmount($order, $currency);
            $downPaymentAmount = $downPayment*100;
            $wallet_amount = $wallet_amount*100;
            $cashback_amount = $cashback_amount*100;
            $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

            $customerEmail = '';
            if (!empty($this->_custmerSession->getCustomer()->getEmail())) {
                $customerEmail = $this->_custmerSession->getCustomer()->getEmail();
            } else {
                $customerEmail = $order->getCustomerEmail();
            }

            $storeName = $this->_storeManager->getStore()->getName();
            $language = $this->getLanguage();
            $postData = [
                'command'             => 'PURCHASE',
                'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
                'access_code'         => $this->getMainConfigData('access_code'),
                'merchant_reference'  => $sessionData['refId'],
                'language'            => $language,
                'payment_option'      => 'VALU',
                'phone_number'        => $mobileNumber,
                'amount'              => round($amount),
                'currency'            => $currency,
                'merchant_order_id'   => $orderId,
                'otp'                 => $otp,
                'customer_email'      => $customerEmail,
                'transaction_id'      => $sessionData['transaction_id'],
                'tenure'              => $tenure,
                'purchase_description'=> "PurchaseWithEGP".$downPaymentAmount."downpaymentAnd".$tenure."MonthsTenure",
                'total_down_payment'  => $downPaymentAmount,
                'customer_code'       => $mobileNumber,
                'wallet_amount'        => $wallet_amount,
                'cashback_wallet_amount' => $cashback_amount
            ];
            $postData = array_merge($postData, $this->pluginParams());
            //calculate request signature
            $signature = $this->calculateSignature($postData, 'request');
            $postData['signature'] = $signature;

            $sessionData['valu_tenure'] = $tenure;
            $sessionData['valu_tenure_amount'] = $valuTenureAmount;
            $sessionData['valu_tenure_interest'] = $valuTenureInterest;

            $this->_custmerSession->setCustomValue($sessionData);

            $gatewayUrl = $this->getGatewayUrl('notificationApi');

            $this->log("Fort Valu Purchase Request Params for payment method (PayFort Valu) : " . json_encode($postData, 1));

            $response = $this->callApi($postData, $gatewayUrl);
            $responseData = [];
            if (!$response) {
                $responseData['status'] = self::VALU_API_FAILED_STATUS;
                $responseData['response_message'] = __('Sorry unknown error occured. Please try again.');
            } else {
                $responseData = $response;
            }
            $debugMsg = "Fort Valu Purchase Response Parameters for payment method (PayFort Valu)"."\n".json_encode($response, true);
            $this->log($debugMsg);
        } else {
            $responseData['status'] = self::VALU_API_FAILED_STATUS;
            $responseData['response_code'] = self::VALU_API_FAILED_RESPONSE_CODE;
            $responseData['response_message'] = __('Session expired please reload cart page again.');
        }
        return $responseData;
    }

    public function getInstallmentRequestParams($order, $integrationType)
    {
        $language = $this->getLanguage();
        $orderId = $order->getRealOrderId();
        $gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'language'            => $language,
            'merchant_reference'  => $orderId,
        ];
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmount($order, $currency);
        $gatewayParams['currency']       = strtoupper($currency);
        $gatewayParams['amount']         = $amount;
        $gatewayParams['installments'] = 'STANDALONE';
        if ($integrationType == self::INTEGRATION_TYPE_STANDARD) {
            $gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/installmentStandardPageResponse');
            $gatewayParams['service_command'] = 'TOKENIZATION';
        } else {
            $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
            $gatewayParams['command'] = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
            $gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/installmentResponseOnline');
            $gatewayParams = array_merge($gatewayParams, $this->pluginParams());
        }
        $signature = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl();
        $logMsg = "Request Params for payment method (Intallment Hosted) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);

        return ['url' => $gatewayUrl, 'params' => $gatewayParams];
    }

    public function getInstallmentPlan($cardNumberOrToken, $binOrTokenFlag)
    {
        $language = $this->getLanguage();

        $gatewayParams = [
            'query_command' => 'GET_INSTALLMENTS_PLANS',
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'language'            => $language,
        ];
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmountCart($currency);
        $gatewayParams['currency']       = strtoupper($currency);
        $gatewayParams['amount']         = $amount;
        switch ($binOrTokenFlag) {
            case self::INSTALLMENTS_PLAN_CARD:
                $gatewayParams['card_bin']          = $cardNumberOrToken;
                break;
            case self::INSTALLMENTS_PLAN_TOKEN:
                $gatewayParams['token_name']             = $cardNumberOrToken;
                break;
            default:
                break;
        }

        $signature = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $logMsg = "Request Params for payment method (Intallment Hosted) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);

        return $this->callApi($gatewayParams, $gatewayUrl);
    }

    public function getPaymentPageRedirectData($order)
    {

        return $this->getPaymentRequestParams($order, self::INTEGRATION_TYPE_REDIRECTION);
    }

    public function getOrderCustomerName($order)
    {
        $customerName = '';
        if ($order->getCustomerId() === null) {
            $customerName = $order->getBillingAddress()->getFirstname(). ' ' . $order->getBillingAddress()->getLastname();
        } else {
            $customerName =  $order->getCustomerName();
        }
        return trim($customerName);
    }

    public function isStandardMethod($order)
    {
        $paymentMethod = $order->getPayment()->getMethod();

        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Cc::CODE && $this->getConfig('payment/aps_fort_cc/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::STANDARD) {
            return true;
        } elseif (($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Installment::CODE) && $this->getConfig('payment/aps_installment/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::STANDARD) {
            return true;
        } elseif ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Vault::CODE && $this->getConfig('payment/aps_fort_cc/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::STANDARD) {
            return true;
        }

        return false;
    }

    public function isHostedMethod($order)
    {
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Cc::CODE && $this->getConfig('payment/aps_fort_cc/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::HOSTED) {
            return true;
        } elseif (($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Installment::CODE) && $this->getConfig('payment/aps_installment/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::HOSTED) {
            return true;
        } elseif ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Vault::CODE && $this->getConfig('payment/aps_fort_cc/integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::HOSTED) {
            return true;
        }
        return false;
    }

    public function merchantPageNotifyFort($fortParams, $order)
    {
        //send host to host
        $orderId = $order->getRealOrderId();

        $return_url = $this->getReturnUrl('amazonpaymentservicesfort/payment/responseOnline');

        $ip = $this->getVisitorIp();
        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => $this->getMainConfigData('command'),
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'customer_ip'           => $ip,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'customer_email'        => trim($order->getCustomerEmail()),
            'token_name'            => $fortParams['token_name'],
            'language'              => $language,
            'return_url'            => $return_url,
        ];

        if (isset($fortParams['remember_me'])) {
            $postData['remember_me'] = $fortParams['remember_me'];
        }
        $paymentMethod = $order->getPayment()->getMethod();

        if ($paymentMethod === \Amazonpaymentservices\Fort\Model\Method\Cc::CODE) {
            $meeza = '/^'.$this->getConfig('payment/aps_fort/meeza_regex').'$/';
            $mada = '/^'.$this->getConfig('payment/aps_fort/mada_regex').'$/';
            if (preg_match($meeza, $fortParams['card_bin'])) {
                $postData['command'] = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
            }
            if (preg_match($mada, $fortParams['card_bin'])) {
                $postData['command'] = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
            }
        }

        $customer_name = $this->getOrderCustomerName($order);
        if (!empty($customer_name)) {
            $postData['customer_name'] = $customer_name;
        }
        $postData = array_merge($postData, $this->pluginParams());
        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');

        $this->log("standardNotifyFort: Request Params : " . json_encode($postData, 1));
        $this->log("standardNotifyFort: Gateway URL : " . $gatewayUrl);

        $response = $this->callApi($postData, $gatewayUrl);

        $debugMsg = "Response Params ($paymentMethod)"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function installmentPageNotifyFort($responseParams, $order)
    {
        //send host to host
        $orderId = $order->getRealOrderId();

        $return_url = $this->getReturnUrl('amazonpaymentservicesfort/payment/installmentResponseOnline');

        $ip = $this->getVisitorIp();
        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE,
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'customer_ip'           => $ip,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'customer_email'        => trim($order->getCustomerEmail()),
            'language'              => $language,
            'return_url'            => $return_url
        ];
        $integrationType = $this->getConfig('payment/aps_installment/integration_type');
        if ($integrationType == self::INTEGRATION_TYPE_STANDARD) {
            $postData['installments'] = 'YES';
        } elseif ($integrationType == self::INTEGRATION_TYPE_HOSTED || $integrationType == self::INTEGRATION_TYPE_EMBEDED) {
            $postData['installments'] = 'HOSTED';
        }
        $sessionData = $this->_custmerSession->getCustomValue();

        $apsParams = $this->getApsParamsFromOrderParams($order->getId(), $orderId);

        if (!empty($apsParams['plan_code'])) {
            $postData['plan_code'] = $apsParams['plan_code'];
        }
        if (!empty($apsParams['issuer_code'])) {
            $postData['issuer_code'] = $apsParams['issuer_code'];
        }

        if (!empty($responseParams['token_name'])) {
            $postData['token_name'] = $responseParams['token_name'];
        }
        if (!empty($responseParams['issuer_code'])) {
            $postData['issuer_code'] = $responseParams['issuer_code'];
        }
        if (!empty($responseParams['plan_code'])) {
            $postData['plan_code'] = $responseParams['plan_code'];
        }

        $paymentMethod = $order->getPayment()->getMethod();

        $customer_name = $this->getOrderCustomerName($order);
        if (!empty($customer_name)) {
            $postData['customer_name'] = $customer_name;
        }

        if (isset($responseParams['remember_me'])) {
            $postData['remember_me'] = $responseParams['remember_me'];
        }
        $postData = array_merge($postData, $this->pluginParams());
        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("installmentPageNotifyFort: Response Params : " . json_encode($responseParams, 1));
        $this->log("installmentPageNotifyFort: Request Params : " . json_encode($postData, 1));
        $this->log("installmentPageNotifyFort: Gateway URL : " . $gatewayUrl);
        $response = $this->callApi($postData, $gatewayUrl);

        $debugMsg = "Response Params ($paymentMethod)"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function visaCheckoutPageNotifyFort($responseParams, $order)
    {
        //send host to host
        $orderId = $order->getRealOrderId();
        $return_url = $this->getReturnUrl('amazonpaymentservicesfort/payment/visaCheckoutResponse');

        $ip = $this->getVisitorIp();
        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => $this->getMainConfigData('command'),
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'customer_ip'           => $ip,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'customer_email'        => trim($order->getCustomerEmail()),
            'language'              => $language,
            'return_url'            => $return_url
        ];
        $integrationType = $this->getConfig('payment/aps_fort_visaco/integration_type');
        if ($integrationType == self::INTEGRATION_TYPE_HOSTED) {
            $postData['digital_wallet'] = 'VISA_CHECKOUT';
            $postData['call_id'] = $responseParams['callid'];
        }

        $paymentMethod = $order->getPayment()->getMethod();

        $customer_name = $this->getOrderCustomerName($order);
        if (!empty($customer_name)) {
            $postData['customer_name'] = $customer_name;
        }
        $postData = array_merge($postData, $this->pluginParams());
        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("visaCheckoutPageNotifyFort: Request Params : " . json_encode($postData, 1));
        $this->log("visaCheckoutPageNotifyFort: Gateway URL : " . $gatewayUrl);
        $response = $this->callApi($postData, $gatewayUrl);

        $debugMsg = "Response Params ($paymentMethod)"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function capturePayment($responseParams)
    {
        $order = $this->orderRepository->get($responseParams['orderId']);
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $responseParams['orderNumber'];
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Valu::CODE) {
            $orderId = $this->getApsValuRefFromOrderParams(null, $orderId);
        }
        $orderCurrency = $order->getOrderCurrencyCode();
        $amount        = $this->convertAmount($responseParams['amount'], $orderCurrency);
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => 'CAPTURE',
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'amount'                => $amount,
            'currency'              => strtoupper($orderCurrency),
            'language'              => $language
        ];

        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Apple::CODE) {
            $postData['access_code'] = $this->getConfig('payment/aps_apple/apple_access_code');
            $signature = $this->calculateSignature($postData, 'request', 'apple_pay');
        } else {
            $signature = $this->calculateSignature($postData, 'request');
        }

        $postData['signature'] = $signature;
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("APS Capture: Request Params : " . json_encode($postData, 1));
        $this->log("Aps Capture: Gateway URL : " . $gatewayUrl);
        $response = $this->callApi($postData, $gatewayUrl);
        $debugMsg = "APs Capture Response Params ($paymentMethod)"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function voidPayment($responseParams)
    {
        $order = $this->_order->load($responseParams['orderId']);
        $paymentMethod = $order->getPayment()->getMethod();

        $orderId = $responseParams['orderNumber'];
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Valu::CODE) {
            $orderId = $this->getApsValuRefFromOrderParams(null, $orderId);
        }
        $orderCurrency = $order->getOrderCurrencyCode();
        $amount        = $this->convertAmount($responseParams['amount'], $orderCurrency);
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => 'VOID_AUTHORIZATION',
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'language'              => $language
        ];

        //calculate request signature
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Apple::CODE) {
            $postData['access_code'] = $this->getConfig('payment/aps_apple/apple_access_code');
            $signature = $this->calculateSignature($postData, 'request', 'apple_pay');
        } else {
            $signature = $this->calculateSignature($postData, 'request');
        }
        $postData['signature'] = $signature;
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("APS Void: Request Params : " . json_encode($postData, 1));
        $this->log("Aps Void: Gateway URL : " . $gatewayUrl);
        $response = $this->callApi($postData, $gatewayUrl);
        $debugMsg = "APs Void Response Params ($paymentMethod)"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function tokenChangeStatus($token, $orderId, $status)
    {
        $language      = $this->getLanguage();
        $postData = [
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'service_command'       => 'UPDATE_TOKEN',
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'language'              => $language,
            'token_name'            => $token,
            'token_status'          => $status
        ];

        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("APS Vault Status ".$status." : Request Params : " . json_encode($postData, 1));
        $this->log("Aps Vault Status: Gateway URL : " . $gatewayUrl);
        $response = $this->callApi($postData, $gatewayUrl);
        $debugMsg = "APs Vault Status Response Params :"."\n".json_encode($response, 1);
        $this->log($debugMsg);

        return $response;
    }

    public function callApi($postData, $gatewayUrl, $certificate_key = '', $certificate_path = '', $certificate_pass = '')
    {
        try {
            $this->getCurlClient()->setOption(CURLOPT_FAILONERROR, 1);
            $this->getCurlClient()->setOption(CURLOPT_ENCODING, "compress, gzip");
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_FOLLOWLOCATION, 0);
            $this->getCurlClient()->setOption(CURLOPT_CONNECTTIMEOUT, 0);
            $this->getCurlClient()->setOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);

            if (!empty($certificate_key)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLKEY, $certificate_key);
            }
            if (!empty($certificate_path)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLCERT, $certificate_path);
            }
            if (!empty($certificate_pass)) {
                $this->getCurlClient()->setOption(CURLOPT_SSLKEYPASSWD, $certificate_pass);
            }
            $this->getCurlClient()->addHeader("Content-Type", "application/json;charset=UTF-8");

            $this->getCurlClient()->post($gatewayUrl, json_encode($postData));

            $response = $this->getCurlClient()->getBody();
            $this->log("CURL Response :". json_encode($response));
            $array_result = json_decode($response, true);
            if (empty($array_result)) {
                return false;
            }
            return $array_result;

        } catch (\Exception $e) {
            $this->log("Call API :".$e->getMessage());
            return false;
        }
    }

    public function getCurlClient()
    {
        return $this->_curl;
    }

    /** @return string */
    private function getVisitorIp()
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    /**
     * calculate fort signature
     * @param $arrData
     * @param string $signType request or response
     * @param string $type
     * @return string fort signature
     */
    public function calculateSignature($arrData, $signType = 'request', $type = '')
    {
        if ($type == 'apple_pay') {
            $shaInPassPhrase  = $this->getConfig('payment/aps_apple/apple_sha_in_pass_phrase');
            $shaOutPassPhrase = $this->getConfig('payment/aps_apple/apple_sha_out_pass_phrase');
            $shaType = $this->getConfig('payment/aps_apple/apple_sha_type');
        } else {
            $shaInPassPhrase  = $this->getMainConfigData('sha_in_pass_phrase');
            $shaOutPassPhrase = $this->getMainConfigData('sha_out_pass_phrase');
            $shaType = $this->getMainConfigData('sha_type');
        }
        //@codingStandardsIgnoreStart
        $shaInPassPhrase = html_entity_decode(htmlentities($shaInPassPhrase));
        $shaOutPassPhrase = html_entity_decode(htmlentities($shaOutPassPhrase));
        //@codingStandardsIgnoreEnd
        $shaString = '';

        ksort($arrData);
        foreach ($arrData as $k => $v) {
            if ($k == 'products') {
                $shaString .= "$k=".$this->getProductArr($v);
            } elseif ($k == 'apple_header' || $k == 'apple_paymentMethod') {
                $shaString .= $k."={";
                foreach ($v as $i => $j) {
                    $shaString .= $i.'='.$j.", ";
                }
                $shaString = rtrim($shaString, ', ');
                $shaString .= "}";
            } else {
                $shaString .= "$k=$v";
            }
        }

        if ($signType == 'request') {
            $shaString = $shaInPassPhrase . $shaString . $shaInPassPhrase;
        } else {
            $shaString = $shaOutPassPhrase . $shaString . $shaOutPassPhrase;
        }

        if ($shaType == \Amazonpaymentservices\Fort\Model\Config\Source\Shaoptions::SHA256 || $shaType == \Amazonpaymentservices\Fort\Model\Config\Source\Shaoptions::SHA512) {
            $shaType = str_replace('-', '', $shaType);
            $signature = hash($shaType, $shaString);
        } elseif ($signType == "request") {
            $shaType = ($shaType == \Amazonpaymentservices\Fort\Model\Config\Source\Shaoptions::HMAC256) ? "SHA256" : "SHA512";
            $signature = hash_hmac($shaType, $shaString, $shaInPassPhrase);
        } else {
            $shaType = ($shaType == \Amazonpaymentservices\Fort\Model\Config\Source\Shaoptions::HMAC256) ? "SHA256" : "SHA512";
            $signature = hash_hmac($shaType, $shaString, $shaOutPassPhrase);
        }

        return $signature;
    }

    /**
     * genarate product string for signature
     *
     * @param mixed $values
     * @return string
     */
    private function getProductArr($values)
    {
        $productsString = '[';
        $productName = '';
        $productPrice = 0;
        $productCategory = '';
        foreach ($values as $val) {
            $productName .= $val['product_name'];
            $productPrice += $val['product_price'];
            $productCategory .= $val['product_category'];
        }
        $productName = substr($productName, 0, 50);
        $productCategory = substr($productCategory, 0, 50);
        $productCategory = empty($productCategory) ? 'Uncategorized' : $productCategory;
        $productsString .= '{product_name='.$productName.', product_price='.$productPrice.', product_category='.$productCategory.'}';
        $productsString .= ']';
        return $productsString;
    }

    /**
     * Convert Amount with dicemal points
     * @param object $order
     * @param string  $currencyCode
     * @return float
     */
    public function convertFortAmount($order, $currencyCode)
    {
        $gateway_currency = $this->getGatewayCurrency();
        $new_amount     = 0;

        if ($gateway_currency == 'front') {
            $amount = $order->getGrandTotal();
        } else {
            $amount = $order->getBaseGrandTotal();
        }
        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = round($amount, $decimal_points);
        return $new_amount * (pow(10, $decimal_points));
    }

    /**
     * Convert Amount with decimal points
     * @param mixed  $amount
     * @param string  $currencyCode
     * @return float
     */
    public function convertRevAmount($amount, $currencyCode)
    {
        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = $amount / (pow(10, $decimal_points));
        $this->log('Convert Amoun. Actual:'.$amount."::Converted:".$new_amount);
        return $new_amount;
    }

    /**
     * Convert Amount with decimal points
     * @param mixed $amount
     * @param string  $currencyCode
     * @return float
     */
    public function convertAmount($amount, $currencyCode)
    {
        $new_amount     = 0;

        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = round($amount, $decimal_points);
        return $new_amount * (pow(10, $decimal_points));
    }

    /**
     * Convert decimal point Amount with original amount
     * @param float $amount
     * @param string  $currencyCode
     * @return float
     */
    public function convertDecAmount($amount, $currencyCode)
    {
        $newAmount     = 0;
        $decimalPoints = $this->getCurrencyDecimalPoint($currencyCode);
        $divideBy      = (int)(str_pad(1, $decimalPoints + 1, 0, STR_PAD_RIGHT));
        if (0 === $decimalPoints) {
            $newAmount = $amount;
        } else {
            $newAmount = round($amount / $divideBy);
        }
        return $newAmount;
    }

    public function convertFortAmountCart($currencyCode)
    {
        $gateway_currency = $this->getGatewayCurrency();
        $new_amount     = 0;

        if ($gateway_currency == 'front') {
            $amount = $this->_cart->getQuote()->getGrandTotal();
        } else {
            $amount = $this->_cart->getQuote()->getBaseGrandTotal();
        }
        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = round((float)$amount, $decimal_points);
        return $new_amount * (pow(10, $decimal_points));
    }

    /**
     * @param string $currency
     * @return integer
     */
    public function getCurrencyDecimalPoint($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = [
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        ];
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    public function getGatewayCurrency()
    {
        $gatewayCurrency = $this->getMainConfigData('gateway_currency');
        if (empty($gatewayCurrency)) {
            $gatewayCurrency = 'base';
        }
        return $gatewayCurrency;
    }

    public function getBaseCurrency()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getFrontCurrency()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getFortCurrency($baseCurrencyCode, $currentCurrencyCode)
    {
        $gateway_currency = $this->getMainConfigData('gateway_currency');
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    public function getGatewayUrl($type = 'redirection')
    {
        $testMode = $this->getMainConfigData('sandbox_mode');
        if ($type == 'notificationApi') {
            $gatewayUrl = $testMode ? $this->_gatewaySandboxNotify.'FortAPI/paymentApi' :  $this->_gatewayNotify.'FortAPI/paymentApi';
        } else {
            $gatewayUrl = $testMode ? $this->_gatewaySandboxHost.'FortAPI/paymentPage' : $this->_gatewayHost.'FortAPI/paymentPage';
        }

        return $gatewayUrl;
    }

    public function getVisaCheckoutJs()
    {
        $testMode = $this->getMainConfigData('sandbox_mode');
        if ($testMode) {
            $gatewayUrl = 'https://sandbox-assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
        } else {
            $gatewayUrl = 'https://assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
        }

        return $gatewayUrl;
    }

    public function getReturnUrl($path)
    {
        return $this->_storeManager->getStore()->getBaseUrl().$path;
    }

    public function getLanguage()
    {
        $language = $this->_localeResolver->getLocale();

        if (str_starts_with($language, 'ar')) {
            $language = 'ar';
        } else {
            $language = 'en';
        }
        return $language;
    }

    /**
     * Restores quote
     *
     * @return void
     */
    public function restoreQuote($order = null)
    {
        if (!$order) {
            return;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
        $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

        $quote = $_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());

        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $_checkoutSession->replaceQuote($quote);
        }
    }

    /**
     * Delete an order after a failed payment (if option selected)
     *
     * @param OrderInterface $order
     *
     * @return void
     */
    public function deleteOrder(OrderInterface $order): void
    {
        if (!$order->getEntityId()) {
            return;
        }

        $this->orderManagement->cancel($order->getEntityId());
        $this->registry->register('isSecureArea', true);
        $this->orderRepository->delete($order);
        $this->registry->unregister('isSecureArea');
    }

    /**
     * Checks if version requires restore quote fix.
     *
     * @return bool
     */
    private function isReturnItemsToInventoryRequired()
    {
        $version = $this->getMagentoVersion();
        return version_compare($version, "2.3", ">=");
    }

    /**
     * Returns items to inventory.
     *
     */
    private function returnItemsToInventory()
    {
        $quote = $this->session->getQuote();
        $items = $this->_productQty->getProductQty($quote->getAllItems());
        $revertedItems = $this->_stockManagement->revertProductsSale($items, $quote->getStore()->getWebsiteId());

        if (is_bool($revertedItems)) {
            return;
        }
        $productIds = array_keys($revertedItems);
        if (!empty($productIds)) {
            $this->_stockIndexerProcessor->reindexList($productIds);
            $this->_priceIndexer->reindexList($productIds);
        }
        // Clear flag, so if order placement retried again with success - it will be processed
        $quote->setInventoryProcessed(false);
    }

    /**
     * Gets the Magento version.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->_productMetadata->getVersion();
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param  string $comment Comment appended to order history
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->session->getLastRealOrder();
        if (!empty($comment)) {
            $comment = 'Aps_Fort :: ' . $comment;
        }
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->cancel();
            $order->addStatusToHistory($order::STATE_CANCELED, $comment, false);
            $order->save();

//            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * Cancel order with specified comment message
     *
     * @return bool
     */
    public function cancelOrder($order, $comment)
    {
        $gotoSection = false;
        if (!empty($comment)) {
            $comment = 'Aps_Fort :: ' . $comment;
        }
        if ($order->getState() != Order::STATE_CANCELED) {
            $order->cancel();
            $order->addStatusToHistory($order::STATE_CANCELED, $comment, false);
            $order->save();

//            $order->registerCancellation($comment)->save();
            /*if ($this->restoreQuote()) {
                //Redirect to payment step
                $gotoSection = 'paymentMethod';
            }*/
            $gotoSection = true;
        }
        return $gotoSection;
    }

    public function orderFailed($order, $reason, $responseCode = '')
    {
        if ($this->canCancelOrder($order)) {
            if ($this->isOrderResponseOnHold($responseCode)) {
                if ($order->getState() != $order::STATE_HOLDED) {
                    $order->setStatus($order::STATE_HOLDED);
                    $order->setState($order::STATE_HOLDED);
                    $order->save();
                    $order->addStatusToHistory($order::STATE_HOLDED, $reason, false);
                    $order->save();
                    $this->apsSubscriptionOrder($order, 0);
                    return true;
                }
            } elseif ($order->getState() != $order::STATE_CANCELED) {
                $order->cancel();
                $order->addCommentToStatusHistory($reason, false, true);
                $order->save();

                $this->apsSubscriptionOrder($order, 0);
                return true;
            }
        }
        return false;
    }

    public function applePayResponse($responseParams)
    {
        //timestamp here
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!empty($order->getRealOrderId())) {
            $orderId = $order->getRealOrderId();
        } else {
            $quote = $this->_cart->getQuote();
            $order = $this->quoteManagement->submit($quote);
            $orderId = $order->getRealOrderId();
        }

        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language = $this->getLanguage();

        $data = [
            "digital_wallet" => "APPLE_PAY",
            "command"=> $this->getMainConfigData('command'),
            "access_code"=> $this->getConfig('payment/aps_apple/apple_access_code'),
            "merchant_identifier"=> $this->getConfig('payment/aps_apple/merchant_identifier'),
            "merchant_reference"=> $orderId,
            "amount"=> $amount,
            "currency"=> strtoupper($currency),
            "language"=> $language,
            "customer_email"=> trim($order->getCustomerEmail()),
            "apple_data"=> $responseParams->paymentData->data,
            "apple_signature"=> $responseParams->paymentData->signature,
            "customer_ip" => $this->getVisitorIp()
        ];
        foreach ($responseParams->paymentData->header as $key => $value) {
            $data['apple_header']['apple_'.$key] = $value;
        }
        foreach ($responseParams->paymentMethod as $key => $value) {
            $data['apple_paymentMethod']['apple_'.$key] = $value;
        }
        $data = array_merge($data, $this->pluginParams());
        $data['signature'] = $this->calculateSignature($data, 'request', "apple_pay");

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        //timestamp here
        $result = $this->callApi($data, $gatewayUrl);
        //timestamp here
        $integrationType = \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::HOSTED;
        $success = $this->handleFortResponse($result, 'online', $integrationType, 'h2h');
        //timestamp here
        return ['success' => $success, 'order' => $order];
    }

    public function applePayCartResponse($responseParams, $shipData)
    {
        $this->log('Apple Submitted Data:'. json_encode($responseParams));
        $customerData = $responseParams->shippingContact;
        $responseParams = $responseParams->token;
        $this->log('Apple Submitted Data:'. json_encode($responseParams));
        $quote = $this->_cart->getQuote();

        $quote->getShippingAddress()->setFirstname($customerData->givenName);
        $quote->getShippingAddress()->setLastname($customerData->familyName);

        if (!$this->_custmerSession->isLoggedIn()) {
            $quote->getShippingAddress()->setEmail($customerData->emailAddress);
        } else {
            $quote->getShippingAddress()->setEmail($this->_custmerSession->getCustomer()->getEmail());
        }
        $quote->getShippingAddress()->setTelephone($customerData->phoneNumber);
        if (!empty($customerData->postalCode)) {
            $quote->getShippingAddress()->setPostcode($customerData->postalCode);
        } else {
            $quote->getShippingAddress()->setPostcode('00000');
        }
        $quote->getShippingAddress()->setStreet(implode(", ", $customerData->addressLines));
        $this->log("ShipData:".json_encode($shipData));
        if (isset($shipData->id)) {
            $quote->getShippingAddress()->setShippingMethod($shipData->id);
        } else {
            $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        }
        $quote->getBillingAddress()->setFirstname($customerData->givenName);
        $quote->getBillingAddress()->setLastname($customerData->familyName);

        if (!$this->_custmerSession->isLoggedIn()) {
            $quote->getBillingAddress()->setEmail($customerData->emailAddress);
        } else {
            $quote->getBillingAddress()->setEmail($this->_custmerSession->getCustomer()->getEmail());
        }

        $quote->getBillingAddress()->setTelephone($customerData->phoneNumber);
        if (!empty($customerData->postalCode)) {
            $quote->getBillingAddress()->setPostcode($customerData->postalCode);
        } else {
            $quote->getBillingAddress()->setPostcode('00000');
        }
        $quote->getBillingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getBillingAddress()->setStreet($quote->getShippingAddress()->getStreet());
        $quote->getBillingAddress()->setCity($quote->getShippingAddress()->getCity());
        $quote->getBillingAddress()->setCountryId($quote->getShippingAddress()->getCountryId());
        $quote->getBillingAddress()->setRegionId($quote->getShippingAddress()->getRegionId());
        $quote->getBillingAddress()->setRegion($quote->getShippingAddress()->getRegion());

        $quote->setPaymentMethod(\Amazonpaymentservices\Fort\Model\Method\Apple::CODE);
        $quote->getPayment()->importData(['method' => \Amazonpaymentservices\Fort\Model\Method\Apple::CODE]);

        if (!$this->_custmerSession->isLoggedIn()) {
            $quote->setCustomerEmail($customerData->emailAddress);
        } else {
            $quote->setCustomerEmail($this->_custmerSession->getCustomer()->getEmail());
        }

        $quote->setCustomerFirstname($customerData->givenName);
        $quote->setCustomerLastname($customerData->familyName);
        if (!$this->_custmerSession->isLoggedIn()) {
            $quote->setCustomerId(null);
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
        }
        $quote->save();

        //$quote = $this->_cart->getQuote();
        $order = $this->quoteManagement->submit($quote);
        $orderId = $order->getRealOrderId();

        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language = $this->getLanguage();

        $data = [
            "digital_wallet" => "APPLE_PAY",
            "command"=> $this->getMainConfigData('command'),
            "access_code"=> $this->getConfig('payment/aps_apple/apple_access_code'),
            "merchant_identifier"=> $this->getConfig('payment/aps_apple/merchant_identifier'),
            "merchant_reference"=> $orderId,
            "amount"=> $amount,
            "currency"=> strtoupper($currency),
            "language"=> $language,
            "customer_email"=> trim($order->getCustomerEmail()),
            "apple_data"=> $responseParams->paymentData->data,
            "apple_signature"=> $responseParams->paymentData->signature,
            "customer_ip" => $this->getVisitorIp()
        ];
        foreach ($responseParams->paymentData->header as $key => $value) {
            $data['apple_header']['apple_'.$key] = $value;
        }
        foreach ($responseParams->paymentMethod as $key => $value) {
            $data['apple_paymentMethod']['apple_'.$key] = $value;
        }
        $data = array_merge($data, $this->pluginParams());
        $data['signature'] = $this->calculateSignature($data, 'request', "apple_pay");

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $result = $this->callApi($data, $gatewayUrl);
        $integrationType = \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::HOSTED;
        $success = $this->handleFortResponse($result, 'online', $integrationType, 'h2h');
        return ['success' => $success, 'order' => $order];
    }

    public function visaCheckoutResponse($responseParams)
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $orderId = $order->getRealOrderId();

        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language = $this->getLanguage();

        $data = [
            "digital_wallet" => "VISA_CHECKOUT",
            "command"=> $this->getMainConfigData('command'),
            "access_code"=> $this->getMainConfigData('access_code'),
            "merchant_identifier"=> $this->getMainConfigData('merchant_identifier'),
            "merchant_reference"=> $orderId,
            "amount"=> $amount,
            "currency"=> strtoupper($currency),
            "language"=> $language,
            "customer_email"=> trim($order->getCustomerEmail()),
            "customer_ip" => $this->getVisitorIp(),
            "call_id" => $responseParams->callid
        ];
        $data = array_merge($data, $this->pluginParams());
        $data['signature'] = $this->calculateSignature($data, 'request');

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $result = $this->callApi($data, $gatewayUrl);
        $integrationType = \Amazonpaymentservices\Fort\Model\Config\Source\VisaCheckoutIntegrationtypeoptions::HOSTED;
        $success = $this->handleFortResponse($result, 'online', $integrationType, 'h2h');
        return ['success' => $success, 'order' => $order];
    }

    public function apsRefund($orderId, $currencyCode, $amount, $paymentMethod, $order)
    {
        $language = $this->getLanguage();
        $amount = $this->convertAmount($amount, $currencyCode);
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Stc::CODE) {
            $orderId = $this->getApsStcRefFromOrderParams(null, $orderId);
        }
        $data = [
            "command"             => 'REFUND',
            "access_code"         => $this->getMainConfigData('access_code'),
            "merchant_identifier" => $this->getMainConfigData('merchant_identifier'),
            "merchant_reference"  => $orderId,
            "amount"              => $amount,
            "currency"            => strtoupper($currencyCode),
            "language"            => $language
        ];

        $signature = '';
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Apple::CODE) {
            $data['access_code'] = $this->getConfig('payment/aps_apple/apple_access_code');
            $signature = $this->calculateSignature($data, 'request', 'apple_pay');
        } else {
            $signature = $this->calculateSignature($data, 'request');
        }

        $data['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        return $this->callApi($data, $gatewayUrl);
    }

    public function checkOrderStatus($orderId, $paymentMethod = '')
    {
        $language = $this->getLanguage();
        $type = '';
        $access_code = $this->getMainConfigData('access_code');
        $merchant_identifier = $this->getMainConfigData('merchant_identifier');
        if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Apple::CODE) {
            $type = 'apple_pay';
            $access_code = $this->getConfig('payment/aps_apple/apple_access_code');
            $merchant_identifier = $this->getConfig('payment/aps_apple/merchant_identifier');
        }

        $data = [
            "query_command"       => 'CHECK_STATUS',
            "access_code"         => $access_code,
            "merchant_identifier" => $merchant_identifier,
            "merchant_reference"  => $orderId,
            "language"            => $language
        ];

        $data['signature'] = $this->calculateSignature($data, 'request', $type);
        $this->log('APS verify order request:'. json_encode($data));
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        return $this->callApi($data, $gatewayUrl);
    }

    public function processOrder($order, $responseParams)
    {
        $this->log('process order');
        if ($order->getState() == $order::STATE_COMPLETE) {
            return false;
        }
        if ($order->getState() != $order::STATE_PROCESSING) {
            $this->log('process order1');
            $payment = $order->getPayment();
            $payment->setTransactionId($responseParams['fort_id'])->setIsTransactionClosed(0);
            $payment->setAdditionalInformation($responseParams['merchant_reference']);

            $sessionData = $this->_custmerSession->getCustomValue();

            if (!empty($sessionData)) {
                if (!empty($sessionData['installment_interest'])) {
                    $responseParams['installment_interest'] = $sessionData['installment_interest'];
                }
                if (!empty($sessionData['installment_amount'])) {
                    $responseParams['installment_amount'] = $sessionData['installment_amount'];
                }
                if (!empty($sessionData['valu_tenure'])) {
                    $responseParams['valu_tenure'] = $sessionData['valu_tenure'];
                }
                if (!empty($sessionData['valu_tenure_amount'])) {
                    $responseParams['valu_tenure_amount'] = $sessionData['valu_tenure_amount'];
                }
                if (!empty($sessionData['valu_tenure_interest'])) {
                    $responseParams['valu_tenure_interest'] = $sessionData['valu_tenure_interest'];
                }
            }

            $payment->setAdditionalData(json_encode($responseParams));
            $payment->save();

            $this->log('process order2');
            $invoice = $this->createInvoice($order, $responseParams);

            $order->setState($order::STATE_PROCESSING)->save();
            $order->setStatus($order::STATE_PROCESSING)->save();

            $this->sendOrderEmail($order);

            $order->addStatusToHistory($order::STATE_PROCESSING, 'APS :: Order has been paid.', true);
            $order->save();
            $this->log('process order2');
            $paymentMethod = $order->getPayment()->getMethod();
            if (( $paymentMethod != \Amazonpaymentservices\Fort\Model\Method\Tabby::CODE || $paymentMethod != \Amazonpaymentservices\Fort\Model\Method\Stc::CODE ) && !empty($responseParams['token_name']) && !empty($order->getCustomerId()) && $this->getConfig('payment/aps_fort_vault/active') == '1') {
                $this->log('process order3');
                $this->log('process order4');
                $year = substr($responseParams['expiry_date'], 0, 2);
                $month = substr($responseParams['expiry_date'], 2, 4);
                $paymentMethodCode = Payment::CODE;

                $hashKey = $responseParams['token_name'];
                if ($order->getCustomerId()) {
                    $hashKey = $order->getCustomerId();
                }
                $hashKey .= $paymentMethodCode
                    . 'card'
                    . '{"type":"'.$responseParams['payment_option'].'","maskedCC":"'.$responseParams['card_number'].'","expirationDate":"'.$year."\/".$month.'","orderId":"'.$responseParams['merchant_reference'].'"}';

                $publicHash = $this->_encryptorInterface->getHash($hashKey);

                $tokenobjectManagerDuplicate = $this->_paymentToken->getByGatewayToken($responseParams['token_name'], $paymentMethodCode, $order->getCustomerId());
                $this->log('process order5');
                $this->saveTokenisation($tokenobjectManagerDuplicate, $order, $publicHash, $paymentMethodCode, $responseParams, $year, $month);
            }
            $this->log('process order8');
            $this->sendInvoiceEmail($invoice);
            $this->apsSubscriptionOrder($order, 1);

            return true;
        }
        return false;
    }

    private function saveTokenisation($tokenobjectManagerDuplicate, $order, $publicHash, $paymentMethodCode, $responseParams, $year, $month)
    {
        $entityID = '';
        if (empty($tokenobjectManagerDuplicate)) {
            $this->log('process order6');
            $_paymentToken = $this->_modelPaymentToken;
            $_paymentToken->setCustomerId($order->getCustomerId());
            $_paymentToken->setPublicHash($publicHash);
            $_paymentToken->setPaymentMethodCode($paymentMethodCode);
            $_paymentToken->setType('card');
            $_paymentToken->setCreatedAt(date('Y-m-d H:i:s'));
            $_paymentToken->setExpiresAt(date("Y-m-d", strtotime(date("Y-m-d", strtotime(date('Y-m-d H:i:s'))) . " + 365 day")));
            $_paymentToken->setGatewayToken($responseParams['token_name']);
            $_paymentToken->setTokenDetails('{"type":"'.$responseParams['payment_option'].'","maskedCC":"'.$responseParams['card_number'].'","expirationDate":"'.$year."\/".$month.'","orderId":"'.$responseParams['merchant_reference'].'"}');
            $_paymentToken->setIsActive(true);
            $_paymentToken->setIsVisible(true);
            $_paymentToken->save();
            $entityID = $_paymentToken->getEntityId();
        } else {
            $this->log('process order7');
            $_paymentToken = $this->_paymentTokenInterface;
            $_paymentToken->setEntityId($tokenobjectManagerDuplicate['entity_id']);
            $_paymentToken->setPublicHash($publicHash);
            $_paymentToken->setPaymentMethodCode($paymentMethodCode);
            $_paymentToken->setGatewayToken($responseParams['token_name']);
            $_paymentToken->setCreatedAt(date('Y-m-d H:i:s'));
            $_paymentToken->setExpiresAt(date("Y-m-d", strtotime(date("Y-m-d", strtotime(date('Y-m-d H:i:s'))) . " + 365 day")));

            $_paymentToken->setTokenDetails('{"type":"'.$responseParams['payment_option'].'","maskedCC":"'.$responseParams['card_number'].'","expirationDate":"'.$year."\/".$month.'","orderId":"'.$responseParams['merchant_reference'].'"}');
            $_paymentToken->setIsActive(true);
            $_paymentToken->setIsVisible(true);
            $paymentToken = $this->_modelPaymentToken;
            $paymentToken->save($_paymentToken);
            $entityID = $paymentToken->getEntityId();
        }

        $connection = $this->_connection->getConnection();
        $connection->insert(
            $this->_connection->getTableName('vault_payment_token_order_payment_link'),
            [
                'order_payment_id' => $order->getPayment()->getEntityId(),
                'payment_token_id' => $entityID
            ]
        );
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice
     */
    private function createInvoice($order, $responseParams)
    {
        if (!$order->hasInvoices()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $invoice->setTransactionId($responseParams['fort_id']);
            $order->addRelatedObject($invoice);
            return $invoice;
        }

        // return the first invoice
        $invoiceCollection = $order->getInvoiceCollection();
        return array_pop($invoiceCollection);
    }

    private function sendInvoiceEmail(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $this->_invoiceSender->send($invoice);
    }

    private function sendOrderEmail($order)
    {
        $this->_orderSender->send($order);
    }

    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($order_id)
    {
        return $this->_order->loadByIncrementId($order_id);
    }

    /**
     * @param array  $fortParams
     * @param string $responseMode
     * @param string $integrationType
     * @param string $responseSource
     *
     * @retrun boolean
     */
    public function handleFortResponse($fortParams = [], $responseMode = 'online', $integrationType = self::INTEGRATION_TYPE_REDIRECTION, $responseSource = '')
    {
        try {

            $responseParams  = $fortParams;
            $success         = false;

            $orderId = $this->getOrderId($responseParams);

            $order = $this->getOrderById($orderId);

            if (empty($responseParams)) {
                $responseMessage = __('Invalid fort response parameters');
                $this->log($responseMessage);
                $this->restoreQuote($order);
                $this->_messageManager->addError($responseMessage);
                return false;
            }

            if (empty($responseParams['merchant_reference'])) {
                $responseMessage = "Merchant Reference not found\n\n" . json_encode($responseParams, 1);
                $this->log($responseMessage);
                $this->restoreQuote($order);
                $this->_messageManager->addError($responseMessage);
                return false;
            }

            $responseType          = $responseParams['response_message'] ?? '';
            $responseStatusMessage = $responseType;
            $signature             = $responseParams['signature'] ?? '';
            $responseCode          = $responseParams['response_code'] ?? '';

            if (!$order->getPayment()) {
                $responseMessage = $responseStatusMessage;
                $this->log($responseMessage);
                $this->_messageManager->addError($responseMessage);

                return false;
            }

            $paymentMethod = $order->getPayment()->getMethod();
            $this->log("Pay method ($paymentMethod)" . json_encode($responseParams, 1));

            $notIncludedParams = ['signature', 'aps_fort', 'integration_type','form_key'];

            $responseGatewayParams = $this->getGatewayResponseParams($responseParams, $notIncludedParams);

            $signType = '';
            if (!empty($responseParams['digital_wallet']) && $responseParams['digital_wallet']=='APPLE_PAY') {
                $signType = 'apple_pay';
            }

            $responseSignature = $this->calculateSignature($responseGatewayParams, 'response', $signType);

            // check the signature
            if (strtolower($responseSignature) !== strtolower($signature)) {
                return $this->invalidSignature($signature, $responseSignature, $order);
            }

            if ($responseSource == 'h2h' && isset($responseParams['3ds_url'])) {
                return $this->checkHostToHost($responseCode, $responseParams);
            }
            if (substr($responseCode, 2) != '000') {
                return $this->errorReponse($responseCode, $order, $responseStatusMessage);
            } else if ($responseMode == 'online' && $responseSource != 'h2h' && (($paymentMethod == self::PAYMENT_METHOD_CC) && ($integrationType == self::INTEGRATION_TYPE_STANDARD || $integrationType == self::INTEGRATION_TYPE_HOSTED))) {
                $host2HostParams = $this->merchantPageNotifyFort($responseParams, $order);
                return $this->handleFortResponse($host2HostParams, 'online', $integrationType, 'h2h');
            } else {
                $this->log("Process Order Called");
                $procesReturn = $this->processOrder($order, $responseParams);
                if ($procesReturn) {
                    $this->log('Process Log :True');
                } else {
                    $this->log('Process Log :False');
                }
            }
        } catch (\Exception $e) {
            if (isset($order)) {
                $this->restoreQuote($order);
            }
            $this->log("APS Error :". json_encode($e->getMessage()));
            $this->_messageManager->addError($e->getMessage());
            return false;
        }
        return true;
    }

    private function getGatewayResponseParams($responseParams, $notIncludedParams)
    {
        $responseGatewayParams = $responseParams;
        foreach ($responseGatewayParams as $k => $v) {
            if (in_array($k, $notIncludedParams)) {
                unset($responseGatewayParams[$k]);
            }
        }
        return $responseGatewayParams;
    }

    public function getApsParamsIncrementIdByReference($parameter, $reference) {
        $connection = $this->_connection->getConnection();

        $query = $connection
            ->select()
            ->from(['table'=>'aps_order_params'])
            ->where('table.' . $parameter . '=?', $reference);
        $collections = $this->fetchAllQuery($query);
        if (empty($collections)) {
            try {
                $collections = $this->_salesCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter($parameter, ['eq'=>$reference]);
                foreach ($collections as $collection) {
                    return $collection->getIncrementId();
                }
            } catch (\Exception $e) {
                $logMsg = "APS '" . $parameter . "' reference number column is not in sales_order table";
                $this->log($logMsg);
            }
        }

        if ($collections) {
            foreach ($collections as $collection) {
                if ($collection['order_increment_id'] ?? null) {
                    return $collection['order_increment_id'];
                }
            }
        }

        return $reference;
    }

    private function getOrderId($responseParams)
    {
        $connection = $this->_connection->getConnection();
        $orderId = $responseParams['merchant_reference'] ?? null;
        $collections = null;

        if (isset($responseParams['payment_option']) && $responseParams['payment_option'] == 'VALU') {
            $query = $connection
                ->select()
                ->from(['table'=>'aps_order_params'])
                ->where('table.aps_valu_ref=?', $orderId);
            $collections = $this->fetchAllQuery($query);
            if (empty($collections)) {
                try {
                    $collections = $this->_salesCollectionFactory->create()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('aps_valu_ref', ['eq' => $orderId]);
                    foreach ($collections as $collection) {
                        return $collection->getIncrementId();
                    }
                } catch (\Exception $e) {
                    $logMsg = "APS Valu reference number column is not in sales_order table";
                    $this->log($logMsg);
                }
            }
        }

        if (
            (isset($responseParams['payment_option']) && $responseParams['payment_option'] == 'STCPAY')
            || (isset($responseParams['digital_wallet']) && $responseParams['digital_wallet'] == 'STCPAY')
        ) {
            $query = $connection
                ->select()
                ->from(['table'=>'aps_order_params'])
                ->where('table.aps_stc_ref=?', $orderId);
            $collections = $this->fetchAllQuery($query);
            if (empty($collections)) {
                try {
                    $collections = $this->_salesCollectionFactory->create()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('aps_stc_ref', ['eq' => $orderId]);
                    foreach ($collections as $collection) {
                        return $collection->getIncrementId();
                    }
                } catch (\Exception $e) {
                    $logMsg = "APS STC reference number column is not in sales_order table";
                    $this->log($logMsg);
                }
            }
        }

        if (!empty($collections)) {
            foreach ($collections as $collection) {
                if ($collection['order_increment_id'] ?? null) {
                    return $collection['order_increment_id'];
                }
            }
        }

        return $orderId;
    }

    private function invalidSignature($signature, $responseSignature, $order)
    {
        $responseMessage = __('Invalid response signature.');
        $logMsg = sprintf('Invalid Signature. Calculated: %1s, Response: %2s', $signature, $responseSignature);
        $this->log($logMsg);
        $r = $this->orderFailed($order, 'Invalid Signature.', '15777');
        if ($r) {
            $this->restoreQuote($order);
            $this->_messageManager->addError($responseMessage);
        }

        return false;
    }

    private function errorReponse($responseCode, $order, $responseStatusMessage)
    {
        if ($responseCode == \Amazonpaymentservices\Fort\Model\Payment::PAYMENT_STATUS_CANCELED) {
            $responseMessage = __('You have canceled the payment, please try again.');
            $r = $this->cancelOrder($order, 'Payment Cancelled');
            if ($r) {
                $this->restoreQuote($order);
                $this->_messageManager->addError($responseMessage);
                return false;
            }
        } elseif ($responseCode == self::PAYMENT_TRANSACTION_DECLINED){
            $responseMessage = __('Your payment transaction was declined, please try again.');
            $r = $this->orderFailed($order, $responseMessage, $responseCode);
            if ($r) {
                $this->restoreQuote($order);
                $this->_messageManager->addError($responseMessage);
                return false;
            }
        }else {
            $responseMessage = sprintf(__('An error occurred while making the transaction. Please try again. (Error message: %s)'), $responseStatusMessage);
            $r = $this->orderFailed($order, $responseStatusMessage, $responseCode);
            if ($r) {
                $this->restoreQuote($order);
                $this->_messageManager->addError($responseMessage);
                return false;
            }
        }

        return false;
    }

    private function checkHostToHost($responseCode, $responseParams)
    {
        if ($responseCode == \Amazonpaymentservices\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK && isset($responseParams['3ds_url'])) {
            $response['url'] =  $responseParams['3ds_url'];
            $response['redirect'] =  true;
            return $response;
        }
        return true;
    }

    /**
     * Log the error on the disk
     */
    public function log($messages)
    {
        $debugMode = $this->getMainConfigData('debug');
        if (!$debugMode) {
            return;
        }
        $debugMsg = "=============== APS Module =============== \n".$messages."\n";
        $this->_logger->debug($debugMsg);
    }

    public function countryId()
    {
        return $this->_checkoutSession->getQuote()->getShippingAddress()->getCountryId();
    }

    public function pluginParams()
    {
        $magentoVersion = $this->_productMetadata;

        $pluginVersion = $this->_moduleResourceInterface;

        return [
            'app_programming'    => 'PHP',
            'app_framework'      => 'Magento2',
            'app_ver'            => 'v' . $magentoVersion->getVersion(),
            'app_plugin'         => 'Amazonpaymentservices_Fort',
            'app_plugin_version' => 'v' . $pluginVersion->getDbVersion('Amazonpaymentservices_Fort'),
        ];
    }

    public function captureAuthorize($responseParams)
    {
        $model = $this->_paymentCaptureFactory->create();
        $orderId = $responseParams['merchant_reference'];
        $orders = $this->_orderInterface->loadByIncrementId($orderId);
        if (empty($orders->getId())) {
            $orderId = $this->getApsParamsIncrementIdByReference('aps_valu_ref', $orderId);
            $orders = $this->_orderInterface->loadByIncrementId($orderId);
        }
        if ($responseParams['response_code'] == self::PAYMENT_METHOD_CAPTURE_STATUS) {
            $saveData['payment_type'] = 'capture';
        } elseif ($responseParams['response_code'] == self::PAYMENT_METHOD_VOID_STATUS) {
            $saveData['payment_type'] = 'void';

            $reason = __("Order is fully refund");
            $orders->setStatus($orders::STATE_CLOSED);
            $orders->setState($orders::STATE_CLOSED);
            $orders->save();
            $orders->addStatusToHistory($orders::STATE_CLOSED, $reason, false);
            $orders->save();
            $this->log('Order Void by APS. Response');
        }
        $amount = 0;
        if (isset($responseParams['amount'])) {
            $amount = $this->convertRevAmount($responseParams['amount'], $responseParams['currency']);
        }
        $saveData['order_number'] = $orderId;
        $saveData['amount'] = $amount;
        $saveData['added_date'] = date('Y-m-d H:i:s');
        $model->setData($saveData)->save();
        $logMsg = "WebHooks CaptureVoid Data (capture void) \n\n" . json_encode($saveData, 1);
        $this->log($logMsg);
        $logMsg = "WebHooks Response for payment method (capture void) \n\n" . json_encode($responseParams, 1);
        $this->log($logMsg);
    }

    public function refundAps($responseParams)
    {
        $amount = $this->convertRevAmount($responseParams['amount'], $responseParams['currency']);
        $orderId = $responseParams['merchant_reference'];
        $this->log('Refund Orderid:'.$orderId);
        $orders = $this->_orderInterface->loadByIncrementId($orderId);
        if (empty($orders->getId())) {
            $this->log('Refund Order empty');
            $orderId = $this->getApsParamsIncrementIdByReference('aps_valu_ref', $orderId);
            $this->log('Refund Orderid:'.$orderId);
            $orders = $this->_orderInterface->loadByIncrementId($orderId);
        }
        $amountRate = $orders->getBaseToOrderRate();
        $orderIncId = $orders->getId();
        $this->log('AmountRate:'.$amountRate);
        if (empty($amountRate)) {
            $amountRate = 1;
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderIncId)->create();
        $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
        $creditmemoRecords = $creditmemos->getItems();
        $creditMemoTotal = 0;
        $flag = 0;
        foreach ($creditmemos as $creditmemo) {
            $adjustment = $creditmemo->getBaseGrandTotal();
            $customerNote = $creditmemo->getCustomerNote();
            $customerNote = json_decode($customerNote, 1);
            $createdAt = $creditmemo->getCreatedAt();
            $todayDate = $this->_date->gmtDate();
            $differenceInSeconds = strtotime($todayDate) - strtotime($createdAt);

            if ($differenceInSeconds < 60 && ($amount/$amountRate) == $adjustment) {
                $this->log('Refund Already done for this order.'.json_encode($responseParams));
                $flag = 1;
            }
            $creditMemoTotal += $adjustment;
        }
        $this->log('Refund Flag:'.$flag);
        if ($flag == 0) {
            $this->creditMemoCalculation($amount, $amountRate, $responseParams, $orders);
        } else {
            $orderTotal = $orders->getGrandTotal()/$amountRate;
            $this->log('Order total:'.$orderTotal);

            $this->log('Credit Memo total:'.$creditMemoTotal);
            if ($orderTotal == $creditMemoTotal) {
                $this->log('Order Refund status');
                $reason = __("Order is fully refund");
                $orders->setStatus($orders::STATE_CLOSED);
                $orders->setState($orders::STATE_CLOSED);
                $orders->save();
                $orders->addStatusToHistory($orders::STATE_CLOSED, $reason, false);
                $orders->save();
            }
        }
    }

    private function creditMemoCalculation($amount, $amountRate, $responseParams, $orders)
    {
        $creditMemoData = [];
        $creditMemoData['do_offline'] = 0;
        $creditMemoData['shipping_amount'] = 0;
        $creditMemoData['adjustment_positive'] = $amount/$amountRate;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['comment_text'] = json_encode($responseParams);
        $creditMemoData['send_email'] = 1;

        $itemToCredit = [];
        foreach ($orders->getAllItems() as $item) {
            $itemToCredit[$item->getId()] = ['qty'=>0];
        }

        $creditMemoData['items'] = $itemToCredit;
        try {
            $orderIncId = $orders->getId();
            $this->creditmemoLoader->setOrderId($orderIncId); //pass order id
            $this->creditmemoLoader->setCreditmemo($creditMemoData);

            $creditmemo = $this->creditmemoLoader->load();
            $this->log(__('Refund Credit memo data:'.json_encode($creditMemoData)));
            $this->log(__('Refund:'.json_encode($creditmemo)));
            if ($creditmemo) {
                $this->log(__('Credit Memo loaded'));
                if (!$creditmemo->isValidGrandTotal()) {
                    $this->log(__('Refund: The credit memo\'s total must be positive.'));
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }

                if (!empty($creditMemoData['comment_text'])) {
                    $creditmemo->addComment(
                        $creditMemoData['comment_text'],
                        isset($creditMemoData['comment_customer_notify']),
                        isset($creditMemoData['is_visible_on_front'])
                    );

                    $creditmemo->setCustomerNote($creditMemoData['comment_text']);
                    $creditmemo->setCustomerNoteNotify(isset($creditMemoData['comment_customer_notify']));
                }

                $creditmemoManagement = $this->_objectManager->create(
                    \Magento\Sales\Api\CreditmemoManagementInterface::class
                );
                $creditmemo->getOrder()->setCustomerNoteNotify(!empty($creditMemoData['send_email']));
                $creditmemoManagement->refund($creditmemo, (bool)$creditMemoData['do_offline']);

                $orderTotal = $orders->getGrandTotal()/$amountRate;

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('order_id', $orderIncId)->create();
                $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
                $creditmemoRecords = $creditmemos->getItems();
                $creditMemoTotal1 = 0;
                $flag = 0;
                foreach ($creditmemos as $_creditmemo) {
                    $adjustment = $_creditmemo->getBaseGrandTotal();

                    $creditMemoTotal1 += $adjustment;
                }
                $this->log('Order total:'.$orderTotal);

                $this->log('Credit Memo total:'.$creditMemoTotal1);

                if ($orderTotal == $creditMemoTotal1) {
                    $this->log('Order Refund status');
                    $reason = __("Order is fully refund");
                    $orders->setStatus($orders::STATE_CLOSED);
                    $orders->setState($orders::STATE_CLOSED);
                    $orders->save();
                    $orders->addStatusToHistory($orders::STATE_CLOSED, $reason, false);
                    $orders->save();
                }

                if (!empty($creditMemoData['send_email'])) {
                    $this->creditmemoSender->send($creditmemo);
                }
                $this->log(__('You created the credit memo.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->log($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->log(__('We can\'t save the credit memo right now.').$e->getMessage());
        }
    }

    public function apsSubscriptionOrder($order, $status)
    {
        /**
         * ---START---
         * IF PRODUCT (ITEM) IS A SUBSCRIPTION ITEM
         * THEN BELOW CODE WILL SAVE THE ITEM TO SUBSCRIPTION TABLES
         */

        // is the Recurring Product feature enabled?
        // if it isn't then skip this part
        $isRecurringEnabled = (int)$this->getConfig('payment/aps_recurring/active') === 1;
        if (!$isRecurringEnabled) {
            return;
        }

        $connection = $this->_connection->getConnection();

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
        $apsSubEnabled = $connection->fetchRow($query);

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval');
        $apsSubInterval = $connection->fetchRow($query);

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval_count');
        $apsSubIntervalCount = $connection->fetchRow($query);

        foreach ($order->getAllItems() as $item) {

            /* @isSubscriptionProduct */
            $query = $connection->select()->from(['table'=>'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $item->getProductId());
            $prodApsSubEnabled = $connection->fetchRow($query);

            if (!empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {

                /* @Subscription Interval */
                $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubInterval['attribute_id'])->where('table.entity_id=?', $item->getProductId());
                $prodApsSubInterval = $connection->fetchRow($query);

                /* @Subscription Interval Count*/
                $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubIntervalCount['attribute_id'])->where('table.entity_id=?', $item->getProductId());
                $prodApsSubIntervalCount = $connection->fetchRow($query);

                $subscriptionStartDate = date('Y-m-d', strtotime('now'));
                $nextPaymentDate = date('Y-m-d', strtotime('+'.$prodApsSubIntervalCount['value'].' '.$prodApsSubInterval['value'], strtotime('now')));

                $this->apsSubscriptionDataSave($item, $order, $subscriptionStartDate, $nextPaymentDate, $status);
            }
        }
    }

    private function apsSubscriptionDataSave($item, $order, $subscriptionStartDate, $nextPaymentDate, $status)
    {
        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionsFactory')->create();

        $model->setProductId($item->getProductId());
        $model->setProductName($item->getName());
        $model->setProductSku($item->getSku());
        $model->setOrderId($order->getId());
        $model->setOrderIncrementId($order->getIncrementId());
        $model->setQty($item->getQtyInvoiced());
        $model->setCustomerId($order->getCustomerId());
        $model->setItemId($item->getItemId());
        $model->setSubscriptionStartDate($subscriptionStartDate);
        $model->setNextPaymentDate($nextPaymentDate);
        $model->setSubscriptionStatus($status);
        $model->setCreatedAt(date('Y-m-d H:i:s'));
        $model->setUpdatedAt(date('Y-m-d H:i:s'));

        $model->save();
        $apsSubscriptionId = $model->getId();

        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionordersFactory')->create();

        $model->setApsSubscriptionId($apsSubscriptionId);
        $model->setOrderId($order->getId());
        $model->setOrderIncrementId($order->getIncrementId());
        $model->setCustomerId($order->getCustomerId());
        $model->setItemId($item->getItemId());
        $model->setCreatedAt(date('Y-m-d H:i:s'));
        $model->setUpdatedAt(date('Y-m-d H:i:s'));
        $model->save();
    }

    public function apsSubscriptionOrderCron($newOrder, $subscriptionOrderId, $status, $order)
    {
        /**
         * ---START---
         * IF PRODUCT (ITEM) IS A SUBSCRIPTION ITEM
         * THEN BELOW CODE WILL SAVE THE ITEM TO SUBSCRIPTION TABLES
         */

        // is the Recurring Product feature enabled?
        // if it isn't then skip this part
        $isRecurringEnabled = (int)$this->getConfig('payment/aps_recurring/active') === 1;
        if (!$isRecurringEnabled) {
            return false;
        }

        try {
            $connection = $this->_connection->getConnection();

            $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
            $apsSubEnabled = $connection->fetchRow($query);
            $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval');
            $apsSubInterval = $connection->fetchRow($query);

            $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval_count');
            $apsSubIntervalCount = $connection->fetchRow($query);

            foreach ($newOrder->getAllItems() as $item) {
                $this->saveSubscriptionData($item, $apsSubEnabled, $apsSubInterval, $apsSubIntervalCount, $subscriptionOrderId, $status, $newOrder);
            }

            return true;
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('APS :: Failed to create child order.', true);
            $order->save();
            $this->cancelSubscription($subscriptionOrderId);
            $this->log("Cron Job failed for Order:".$order->getId());
            $this->log($e->getMessage());

            return false;
        }
    }

    private function saveSubscriptionData($item, $apsSubEnabled, $apsSubInterval, $apsSubIntervalCount, $subscriptionOrderId, $status, $newOrder)
    {
        /* @isSubscriptionProduct */
        $connection = $this->_connection->getConnection();
        $query = $connection->select()->from(['table'=>'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $item->getProductId());
        $prodApsSubEnabled = $connection->fetchRow($query);

        /* @Subscription Interval */
        $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubInterval['attribute_id'])->where('table.entity_id=?', $item->getProductId());
        $prodApsSubInterval = $connection->fetchRow($query);

        /* @Subscription Interval Count*/
        $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubIntervalCount['attribute_id'])->where('table.entity_id=?', $item->getProductId());
        $prodApsSubIntervalCount = $connection->fetchRow($query);

        $date_now = date('Y-m-d H:i:s', strtotime('now'));
        $nextPaymentDate = date('Y-m-d', strtotime('+'.$prodApsSubIntervalCount['value'].' '.$prodApsSubInterval['value'], strtotime('now')));

        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionsFactory')->create();

        $model->load($subscriptionOrderId);

        if ($status == 1) {
            $model->setNextPaymentDate($nextPaymentDate);
        } else {
            $model->setSubscriptionStatus($status);
        }
        $model->setUpdatedAt($date_now);
        $model->save();

        $this->log('Order Next_Payment_Date is updated in aps_subscriptions table.');

        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionordersFactory')->create();

        $model->setApsSubscriptionId($subscriptionOrderId);
        $model->setOrderId($newOrder->getId());
        $model->setOrderIncrementId($newOrder->getIncrementId());
        $model->setCustomerId($newOrder->getCustomerId());
        $model->setItemId($item->getItemId());
        $model->setCreatedAt(date('Y-m-d H:i:s'));
        $model->setUpdatedAt(date('Y-m-d H:i:s'));
        $model->save();

        $this->log('New Order Detail entry is inserted in aps_subscription_orders table.');
        $this->log('Subscription is taken now.');
    }

    public function apsSubscriptionPaymentApi(&$newOrder, $tokenName, $order, $remoteIp = '')
    {
        $responseParams = [];
        try {
            $paymentMethod = $newOrder->getPayment()->getMethod();
            $orderId = $newOrder->getRealOrderId();
            if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Stc::CODE) {
                $orderId = $this->getApsStcRefFromOrderParams($newOrder->getId(), null);

                $connection = $this->_connection->getConnection();
                $query = $connection->select()->from(['table'=>'aps_stc_token_order_relation'], ['token_name'])->where('table.order_increment_id=?', $orderId);
                $cardList = $connection->fetchAll($query);
                foreach ($cardList as $card) {
                    $tokenName = $card['token_name'];
                }
            }

            $language = $this->getLanguage();
            $baseCurrency = $this->getBaseCurrency();
            $orderCurrency = $newOrder->getOrderCurrency()->getCurrencyCode();
            $currency = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount = $this->convertFortAmount($newOrder, $currency);
            $remoteIp = !empty($newOrder->getRemoteIp()) ? $newOrder->getRemoteIp() : $remoteIp;
            $gatewayParams         = [
                'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
                'access_code'         => $this->getMainConfigData('access_code'),
                'command'             => \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE,
                'merchant_reference'  => $orderId,
                'amount'              => $this->convertFortAmount($newOrder, $currency),
                'currency'            => strtoupper($currency),
                'customer_ip'         => $remoteIp,
                'language'            => $language,
                'customer_email'      => trim($newOrder->getCustomerEmail()),
                'eci'                 => 'RECURRING',
                'token_name'          => $tokenName,
                'return_url'          => $this->getReturnUrl('amazonpaymentservicesfort/payment/responseOnline')
            ];
            if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Stc::CODE) {
                $gatewayParams['digital_wallet'] = "STCPAY";
            }
            if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Tabby::CODE) {
                $gatewayParams['payment_option'] = "TABBY";
            }
            $signature = $this->calculateSignature($gatewayParams, 'request');
            $gatewayParams['signature'] = $signature;
            $this->log('API PARAM: '.json_encode($gatewayParams));
            $gatewayUrl = $this->getGatewayUrl('notificationApi');
            $responseParams = $this->callApi($gatewayParams, $gatewayUrl);
            $payment = $invoice = '';
            if ($responseParams['response_code'] == '14000') {

                $payment = $newOrder->getPayment();
                $payment->setTransactionId($responseParams['fort_id'])->setIsTransactionClosed(0);
                $payment->setAdditionalInformation($responseParams['merchant_reference']);
                $payment->setAdditionalData(json_encode($responseParams));
                $payment->save();

                try {

                    $this->log('Gerenating Invoice: '.$newOrder->getId());
                    $this->log('OrderData:'.json_encode($newOrder->getData()));

                    $invoice = $this->invoiceService->prepareInvoice($newOrder);
                    if (!$invoice) {
                        $this->_helper->log('Invoice not generated');
                    } elseif (!$invoice->getTotalQty()) {
                        $this->_helper->log('Invoice not generated');
                    } else {
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $invoice->getOrder()->setCustomerNoteNotify(false);
                        $invoice->getOrder()->setIsInProcess(true);
                        $newOrder->addStatusHistoryComment('Automatically INVOICED', false);
                        $transactionSave = $this->transaction->create()->addObject($invoice)->addObject($invoice->getOrder());
                        $transactionSave->save();
                        $this->_invoiceSender->send($invoice);

                    }
                } catch (\Exception $e) {
                    $this->log("Failed in sending invoice:".$newOrder->getId());
                    $this->log($e);
                    return false;
                }

                try {
                    $this->sendOrderEmail($newOrder);
                    $this->log('Order Email Sent: '.$newOrder->getId());
                } catch (\Exception $e) {
                    $this->log("Failed in sending order mail:".$newOrder->getId());
                    return false;
                }
            } else {
                $payment = $newOrder->getPayment();
                $payment->setAdditionalInformation($responseParams['merchant_reference']);
                $payment->setAdditionalData(json_encode($responseParams));
                $payment->save();
            }
            return $responseParams;
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('APS :: Failed to create child order.', true);
            $order->save();
            $this->cancelSubscription($order->getId());
            $this->log("Cron Job API failed for Order:".$order->getId());
            $this->log($e->getMessage());
            return $responseParams;
        }
    }

    public function checkSubscriptionItemInCart()
    {
        // is the Recurring Product feature enabled?
        // if it isn't then skip this part
        // return true, no restriction regarding the cart items
        $isRecurringEnabled = (int)$this->getConfig('payment/aps_recurring/active') === 1;
        if (!$isRecurringEnabled) {
            return true;
        }

        /** This function is use in payment methods to remove methods while item is subscription */
        $items = $this->_cart->getQuote()->getAllItems();
        $connection = $this->_connection->getConnection();

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
        $apsSubEnabled = $connection->fetchRow($query);

        foreach ($items as $item) {
            $productEntityId = $item->getProductId();

            /** @isSubscriptionProduct */
            $query = $connection->select()->from(['table'=>'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $productEntityId);
            $prodApsSubEnabled = $connection->fetchRow($query);

            if (!empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {
                return false;
            }
        }
        return true;
    }

    public function fetchAllQuery($query)
    {
        $connection = $this->_connection->getConnection();
        //@codingStandardsIgnoreStart
        $queryResponse = $connection->fetchAll($query);
        //@codingStandardsIgnoreEnd
        return $queryResponse;
    }

    public function apsCookieUpdate()
    {
        $sessionName = $this->getSessionName();
        $time = time() + $this->getConfig('web/cookie/cookie_lifetime');
        $id = $this->getSessionId();
        $time = gmdate("D, d-M-Y H:i:s T", $time);
        //@codingStandardsIgnoreStart
        header('Set-Cookie: '.$sessionName.'=' . $id. '; expires='.$time.'; Path='.ini_get('session.cookie_path').'; SameSite=None; Secure=true; httponly='.ini_get('session.cookie_httponly').';domain='.ini_get('session.cookie_domain'));
        //@codingStandardsIgnoreEnd
    }

    public function getSessionId()
    {
        return $this->session->getSessionId();
    }

    public function getSessionName()
    {
        return $this->session->getName();
    }

    public function cancelSubscription($subscriptionOrderId)
    {
        $date_now = date('Y-m-d H:i:s', strtotime('now'));
        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionsFactory')->create();
        $model->load($subscriptionOrderId);
        $model->setSubscriptionStatus(0);
        $model->setUpdatedAt($date_now);
        $model->save();
    }

    public function getStcRequestParams($order, $integrationType)
    {
        $language = $this->getLanguage();
        $orderId = $order->getRealOrderId();

        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApsorderparamsFactory')->create();
        $model->setOrderId($order->getId());
        $model->setOrderIncrementId($orderId);
        $model->setApsStcRef($orderId);
        $model->setCreatedAt(date('Y-m-d H:i:s'));
        $model->setUpdatedAt(date('Y-m-d H:i:s'));
        $model->save();

        $gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'language'            => $language,
            'merchant_reference'  => $orderId,
        ];
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmount($order, $currency);
        $gatewayParams['currency']       = strtoupper($currency);
        $gatewayParams['amount']         = $amount;

        $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
        $gatewayParams['command'] = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
        $gatewayParams['digital_wallet'] = 'STCPAY';
        $gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/stcResponseOnline');
        $gatewayParams = array_merge($gatewayParams, $this->pluginParams());

        $signature = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl();
        $logMsg = "Request Params for payment method (STC) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);

        return ['url' => $gatewayUrl, 'params' => $gatewayParams];
    }

    public function stcPayRequestOtp($orderId, $mobileNumber)
    {
        $refId = 'MA'.round(microtime(true) * 1000);
        //$refId = $orderId;
        if ($this->getConfig('payment/aps_fort_stc/ref_id_as_order_id') == 1)
        {
            $refId = substr(uniqid($orderId.'APS'), 0, 40);
        }
        $language = $this->getLanguage();
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmountCart($currency);

        $data = [
            "amount" => $amount,
            "digital_wallet" => "STCPAY",
            "merchant_identifier" => $this->getMainConfigData('merchant_identifier'),
            "service_command" => "GENERATE_OTP",
            "access_code" => $this->getMainConfigData('access_code'),
            "merchant_reference" => $refId,
            "currency" => strtoupper($currency),
            "language" => $language,
            "phone_number" => $mobileNumber
        ];
        $this->_custmerSession->setCustomValue(['refId' => $refId]);
        $signature = $this->calculateSignature($data, 'request');
        $data['signature'] = $signature;
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log(json_encode($data));
        return $this->callApi($data, $gatewayUrl);
    }

    public function getStcPaymentRequestParams($order, $postData = [])
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $sessionData = $this->_custmerSession->getCustomValue();

        $orderId = $order->getRealOrderId();
        $language = $this->getLanguage();

        $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApsorderparamsFactory')->create();
        $model->setOrderId($order->getId());
        $model->setOrderIncrementId($orderId);
        $model->setApsStcRef($sessionData['refId']);
        $model->setCreatedAt(date('Y-m-d H:i:s'));
        $model->setUpdatedAt(date('Y-m-d H:i:s'));
        $model->save();

        $this->_custmerSession->setCustomValue(['refId' =>$sessionData['refId'],'orderId' => $orderId]);
        $this->_gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $sessionData['refId'],
            'language'            => $language,
            'order_description'   => $orderId,
            'merchant_extra'      => $orderId
        ];

        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmount($order, $currency);
        $this->_gatewayParams['currency']       = strtoupper($currency);
        $this->_gatewayParams['amount']         = $amount;
        $this->_gatewayParams['customer_email'] = trim($order->getCustomerEmail());
        $this->_gatewayParams['command']        = 'PURCHASE';
        $this->_gatewayParams['digital_wallet'] = 'STCPAY';
        $this->_gatewayParams['otp']   = $postData['otp'];
        $ip = $this->getVisitorIp();
        $this->_gatewayParams['customer_ip']    = $ip;

        $this->_gatewayParams['return_url']     = $this->getReturnUrl('amazonpaymentservicesfort/payment/stcResponse');

        if ($this->getConfig('payment/aps_fort_stc/token') == 1){
            $this->_gatewayParams['remember_me'] = 'YES';
        }

        $this->_gatewayParams['phone_number']   = $postData['mobileNumber'];
        $this->_gatewayParams = array_merge($this->_gatewayParams, $this->pluginParams());
        $signature = $this->calculateSignature($this->_gatewayParams, 'request');
        $this->_gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        $this->log("Request Data STC:".json_encode($this->_gatewayParams));
        $responseParams = $this->callApi($this->_gatewayParams, $gatewayUrl);

        $integrationType = self::INTEGRATION_TYPE_REDIRECTION;
        $success = $this->handleFortResponse($responseParams, 'online', $integrationType);

        $returnUrl = '';
        if ($success) {
            $this->stcSaveCard($order, $responseParams);
            $returnUrl = $this->getUrl('checkout/onepage/success');
        } else {
            if ($order->getState() == $order::STATE_PROCESSING) {
                $this->stcSaveCard($order, $responseParams);
                $returnUrl = $this->getUrl('checkout/onepage/success');
            } else {
                if (isset($responseParams['response_message'])) {
                    $this->_messageManager->addError($responseParams['response_message']);
                }
                $returnUrl = $this->getUrl('checkout/cart');

                $orderAfterPayment = $this->getMainConfigData('orderafterpayment');
                if ($orderAfterPayment === OrderOptions::DELETE_ORDER && !$this->isOrderResponseOnHold($responseParams['response_code'] ?? '')) {
                    $this->deleteOrder($order);
                }

            }
        }
        return ['url' => $returnUrl];
    }

    private function stcSaveCard($order, $responseParams)
    {
        if ($this->getConfig('payment/aps_fort_stc/token') == 1) {
            $connection = $this->_connection->getConnection();
            $query = $connection->select()->from(['table'=>'aps_stc_relation'], ['id'])->where('table.token_name=?', $responseParams['token_name']);
            $stcTokenData = $connection->fetchRow($query);
            if (empty($stcTokenData)) {
                $connection->insert(
                    $connection->getTableName('aps_stc_relation'),
                    [
                        'customer_id' => $order->getCustomerId(),
                        'order_increment_id' => $order->getIncrementId(),
                        'token_name' => $responseParams['token_name'],
                        'phone_number' => $responseParams["phone_number"],
                        'added_date' => date('Y-m-d H:i:s'),
                    ]
                );
            }
            $connection->insert(
                $connection->getTableName('aps_stc_token_order_relation'),
                [
                    'order_increment_id' => $order->getIncrementId(),
                    'token_name' => $responseParams['token_name']
                ]
            );
        }
    }
    public function getTabbyRequestParams($order, $integrationType)
    {
        $language = $this->getLanguage();
        $orderId = $order->getRealOrderId();

        $gatewayParams = [
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'language'            => $language,
            'merchant_reference'  => $orderId,
        ];
        $baseCurrency                    = $this->getBaseCurrency();
        $orderCurrency                   = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount                          = $this->convertFortAmount($order, $currency);
        $gatewayParams['currency']       = strtoupper($currency);
        $gatewayParams['amount']         = $amount;

        $gatewayParams['customer_email'] = trim($order->getCustomerEmail());
        $gatewayParams['command'] = \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::PURCHASE;
        $gatewayParams['payment_option'] = 'TABBY';

        $ip = $this->getVisitorIp();
        $gatewayParams['customer_ip'] = $ip;
        $gatewayParams['order_description'] = $orderId;
        $gatewayParams['phone_number'] = $order->getBillingAddress()->getTelephone();

        $gatewayParams['return_url']      = $this->getReturnUrl('amazonpaymentservicesfort/payment/tabbyResponseOnline');
        $gatewayParams = array_merge($gatewayParams, $this->pluginParams());

        $signature = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl();
        $logMsg = "Request Params for payment method (TABBY) \n\n" . json_encode($gatewayParams, 1);
        $this->log($logMsg);

        return ['url' => $gatewayUrl, 'params' => $gatewayParams];
    }

    /**
     * @param $order
     *
     * @return bool
     */
    public function canCancelOrder($order)
    {
        return in_array($order->getState(), [
            $order::STATE_NEW,
            $order::STATE_PENDING_PAYMENT,
            $order::STATE_HOLDED,
            $order::STATE_PAYMENT_REVIEW,
        ]);
    }
    /**
     * Creates invoice and sends invoice email
     *
     * @param $order
     * @param $response
     *
     * @return void
     */
    public function handleSendingInvoice(&$order, $response): void
    {
        $invoice = $this->createInvoice($order, $response);
        $this->sendInvoiceEmail($invoice);
    }

    /**
     * Return whether the response code from APS refers
     * to a case when order payment is considered ON HOLD
     *
     * @param $responseCode
     *
     * @return bool
     */
    public function isOrderResponseOnHold($responseCode): bool
    {
        return in_array($responseCode, self::APS_ONHOLD_RESPONSE_CODES, true);
    }

    /**
     * Return the STC order subscription reference
     *
     * @param $orderId
     * @param $orderIncrementId
     *
     * @return mixed|null
     */
    public function getApsStcRefFromOrderParams($orderId, $orderIncrementId)
    {
        $connection = $this->_connection->getConnection();

        if ($orderId) {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_id=?', $orderId);
            $orderParams = $this->fetchAllQuery($query);

            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.entity_id=?', $orderId);
                $orderParams = $this->fetchAllQuery($query);
            }
        } else {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_increment_id=?', $orderIncrementId);
            $orderParams = $this->fetchAllQuery($query);

            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.increment_id=?', $orderIncrementId);
                $orderParams = $this->fetchAllQuery($query);
            }
        }
        foreach ($orderParams as $orderParam) {
            if ($orderParam['aps_stc_ref']) {
                return $orderParam['aps_stc_ref'];
            }
        }

        return null;
    }

    /**
     * Return the TABBY order subscription reference
     *
     * @param $orderId
     * @param $orderIncrementId
     *
     * @return mixed|null
     */
    public function getApsTabbyRefFromOrderParams($orderId, $orderIncrementId)
    {
        $connection = $this->_connection->getConnection();

        if ($orderId) {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_id=?', $orderId);
            $orderParams = $this->fetchAllQuery($query);
            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.entity_id=?', $orderId);
                $orderParams = $this->fetchAllQuery($query);
            }
        } else {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_increment_id=?', $orderIncrementId);
            $orderParams = $this->fetchAllQuery($query);
            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.increment_id=?', $orderIncrementId);
                $orderParams = $this->fetchAllQuery($query);
            }
        }

        foreach ($orderParams as $orderParam) {
            if ($orderParam['aps_tabby_ref']) {
                return $orderParam['aps_tabby_ref'];
            }
        }

        return null;
    }

    /**
     * Return the VALU order subscription reference
     *
     * @param $orderId
     * @param $orderIncrementId
     *
     * @return mixed|null
     */
    public function getApsValuRefFromOrderParams($orderId, $orderIncrementId)
    {
        $connection = $this->_connection->getConnection();

        if ($orderId) {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_id=?', $orderId);
            $orderParams = $this->fetchAllQuery($query);
            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.entity_id=?', $orderId);
                $orderParams = $this->fetchAllQuery($query);
            }
        } else {
            $query = $connection->select()->from(['table' => 'aps_order_params'])->where('table.order_increment_id=?', $orderId);
            $orderParams = $this->fetchAllQuery($query);
            if (empty($orderParams)) {
                // backwards compatibility
                // in case the value is not inside the new table
                // search it inside the sales_order table

                $query = $connection->select()->from(['table' => 'sales_order'])->where('table.increment_id=?', $orderId);
                $orderParams = $this->fetchAllQuery($query);
            }
        }
        foreach ($orderParams as $orderParam) {
            if ($orderParam['aps_valu_ref']) {
                return $orderParam['aps_valu_ref'];
            }
        }

        return null;
    }

    /**
     * Return the APS Params field from the aps_order_params table
     *
     * @param $orderId
     * @param $orderIncrementId
     *
     * @return array|mixed
     */
    public function getApsParamsFromOrderParams($orderId, $orderIncrementId)
    {
        $connection = $this->_connection->getConnection();

        $query = $connection->select()->from(['table'=>'aps_order_params'])->where('table.order_id=?', $orderId);
        $orderParams = $this->fetchAllQuery($query);
        if (empty($orderParams)) {
            // backwards compatibility
            // in case the value is not inside the new table
            // search it inside the sales_order table
            $orderParams = $this->_salesCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('increment_id', ['eq'=>$orderIncrementId]);
        }

        foreach ($orderParams as $collection) {
            $apsParams = $collection['aps_params'] ?? [];
            if (!empty($apsParams)) {
                return json_decode($apsParams, 1);
            }
        }

        return [];
    }
}
