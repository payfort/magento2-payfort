<?php
/**
 * Payment Integration Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class InstallmentsIntegrationtypeoptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => "redirection",
                'label' => __('Redirection'),
            ],
            [
                'value' => "merchantPage",
                'label' => __('Merchant Page')
            ],
        ];
    }
}
