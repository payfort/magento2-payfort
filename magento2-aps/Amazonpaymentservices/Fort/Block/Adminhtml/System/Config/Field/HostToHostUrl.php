<?php
/**
 * Host To Host Url
 * php version 8.2.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices_Fort
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/

namespace Amazonpaymentservices\Fort\Block\Adminhtml\System\Config\Field;

/**
 * Host To Host Url
 * php version 8.2.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @link     Amazonpaymentservices
 **/
class HostToHostUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'host_to_host_url.phtml';

    private $_base_currency = '';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Amazonpaymentservices\Fort\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }
    
    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setRenderer($this);
        return $this->_toHtml();
    }
    
    public function getHostUrl()
    {
        return $this->_helper->getReturnUrl('amazonpaymentservicesfort/payment/webhook');
    }
}
