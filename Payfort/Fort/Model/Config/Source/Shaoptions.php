<?php
/**
 * Hash Algorithm Sha Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class Shaoptions implements \Magento\Framework\Option\ArrayInterface
{
    const SHA1   = 'SHA-1';
    const SHA256 = 'SHA-256';
    const SHA512 = 'SHA-512';
    const HMAC256 = 'HMAC-256';
    const HMAC512 = 'HMAC-512';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHA1,
                'label' => __('SHA-1')
            ],
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
