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
        var vaultSelected = '';
        var self = '';
        if (!customer.isLoggedIn()) {
            vaultSelected = 'newCard';
        }
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
                $('#frm_aps_fort_payment input[name=form_key]').remove();
                $('#frm_aps_fort_payment input[name=form_key]').attr("disabled", "disabled");
            }
        );
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-form'
            },
            getTitle: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_cc.mada == 'yes' || window.checkoutConfig.payment.apsFort.aps_fort_cc.meeza == 'yes') {
                    return $.mage.__('mada debit card / Credit Cards');
                }
                return window.checkoutConfig.payment.apsFort.aps_fort_cc.title
            },
            afterVaultRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == false) {
                    $('.ccform .newcardtype').css('display', 'none');
                }
            },
            getCode: function () {
                return 'aps_fort_cc';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            getVault : function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_vault.data;
            },
            newCard: function () {
                self = this;
                vaultSelected = $("input[name='vaultHash1']:checked").val();
                $('.cvv-error').remove();
                $(".vault .label-cvv").addClass('cvv-hide');
                $('.vault .input-text').prop("disabled", true);
                if (vaultSelected == 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'standard') {
                    $("#co-transparent-form").css('display','');
                } else if (vaultSelected == 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'redirection') {
                    $("#co-transparent-form").css('display','none');
                } else if (vaultSelected != 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'standard') {
                    $("input[value='"+vaultSelected+"']").parent('.vault').find('.label-cvv').removeClass('cvv-hide');
                    $("input[value='"+vaultSelected+"']").parent('.vault').find('.input-text').prop("disabled", false);
                    $("input[value='"+vaultSelected+"']").parent('.vault').find('.input-text').val('');
                } else if (vaultSelected != 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'redirection') {
                    
                }
                return true;
            },
            getInstructions: function () {
                //return window.checkoutConfig.payment.apsFort.aps_fort_cc.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,

            preparePayment: function () {
                $('.cvv-error').remove();
                $('.ccform .error-ccform').html('');
                if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == false) {
                    vaultSelected = 'newCard';
                    this.placeOrder();
                } else if (customer.isLoggedIn() && ($("input[name='vaultHash1']:checked").val() == undefined )) {
                    $('.error-ccform').html($.mage.__('Please select a card.'));
                    return false;
                } else if (customer.isLoggedIn() && ($("input[name='vaultHash1']:checked").val() != 'newCard' ) && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'standard') {
                    if ($("input[value='"+vaultSelected+"']").parent('.vault').find('.input-text').val().length === 0) {
                        $('input[name="cvv"]').after('<span class="cvv-error">'+$.mage.__('CVV is mandatory')+'</span>');
                        return false;
                    } else {
                        this.placeOrder();
                    }
                } else {
                    this.placeOrder();
                }
            },
            
            afterPlaceOrder : function () {
                if (vaultSelected == 'newCard') {
                    var randomNum = new Date().getTime();
                    $.mage.redirect(window.checkoutConfig.payment.apsFort.aps_fort_cc.redirectUrl+"?id="+randomNum);
                } else if (vaultSelected != 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'redirection') {
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_fort_vault.ajaxVaultUrl,
                        type: 'post',
                        context: this,
                        data:{publicHash:vaultSelected},
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
                    return false;
                } else if (vaultSelected != 'newCard' && window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'standard') {
                    var cvv = $("input[value='"+vaultSelected+"']").parent('.vault').find('.input-text').val();
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_fort_vault.ajaxVaultUrl,
                        type: 'post',
                        context: this,
                        data:{publicHash:vaultSelected,cvv:cvv},
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
            }
        });
    }
);