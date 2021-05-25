<?php
/**
 * Payment Integration Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class Integrationtypeoptions implements \Magento\Framework\Option\ArrayInterface
{
    const REDIRECTION  = "redirection";
    const MERCHANT_PAGE = "merchantPage";
    const MERCHANT_PAGE2 = "merchantPage2";
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
                'value' => self::MERCHANT_PAGE,
                'label' => __('Merchant Page')
            ],
            [
                'value' => self::MERCHANT_PAGE2,
                'label' => __('Merchant Page 2.0')
            ]
        ];
    }
}
