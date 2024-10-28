<?php
/**
 * Sha Algorithm options
 * php version 8.2.*
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
 * Sha Algorithm options
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class Shaoptions implements \Magento\Framework\Option\ArrayInterface
{
    const SHA256 = 'SHA-256';
    const SHA512 = 'SHA-512';
    const HMAC256 = 'HMAC-256';
    const HMAC512 = 'HMAC-512';

    /**
     * Sha Algorithm Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHA256,
                'label' => __('SHA-256')
            ],
            [
                'value' => self::SHA512,
                'label' => __('SHA-512')
            ],
            [
                'value' => self::HMAC256,
                'label' => __('HMAC-256')
            ],
            [
                'value' => self::HMAC512,
                'label' => __('HMAC-512')
            ]
        ];
    }
}
