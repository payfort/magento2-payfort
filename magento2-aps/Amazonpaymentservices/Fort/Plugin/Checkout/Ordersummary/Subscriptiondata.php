<?php

namespace Amazonpaymentservices\Fort\Plugin\Checkout\Ordersummary;

use Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\App\ObjectManager;

class Subscriptiondata
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        $items = $result['totalsData']['items'];
        foreach ($items as $index => $item) {

            $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
            $productEntityId = $quoteItem->getProduct()->getData('entity_id');

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

                $result['quoteItemData'][$index]['aps_product_subscription'] = true;
                $result['quoteItemData'][$index]['aps_product_subscription_frequency'] = $prodApsSubIntervalCount['value'] == 1 ? $prodApsSubInterval['value'] : $prodApsSubInterval['value'].'s';
                $result['quoteItemData'][$index]['aps_product_subscription_frequency_count'] = sprintf("%02d", $prodApsSubIntervalCount['value']);
                
            } else {

                $result['quoteItemData'][$index]['aps_product_subscription'] = false;
                $result['quoteItemData'][$index]['aps_product_subscription_frequency'] = null;
                $result['quoteItemData'][$index]['aps_product_subscription_frequency_count'] = null;
            }
        }
        return $result;
    }
}
