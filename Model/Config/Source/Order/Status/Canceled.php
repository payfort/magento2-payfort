<?php

namespace Payfort\Fort\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Order Status source model
 */
class Canceled extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_CANCELED];
}
