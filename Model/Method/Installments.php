<?php
/**
 * Payment Command Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Method;

class Installments extends \Payfort\Fort\Model\Payment
{
    const CODE = 'payfort_fort_installments';

    protected $_code = self::CODE;
    protected $_helper;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Payfort\Fort\Helper\Data $paymentData,
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
}