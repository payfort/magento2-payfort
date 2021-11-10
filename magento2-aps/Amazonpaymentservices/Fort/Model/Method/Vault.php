<?php
/**
 * Payment Command Types Source Model
 *
 * @category    Amazonpaymentservices
 * @package     Amazonpaymentservices_Fort
 */

namespace Amazonpaymentservices\Fort\Model\Method;

class Vault extends \Amazonpaymentservices\Fort\Model\Payment
{
    const CODE = 'aps_fort_vault';

    protected $_code = self::CODE;
    protected $_helper;

    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Amazonpaymentservices\Fort\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Payment\Model\CcConfig $ccConfig,
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
        $this->ccConfig = $ccConfig;
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
     * Retrieve the cvv image from ccconfig
     *
     * @return string
     */
    public function getCvvImageUrl()
    {
        return $this->ccConfig->getCvvImageUrl();
    }
}
