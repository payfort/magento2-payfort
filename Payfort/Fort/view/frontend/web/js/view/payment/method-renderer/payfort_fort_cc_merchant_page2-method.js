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
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/quote',
        'underscore',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'uiRegistry',
        'mage/utils/wrapper'
    ],
    function (ko, $, Component, quote, _, fullScreenLoader, setPaymentInformationAction, placeOrderAction, additionalValidators, messageList, $t) {
        'use strict';
        return Component.extend({
            placeOrderHandler: null,
            validateHandler: null,
            defaults: {
                template: 'Payfort_Fort/payment/payfort-form-merchant-page2',
                isCcFormShown: true,
            },
            
            getCode: function() {
                return 'payfort_fort_cc';
            },
            
            isActive: function() {
                return true;
            },
            
            /**
             * @returns {exports.context}
             */
            context: function() {
                return this;
            },
            
            getInstructions: function() {
                return window.checkoutConfig.payment.payfortFort.payfort_fort_cc.instructions;
            },
            
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            /**
             * @param {Function} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            
            /**
             * @param {Function} handler
             */
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },
            
            /**
             * @returns {Boolean}
             */
            isShowLegend: function () {
                return true;
            },
            
            /**
             * @returns {*|String}
             */
            canInitialise: function () {
                return true;
            },
            
            /**
             * @function
             */
            initVars: function () {
                this.canSaveCard = false;
                this.isPaymentProcessing = null;
                this.quoteBaseGrandTotals = quote.totals()['base_grand_total'];
            },
            
            /**
             * @override
             */
            initObservable: function () {
                var self = this;

                this.initVars();
                this._super()
                    .track('availableCcValues')
                    .observe([
                        'paymentMethodNonce',
                        'verified'
                    ]);

                // subscribe on billing address update
                /*quote.billingAddress.subscribe(function () {
                    self.updateAvailableTypeValues();
                });*/

                return this;
            },
            
            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        
                    }
                };
            },
            
            /**
             * Get list of available CC types
             */
            /*getCcAvailableTypes: function () {
                return window.checkoutConfig.payment.ccform.availableTypes[this.getCode()];
            },*/
            
            /**
             * @returns {*}
             */
            isCcDetectionEnabled: function () {
                return true;
            },
            
            
            /**
             * @returns {String}
             */
            getCssClass: function () {
                return 'field type required';
            },
            
            /**
             * Update list of available CC types values
             */
            updateAvailableTypeValues: function () {
                this.availableCcValues = this.getCcAvailableTypesValues();
            },
            
            /**
             * Prepare and process payment information
             */
            preparePayment: function () {
                var self = this,
                    cardInfo = null;

                /*if (this.validateHandler() && this.validate() && additionalValidators.validate()) {
                    
                }
                return false;*/
                if (this.validateHandler()) {
                    this.messageContainer.clear();
                    this.quoteBaseGrandTotals = quote.totals()['base_grand_total'];

                    /*this.isPaymentProcessing = $.Deferred();
                    $.when(this.isPaymentProcessing).done(
                        function () {
                            self.placeOrder();
                        }
                    ).fail(
                        function (result) {
                            self.handleError(result);
                        }
                    );*/

                    cardInfo = {
                        number: this.creditCardNumber(),
                        expirationMonth: this.creditCardExpMonth(),
                        expirationYear: this.creditCardExpYear(),
                        cvv: this.creditCardVerificationNumber()
                    };
                    this.placeOrder();
                }
            },
            
            afterPlaceOrder: function() {
                var cardInfo = {
                        number: this.creditCardNumber(),
                        expirationMonth: this.creditCardExpMonth(),
                        expirationYear: this.creditCardExpYear(),
                        cvv: this.creditCardVerificationNumber(),
                        holderName: $('#payfort_fort_cc_cc_holder_name').val()
                    };
                var expMonth = cardInfo.expirationMonth;
                if(expMonth.length == 1) {
                    expMonth = '0'+expMonth;
                }
                var expYear = cardInfo.expirationYear;
                expYear = expYear.substr(expYear.length - 2);
                var expiryDate = expYear+''+expMonth;
                cardInfo.expiryDate = expiryDate;
                $.ajax({
                    url: window.checkoutConfig.payment.payfortFort.payfort_fort_cc.ajaxUrl,
                    type: 'get',
                    context: this,
                    dataType: 'json',
                    success: function(response) {
                        var preparedData,
                            msg;
                        if (response.success) {
                            var formId = 'frm_payfort_fort_payment';
                            if(jQuery("#"+formId).size()) {
                                jQuery( "#"+formId ).remove();
                            }
                            $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                            response.params.card_number = cardInfo.number;
                            response.params.card_holder_name = cardInfo.holderName;
                            response.params.card_security_code = cardInfo.cvv;
                            response.params.expiry_date = cardInfo.expiryDate;
                            response.params.form_key = "abcd";
                            $.each(response.params, function(k, v){
                                $('<input>').attr({
                                    type: 'hidden',
                                    id: k,
                                    name: k,
                                    value: v
                                }).appendTo($('#'+formId));
                            });
                            
                            $('#'+formId +' input[name=form_key]').attr("disabled", "disabled");
                            
                            $('#'+formId).attr('action', response.url);
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
        });
    }
);