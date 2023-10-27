<?php

/**
 * Admin System Config Changed Payment Observer
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Admin System Config Changed Payment Observer
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class AdminSystemConfigChangedPaymentObserver implements ObserverInterface
{
    /**
     * Resource Config
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * App Config
     *
     * @var \Magento\Config\Model\Config
     */
    protected $appConfig;

    /**
     * Class Contructor
     *
     * @param \Magento\Config\Model\Config $appConfig appConfig
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig resourceConfig
     *
     * @return void
     */
    public function __construct(
        \Magento\Config\Model\Config $appConfig,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        $this->appConfig = $appConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * Update items stock status and low stock date.
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $commonConfigKeys = [
            'language',
            'merchant_identifier',
            'access_code',
            'sha_type',
            'sha_in_pass_phrase',
            'sha_out_pass_phrase',
            'command',
            'debug',
            'sandbox_mode',
            'gateway_currency',
            'allowspecific',
            'specificcountry'
        ];
        $scope = $this->appConfig->getScope();
        $scopeId = $this->appConfig->getScopeId();
        foreach ($commonConfigKeys as $configKey) {
            $configVal = $this->appConfig->getConfigDataValue('payment/aps_fort/'. $configKey);
            $this->saveCommonConfig($configKey, $configVal, $scope, $scopeId);
        }
    }

    /**
     * Save Common Config
     *
     * @param $configKey   configkey
     * @param $configValue configValue
     * @param $scope       scope
     * @param $scopeId     scopeId
     *
     * @return void
     */
    protected function saveCommonConfig($configKey, $configValue, $scope, $scopeId)
    {
        $this->resourceConfig->saveConfig('payment/aps_fort_cc/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_vault/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_installment/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_naps/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_knet/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_apple/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_valu/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_visaco/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_stc/'.$configKey, $configValue, $scope, $scopeId);
        $this->resourceConfig->saveConfig('payment/aps_fort_tabby/'.$configKey, $configValue, $scope, $scopeId);
    }
}
