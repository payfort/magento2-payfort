<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Amazonpaymentservices\Fort\Plugin\Checkout\CustomerData;

use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\ObjectManager;

class DefaultItem
{
    /**
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $helper;

    /**
     * @param \Amazonpaymentservices\Fort\Helper\Data $helper
     */
    public function __construct(
        \Amazonpaymentservices\Fort\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    public function aroundGetItemData($subject, \Closure $proceed, Item $item)
    {
        // is the Recurring Product feature enabled?
        $isRecurringEnabled = (int)$this->helper->getConfig('payment/aps_recurring/active') === 1;

        $data = $proceed($item);

        $data["aps_product_subscription"] = false;
        $data["aps_product_subscription_frequency"] = null;
        $data["aps_product_subscription_frequency_count"] = null;

        if (!$isRecurringEnabled) {
            return $data;
        }

        $product = $item->getProduct();

        $resource = ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $query = $connection->select()->from(['table' => 'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
        $apsSubEnabled = $connection->fetchRow($query);

        $query = $connection->select()->from(['table' => 'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval');
        $apsSubInterval = $connection->fetchRow($query);

        $query = $connection->select()->from(['table' => 'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval_count');
        $apsSubIntervalCount = $connection->fetchRow($query);

        /* @isSubscriptionProduct */
        $query = $connection->select()->from(['table' => 'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $product->getId());
        $prodApsSubEnabled = $connection->fetchRow($query);

        if (!empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {

            $query = $connection->select()->from(['table' => 'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubInterval['attribute_id'])->where('table.entity_id=?', $product->getId());
            $prodApsSubInterval = $connection->fetchRow($query);

            $query = $connection->select()->from(['table' => 'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubIntervalCount['attribute_id'])->where('table.entity_id=?', $product->getId());
            $prodApsSubIntervalCount = $connection->fetchRow($query);

            $data['aps_product_subscription'] = true;
            $data['aps_product_subscription_frequency'] = $prodApsSubIntervalCount['value'] == 1 ? $prodApsSubInterval['value'] : $prodApsSubInterval['value'] . 's';
            $data['aps_product_subscription_frequency_count'] = sprintf("%02d", $prodApsSubIntervalCount['value']);

        }

        return $data;
    }
}
