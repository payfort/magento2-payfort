<?php

namespace Amazonpaymentservices\Fort\Model\ResourceModel\Apsorderparams;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'aps_order_params_collection';
    protected $_eventObject = 'order_param_collection';

    protected function _construct()
    {
        $this->_init('Amazonpaymentservices\Fort\Model\Apsorderparams', 'Amazonpaymentservices\Fort\Model\ResourceModel\Apsorderparams');
    }
}
