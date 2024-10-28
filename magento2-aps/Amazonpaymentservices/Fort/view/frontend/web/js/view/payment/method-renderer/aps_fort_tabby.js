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
        'Magento_Customer/js/model/customer',
        'mage/translate'
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, customer) {
        'use strict';
        var self = '';
        var tabbyToken = 'newCard';
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-tabby'
            },
            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_fort_tabby.title)
            },
            getTabbyToken: function () {
                return [];
            },
            newCard: function () {
                return true;
            },
            getCode: function () {
                return 'aps_fort_tabby';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            setTabbyLogo: function () {
                $('[data-action="setTabbyLogo"]').attr('src',window.checkoutConfig.payment.apsFort.aps_fort_tabby.tabbyLogo);
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_tabby.instructions;
            },
            getTabbyCard : function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_tabby.data;
            },
            afterTokenRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_tabby.tokenstatus == 0) {
                    $('.ccform .newcardtype').css('display', 'none');
                    $('.ccform #cc-co-transparent-form').css('display', '');
                    tabbyToken = 'newCard';
                } else if (window.checkoutConfig.payment.apsFort.aps_fort_tabby.hasOwnProperty('data') == false) {
                    $('.ccform .newcardtype').css('display', 'none');
                    $('.ccform #cc-co-transparent-form').css('display', '');
                    tabbyToken = 'newCard';
                }
            },
            preparePayment: function () {
                tabbyToken = $("input[name='tabbytoken']:checked").val();
                if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_fort_tabby.hasOwnProperty('data') == false) {
                    tabbyToken = 'newCard' ;
                }
                if (window.checkoutConfig.payment.apsFort.aps_fort_tabby.tokenstatus == 0) {
                    tabbyToken = 'newCard';
                }
                this.placeOrder();
            },
            afterPlaceOrder : function () {
                console.log('1');
                console.log(tabbyToken);
                if(tabbyToken == 'newCard') {
                    console.log('2');
                    var randomNum = new Date().getTime();
                    $.mage.redirect(window.checkoutConfig.payment.apsFort.aps_fort_tabby.redirectUrl+"?id="+randomNum);
                } else {
                    console.log('3');
                    console.log(window.checkoutConfig.payment.apsFort.aps_fort_tabby.ajaxUrlToken);
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_fort_tabby.ajaxUrlToken,
                        type: 'post',
                        context: this,
                        data:{tabbyToken:tabbyToken},
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            if (response.success) {
                                var formId = 'frm_aps_fort_payment';
                                if (jQuery("#"+formId).size()) {
                                    jQuery("#"+formId).remove();
                                }
                                $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                                $.each(response.params, function (k, v) {
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
                                let msg = response.error_messages;
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
                }
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
            redirectAfterPlaceOrder: false
        });
    }
);
