<?php

namespace Amazonpaymentservices\Fort\Model\ResourceModel\Apssubscriptions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'aps_subscription_collection';
    protected $_eventObject = 'subscription_collection';
    
    protected function _construct()
    {
        $this->_init('Amazonpaymentservices\Fort\Model\Apssubscriptions', 'Amazonpaymentservices\Fort\Model\ResourceModel\Apssubscriptions');
    }
}
