<?php
namespace Amazonpaymentservices\Fort\Model;

class Paymentcapture extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'aps_capture_payment';

    protected $_cacheTag = 'aps_capture_payment';

    protected $_eventPrefix = 'aps_capture_payment';
    
    protected function _construct()
    {
        $this->_init('Amazonpaymentservices\Fort\Model\ResourceModel\Paymentcapture');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}
