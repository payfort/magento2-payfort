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
        'Amazonpaymentservices_Fort/js/action/post-redirect'
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, postRedirect) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-payment'
            },
            getCode: function () {
                return 'aps_fort_visaco';
            },
            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_fort_visaco.title);
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_visaco.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            afterPlaceOrder : function () {
                postRedirect(window.checkoutConfig.payment.apsFort.aps_fort_visaco.redirectUrl);
            }
        });
    }
);