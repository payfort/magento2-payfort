<?php
/**
 * Gateway Currency options
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
 * Gateway Currency options
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class Gatewaycurrencyoptions implements \Magento\Framework\Option\ArrayInterface
{
    const BASE  = 'base';
    const FRONT = 'front';

    /**
     * Gateway Currency Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BASE,
                'label' => __('Base Currency')
            ],
            [
                'value' => self::FRONT,
                'label' => __('Front Currency')
            ]
        ];
    }
}
