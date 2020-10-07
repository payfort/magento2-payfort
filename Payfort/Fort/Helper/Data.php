<?php
namespace Payfort\Fort\Helper;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;

/**
 * Payment module base helper
 */
class Data extends \Magento\Payment\Helper\Data
{
    protected $_code;
    private $_gatewayHost        = 'https://checkout.payfort.com/';
    private $_gatewaySandboxHost = 'https://sbcheckout.payfort.com/';
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    
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
        
    const PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION = 'redirection';
    const PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE = 'merchantPage';
    const PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2 = 'merchantPage2';
    const PAYFORT_FORT_PAYMENT_METHOD_CC = 'payfort_fort_cc';
    const PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS = 'payfort_fort_installments';
    const PAYFORT_FORT_PAYMENT_METHOD_NAPS = 'payfort_fort_naps';
    const PAYFORT_FORT_PAYMENT_METHOD_SADAD = 'payfort_fort_sadad';
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
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer,
        \Magento\CatalogInventory\Observer\ProductQty $productQty,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($context,$layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_storeManager = $storeManager;
        $this->session = $session;
        $this->_logger = $context->getLogger();
        $this->_localeResolver = $localeResolver;
        $this->orderManagement = $orderManagement;
        $this->_objectManager = $objectManager;
        $this->_stockManagement = $stockManagement;
        $this->_stockIndexerProcessor = $stockIndexerProcessor;
        $this->_priceIndexer = $priceIndexer;
        $this->_productQty = $productQty;
        $this->_productMetadata = $productMetadata;
    }
    
    public function setMethodCode($code) {
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
            ('payment/payfort_fort/'.$config_field),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function isPayfortPaymentMethod($paymentMethod) {
        if (preg_match('#^payfort\_fort\_#', $paymentMethod)) {
            return true;
        }
        return false;
    }
    
    public function getPaymentRequestParams($order, $integrationType = self::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION)
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getRealOrderId();        
        $language = $this->getLanguage();

        $gatewayParams = array(
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $orderId,
            'language'            => $language,
        );
        if ($integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION) {
            $baseCurrency                    = $this->getBaseCurrency();
            $orderCurrency                   = $order->getOrderCurrency()->getCurrencyCode();
            $currency                        = $this->getFortCurrency($baseCurrency, $orderCurrency);
            $amount                          = $this->convertFortAmount($order, $currency);
            $gatewayParams['currency']       = strtoupper($currency);
            $gatewayParams['amount']         = $amount;
            $gatewayParams['customer_email'] = trim( $order->getCustomerEmail() );
            $gatewayParams['command']        = $this->getMainConfigData('command');
            $gatewayParams['return_url']     = $this->getReturnUrl('payfortfort/payment/responseOnline');
            if ($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_SADAD) {
                $gatewayParams['payment_option'] = 'SADAD';
            }
            elseif ($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_NAPS) {
                $gatewayParams['payment_option']    = 'NAPS';
                $gatewayParams['order_description'] = $orderId;
            }
            elseif ($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS) {
                $gatewayParams['installments'] = 'STANDALONE';
                $gatewayParams['command']      = 'PURCHASE';
            }
        }
        elseif ($integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE || $integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
            $gatewayParams['service_command'] = 'TOKENIZATION';
            $gatewayParams['return_url']      = $this->getReturnUrl('payfortfort/payment/merchantPageResponse');
            if($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS && $integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE){
                $baseCurrency                           = $this->getBaseCurrency();
                $orderCurrency                          = $order->getOrderCurrency()->getCurrencyCode();
                $currency                               = $this->getFortCurrency($baseCurrency, $orderCurrency);
                $gatewayParams['currency']              = strtoupper($currency);
                $gatewayParams['installments']          = 'STANDALONE';
                $gatewayParams['amount']                = $this->convertFortAmount($order, $currency);                
            }
        }
        $signature                  = $this->calculateSignature($gatewayParams, 'request');
        $gatewayParams['signature'] = $signature;

        $gatewayUrl = $this->getGatewayUrl();
        
        $this->log("Payfort Request Params for payment method ($paymentMethod) \n\n" . print_r($gatewayParams, 1));
        
        return array('url' => $gatewayUrl, 'params' => $gatewayParams);
    }
    
    public function getPaymentPageRedirectData($order) {
        
        return $this->getPaymentRequestParams($order, self::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION);
    }
    
    public function getOrderCustomerName($order) {
        $customerName = '';
        if( $order->getCustomerId() === null ){
            $customerName = $order->getBillingAddress()->getFirstname(). ' ' . $order->getBillingAddress()->getLastname();
        }
        else{
            $customerName =  $order->getCustomerName();
        }
        return trim($customerName);
    }
    
    public function isMerchantPageMethod($order) {
        $paymentMethod = $order->getPayment()->getMethod();
        if($paymentMethod == \Payfort\Fort\Model\Method\Cc::CODE && $this->getConfig('payment/payfort_fort_cc/integration_type') == \Payfort\Fort\Model\Config\Source\Integrationtypeoptions::MERCHANT_PAGE) {
            return true;
        }
        elseif($paymentMethod == \Payfort\Fort\Model\Method\Installments::CODE && $this->getConfig('payment/payfort_fort_installments/integration_type') == \Payfort\Fort\Model\Config\Source\Integrationtypeoptions::MERCHANT_PAGE) {
            return true;
        }
        return false;
    }
    
    public function isMerchantPageMethod2($order) {
        $paymentMethod = $order->getPayment()->getMethod();
        if($paymentMethod == \Payfort\Fort\Model\Method\Cc::CODE && $this->getConfig('payment/payfort_fort_cc/integration_type') == \Payfort\Fort\Model\Config\Source\Integrationtypeoptions::MERCHANT_PAGE2) {
            return true;
        }
        return false;
    }
    
    public function merchantPageNotifyFort($fortParams, $order) {
        //send host to host
        $orderId = $order->getRealOrderId();

        $return_url = $this->getReturnUrl('payfortfort/payment/responseOnline');

        $ip = $this->getVisitorIp();
        $baseCurrency  = $this->getBaseCurrency();
        $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        $currency      = $this->getFortCurrency($baseCurrency, $orderCurrency);
        $amount        = $this->convertFortAmount($order, $currency);
        $language      = $this->getLanguage();
        $postData = array(
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => $this->getMainConfigData('command'),
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'customer_ip'           => $ip,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'customer_email'        => trim( $order->getCustomerEmail() ),
            'token_name'            => $fortParams['token_name'],
            'language'              => $language,
            'return_url'            => $return_url,
        );
        
        $paymentMethod = $order->getPayment()->getMethod();
        
        if($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS) {
            $postData['installments'] = 'YES';
            $postData['plan_code']    = $fortParams['plan_code'];
            $postData['issuer_code']  = $fortParams['issuer_code'];
            $postData['command']      = 'PURCHASE';
        }
        
        $customer_name = $this->getOrderCustomerName($order);
        if(!empty($customer_name)) {
            $postData['customer_name'] = $customer_name;
        }
        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;
       
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        
        $this->log("Merchant Page Notify Api Request Params for payment method ($paymentMethod) : " . print_r($postData, 1));
        
        $response = $this->callApi($postData, $gatewayUrl);

        $debugMsg = "Fort Merchant Page Notifiaction Response Parameters for payment method ($paymentMethod)"."\n".print_r($response, true);
        $this->log($debugMsg);
        
        return $response;
    }

    public function callApi($postData, $gatewayUrl)
    {
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json;charset=UTF-8',
                //'Accept: application/json, application/*+json',
                //'Connection:keep-alive'
        ));
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects		
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
        //curl_setopt($ch, CURLOPT_TIMEOUT, Yii::app()->params['apiCallTimeout']); // timeout in seconds
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);

        curl_close($ch);

        $array_result = json_decode($response, true);

        if (!$response || empty($array_result)) {
            return false;
        }
        return $array_result;
    }
    
    /** @return string */
    function getVisitorIp() {
            /** @var \Magento\Framework\ObjectManagerInterface $om */
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $a */
            $a = $om->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
            return $a->getRemoteAddress();
    }

    /**
     * calculate fort signature
     * @param array $arr_data
     * @param sting $sign_type request or response
     * @return string fort signature
     */
    public function calculateSignature($arr_data, $sign_type = 'request')
    {
        $sha_in_pass_phrase  = $this->getMainConfigData('sha_in_pass_phrase');
        $sha_out_pass_phrase = $this->getMainConfigData('sha_out_pass_phrase');
        $sha_type = $this->getMainConfigData('sha_type');
        $sha_type = str_replace('-', '', $sha_type);
        
        $shaString = '';

        ksort($arr_data);
        foreach ($arr_data as $k => $v) {
            $shaString .= "$k=$v";
        }

        if ($sign_type == 'request') {
            $shaString = $sha_in_pass_phrase . $shaString . $sha_in_pass_phrase;
        }
        else {
            $shaString = $sha_out_pass_phrase . $shaString . $sha_out_pass_phrase;
        }
        $signature = hash($sha_type, $shaString);

        return $signature;
    }
    
    /**
     * Convert Amount with dicemal points
     * @param object $order
     * @param string  $currencyCode
     * @return decimal
     */
    public function convertFortAmount($order, $currencyCode)
    {
        $gateway_currency = $this->getGatewayCurrency();
        $new_amount     = 0;
        
        if($gateway_currency == 'front') {
            $amount = $order->getGrandTotal();
        }
        else {
            $amount = $order->getBaseGrandTotal();
        }
        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = round($amount, $decimal_points);
        $new_amount     = $new_amount * (pow(10, $decimal_points));
        return $new_amount;
    }
    
    /**
     * 
     * @param string $currency
     * @param integer 
     */
    public function getCurrencyDecimalPoint($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }
    public function getGatewayCurrency()
    {
        $gatewayCurrency = $this->getMainConfigData('gateway_currency');
        if(empty($gatewayCurrency)) {
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
    
    public function getGatewayUrl($type='redirection') {
        $testMode = $this->getMainConfigData('sandbox_mode');
        if($type == 'notificationApi') {
            $gatewayUrl = $testMode ?  'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' :  'https://paymentservices.payfort.com/FortAPI/paymentApi';
        }
        else{
            $gatewayUrl = $testMode ? $this->_gatewaySandboxHost.'FortAPI/paymentPage' : $this->_gatewayHost.'FortAPI/paymentPage';
        }
        
        return $gatewayUrl;
    }
    
    public function getReturnUrl($path) {
        return $this->_storeManager->getStore()->getBaseUrl().$path;
        //return $this->getUrl($path);
    }
    
    public function getLanguage() {
        $language = $this->getMainConfigData('language');
        if ($language == \Payfort\Fort\Model\Config\Source\Languageoptions::STORE) {
            $language = $this->_localeResolver->getLocale();
        }
        if(substr($language, 0, 2) == 'ar') {
            $language = 'ar';
        }
        else{
            $language = 'en';
        }
        return $language;
    }
    
    /**
     * Restores quote
     *
     * @return bool
     */
    public function restoreQuote()
    {
        $result = $this->session->restoreQuote();
        // Versions 2.2.4 onwards need an explicit action to return items.
        if ($result && $this->isReturnItemsToInventoryRequired()) {
            $this->returnItemsToInventory();
        }

        return $result;
    }

    /**
     * Checks if version requires restore quote fix.
     *
     * @return bool
     */
    private function isReturnItemsToInventoryRequired()
    {
        $version = $this->getMagentoVersion();
        return version_compare($version, "2.2.4", ">=");
    }

    /**
     * Returns items to inventory.
     *
     */
    private function returnItemsToInventory()
    {
        // Code from \Magento\CatalogInventory\Observer\RevertQuoteInventoryObserver
        $quote = $this->session->getQuote();
        $items = $this->_productQty->getProductQty($quote->getAllItems());
        $revertedItems = $this->_stockManagement->revertProductsSale($items, $quote->getStore()->getWebsiteId());

        // If the Magento 2 server has multi source inventory enabled, 
        // the revertProductsSale method is intercepted with new logic that returns a boolean.
        // In such case, no further action is necessary.
        if (gettype($revertedItems) === "boolean") {
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
    public function getMagentoVersion() {
        return $this->_productMetadata->getVersion();
    }
    
    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->session->getLastRealOrder();
        if(!empty($comment)) {
            $comment = 'Payfort_Fort :: ' . $comment;
        }
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }
    
    /**
     * Cancel order with specified comment message
     *
     * @return Mixed
     */
    public function cancelOrder($order, $comment)
    {
        $gotoSection = false;
        if(!empty($comment)) {
            $comment = 'Payfort_Fort :: ' . $comment;
        }
        if ($order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            /*if ($this->restoreQuote()) {
                //Redirect to payment step
                $gotoSection = 'paymentMethod';
            }*/
            $gotoSection = true;
        }
        return $gotoSection;
    }
    
    public function orderFailed($order, $reason) {
        if ($order->getState() != $this->getMainConfigData('order_status_on_fail')) {
            $order->setStatus($this->getMainConfigData('order_status_on_fail'));
            $order->setState($this->getMainConfigData('order_status_on_fail'));
            $order->save();
            //$customerNotified = $this->sendOrderEmail($order);
            $order->addStatusToHistory( $this->getMainConfigData('order_status_on_fail') , $reason, false );
            $order->save();
            return true;
        }
        return false;
    }
    
    public function processOrder($order, $responseParams, $responseMode = 'online') {
     
        if ($responseMode == 'offline' && $order->getState() != $order::STATE_PROCESSING) {
            
            $payment = $order->getPayment();
            $payment->setTransactionId($responseParams['fort_id'])->setIsTransactionClosed(1);
            $payment->save();
            
            if($this->getMainConfigData('command') == \Payfort\Fort\Model\Config\Source\Commandoptions::AUTHORIZATION) {
                $payment->authorize(true, $order->getGrandTotal());
            }
            //else{ //purchase => Capture
                
            //}
            
            $invoice = $this->createInvoice($order, $responseParams);
            $invoice->capture();
            
            //$order->setExtOrderId($orderNumber);
            $order->setState($order::STATE_PROCESSING)->save();
            $order->setStatus($order::STATE_PROCESSING)->save();
            
            //send order confirmation
            $emailSender = $this->_objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $emailSender->send($order);

            //$customerNotified = $this->sendOrderEmail($order);
            $order->addStatusToHistory( $order::STATE_PROCESSING , 'Payfort_Fort :: Order has been paid.', true );
            $order->save();
            
            $this->sendInvoiceEmail($invoice);
            return true;
        }
        return false;
    }
    
    public function sendOrderEmail($order) {
        $result = true;
        try{
            if($order->getState() != $order::STATE_PROCESSING) {
                $orderCommentSender = $this->_objectManager
                    ->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
                $orderCommentSender->send($order, true, '');
            }
            else{
                $this->orderManagement->notify($order->getEntityId());
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_logger->critical($e);
        }
        
        return $result;
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice
     */
    private function createInvoice(&$order, $responseParams)
    {
            //$payment = $this->getContext()->getTransaction()->getOrderPayment();
            if (!$order->hasInvoices()) {
                    $invoice = $order->prepareInvoice();
                    $invoice->register();
                    $invoice->setTransactionId($responseParams['fort_id']);
                    $order->addRelatedObject($invoice);
                    return $invoice;
            }
    }

    private function sendInvoiceEmail(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoiceSender = $this->_objectManager
                    ->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
        $invoiceSender->send($invoice);
            
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
    protected function getOrderById($order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->get('Magento\Sales\Model\Order');
        $order_info = $order->loadByIncrementId($order_id);
        return $order_info;
    }
    
    /**
     * 
     * @param array  $fortParams
     * @param string $responseMode (online, offline)
     * @retrun boolean
     */
    public function handleFortResponse($fortParams = array(), $responseMode = 'online', $integrationType = self::PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION, $responseSource = '')
    {
        try {
            $responseParams  = $fortParams;
            $success         = false;
            $responseMessage = __('You have canceled the payment, please try again.');

            if (empty($responseParams)) {
                
                $this->log('Invalid fort response parameters (' . $responseMode . ')');
                throw new \Exception($responseMessage);
            }

            if (!isset($responseParams['merchant_reference']) || empty($responseParams['merchant_reference'])) {
                $this->log("Invalid fort response parameters. merchant_reference not found ($responseMode) \n\n" . print_r($responseParams, 1));
                throw new \Exception($responseMessage);
            }

            $orderId = $responseParams['merchant_reference'];
            $order = $this->getOrderById($orderId);

            $paymentMethod = $order->getPayment()->getMethod();
            $this->log("Fort response parameters ($responseMode) for payment method ($paymentMethod) \n\n" . print_r($responseParams, 1));

            $notIncludedParams = array('signature', 'payfort_fort', 'integration_type');

            $responseType          = $responseParams['response_message'];
            $signature             = $responseParams['signature'];
            $responseOrderId       = $responseParams['merchant_reference'];
            $responseStatus        = isset($responseParams['status']) ? $responseParams['status'] : '';
            $responseCode          = isset($responseParams['response_code']) ? $responseParams['response_code'] : '';
            $responseStatusMessage = $responseType;

            $responseGatewayParams = $responseParams;
            foreach ($responseGatewayParams as $k => $v) {
                if (in_array($k, $notIncludedParams)) {
                    unset($responseGatewayParams[$k]);
                }
            }
            $responseSignature = $this->calculateSignature($responseGatewayParams, 'response');
            // check the signature
            if (strtolower($responseSignature) !== strtolower($signature)) {
                $responseMessage = __('Invalid response signature.');
                $this->log(sprintf('Invalid Signature. Calculated Signature: %1s, Response Signature: %2s', $signature, $responseSignature));
                // There is a problem in the response we got
                if ($responseMode == 'offline') {
                    $r = $this->orderFailed($order, 'Invalid Signature.');
                    if ($r) {
                        throw new \Exception($responseMessage);
                    }
                }
                else {
                    throw new \Exception($responseMessage);
                }
            }
            if (empty($responseCode)) {
                //get order status
                $orderStaus = $order->getState();
                if ($orderStaus == $order::STATE_PROCESSING) {
                    $responseCode   = '00000';
                    $responseStatus = '02';
                }
                else {
                    $responseCode   = 'failed';
                    $responseStatus = '10';
                }
            }

            if ($responseSource == 'h2h') {
                if ($responseCode == \Payfort\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK && isset($responseParams['3ds_url'])) {
                    if($integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
                        echo '<script>window.top.location.href = "'.$responseParams['3ds_url'].'"</script>';
                        exit;
                    }
                    else{
                        header('location:' . $responseParams['3ds_url']);
                        exit;
                    }
                }
            }
            if (substr($responseCode, 2) != '000') {
                if ($responseCode == \Payfort\Fort\Model\Payment::PAYMENT_STATUS_CANCELED) {
                    $responseMessage = __('You have canceled the payment, please try again.');
                    if ($responseMode == 'offline') {
                        $r = $this->cancelOrder($order, 'Payment Cancelled');
                        if ($r) {
                            throw new \Exception($responseMessage);
                        }
                    }
                    else {
                        throw new \Exception($responseMessage);
                    }
                }
                $responseMessage = sprintf(__('An error occurred while making the transaction. Please try again. (Error message: %s)'), $responseStatusMessage);
                if ($responseMode == 'offline') {
                    $r = $this->orderFailed($order, $responseStatusMessage);
                    if ($r) {
                        throw new \Exception($responseMessage);
                    }
                }
                else {
                    throw new \Exception($responseMessage);
                }
            }
            if (substr($responseCode, 2) == '000') {
                if ($responseMode == 'online' && $responseSource != 'h2h' && (($paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_CC || $paymentMethod == self::PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS) && ($integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE || $integrationType == self::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2))) {
                    $host2HostParams = $this->merchantPageNotifyFort($responseParams, $order);
                    return $this->handleFortResponse($host2HostParams, 'online', $integrationType, 'h2h');
                }
                else { //success order
                    $this->processOrder($order, $responseParams, $responseMode);
                }
            }
            else {
                $responseMessage = sprintf(__('An error occurred while making the transaction. Please try again. (Error message: %s)'), __('Response Unknown'));
                if ($responseMode == 'offline') {
                    $r = $this->orderFailed($order, 'Unknown Response');
                    if ($r) {
                        throw new \Exception($responseMessage);
                    }
                }
                else {
                    throw new \Exception($responseMessage);
                }
            }
        } catch (\Exception $e) {
            $this->restoreQuote();
            $messageManager = $this->_objectManager->get('Magento\Framework\Message\Manager');
            $messageManager->addError( $e->getMessage() );
            return false;
        }
        return true;
    }
    
    /**
     * Log the error on the disk
     */
    public function log($messages, $forceLog = false) {
        $debugMode = $this->getMainConfigData('debug');
        if(!$debugMode && !$forceLog) {
            return;
        }
        $debugMsg = "=============== Payfort_Fort Module =============== \n".$messages."\n";
        $this->_logger->debug($debugMsg);
    }
}
