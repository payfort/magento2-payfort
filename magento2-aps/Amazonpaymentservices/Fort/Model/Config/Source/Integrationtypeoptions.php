<?php
/**
 * Payment Config Source Integraton Type Options
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Model\Config\Source;

/**
 * Payment Config Source Integraton Type Options
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Integrationtypeoptions implements \Magento\Framework\Option\ArrayInterface
{
    const REDIRECTION  = "redirection";

    const STANDARD = "standard";

    const HOSTED = "hosted";
    
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::REDIRECTION,
                'label' => __('Redirection'),
            ],
            [
                'value' => self::STANDARD,
                'label' => __('Standard Checkout')
            ],
            [
                'value' => self::HOSTED,
                'label' => __('Hosted Checkout')
            ]
        ];
    }
}
