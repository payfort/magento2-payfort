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
        'uiRegistry',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/customer-data',
        'Magento_Catalog/js/price-utils'
    ],
    function (ko, $, registry, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, customerData, priceUtils) {
        'use strict';
        var promise = '';
        $("#applePay").addClass(window.checkoutConfig.payment.apsFort.aps_apple.appleButtonClass);
        $(document).on(
            'submit',
            'form',
            function (e) {
                var formKeyElement,
                    existingFormKeyElement,
                    isKeyPresentInForm,
                    form = $(e.target),
                    formKey = $('input[name="form_key"]').val();
                existingFormKeyElement = form.find('input[name="form_key"]');
                isKeyPresentInForm = existingFormKeyElement.length;
                if (isKeyPresentInForm && existingFormKeyElement.attr('auto-added-form-key') === '1') {
                    isKeyPresentInForm = form.find('> input[name="form_key"]').length;
                }
                $('#frm_aps_fort_apple_payment input[name=form_key]').remove();
                $('#frm_aps_fort_apple_payment input[name=form_key]').attr("disabled", "disabled");
            }
        );
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-apple'
            },
            getCode: function () {
                return 'aps_apple';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            redirectAfterPlaceOrder: false,

            isApplePayChecked: function () {
                var applePayRadio = document.getElementById("aps_apple");
                return applePayRadio.checked;
            },
            beforeApplePay: function () {
                document.getElementById("applePay").disabled = false;
                if (window.ApplePaySession) {
                    if (ApplePaySession.canMakePayments) {
                        $("#applePay").addClass(window.checkoutConfig.payment.apsFort.aps_apple.appleButtonClass);
                        if (this.isApplePayChecked()) {
                            document.getElementById("applePay").style.display = "block";
                        }
                        if($('.bss-onestepcheckout').length && this.isApplePayChecked()){
                            var button = document.getElementsByClassName('btn-placeorder')[0];
                            button.style = "display:none;";
                        }
                    } else {
                        $(".apple-err").text('');
                        $("#applePay").remove();
                        $(".payment-method.apple-pay").remove();
                    }
                } else {
                    $(".apple-err").text('');
                    $("#applePay").remove();
                    $(".payment-method.apple-pay").remove();
                }
            },
            afterPlaceOrder : function () {
                if(registry) {
                    var shippingAddressComponent  = registry.get('checkout.steps.shipping-step.shippingAddress');
                    var validateShippingInfomation;
                    if ($('.selected-store-pickup').length) {
                        validateShippingInfomation = true;
                    } else {
                        validateShippingInfomation = shippingAddressComponent.validateShippingInformation();
                    }
                    if(!validateShippingInfomation) return false;
                }
                this.placeOrder();

                var isBaseCurrency = window.checkoutConfig.payment.apsFort.configParams.gatewayCurrency != 'front';

                var totals = quote.totals();
                var runningAmount = (totals ? totals : quote)[isBaseCurrency ? 'base_subtotal' : 'subtotal'], runningAmountFormatted = '';
                var priceFormat = quote.getPriceFormat();
                priceFormat.pattern = "%s";
                runningAmountFormatted = priceUtils.formatPrice(runningAmount, priceFormat, false);
                // runningAmount = runningAmount.replace(/,/g, '');
                runningAmount = parseFloat(runningAmount);

                var totalTax = (totals ? totals : quote)[isBaseCurrency ? 'base_tax_amount' : 'tax_amount'], totalTaxFormatted = '';
                totalTaxFormatted = priceUtils.formatPrice(totalTax, priceFormat, false);
                // totalTax = totalTax.replace(/,/g, '');
                totalTax = parseFloat(totalTax);

                var runningPP = 0, runningPPFormatted = '';
                var displayPP = 0, displayPPFormatted = '';
                if (window.checkoutConfig.payment.apsFort.aps_apple.shippingconfig == 0) {

                    if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 1) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_amount' : 'shipping_amount'];
                    } else if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 2) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_incl_tax' : 'shipping_incl_tax'];
                    } else if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 3) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_amount' : 'shipping_amount'];
                    }

                } else {
                    if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 1) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_amount' : 'shipping_amount'];
                    } else if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 2) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_incl_tax' : 'shipping_incl_tax'];
                    } else if (window.checkoutConfig.payment.apsFort.aps_apple.shippingdisplayconfig == 3) {
                        displayPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_amount' : 'shipping_amount'];
                    }
                }

                runningPP = (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_amount' : 'shipping_amount'];
                runningPPFormatted = priceUtils.formatPrice(runningPP, priceFormat, false);
                // runningPP = runningPP.replace(/,/g, '');
                runningPP = parseFloat(runningPP);

                displayPPFormatted = priceUtils.formatPrice(displayPP, priceFormat, false);
                // displayPP = displayPP.replace(/,/g, '');
                displayPP = parseFloat(displayPP);
                var runningShipDiscount = Math.abs( (totals ? totals : quote)[isBaseCurrency ? 'base_shipping_discount_amount' : 'shipping_discount_amount']), runningShipDiscountFormatted = '';
                runningShipDiscountFormatted = priceUtils.formatPrice(runningShipDiscount, priceFormat, false);
                // runningShipDiscount = runningShipDiscount.replace(/,/g, '');
                runningShipDiscount = parseFloat(runningShipDiscount);

                var discountAmount = Math.abs( (totals ? totals : quote)[isBaseCurrency ? 'base_discount_amount' : 'discount_amount']), discountAmountFormatted = '' ;
                discountAmountFormatted = priceUtils.formatPrice(discountAmount, priceFormat, false);
                // discountAmount = discountAmount.replace(/,/g, '');
                discountAmount = parseFloat(discountAmount);

                var currencyCode = (totals ? totals : quote)[isBaseCurrency ? 'base_currency_code' : 'quote_currency_code'];

                var runningTotal    = function () {
                    var runningAmount1 = parseFloat(runningAmount);
                    var runningPP1 = parseFloat(runningPP);
                    var totalTax1 = parseFloat(totalTax);
                    var discountAmount1 = parseFloat(discountAmount);
                    var tempTotals =  (runningAmount1 + runningPP1 + totalTax1 - discountAmount1), tempTotalsFormatted = '';
                    tempTotalsFormatted = priceUtils.formatPrice(tempTotals, priceFormat, false);
                    // tempTotals = tempTotals.replace(/,/g, '');
                    tempTotals = parseFloat(tempTotals);
                    return tempTotals;
                }
                var shippingOption = "";

                var cartItems = customerData.get('cart')().items;

                var shippingAddress = quote.shippingAddress();
                //var countryCode = (shippingAddress ? shippingAddress : quote)['countryId'];
                var countryCode = window.checkoutConfig.payment.apsFort.aps_apple.storeCountryCode;

                var newItemArray = [];
                var x = 0;
                var subTotal = 0.00;
                cartItems.forEach(function (arrayItem) {

                    subTotal = subTotal + parseFloat(arrayItem.product_price_value * arrayItem.qty);
                });

                newItemArray[x++] = {type: 'final',label: 'Subtotal', amount: runningAmount};
                if (discountAmount > parseFloat(0)) {
                    newItemArray[x++] = {type: 'final',label: 'Discount', amount: discountAmount };
                }
                newItemArray[x++] = {type: 'final',label: 'Shipping Fees', amount: displayPP };

                newItemArray[x++] = {type: 'final',label: 'Taxes', amount: totalTax };

                function getShippingOptions()
                {
                    return [{label: 'Standard Shipping', amount: runningPPFormatted, detail: '3-5 days', identifier: 'domestic_std'}];
                }
                var storeName = window.checkoutConfig.payment.apsFort.aps_apple.storeName;
                var paymentRequest = {
                    currencyCode: currencyCode,
                    countryCode: countryCode,
                    lineItems: newItemArray,
                    total: {
                        label: storeName,
                        amount: runningTotal()
                    },
                    supportedNetworks: window.checkoutConfig.payment.apsFort.aps_apple.appleSupportedNetwork.split(','),
                    merchantCapabilities: [ 'supports3DS' ]
                };

                var supportedNetworks = window.checkoutConfig.payment.apsFort.aps_apple.appleSupportedNetwork.split(',');
                if(supportedNetworks.indexOf('mada') >= 0) {
                    var session = new ApplePaySession(5, paymentRequest);
                } else {
                    var session = new ApplePaySession(3, paymentRequest);
                }
                session.onvalidatemerchant = function (event) {
                    var promise = performValidation(event.validationURL);
                    promise.then(function (merchantSession) {
                        session.completeMerchantValidation(merchantSession);
                    }).catch(function (validationErr) {
                        // You should show an error to the user, e.g. 'Apple Pay failed to load.'
                        session.abort();
                    });
                }

                function performValidation(valURL)
                {
                    return new Promise(function (resolve, reject) {
                        var xhr = new XMLHttpRequest();
                        xhr.onload = function () {
                            var data = JSON.parse(this.responseText);
                            resolve(data);
                        };
                        xhr.onerror = reject;
                        xhr.open('POST',window.checkoutConfig.payment.apsFort.aps_apple.appleValidation);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.send('valURL=' + valURL);
                    }).catch(function (validationErr) {
                        // You should show an error to the user, e.g. 'Apple Pay failed to load.'
                        session.abort();
                    });
                }

                session.onpaymentmethodselected = function (event) {
                    var newTotal = { type: 'final', label: storeName, amount: runningTotal() };

                    session.completePaymentMethodSelection(newTotal, newItemArray);
                }
                var paymentData = {};
                session.onpaymentauthorized = function (event) {
                    var promise = sendPaymentToken(event.payment.token);
                    promise.then(function (success) {
                        var status;
                        if (success) {
                            status = ApplePaySession.STATUS_SUCCESS;
                            sendPaymentToAps(paymentData);
                        } else {
                            status = ApplePaySession.STATUS_FAILURE;
                        }
                        session.completePayment(status);
                    }).catch(function (validationErr) {
                        // You should show an error to the user, e.g. 'Apple Pay failed to load.'
                        session.abort();
                    });
                }

                session.oncancel = function (event) {
                    window.location.href = window.checkoutConfig.payment.apsFort.aps_apple.cancelUrl;
                }

                function sendPaymentToken(paymentToken)
                {
                    return new Promise(function (resolve, reject) {
                        paymentData = paymentToken;
                        resolve(true);
                    }).catch(function (validationErr) {
                        // You should show an error to the user, e.g. 'Apple Pay failed to load.'
                        session.abort();
                    });
                }

                function sendPaymentToAps(data)
                {
                    var formId = 'frm_aps_fort_apple_payment';
                    if (jQuery("#"+formId).length > 0) {
                        jQuery("#"+formId).remove();
                    }

                    $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                    var response = {};
                    response.data = JSON.stringify({ "data" : data});
                    $.each(response, function (k, v) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: k,
                            name: k,
                            value: v
                        }).appendTo($('#'+formId));
                    });

                    $('#'+formId).attr('action', window.checkoutConfig.payment.apsFort.aps_apple.appleToAps);
                    $('#'+formId).submit();
                }

                session.begin();
            },
            isChecked: ko.computed(function () {
                var checked = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if($('.bss-onestepcheckout').length){
                    if(document.getElementById("applePay") && checked == 'aps_apple'){
                        var button = document.getElementsByClassName('btn-placeorder')[0];
                        button.style = "display:none;";
                    }
                }
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