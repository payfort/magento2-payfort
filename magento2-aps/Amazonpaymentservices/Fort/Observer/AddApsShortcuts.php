<?php

namespace Amazonpaymentservices\Fort\Observer;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\ObserverInterface;

/**
 * Amazonpaymentservices Shortcut button
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class AddApsShortcuts implements ObserverInterface
{
    /**
     * Alias for mini-cart block.
     */
    private const APS_MINICART_ALIAS = 'mini_cart';

    /**
     * @var string[]
     */
    private $buttonBlocks;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @param string[] $buttonBlocks
     */
    public function __construct(
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        array $buttonBlocks = []
    ) {
        $this->_helper = $helperFort;
        $this->buttonBlocks = $buttonBlocks;
    }

    /**
     * Add Amazonpaymentservices shortcut buttons
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getConfig('payment/aps_apple/active')) {
            if ($this->_helper->getConfig('payment/aps_apple/cart_button')) {
                $shortcutButtons = $observer->getEvent()->getContainer();

                if (!$observer->getData('is_shopping_cart') || 1) {
                    $shortcut = $shortcutButtons->getLayout()
                        ->createBlock($this->buttonBlocks[self::APS_MINICART_ALIAS]);
                }

                $shortcutButtons->addShortcut($shortcut);
            }
        }
    }
}
