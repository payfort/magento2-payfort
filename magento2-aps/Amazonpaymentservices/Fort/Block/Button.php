<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Amazonpaymentservices\Fort\Block;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use Amazonpaymentservices\Fort\Helper\Data as apsHelper;
use Amazonpaymentservices\Fort\Helper\Aps as aps;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Button
 *
 * in favor of official payment integration available on the marketplace
 */
class Button extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';

    const BUTTON_ELEMENT_INDEX = 'button_id';

    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var MethodInterface
     */
    private $payment;

    /**
     * @var File System
     */
    protected $_filesystem;

    /**
     * @var apsHelper
     */
    protected $_apsHelper;

    /**
     * @var aps
     */
    protected $_aps;

    /**
     * @var Store Manager
     */
    protected $_storeManager;

    /**
     * @var Magento\Framework\Registry
     */
    protected $_frameworkRegistry;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @param Context $context
     * @param ResolverInterface $localeResolver
     * @param Session $checkoutSession
     * @param MethodInterface $payment
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        Session $checkoutSession,
        MethodInterface $payment,
        apsHelper $apsHelper,
        aps $aps,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $frameworkRegistry,
        \Magento\Directory\Model\Country $countryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->_apsHelper = $apsHelper;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_frameworkRegistry = $frameworkRegistry;
        $this->countryFactory = $countryFactory;
        $this->_aps = $aps;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $this->prepareBlockData();
        return parent::_toHtml();
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Returns container id.
     *
     * @return string
     */
    public function getContainerId()
    {
        return $this->getData(self::BUTTON_ELEMENT_INDEX);
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * Returns currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->checkoutSession->getQuote()->getCurrency()->getBaseCurrencyCode();
    }

    /**
     * Returns amount.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $this->addData(
            []
        );
    }

    public function appleDataAps()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/sendAppleDataToAps');
    }

    public function appleButtonTypes()
    {
        return $this->_apsHelper->getConfig('payment/aps_apple/apple_button_types');
    }

    public function appleSupportedNetworks()
    {
        return $this->_apsHelper->getReturnUrl('payment/aps_apple/apple_supported_networks');
    }

    public function getStoreName()
    {
        return $this->_apsHelper->getConfig('payment/aps_apple/apple_display_name');
    }

    public function getCountryCode()
    {
        $country = $this->_scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_WEBSITES
        );
        return $country;
    }

    public function getAppleCancelUrl()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/appleCancelResponse');
    }

    public function getCurrentProduct()
    {
        $product = $this->_frameworkRegistry->registry('current_product');
        return $product;
    }

    public function getCurrenctCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getCurrentCurrencyRate()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyRate();
    }

    public function getSupportedNetwork()
    {
        return $this->_apsHelper->getConfig('payment/aps_apple/apple_supported_networks');
    }

    public function getAppleValidationUrl()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getAppleValidation');
    }

    public function postAppleData()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/sendAppleCartDataToAps');
    }

    public function validateAddress()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/appleValidateAddress');
    }

    public function addProductToCart()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/product/addProductToCart');
    }

    public function getRegions()
    {
        $values = [];
        $countryCode = 'in';
        $country = $this->countryFactory->loadByCode($countryCode);

        $regions = $country->getRegions();

        return $regions->loadData()->toOptionArray(false);
    }

    public function getQuoteId()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    public function getQuoteData()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/product/getQuoteData');
    }

    public function getCancelUrl()
    {
        return $this->_apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/appleCancelResponse');
    }

    public function getAppleProductConfig()
    {
        return $this->_apsHelper->getConfig('payment/aps_apple/product_button');
    }
}
