<?php
/**
 * Payment Integration Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class madaBranding implements \Magento\Framework\Option\ArrayInterface
{
    const Disabled = "Disabled";
    const Enabled  = "Enabled";
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::Disabled,
                'label' => __('Disabled'),
            ],
            [
                'value' => self::Enabled,
                'label' => __('Enabled')
            ]
        ];
    }
}
