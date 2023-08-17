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
        'Magento_Customer/js/model/customer'
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, customer) {
        'use strict';
        var self = '';
        var stcToken = 'newCard';
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-stc'
            },
            getTitle: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_stc.title
            },
            getStcToken: function () {
                return [];
            },
            newCard: function () {
                return true;
            },
            getCode: function () {
                return 'aps_fort_stc';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            setStcLogo: function () {
                $('[data-action="setStcLogo"]').attr('src',window.checkoutConfig.payment.apsFort.aps_fort_stc.stcLogo);
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_stc.instructions;
            },
            getStcCard : function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_stc.data;
            },
            afterTokenRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_stc.tokenstatus == 0) {
                    $('.ccform .newcardtype').css('display', 'none');
                    $('.ccform #cc-co-transparent-form').css('display', '');
                    stcToken = 'newCard';
                } else if (window.checkoutConfig.payment.apsFort.aps_fort_stc.hasOwnProperty('data') == false) {
                    $('.ccform .newcardtype').css('display', 'none');
                    $('.ccform #cc-co-transparent-form').css('display', '');
                    stcToken = 'newCard';
                }
            },
            preparePayment: function () {
                stcToken = $("input[name='stctoken']:checked").val();
                if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_fort_stc.hasOwnProperty('data') == false) {
                    stcToken = 'newCard' ;
                }
                if (window.checkoutConfig.payment.apsFort.aps_fort_stc.tokenstatus == 0) {
                    stcToken = 'newCard';
                }
                this.placeOrder();
            },
            afterPlaceOrder : function () {
                console.log('1');
                console.log(stcToken);
                if(stcToken == 'newCard') {
                    console.log('2');
                    var randomNum = new Date().getTime();
                    $.mage.redirect(window.checkoutConfig.payment.apsFort.aps_fort_stc.redirectUrl+"?id="+randomNum);
                } else {
                    console.log('3');
                    console.log(window.checkoutConfig.payment.apsFort.aps_fort_stc.ajaxUrlToken);
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_fort_stc.ajaxUrlToken,
                        type: 'post',
                        context: this,
                        data:{stcToken:stcToken},
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            if (response.success) {
                                var formId = 'frm_aps_fort_payment';
                                if (jQuery("#"+formId).length) {
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