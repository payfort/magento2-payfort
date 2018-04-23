<?php
/**
 * Language Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 */

namespace Payfort\Fort\Model\Config\Source;

class Languageoptions implements \Magento\Framework\Option\ArrayInterface
{
    const STORE = 'store';
    const EN    = 'en';
    const AR    = 'ar';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::STORE,
                'label' => __('Store Language')
            ],
            [
                'value' => self::EN,
                'label' => __('en')
            ],
            [
                'value' => self::AR,
                'label' => __('ar')
            ]/*,
            [
                'value' => 'no_language',
                'label' => __('Use store locale')
            ]*/
        ];
    }
}
