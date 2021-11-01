<?php
/**
 * Payment Config Source Mada Branding Options
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
 * Payment Config Source Mada Branding Options
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Madaoptions implements \Magento\Framework\Option\ArrayInterface
{
    const SHOW  = "yes";

    const HIDE = "no";
    
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHOW,
                'label' => __('yes'),
            ],
            [
                'value' => self::HIDE,
                'label' => __('no')
            ]
        ];
    }
}
