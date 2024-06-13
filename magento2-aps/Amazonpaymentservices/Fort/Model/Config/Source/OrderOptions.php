<?php

/**
 * Order after an unsuccessful payment options
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

class OrderOptions implements \Magento\Framework\Option\ArrayInterface
{
    const CANCEL_ORDER = 'cancel_order';
    const DELETE_ORDER = 'delete_order';

    /**
     * Order after unsuccessful payment Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CANCEL_ORDER,
                'label' => __('Cancel Order'),
            ],
            [
                'value' => self::DELETE_ORDER,
                'label' => __('Delete Order'),
            ],
        ];
    }
}
