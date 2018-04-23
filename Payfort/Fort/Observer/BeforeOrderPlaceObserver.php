<?php
namespace Payfort\Fort\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Sales order place  observer
 */
class BeforeOrderPlaceObserver implements ObserverInterface 
{
    /**
     * Update items stock status and low stock date.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $order->setCanSendNewEmailFlag(false);
    }
}
