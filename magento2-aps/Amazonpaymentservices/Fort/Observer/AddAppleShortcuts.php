<?php
namespace Amazonpaymentservices\Fort\Observer;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\ObserverInterface;

/**
 * Amazonpaymentservices Shortcut button
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class AddAppleShortcuts implements ObserverInterface
{
    /**
     * Alias for mini-cart block.
     */
    private const APPLE_MINICART_ALIAS = 'mini_apple_cart';

    /**
     * @var string[]
     */
    private $buttonAppleBlocks;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @param string[] $buttonAppleBlocks
     */
    public function __construct(
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        array $buttonAppleBlocks = []
    ) {
        $this->_helper = $helperFort;
        $this->buttonAppleBlocks = $buttonAppleBlocks;
    }

    /**
     * Add Amazonpaymentservices APPLE shortcut buttons
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var ShortcutButtons $shortcutButtons */
        if ($this->_helper->getConfig('payment/aps_apple/product_button')) {
            $shortcutButtons = $observer->getEvent()->getContainer();
            $shortcut = $shortcutButtons->getLayout()
                    ->createBlock($this->buttonAppleBlocks[self::APPLE_MINICART_ALIAS]);
            
            $shortcutButtons->addShortcut($shortcut);
        }
    }
}
