<?php
/**
 * Language Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class Gatewaycurrencyoptions implements \Magento\Framework\Option\ArrayInterface
{
    const BASE  = 'base';
    const FRONT = 'front';
    /**
     * {@inheritdoc}
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
