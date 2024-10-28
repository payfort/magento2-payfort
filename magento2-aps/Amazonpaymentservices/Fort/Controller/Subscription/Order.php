<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazonpaymentservices\Fort\Controller\Subscription;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\ObjectManager;

class Order extends \Magento\Sales\Controller\AbstractController\View implements OrderInterface, HttpGetActionInterface
{
    
}
