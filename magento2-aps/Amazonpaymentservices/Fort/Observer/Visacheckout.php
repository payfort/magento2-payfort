<?php

namespace Amazonpaymentservices\Fort\Observer;

use Amazonpaymentservices\Fort\Helper\Data;

class Visacheckout implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Amazonpaymentservices\Fort\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_helper->getConfig('payment/aps_fort_visaco/active')) {
            $visaSandboxMode = $this->_helper->getConfig('payment/aps_fort_visaco/sandbox_mode');
            $layout = $observer->getLayout();
            if (!$visaSandboxMode) {
                $layout->getUpdate()->addHandle('visacheckout');
            } else {
                $layout->getUpdate()->addHandle('visacheckout_sandbox');
            }
        }
        return $this;
    }
}
