<?php
namespace Amazonpaymentservices\Fort\Block;

class Visacheckout extends \Magento\Backend\Block\AbstractBlock
{
    protected $_helper;

    protected $_pageConfig;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_pageConfig = $pageConfig;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        if ($this->_helper->getConfig('payment/aps_fort_visaco/sandbox_mode')) {
            $js = 'https://sandbox-assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
        } else {
            $js = 'https://assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
        }
        $this->_pageConfig->addPageAsset($js);
    }
}
