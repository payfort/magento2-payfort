define(
    [
        'uiComponent'
    ],
    function (Component) {
        "use strict";
        var quoteItemData = window.checkoutConfig.quoteItemData;
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/summary/item/details'
            },
            quoteItemData: quoteItemData,
            getValue: function (quoteItem) {
                return quoteItem.name;
            },
            getApsProductSubscription: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                if (item.aps_product_subscription == true) {
                    return true;
                } else {
                    return false;
                }
            },
            getApsProductSubscriptionFrequency: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                if (item.aps_product_subscription_frequency) {
                    return item.aps_product_subscription_frequency;
                } else {
                    return '';
                }
            },
            getApsProductSubscriptionFrequencyCount: function (quoteItem) {
                var item = this.getItem(quoteItem.item_id);
                if (item.aps_product_subscription_frequency_count) {
                    return item.aps_product_subscription_frequency_count;
                } else {
                    return '';
                }
            },
            getItem: function (item_id) {
                var itemElement = null;
                _.each(this.quoteItemData, function (element, index) {
                    if (element.item_id == item_id) {
                        itemElement = element;
                    }
                });
                return itemElement;
            }
        });
    }
);