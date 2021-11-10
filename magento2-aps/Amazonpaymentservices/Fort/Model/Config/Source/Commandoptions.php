<?php
/**
 * Command options
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
 * Command options
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class Commandoptions implements \Magento\Framework\Option\ArrayInterface
{
    const AUTHORIZATION = 'AUTHORIZATION';
    const PURCHASE      = 'PURCHASE';

    /**
     * Command Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTHORIZATION,
                'label' => __('AUTHORIZATION')
            ],
            [
                'value' => self::PURCHASE,
                'label' => __('PURCHASE')
            ]
        ];
    }
}
