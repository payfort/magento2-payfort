/**
 * Aps_Fort Magento JS component
 *
 * @category    Aps
 * @package     Aps_Fort
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
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-payment'
            },
            getCode: function () {
                return 'aps_knet';
            },
            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_knet.title);
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_knet.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            afterPlaceOrder : function () {
                $.mage.redirect(window.checkoutConfig.payment.apsFort.aps_knet.redirectUrl);
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
