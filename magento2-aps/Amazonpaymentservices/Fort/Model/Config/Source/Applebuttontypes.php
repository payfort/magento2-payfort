<?php
/**
 * Apple Button Types
 * php version 8.2.*
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
 * Apple Button Types
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class Applebuttontypes implements \Magento\Framework\Option\ArrayInterface
{
    const BUY = 'apple-pay-buy';
    const DONATE = 'apple-pay-donate';
    const PLAIN = 'apple-pay-plain';
    const SETUP = 'apple-pay-set-up';
    const BOOK = 'apple-pay-book';
    const CHECKOUT = 'apple-pay-check-out';
    const SUBSCRIBE = 'apple-pay-subscribe';
    const ADDMONEY = 'apple-pay-add-money';
    const CONTRIBUTE = 'apple-pay-contribute';
    const ORDER = 'apple-pay-order';
    const RELOAD = 'apple-pay-reload';
    const RENT = 'apple-pay-rent';
    const SUPPORT = 'apple-pay-support';
    const TIP = 'apple-pay-tip';
    const TOPUP = 'apple-pay-top-up';

    /**
     * Apple Button Type Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BUY,
                'label' => __('buy')
            ],
            [
                'value' => self::DONATE,
                'label' => __('donate')
            ],
            [
                'value' => self::PLAIN,
                'label' => __('plain')
            ],
            [
                'value' => self::SETUP,
                'label' => __('set-up')
            ],
            [
                'value' => self::BOOK,
                'label' => __('book')
            ],
            [
                'value' => self::CHECKOUT,
                'label' => __('check-out')
            ],
            [
                'value' => self::SUBSCRIBE,
                'label' => __('subscribe')
            ],
            [
                'value' => self::ADDMONEY,
                'label' => __('add-money')
            ],
            [
                'value' => self::CONTRIBUTE,
                'label' => __('contribute')
            ],
            [
                'value' => self::ORDER,
                'label' => __('order')
            ],
            [
                'value' => self::RELOAD,
                'label' => __('reload')
            ],
            [
                'value' => self::RENT,
                'label' => __('rent')
            ],
            [
                'value' => self::SUPPORT,
                'label' => __('support')
            ],
            [
                'value' => self::TIP,
                'label' => __('tip')
            ],
            [
                'value' => self::TOPUP,
                'label' => __('top-up')
            ],
        ];
    }
}
