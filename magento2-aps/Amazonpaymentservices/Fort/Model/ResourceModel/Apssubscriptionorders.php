<?php
namespace Amazonpaymentservices\Fort\Model\ResourceModel;

class Apssubscriptionorders extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('aps_subscription_orders', 'id');
    }
}
