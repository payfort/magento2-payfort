<?php
namespace Amazonpaymentservices\Fort\Model\ResourceModel;

class Apsorderparams extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('aps_order_params', 'id');
    }
}
