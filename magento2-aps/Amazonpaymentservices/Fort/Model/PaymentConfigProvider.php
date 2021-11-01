<?php
/**
 * Payment Config Provider
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Amazonpaymentservices\Fort\Helper\Data as apsHelper;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Payment Config Provider
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class PaymentConfigProvider implements ConfigProviderInterface
{
     /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string[]
     */
    protected $_methodCodes = [
        \Amazonpaymentservices\Fort\Model\Method\Vault::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Cc::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Naps::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Knet::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Apple::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Installment::CODE,
        \Amazonpaymentservices\Fort\Model\Method\Valu::CODE,
        \Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE
    ];

    /**
     * @var string[]
     */
    protected $_cardTypes = [
        'mada' => 'mada-logo.png',
        'amex' => 'amex-logo.png',
        'visa' => 'visa-logo.png',
        'master' => 'mastercard-logo.png',
        'meeza' => 'meeza-logo.jpg'
    ];

    /**
     * @var string[]
     */
    protected $_cardShortName = [
        'mada' => 'MD',
        'amex' => 'AE',
        'visa' => 'VI',
        'master' => 'MC',
        'meeza' => 'MZ'
    ];

    /**
     * @var string[]
     */
    protected $_cardTokenShortName = [
        'mada' => 'MD',
        'amex' => 'AE',
        'visa' => 'VI',
        'mastercard' => 'MC',
        'meeza' => 'MZ'
    ];

    /**
     * @var AssetRepository
     */
    protected $_assetRepository;

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var apsHelper
     */
    protected $apsHelper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Store Manager
     */
    protected $_storeManager;

    /**
     * @var Store
     */
    protected $_store;

    protected $session;
    
    protected $paymenttokenmanagement;

    protected $config;

    /**
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        apsHelper $apsHelper,
        UrlInterface $urlBuilder,
        AssetRepository $assetRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymenttokenmanagement,
        \Magento\Customer\Model\Session $session,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->apsHelper = $apsHelper;
        $this->urlBuilder = $urlBuilder;
        $this->_assetRepository = $assetRepository;
        $this->_storeManager = $storeManager;
        $this->_store = $store;
        $this->paymenttokenmanagement = $paymenttokenmanagement;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        foreach ($this->_methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $this->config = [
            'payment' => [
                'apsFort' => [],
            ],
        ];
        $this->config['payment']['apsFort']['configParams']['gatewayCurrency'] = $this->apsHelper->getGatewayCurrency();
        $this->config['payment']['apsFort']['configParams']['storeLanguage'] = $this->apsHelper->getLanguage();
        $ccIntegrationType = $this->methods[\Amazonpaymentservices\Fort\Model\Method\Cc::CODE]->getConfigData('integration_type');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Cc::CODE]['integrationType'] = $ccIntegrationType;

        $installIntegrationType = $this->methods[\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]->getConfigData('integration_type');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]['integrationType'] = $installIntegrationType;

        $visaCoIntegrationType = $this->methods[\Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE]->getConfigData('integration_type');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE]['integrationType'] = $visaCoIntegrationType;

        $vaultIntegrationType = $this->methods[\Amazonpaymentservices\Fort\Model\Method\Vault::CODE]->getConfigData('integration_type');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Vault::CODE]['integrationType'] = $vaultIntegrationType;
       
        $active = $this->methods[\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]->getConfigData('active');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]['active'] = $active;

        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]['ajaxInstallmentUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getPaymentData');
        $this->config['payment']['apsFort'][\Amazonpaymentservices\Fort\Model\Method\Installment::CODE]['vaultInstallment']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getVaultInstallment');
        
        $this->getCardImages();
        $this->getCardLogo();
        
        foreach ($this->_methodCodes as $code) {
            $this->config['payment']['apsFort']['allpayment'][] = $code;
            if ($this->methods[$code]->isAvailable()) {
                $this->config['payment']['apsFort']['avaiable'][] = $code;
                $this->config['payment']['apsFort'][$code]['redirectUrl']  = $this->getActionUrl();
                $this->config['payment']['apsFort'][$code]['instructions'] = $this->methods[$code]->getInstructions();
                $this->config['payment']['apsFort'][$code]['title'] = $this->methods[$code]->getTitle();
                $this->config['payment']['apsFort'][$code]['sort_order'] = $this->apsHelper->getConfig('payment/aps_fort_cc/sort_order');

                if ($code == \Amazonpaymentservices\Fort\Model\Method\CC::CODE) {
                    $this->ccConfig($code);
                }
                if ($code == \Amazonpaymentservices\Fort\Model\Method\VisaCheckout::CODE) {
                    $this->visaConfig($code);
                }
                if ($code == \Amazonpaymentservices\Fort\Model\Method\Valu::CODE) {
                    $this->config['payment']['apsFort'][$code]['valuLogo'] = $this->getValuConfig();
                    $this->config['payment']['apsFort'][$code]['ajaxOtpUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/GetUserData');
                    $this->config['payment']['apsFort'][$code]['ajaxOtpVerifyUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/GetOtpVerifyData');
                    $this->config['payment']['apsFort'][$code]['ajaxPurchaseUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/GetPurchaseData');
                    $this->config['payment']['apsFort'][$code]['response']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/response');
                    $this->config['payment']['apsFort'][$code]['storeName']  = $this->_storeManager->getStore()->getName();
                }
                if ($code == \Amazonpaymentservices\Fort\Model\Method\Apple::CODE) {
                    $this->config['payment']['apsFort'][$code]['shippingconfig'] = $this->apsHelper->getConfig('tax/calculation/shipping_includes_tax');
                    $this->config['payment']['apsFort'][$code]['shippingdisplayconfig'] = $this->apsHelper->getConfig('tax/cart_display/shipping');

                    $this->config['payment']['apsFort'][$code]['appleValidation']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getAppleValidation');
                    $this->config['payment']['apsFort'][$code]['appleToAps']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/sendAppleDataToAps');
                    $this->config['payment']['apsFort'][$code]['appleButtonClass']  = $this->apsHelper->getConfig('payment/aps_apple/apple_button_types');
                    $this->config['payment']['apsFort'][$code]['appleSupportedNetwork']  = $this->apsHelper->getConfig('payment/aps_apple/apple_supported_networks');
                    $this->config['payment']['apsFort'][$code]['storeName']  = $this->apsHelper->getConfig('payment/aps_apple/apple_display_name');
                    $country = $this->scopeConfig->getValue(
                        self::COUNTRY_CODE_PATH,
                        ScopeInterface::SCOPE_WEBSITES
                    );
                    $this->config['payment']['apsFort'][$code]['storeCountryCode']  = $country;
                    $this->config['payment']['apsFort'][$code]['cancelUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/appleFailedResponse');
                }
                if ($code == \Amazonpaymentservices\Fort\Model\Method\Installment::CODE) {
                    $this->installmentConfig($code, $installIntegrationType);
                }

                if ($ccIntegrationType == \Amazonpaymentservices\Fort\Helper\Data::INTEGRATION_TYPE_HOSTED) {
                    $this->config['payment']['apsFort'][$code]['ajaxUrlToken']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/createToken');
                    $this->config['payment']['apsFort'][$code]['ajaxUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getPaymentData');
                    $this->config['payment']['ccform']['availableTypes'][$code] = $this->methods[$code]->getCcAvailableTypes();
                    $this->config['payment']['ccform']['years'][$code] = $this->methods[$code]->getCcYears();
                    $this->config['payment']['ccform']['months'][$code] = $this->methods[$code]->getCcMonths();
                    $this->config['payment']['ccform']['hasVerification'][$code] = $this->methods[$code]->hasVerification();
                    $this->config['payment']['ccform']['ssStartYears'][$code] = $this->methods[$code]->getSsStartYears();
                    $this->config['payment']['ccform']['hasSsCardType'][$code] = false;
                    $this->config['payment']['ccform']['cvvImageUrl'][$code] = $this->methods[$code]->getCvvImageUrl();
                }
            }
        }
        return $this->config;
    }

    private function getCardImages()
    {
        foreach ($this->_cardTypes as $key => $value) {
            if ($key == 'mada' || $key == 'meeza') {
                if ($this->methods[\Amazonpaymentservices\Fort\Model\Method\Cc::CODE]->getConfigData($key.'_branding') !== 'yes') {
                    continue;
                }
            }
            if ($key == 'visa' || $key == 'master') {
                $this->config['payment']['apsFort']['cardInstallImg'][] = $this->getCardTypeImg($value);
            }
            $this->config['payment']['apsFort']['cardImg'][] = $this->getCardTypeImg($value);
        }
    }

    private function getCardLogo()
    {
        foreach ($this->_cardTypes as $key => $value) {
            $this->config['payment']['apsFort']['templogoImg'][$this->_cardShortName[$key]] = $this->getCardTypeImg($value);
            if ($key == 'mada' || $key == 'meeza') {
                if ($this->methods[\Amazonpaymentservices\Fort\Model\Method\Cc::CODE]->getConfigData($key.'_branding') !== 'yes') {
                    continue;
                }
            }
            $this->config['payment']['apsFort']['logoImg'][$this->_cardShortName[$key]] = $this->getCardTypeImg($value);
            
        }
    }

    private function installmentConfig($code, $installIntegrationType)
    {
        $this->config['payment']['apsFort'][$code]['title'] = $this->methods[$code]->getTitle();
        $this->config['payment']['apsFort'][$code]['getInstallmentPlans']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getInstallmentPlans');
        $this->config['payment']['apsFort'][$code]['installmentResponse']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/installmentStandardPageResponse');
        $this->config['payment']['apsFort'][$code]['bankLogo']  = $this->apsHelper->getConfig('payment/aps_installment/bank_logo');
        $this->config['payment']['apsFort'][$code]['issuerCode']  = $this->apsHelper->getConfig('payment/aps_installment/issuer_code');
        
        if ($installIntegrationType == \Amazonpaymentservices\Fort\Helper\Data::INTEGRATION_TYPE_HOSTED) {
            $this->config['payment']['apsFort'][$code]['ajaxUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getPaymentData');
            $this->config['payment']['apsFort'][$code]['vaultInstallment']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getVaultInstallment');
            $this->config['payment']['ccform']['availableTypes'][$code] = $this->methods[$code]->getCcAvailableTypes();
            $this->config['payment']['ccform']['years'][$code] = $this->methods[$code]->getCcYears();
            $this->config['payment']['ccform']['months'][$code] = $this->methods[$code]->getCcMonths();
            $this->config['payment']['ccform']['hasVerification'][$code] = $this->methods[$code]->hasVerification();
            $this->config['payment']['ccform']['ssStartYears'][$code] = $this->methods[$code]->getSsStartYears();
            $this->config['payment']['ccform']['hasSsCardType'][$code] = false;
            $this->config['payment']['ccform']['cvvImageUrl'][$code] = $this->methods[$code]->getCvvImageUrl();

            $vaultCode = \Amazonpaymentservices\Fort\Model\Method\Vault::CODE;
            $vaultStatus = $this->apsHelper->getConfig('payment/aps_fort_vault/active');
            if ($vaultStatus == 1) {
                $customerId = $this->session->getCustomer()->getId();
                $cardList = $this->paymenttokenmanagement->getListByCustomerId($customerId);
                $this->installmentVault($cardList, $code, $vaultCode);
            }
            $this->config['payment']['apsFort'][$code][$vaultCode]['active'] = $vaultStatus;
        }
    }

    private function installmentVault($cardList, $code, $vaultCode)
    {
        $temp=0;
        $cardType = ['MASTERCARD','VISA'];
        foreach ($cardList as $card) {
            if ($card->getIsActive()) {
                $vaultData = $card->getData();
                if ($vaultData['payment_method_code'] == \Amazonpaymentservices\Fort\Model\Payment::CODE) {
                    $details = json_decode($vaultData['details'], 1);
                    if (in_array($details['type'], $cardType)) {
                        $this->config['payment']['apsFort'][$code][$vaultCode]['data'][$temp]['public_hash'] = $vaultData['public_hash'];
                        $this->config['payment']['apsFort'][$code][$vaultCode]['data'][$temp]['type'] = $details['type'];
                        $this->config['payment']['apsFort'][$code][$vaultCode]['data'][$temp]['typename'] = $this->config['payment']['apsFort']['logoImg'][$this->_cardTokenShortName[strtolower($details['type'])]];
                        $this->config['payment']['apsFort'][$code][$vaultCode]['data'][$temp]['maskedCC'] = $details['maskedCC'];
                        $this->config['payment']['apsFort'][$code][$vaultCode]['data'][$temp]['expirationDate'] = $details['expirationDate'];
                        $temp++;
                    }
                }
            }
        }
    }

    private function visaConfig($code)
    {
        $this->config['payment']['apsFort'][$code]['visajs'] = $this->apsHelper->getVisaCheckoutJs();
        $this->config['payment']['apsFort'][$code]['response'] = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/VisaCheckout');
        $this->config['payment']['apsFort'][$code]['locale'] = $this->_store->getLocale();
        $this->config['payment']['apsFort'][$code]['profileId'] = $this->apsHelper->getConfig('payment/aps_fort_visaco/profile_id');
        $this->config['payment']['apsFort'][$code]['apiKey'] = $this->apsHelper->getConfig('payment/aps_fort_visaco/api_key');
        $this->config['payment']['apsFort'][$code]['storeName']  = $this->_storeManager->getStore()->getName();
        $this->config['payment']['apsFort'][$code]['ajaxUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/VisaCheckout');
        $this->config['payment']['apsFort'][$code]['responseUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/VisaCheckoutResponse');
        $sandboxMode = $this->apsHelper->getConfig('payment/aps_fort_visaco/sandbox_mode');
        if ($sandboxMode) {
            $this->config['payment']['apsFort'][$code]['visalogo']  = 'https://sandbox.secure.checkout.visa.com/wallet-services-web/xo/button.png';
        } else {
            $this->config['payment']['apsFort'][$code]['visalogo']  = 'https://secure.checkout.visa.com/wallet-services-web/xo/button.png';
        }
    }

    private function ccConfig($code)
    {
        $this->config['payment']['apsFort'][$code]['mada'] = $this->apsHelper->getConfig('payment/aps_fort_cc/mada_branding');
        $this->config['payment']['apsFort'][$code]['meeza'] = $this->apsHelper->getConfig('payment/aps_fort_cc/meeza_branding');
        $this->config['payment']['apsFort'][$code]['madabin'] = $this->apsHelper->getConfig('payment/aps_fort/mada_regex');
        $this->config['payment']['apsFort'][$code]['meezabin'] = $this->apsHelper->getConfig('payment/aps_fort/meeza_regex');
        $this->config['payment']['apsFort'][$code]['getInstallmentPlans']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getInstallmentPlans');

        $vaultCode = \Amazonpaymentservices\Fort\Model\Method\Vault::CODE;
        $vaultStatus = $this->apsHelper->getConfig('payment/aps_fort_vault/active');
        if ($vaultStatus == 1) {
            $customerId = $this->session->getCustomer()->getId();
            $cardList = $this->paymenttokenmanagement->getListByCustomerId($customerId);
            $temp=0;
            foreach ($cardList as $card) {
                if ($card->getIsActive()) {
                    $vaultData = $card->getData();
                    if ($vaultData['payment_method_code'] == \Amazonpaymentservices\Fort\Model\Payment::CODE) {

                        $this->config['payment']['apsFort'][$vaultCode]['data'][$temp]['public_hash'] = $vaultData['public_hash'];
                        $details = json_decode($vaultData['details'], 1);
                        $this->config['payment']['apsFort'][$vaultCode]['data'][$temp]['type'] = $details['type'];
                        $this->config['payment']['apsFort'][$vaultCode]['data'][$temp]['typename'] = $this->config['payment']['apsFort']['templogoImg'][$this->_cardTokenShortName[strtolower($details['type'])]];
                        $this->config['payment']['apsFort'][$vaultCode]['data'][$temp]['maskedCC'] = $details['maskedCC'];
                        $this->config['payment']['apsFort'][$vaultCode]['data'][$temp]['expirationDate'] = $details['expirationDate'];
                        $temp++;
                    }
                }
            }
            $this->config['payment']['apsFort'][$vaultCode]['ajaxVaultUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getVaultPaymentData');
            $this->config['payment']['apsFort'][$vaultCode]['redirectUrl']  = $this->apsHelper->getReturnUrl('amazonpaymentservicesfort/payment/getVaultPaymentData');
        }
        $this->config['payment']['apsFort'][$vaultCode]['active']  = $vaultStatus;
    }

    /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    protected function getActionUrl()
    {
        $url = $this->urlBuilder->getUrl('amazonpaymentservicesfort/payment/redirect', ['_secure' => true]);

        return $url;
    }

    /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    protected function getInstallmentUrl()
    {
        $url = $this->urlBuilder->getUrl('amazonpaymentservicesfort/payment/redirectinstallment', ['_secure' => true]);

        return $url;
    }

    protected function getCardTypeImg($imgName)
    {
        $output = $this->getViewFileUrl('Amazonpaymentservices_Fort::images/methods/'.$imgName);
        return $output;
    }

    protected function getCardLogoImg($imgName)
    {
        $output = $this->getViewFileUrl('Amazonpaymentservices_Fort::images/logos/'.$imgName);
        return $output;
    }

    protected function getValuConfig()
    {
        $output = $this->getViewFileUrl('Amazonpaymentservices_Fort::images/valu_logo.png');
        return $output;
    }
    
    public function getViewFileUrl($fileId, array $params = [])
    {
        return $this->_assetRepository->getUrlWithParams($fileId, $params);
    }
}
