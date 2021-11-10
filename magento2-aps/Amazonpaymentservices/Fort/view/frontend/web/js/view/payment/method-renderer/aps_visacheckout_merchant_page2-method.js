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
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/quote',
        'underscore',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Customer/js/customer-data',
        'uiRegistry',
        'mage/utils/wrapper',
        'visa',
    ],
    function (ko, $, Component, quote, _, fullScreenLoader, setPaymentInformationAction, placeOrderAction, additionalValidators, messageList, $t,customerData) {
        'use strict';
        var visaData = {};
        var self = this;
        
        return Component.extend({
            validateHandler: null,
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-form-visa',
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
            setVisaLogo: function () {
                $('[data-action="setvisaLogo"]').attr('src',window.checkoutConfig.payment.apsFort.aps_fort_visaco.visalogo);
            },
            /**
             * @returns {exports.context}
             */
            context: function () {
                return this;
            },
            
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_visaco.instructions;
            },
            
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            /**
             * @param {Function} handler
             */
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },
            
            /**
             * @param {Function} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            onSuccess: function () {
                this.placeOrder();
            },
            onVisaCheckout: function () {
                var self = this;
                var totals = quote.totals();
                var subTotal = (totals ? totals : quote)['subtotal'];
                
                var totalTax = (totals ? totals : quote)['tax_amount'];
                var shipping = (totals ? totals : quote)['shipping_incl_tax'];
                var discount = (totals ? totals : quote)['discount_amount'];
                var grandTotal    = function () {
                    return parseFloat(subTotal + totalTax + shipping + discount).toFixed(2); }
                
                var currencyCode = (totals ? totals : quote)['quote_currency_code'];
                V.init({
                    apikey : window.checkoutConfig.payment.apsFort.aps_fort_visaco.apiKey,
                    externalProfileId : window.checkoutConfig.payment.apsFort.aps_fort_visaco.profileId,
                    settings : {
                        locale : window.checkoutConfig.payment.apsFort.aps_fort_visaco.locale,
                        countryCode : window.checkoutConfig.defaultCountryId,
                        review : {
                            message : window.checkoutConfig.payment.apsFort.aps_fort_visaco.storeName,
                            buttonAction : $.mage.__("Continue")
                        },
                        threeDSSetup : {
                            threeDSActive : "false"
                        }
                    },
                    paymentRequest : {
                        currencyCode : currencyCode,
                        subtotal : grandTotal,
                    }
                });
                V.on("payment.success", function (payment) {
                    visaData = payment;
                    self.onSuccess();
                });
                V.on("payment.cancel", function (payment) {
                });
                V.on("payment.error", function (payment, error) {
                });
            },
            
            afterPlaceOrder: function () {
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_visaco.response,
                    type: 'get',
                    context: this,
                    data : {callid : visaData.callid},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        var preparedData,
                            msg;
                        if (response.success) {
                            var formId = 'frm_aps_fort_payment';
                            if (jQuery("#"+formId).size()) {
                                jQuery("#"+formId).remove();
                            }
                            $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                            //response.params.form_key = 'abcd';
                            $.each(response.params, function (k, v) {
                                $('<input>').attr({
                                    type: 'hidden',
                                    id: k,
                                    name: k,
                                    value: v
                                }).appendTo($('#'+formId));
                            });
                            
                            $('#'+formId).attr('action', window.checkoutConfig.payment.apsFort.aps_fort_visaco.responseUrl);
                            $('#'+formId).submit();
                            return false;
                        } else {
                            msg = response.error_messages;
                            if (typeof (msg) === 'object') {
                                alert({
                                    content: msg.join("\n")
                                });
                            }
                            if (msg) {
                                alert({
                                    content: msg
                                });
                            }
                        }
                    }
                });

                return false;
            },
            isChecked: ko.computed(function () {
                var method = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if (method == 'aps_fort_visaco') {
                    var totals = quote.totals();
                    var subTotal = (totals ? totals : quote)['subtotal'];
                    
                    var totalTax = (totals ? totals : quote)['tax_amount'];
                    var shipping = (totals ? totals : quote)['shipping_incl_tax'];
                    var discount = (totals ? totals : quote)['discount_amount'];
                    var grandTotal    = function () {
                        return parseFloat(subTotal + totalTax + shipping + discount).toFixed(2); }
                    
                    var currencyCode = (totals ? totals : quote)['quote_currency_code'];
                    V.init({
                        apikey : window.checkoutConfig.payment.apsFort.aps_fort_visaco.apiKey,
                        externalProfileId : window.checkoutConfig.payment.apsFort.aps_fort_visaco.profileId,
                        settings : {
                            locale : window.checkoutConfig.payment.apsFort.aps_fort_visaco.locale,
                            countryCode : window.checkoutConfig.defaultCountryId,
                            review : {
                                message : window.checkoutConfig.payment.apsFort.aps_fort_visaco.storeName,
                                buttonAction : $.mage.__("Continue")
                            },
                            threeDSSetup : {
                                threeDSActive : "false"
                            }
                        },
                        paymentRequest : {
                            currencyCode : currencyCode,
                            subtotal : grandTotal,
                        }
                    });
                    V.on("payment.success", function (payment) {
                        visaData = payment;
                        self.onSuccess();
                    });
                    V.on("payment.cancel", function (payment) {
                    });
                    V.on("payment.error", function (payment, error) {
                    });
                }
                return method;
            })
        });
    }
);
