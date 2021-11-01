<?php

/**
 * Before Order Place Observer
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/

namespace Amazonpaymentservices\Fort\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Before Order Place Observer
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class BeforeOrderPlaceObserver implements ObserverInterface
{
    /**
     * Helper Class
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $helper;
    
    /**
     * Constructor
     *
     * @param \Amazonpaymentservices\Fort\Helper\Data $helper helper
     */
    public function __construct(\Amazonpaymentservices\Fort\Helper\Data $helper)
    {
        $this->helper = $helper;
    }
    
    /**
     * Update items stock status and low stock date.
     *
     * @param EventObserver $observer observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($this->helper->isApsPaymentMethod($paymentMethod)) {
            $order->setCanSendNewEmailFlag(false);
        }
    }
}
