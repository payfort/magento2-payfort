<?php
/**
 * Payment Config Source Interval Options
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
 * Payment Config Source Interval Options
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Intervaloptions implements \Magento\Framework\Option\ArrayInterface
{
    const MIN15  = "15";

    const MIN30 = "30";

    const MIN45 = "45";

    const MIN60 = "60";

    const MIN120 = "120";
    
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::MIN15,
                'label' => __('15min'),
            ],
            [
                'value' => self::MIN30,
                'label' => __('30min')
            ],
            [
                'value' => self::MIN45,
                'label' => __('45min')
            ],
            [
                'value' => self::MIN60,
                'label' => __('1hour')
            ],
            [
                'value' => self::MIN120,
                'label' => __('2hours')
            ]
        ];
    }
}
