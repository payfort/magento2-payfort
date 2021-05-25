<?php
/**
 * Payment Command Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Method;
//use Payfort\Fort\Helper\Data;
use \Magento\Core\Model\ObjectManager;
use \Magento\Framework\Locale\Bundle\DataBundle;

class Cc extends \Payfort\Fort\Model\Payment
{
    const CODE = 'payfort_fort_cc';

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
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Payfort\Fort\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Model\Config $paymentConfig,
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
    }
    
    /**
     * @return bool
     */
    public function isMerchantPage()
    {
        if ($this->getConfigData('integration_type') == \Payfort\Fort\Model\Config\Source\Integrationtypeoptions::MERCHANT_PAGE) {
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
        if($this->isMerchantPage()) {
           //return __('Payfrot merchant page instructions'); 
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
        $availableTypes = 'VI,MC,OT';
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
        //$years = [0 => __('Year')] + $years;
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
    
}