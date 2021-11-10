<?php
namespace Amazonpaymentservices\Fort\Model\ResourceModel\Paymentcapture;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'aps_capture_payment_collection';
    protected $_eventObject = 'capture_collection';
    
    protected function _construct()
    {
        $this->_init('Amazonpaymentservices\Fort\Model\Paymentcapture', 'Amazonpaymentservices\Fort\Model\ResourceModel\Paymentcapture');
    }
}
