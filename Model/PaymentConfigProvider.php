<?php

namespace Payfort\Fort\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Payfort\Fort\Helper\Data as pfHelper;

class PaymentConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        \Payfort\Fort\Model\Method\Cc::CODE,
        \Payfort\Fort\Model\Method\Sadad::CODE,
        \Payfort\Fort\Model\Method\Naps::CODE,
        \Payfort\Fort\Model\Method\Installments::CODE,
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var pfHelper
     */
    protected $pfHelper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        pfHelper $pfHelper,
        UrlInterface $urlBuilder
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->pfHelper = $pfHelper;
        $this->urlBuilder = $urlBuilder;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'payfortFort' => [],
            ],
        ];
        $config['payment']['payfortFort']['configParams']['gatewayCurrency'] = $this->pfHelper->getGatewayCurrency();
        $ccIntegrationType = $this->methods[\Payfort\Fort\Model\Method\Cc::CODE]->getConfigData('integration_type');
        $config['payment']['payfortFort'][\Payfort\Fort\Model\Method\Cc::CODE]['integrationType'] = $ccIntegrationType;
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['payfortFort'][$code]['redirectUrl']  = $this->getActionUrl($code);
                $config['payment']['payfortFort'][$code]['instructions'] = $this->methods[$code]->getInstructions();
                if($code == \Payfort\Fort\Model\Method\Cc::CODE || $code == \Payfort\Fort\Model\Method\Installments::CODE) {
                    $config['payment']['payfortFort'][$code]['isMerchantPage'] = $this->methods[$code]->isMerchantPage();
                    $config['payment']['payfortFort'][$code]['merchantPageUrl'] = $this->urlBuilder->getUrl('payfortfort/payment/merchantPage', ['_secure' => true]);
                    if($ccIntegrationType == \Payfort\Fort\Helper\Data::PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
                        $config['payment']['payfortFort'][$code]['ajaxUrl']  = $this->pfHelper->getReturnUrl('payfortfort/payment/getPaymentData');
                        $config['payment']['ccform']['availableTypes'][$code] = $this->methods[$code]->getCcAvailableTypes();
                        $config['payment']['ccform']['years'][$code] = $this->methods[$code]->getCcYears();
                        $config['payment']['ccform']['months'][$code] = $this->methods[$code]->getCcMonths();
                        $config['payment']['ccform']['hasVerification'][$code] = $this->methods[$code]->hasVerification();
                        $config['payment']['ccform']['ssStartYears'][$code] = $this->methods[$code]->getSsStartYears();
                        $config['payment']['ccform']['hasSsCardType'][$code] = false;
                        $config['payment']['ccform']['cvvImageUrl'][$code] = $this->methods[$code]->getCvvImageUrl();
                    }
                }
            }
        }
        return $config;
    }

    /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    protected function getActionUrl($code)
    {
        $url = $this->urlBuilder->getUrl('payfortfort/payment/redirect', ['_secure' => true]);

        return $url;
    }
}
