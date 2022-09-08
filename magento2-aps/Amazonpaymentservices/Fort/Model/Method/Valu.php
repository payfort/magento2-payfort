<?php
/**
 * Payment Command Types Source Model
 *
 * @category    Amazonpaymentservices
 * @package     Amazonpaymentservices_Fort
 */

namespace Amazonpaymentservices\Fort\Model\Method;

class Valu extends \Amazonpaymentservices\Fort\Model\Payment
{
    const CODE = 'aps_fort_valu';

    protected $_code = self::CODE;
    protected $_helper;
    protected $cart;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Amazonpaymentservices\Fort\Helper\Data $paymentData,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        
        $this->_code = self::CODE;
        $this->_helper = $paymentData;
        $this->cart = $cart;
    }
    
    /**
     * @return bool
     */
    public function isStandard()
    {
        if ($this->getConfigData('integration_type') == \Amazonpaymentservices\Fort\Model\Config\Source\Integrationtypeoptions::STANDARD) {
            return true;
        }
        return false;
    }
    
    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        if ($this->isStandard()) {
            return false;
        }
        return parent::getInstructions();
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $baseCurrency                    = $this->_helper->getBaseCurrency();
        $frontCurrency                   = $this->_helper->getFrontCurrency();
        $currency                        = $this->_helper->getFortCurrency($baseCurrency, $frontCurrency);
        if ($frontCurrency != 'EGP') {
            return false;
        }
        $minAmount = $this->_helper->getConfig('payment/aps_fort_valu/purchase_limit');

        $grandTotal = $this->cart->getQuote()->getGrandTotal();
        if ($grandTotal < $minAmount) {
            return false;
        }
        
        if (!$this->_helper->checkSubscriptionItemInCart()) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
