<?php

namespace Amazonpaymentservices\Fort\Block\Subscription;

use \Magento\Framework\App\ObjectManager;

class CheckoutAdditionalInfo extends \Magento\Framework\View\Element\Template
{

    /**
     * @return additional information data
     */
    public function getSubscriptionData($productEntityId)
    {
        $resource = ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_enabled');
        $apsSubEnabled = $connection->fetchRow($query);

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval');
        $apsSubInterval = $connection->fetchRow($query);

        $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval_count');
        $apsSubIntervalCount = $connection->fetchRow($query);

        /* @isSubscriptionProduct */
        $query = $connection->select()->from(['table'=>'catalog_product_entity_int'], ['value'])->where('table.attribute_id=?', $apsSubEnabled['attribute_id'])->where('table.entity_id=?', $productEntityId);
        $prodApsSubEnabled = $connection->fetchRow($query);

        if (!empty($prodApsSubEnabled) && $prodApsSubEnabled['value'] == 1) {
            $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubInterval['attribute_id'])->where('table.entity_id=?', $productEntityId);
            $prodApsSubInterval = $connection->fetchRow($query);

            $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubIntervalCount['attribute_id'])->where('table.entity_id=?', $productEntityId);
            $prodApsSubIntervalCount = $connection->fetchRow($query);

            $atts = [
                "aps_product_subscription" => true,
                "aps_product_subscription_frequency" => $prodApsSubIntervalCount['value'] == 1 ? $prodApsSubInterval['value'] : $prodApsSubInterval['value'].'s',
                "aps_product_subscription_frequency_count" => sprintf("%02d", $prodApsSubIntervalCount['value'])
            ];
            
        } else {
            $atts = [
                "aps_product_subscription" => false,
                "aps_product_subscription_frequency" => null,
                "aps_product_subscription_frequency_count" => null
            ];
        }
        return $atts;
    }
}
