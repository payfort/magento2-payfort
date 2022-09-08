<?php
/**
 * Amazonpaymentservices Payment Installment Model
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Model\Method;

use \Magento\Core\Model\ObjectManager;
use \Magento\Framework\Locale\Bundle\DataBundle;

/**
 * Amazonpaymentservices Payment Installment Model
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Installment extends \Amazonpaymentservices\Fort\Model\Payment
{
    const CODE = 'aps_installment';

    protected $_code = self::CODE;

    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;
    
    /**
     * DateTime
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    
    /**
     * Payment config model
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;
    
    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Amazonpaymentservices\Fort\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Checkout\Model\Cart $cart,
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
        $this->localeResolver = $localeResolver;
        $this->_date = $date;
        $this->_paymentConfig = $paymentConfig;
        $this->ccConfig = $ccConfig;
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
            return '';
        }
        return parent::getInstructions();
    }
    
    /**
     * Retrieve availables credit card types
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_paymentConfig->getCcTypes();
        $availableTypes = 'VI,MC,OT,MD,MZ,AE';
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }
    
    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        //$months[0] = __('Month');
        $months = $this->getMonths();
        return $months;
    }
    
    /**
     * Retrieve list of months translation
     *
     * @return array
     * @api
     */
    public function getMonths()
    {
        $data = [];
        $months = (new DataBundle())->get(
            $this->localeResolver->getLocale()
        )['calendar']['gregorian']['monthNames']['format']['wide'];
        foreach ($months as $key => $value) {
            $monthNum = ++$key < 10 ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }
    
    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getYears();
        return $years;
    }
    
    /**
     * Retrieve array of available years
     *
     * @return array
     * @api
     */
    public function getYears()
    {
        $years = [];
        $first = (int)$this->_date->date('Y');
        for ($index = 0; $index <= \Magento\Payment\Model\Config::YEARS_RANGE; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }
    
    /**
     * Retrieve has verification configuration
     *
     * @return bool
     */
    public function hasVerification()
    {
        return true;
    }
    
    /**
     * Solo/switch card start year
     *
     * @return array
     */
    public function getSsStartYears()
    {
        $years = [];
        $first = date("Y");

        for ($index = 5; $index >= 0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = [0 => __('Year')] + $years;
        return $years;
    }

    /**
     * Retrieve the cvv image from ccconfig
     *
     * @return string
     */
    public function getCvvImageUrl()
    {
        return $this->ccConfig->getCvvImageUrl();
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->_helper->getConfig('payment/aps_installment/integration_type') === \Amazonpaymentservices\Fort\Model\Config\Source\Installmentintegrationtype::EMBEDED) {
            return false;
        }

        $baseCurrency = $this->_helper->getBaseCurrency();
        $frontCurrency = $this->_helper->getFrontCurrency();
        $currency = $this->_helper->getFortCurrency($baseCurrency, $frontCurrency);
        
        if ($currency != 'SAR' && $currency != 'EGP' && $currency != 'AED') {
            return false;
        }
        $minAmount = 0;
        if ($currency === 'SAR') {
            $minAmount = $this->_helper->getConfig('payment/aps_installment/sar_min_order');
        }
        if ($currency === 'EGP') {
            $minAmount = $this->_helper->getConfig('payment/aps_installment/egp_min_order');
        }
        if ($currency === 'AED') {
            $minAmount = $this->_helper->getConfig('payment/aps_installment/aed_min_order');
        }
        
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
