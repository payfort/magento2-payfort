<?php
namespace Payfort\Fort\view\frontend\web;
 
class EnableMadaLogo extends \Payfort\Fort\Helper\Data
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;
    
    /**
     * @var \Payfort\Fort\Helper\Data
     */
    protected $_helper;
 
    /**
     * Plugin constructor.
     *
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Payfort\Fort\Helper\Data $helper  
    )
    {
        $this->assetRepo = $assetRepo;
        $this->_helper = $helper;
    }
 
    /**
     * @param \Magento\Payment\Model\CcConfigProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetIcons(\Magento\Payment\Model\CcConfigProvider $subject, $result)
    {
        $madaBranding = $this->_helper->getConfig('payment/payfort_fort_cc/mada_branding');
        $baseCurrency                    = $this->_helper->getBaseCurrency();
        $frontCurrency                   = $this->_helper->getFrontCurrency();
        $currency                        = $this->_helper->getFortCurrency($baseCurrency, $frontCurrency);        
        
        if ($madaBranding=='Enabled' && $currency == 'SAR') {
            $result['MA']['url']    = $this->assetRepo->getUrl('Payfort_Fort::images/methods/mada-logo.png');
            $result['MA']['width']  = 46;
            $result['MA']['height'] = 30;
            $result['MA']['title']  = 'mada';
        }
        return $result;
    }
}