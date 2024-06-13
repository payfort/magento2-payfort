/**
 * APS Magento JS component
 *
 * @category    APS
 * @package     APS
 */
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'mage/translate',
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-payment'
            },
            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_fort_naps.title);
            },
            getCode: function () {
                return 'aps_fort_naps';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_naps.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            afterPlaceOrder : function () {
                var quoteId = window.checkoutConfig.quoteData.entity_id;
                var redirectUrl = window.checkoutConfig.payment.apsFort.aps_fort_naps.redirectUrl;
                var hasQueryParams = redirectUrl.includes('?');
                var delimiter = hasQueryParams ? '&' : '?';
                var redirectUrlWithQuoteId = redirectUrl + delimiter + 'quoteId=' + encodeURIComponent(quoteId);
                $.mage.redirect(redirectUrlWithQuoteId);
            },
            isChecked: ko.computed(function () {
                var checked = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if (window.checkoutConfig.payment.apsFort.configParams.gatewayCurrency == 'front') {
                    if (checked) {
                        $('.totals.charge').hide();
                    }
                }
                return checked;
            }),
        });
    }
);
