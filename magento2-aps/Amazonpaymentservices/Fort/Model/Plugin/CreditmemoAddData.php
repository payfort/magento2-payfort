<?php
/**
 * Amazonpaymentservices Payment Apple Model
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Model\Plugin;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Amazonpaymentservices Payment Apple Model
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class CreditmemoAddData
{
    /**
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory
     */
    protected $_paymentCaptureFactory;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_order;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    public function __construct(
        \Amazonpaymentservices\Fort\Helper\Data $apsHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CreditmemoRepositoryInterface $creditmemoRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory $paymentCaptureFactory,
        \Magento\Sales\Model\OrderRepository $order,
        \Magento\Tax\Model\Config $configProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_helper = $apsHelper;
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_paymentCaptureFactory = $paymentCaptureFactory;
        $this->_order = $order;
        $this->configProvider = $configProvider;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    public function beforeSave(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $entity
    ) {
        $postParams = $this->_request->getParams();
        $order = $this->_order->get($postParams['order_id']);

        $paymentMethod = $order->getPayment()->getMethod();
        if (in_array($paymentMethod, \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD)) {
            if (\Amazonpaymentservices\Fort\Model\Method\Knet::CODE == $paymentMethod) {
                $this->_helper->log("\n\n Payment Method (".$paymentMethod.") not allow for refund for order id ".$postParams['order_id']." \n\n");
                throw new \Exception('Payment Method not allow for refund.');
            }

            $orderId = $order->getId();
            $orderTotal = $order->getGrandTotal();
            $orderIncrementId = $order->getIncrementId();

            $payment = $order->getPayment();
            $paymentData = $payment->getAdditionalData();
            $orderAdditionalData = json_decode($paymentData, true);

            $amount = $this->getItemTotals($order, $postParams);
            
            $amountRate = $order->getBaseToOrderRate();
            if (empty($amountRate)) {
                $amountRate = 1;
            }

            $shipAmount = isset($postParams['creditmemo']['shipping_amount']) ? ((float)$postParams['creditmemo']['shipping_amount'] * $amountRate) : 0;
            $adjustAmount = isset($postParams['creditmemo']['adjustment_positive']) ? ((float)$postParams['creditmemo']['adjustment_positive'] * $amountRate) : 0;
            $adjustNegative = isset($postParams['creditmemo']['adjustment_negative']) ? ((float)$postParams['creditmemo']['adjustment_negative'] * $amountRate) : 0;

            $taxAmount = 0;
            if($shipAmount > 0 && !$this->configProvider->shippingPriceIncludesTax()) {
                $shippingTax = $order->getShippingInclTax() - $order->getShippingAmount();
                if($shippingTax > 0) {
                    $shippingTaxPer =  $shippingTax/$order->getShippingAmount()*100;
                    $taxAmount = $shipAmount*$shippingTaxPer/100;
                }
            }
            $this->_helper->log("Shipping Tax amount: ".$taxAmount);

            if($amount > 0) {
                $orderTax = $order->getSubtotalInclTax() - $order->getSubtotal();
                if($orderTax > 0) {
                    $orderTaxPer =  $orderTax/$order->getSubtotal()*100;
                    $taxAmount  += $amount*$orderTaxPer/100;
                }
            }
            $this->_helper->log("Creditmemo with Shipping Tax amount : ".$taxAmount);

            $amount += $shipAmount + $adjustAmount - $adjustNegative + $taxAmount;

            if (( \Amazonpaymentservices\Fort\Model\Method\Naps::CODE == $paymentMethod) && $amount != $orderTotal) {
                $this->_helper->log("\n\n Partial Refund is not allowed for this payment method(".$paymentMethod."). (order id: ".$postParams['order_id'].") \n\n");
                throw new \Exception('Partial Refund is not allowed for this payment method.');
            }
            
            if (isset($orderAdditionalData['command']) && $orderAdditionalData['command'] == \Amazonpaymentservices\Fort\Model\Config\Source\Commandoptions::AUTHORIZATION) {
                $authoriseAmount = $this->getAuthoriseAmount($orderIncrementId);
                if ($authoriseAmount == 0) {
                    $this->_helper->log("You need to capture the before refund.");
                    throw new \Exception('You need to capture the before refund.');
                }
                $creditmemos = $this->getCreditMemoByOrderId($orderId);
                $creditMemoTotal = $this->getCreditMemosTotal($creditmemos);

                if (($authoriseAmount - $creditMemoTotal) < $amount) {
                    $this->_helper->log("Refund amount is more than Captured Amount.");
                    throw new \Exception('Refund amount is more than Captured Amount.');
                }
            } else {
                $creditmemos = $this->getCreditMemoByOrderId($orderId);
                $creditMemoTotal = $this->getCreditMemosTotal($creditmemos);
                
                $orderRemainingAmount = $orderTotal - $creditMemoTotal;
                if (!$orderRemainingAmount >= $amount) {
                    $this->_helper->log("You cannot refund as Refund amount(".$amount.") is greater than order Remaining amount(".$orderRemainingAmount.").");
                    throw new \Exception("You cannot refund as Refund amount is greater than order Remaining amount.");
                }
            }

            $configCurrency = $this->_scopeConfig->getValue('payment/aps_fort/gateway_currency');
            $baseCurrency = $this->_storeManager->getStore()->getBaseCurrencyCode();

            if ($configCurrency === $baseCurrency) {
                $currencyCode = $baseCurrency;
            } else {
                $currencyCode = $order->getOrderCurrencyCode();
            }
            
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod == \Amazonpaymentservices\Fort\Model\Method\Valu::CODE) {
                $orderIncrementId = $order->getApsValuRef();
            }
            $response = $this->_helper->apsRefund($orderIncrementId, $currencyCode, $amount, $paymentMethod, $order);

            if ($response['response_code'] != '06000') {
                throw new \Exception('Amazon Payment Service Error : '.$response['response_message']);
                
            } elseif ($response['response_code'] == '06000') {
                $entity->setCustomerNote(json_encode($response));
                $this->_messageManager->addSuccessMessage('Refund Initiated');
                $this->_helper->log("\n\n 'Amazon Payment Service : ".$response['response_message']."\n\n");
            }
        }
    }

    private function getCreditMemosTotal($creditmemos)
    {
        $creditMemoTotal = 0;
        foreach ($creditmemos as $creditmemo) {
            $adjustment = $creditmemo->getBaseGrandTotal();
            $creditMemoTotal += $adjustment;
        }
        return $creditMemoTotal;
    }

    private function getItemTotals($order, $postParams)
    {
        $amount = 0;
        foreach ($order->getAllItems() as $item) {
            $itemData = $item->getData();
            $this->_helper->log("pre line item for refund ".json_encode($postParams['creditmemo']['items']));
            $this->_helper->log("line item for refund ".json_encode($itemData));
            if (!empty($postParams['creditmemo']['items'][$itemData['item_id']]['qty'])) {
                $this->_helper->log("child item price ".json_encode($itemData['price']));
                $this->_helper->log("child item qty ".json_encode($postParams['creditmemo']['items'][$itemData['item_id']]['qty']));
                $amount += $itemData['price'] * $postParams['creditmemo']['items'][$itemData['item_id']]['qty'];
                if(!empty($itemData['discount_amount'])){
                    $amount -= $itemData['discount_amount'];
                    $this->_helper->log("amount after discount ".json_encode($amount));
                }
            } 
            $this->_helper->log("amount at items iteration:".$amount);
        }
        return $amount;
    
    }

    private function getAuthoriseAmount($orderIncrementId)
    {
        $authoriseAmount = 0;
        $authorisedPayment = $this->getPaymentData($orderIncrementId);
        $count = count($authorisedPayment);
        for ($x=0; $x < $count; $x++) {
            if ($authorisedPayment[$x]['payment_type'] == 'void') {
                $this->_helper->log("Refund not allowed as order already void.");
                throw new \Exception('Refund not allowed as order already void.');
            } else {
                $authoriseAmount += $authorisedPayment[$x]['amount'];
            }
        }
        return $authoriseAmount;
    }

    /**
     * Get Creditmemo data by Order Id
     *
     * @param int $orderId
     * @return CreditmemoInterface[]|null
     */
    public function getCreditMemoByOrderId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
            $creditmemoRecords = $creditmemos->getItems();
        } catch (\Exception $exception) {
            $this->_helper->log('Credit Memo issue');
            $this->_helper->log($exception->getMessage());
            $creditmemoRecords = null;
        }
        return $creditmemoRecords;
    }

    public function getPaymentData($orderIncrementId)
    {
        $dataArr = [];
        $post = $this->_paymentCaptureFactory->create();
        $collection = $post->getCollection()->addFieldToFilter('order_number', ['eq' => $orderIncrementId]);
        foreach ($collection as $item) {
            $dataArr[] = $item->getData();
        }
        return $dataArr;
    }
}
