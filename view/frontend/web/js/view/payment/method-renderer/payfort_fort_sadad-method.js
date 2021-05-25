/**
 * Payfot_Fort Magento JS component
 *
 * @category    Payfort
 * @package     Payfot_Fort
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
                template: 'Payfort_Fort/payment/payfort-form'
            },
            getCode: function() {
                return 'payfort_fort_sadad';
            },
            isActive: function() {
                return true;
            },
            context: function() {
                return this;
            },
            getInstructions: function() {
                return window.checkoutConfig.payment.payfortFort.payfort_fort_sadad.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            afterPlaceOrder : function() {
                $.mage.redirect(window.checkoutConfig.payment.payfortFort.payfort_fort_sadad.redirectUrl);
            },
            isChecked: ko.computed(function () {
                var checked = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if(window.checkoutConfig.payment.payfortFort.configParams.gatewayCurrency == 'front') {
                    if(checked) {
                        $('.totals.charge').hide();
                    }
                }
                return checked;
            }),
        });
    }
);
