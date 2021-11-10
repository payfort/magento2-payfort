<?php
/**
 * Apple Supported Networks
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
 * Apple Supported Networks
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class Applesupportednetworks implements \Magento\Framework\Option\ArrayInterface
{
    const AMEX = 'amex';
    const MASTERCARD = 'masterCard';
    const VISA = 'visa';
    const MADA = 'mada';
    
    /**
     * Apple Button Type Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AMEX,
                'label' => __('amex')
            ],
            [
                'value' => self::MASTERCARD,
                'label' => __('master card')
            ],
            [
                'value' => self::VISA,
                'label' => __('visa')
            ],
            [
                'value' => self::MADA,
                'label' => __('mada')
            ]
        ];
    }
}
