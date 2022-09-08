<?php
namespace Amazonpaymentservices\Fort\Model\ResourceModel;

class Apssubscriptions extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('aps_subscriptions', 'id');
    }
}
