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
        'Magento_Customer/js/model/customer',
        'uiRegistry',
        'mage/utils/wrapper',
        'slick',
    ],
    function (ko, $, Component, quote, _, fullScreenLoader, setPaymentInformationAction, placeOrderAction, additionalValidators, messageList, $t,customer) {
        'use strict';
        $('.error-instaform').html('');
        $('.instaform [data-action="issuer-logo"]').html('');
        $('.instaform [data-action="plan-error"]').html('');
        $("#co-transparent-form").css('display','none');
        
        var flag = 0;
        var tenure = 0;
        var issuer_code = '';
        var plan_code = '';
        var vaultSelected = 'newCard';
        var instaValue = '';
        var instaInterest = '';
        $("body").on("click",".instaslider-tenure", function () {
            tenure = $(this).attr('data-attr');
            issuer_code = $(this).attr('data-code');
            plan_code = $(this).attr('data-plan');
            instaValue = $(this).attr('data-intvalue');
            instaInterest = $(this).attr('data-interest');
            $("body .instaform .instaslider-tenure").removeClass('slide-selected');
            $(this).addClass('slide-selected');
        });
        return Component.extend({
            
            placeOrderHandler: null,
            validateHandler: null,
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-installment',
                isCcFormShown: true,
            },
            
            getCode: function () {
                return 'aps_installment';
            },

            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_installment.title);
            },
            
            isActive: function () {
                return true;
            },
            
            /**
             * @returns {exports.context}
             */
            context: function () {
                return this;
            },

            getImages: function () {
                return window.checkoutConfig.payment.apsFort.cardInstallImg;
            },
            
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_installment.instructions;
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

            afterVaultRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_installment.aps_fort_vault.hasOwnProperty('data') == false) {
                    $('.instaform .newcardtype').css('display', 'none');
                    $('.instaform #co-transparent-form').css('display', '');
                    $(".instaform .newcardtype input[name='vaultHash']").attr('checked', true);
                }
                if (window.checkoutConfig.payment.apsFort.aps_installment.aps_fort_vault.active == 1 && customer.isLoggedIn()) {
                    $(".instaform .remember_me").css('display','');
                    $(".instaform .remember_me  .input-text").prop('checked','true');
                } else {
                    $(".instaform .remember_me").css('display','none');
                }
            },

            getVault : function () {
                return window.checkoutConfig.payment.apsFort.aps_installment.aps_fort_vault.data;
            },

            newCard: function () {
                $('.cvv-error').remove();
                $(".show-insta-slider .widget-insta-grid").removeClass('slick-initialized');
                $('[data-action="widget-insta-grid"]').html('');
                $('[data-action="issuer-name"]').html('');
                $('[data-action="plan-info"]').html('');
                $("#aps_installment_cc_number").val('');
                $("#aps_installment_cc_holder_name").val('');
                $("#aps_installment_expiration").val('');
                $("#aps_installment_expiration_yr").val('');
                $("#aps_installment_cc_cid").val('');

                
                vaultSelected = $("input[name='vaultHash']:checked").val();
                $(".vaultinsta .label-cvv").addClass('cvv-hide');
                $('input[name="instacvv"]').prop("disabled", true);
                if (vaultSelected == 'newCard') {
                    $("#co-transparent-form").css('display','');
                } else {
                    $("input[value='"+vaultSelected+"']").parent('.vaultinsta').find('.label-cvv').removeClass('cvv-hide');
                    $("input[value='"+vaultSelected+"']").parent('.vaultinsta').find('.input-text').prop("disabled", false);
                    $("input[value='"+vaultSelected+"']").parent('.vaultinsta').find('.input-text').val('');
                    $("#co-transparent-form").css('display','none');
                }
                return true;
            },
            
            /**
             * Prepare and process payment information
             */
            preparePayment: function () {
                $('.error-instaform').html('');
                $('.instaform [data-action="issuer-logo"]').html('');
                $('.instaform [data-action="plan-error"]').html('');
                $('.insta-form-error').remove();
                var self = this,
                    cardInfo = null;
                if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_installment.aps_fort_vault.hasOwnProperty('data') == true && $("input[name='vaultHash']:checked").val() == undefined) {
                    $('.error-instaform').html($.mage.__('Please select a card.'));
                    return false;
                } else if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_installment.aps_fort_vault.hasOwnProperty('data') == false) {
                    vaultSelected = 'newCard' ;
                }
                if (vaultSelected == 'newCard') {
                    var flagVali = this.validateHandler();
                    var str = $("#aps_installment_cc_holder_name").val();
                    var patt = new RegExp(/^[a-zA-Zء-ي ]+$/);
                    var res = patt.test(str);
                    if (res == false) {
                        $("#aps_installment_cc_holder_name").after('<div class="mage-error insta-form-error" style="display: block;">Please enter a valid name in this field.</div>');
                    }
                    if (flagVali == true && res == true) {
                        this.messageContainer.clear();
                        this.quoteBaseGrandTotals = quote.totals()['base_grand_total'];

                        cardInfo = {
                            number: this.creditCardNumber(),
                            expirationMonth: this.creditCardExpMonth(),
                            expirationYear: this.creditCardExpYear(),
                            cvv: this.creditCardVerificationNumber()
                        };
                        var flag = 0;
                        if ($(".instaform #installment_term").prop('checked') == false) {
                            $('.instaform [data-action="plan-error"]').html($.mage.__('Kindly check terms and conditions'));
                            flag = 1;
                        }
                        if (tenure < 1 || issuer_code.length == 0 || plan_code.length == 0) {
                            $('.instaform [data-action="plan-error"]').html($.mage.__('Please select your installment plan.'));
                            flag = 1;
                        }
                        if (flag == 0) {
                            this.placeOrder();
                        }
                    }
                } else {
                    var flag = 0;
                    $('.cvv-error').remove();
                    var cvv = $("input[value='"+vaultSelected+"']").parent('.vaultinsta').find('.input-text').val();
                    if ( cvv.length < 1) {
                        $('input[name="instacvv"]').after('<span class="cvv-error">'+$.mage.__('CVV is mandatory')+'</span>');
                        return false;
                    }
                    if ($("#installment_term").prop('checked') == false) {
                        $('.instaform [data-action="plan-error"]').html('Kindly check terms and conditions');
                        flag = 1;
                    }
                    if (tenure < 1 || issuer_code.length == 0 || plan_code.length == 0) {
                        $('.instaform [data-action="plan-error"]').html('Please select your installment plan.');
                        flag = 1;
                    }
                    if (flag == 0) {
                        this.placeOrder();
                    }
                }
            },
            getInstallment: function () {
                $('.cvv-error').remove();
                var sliderText = '';
                $(".show-insta-slider .widget-insta-grid").removeClass('slick-initialized');

                if (vaultSelected == 'newCard') {
                    var getCode = this.getCode();
                    var cardId = "#" + getCode + "_cc_number";
                    var cardNumber = $(cardId).val();
                    cardNumber = cardNumber.substring(0, 6);
                    if ($(cardId).val().length >= 15) {
                        $('[data-action="widget-insta-grid"]').html(sliderText);
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_installment.getInstallmentPlans,
                            type: 'get',
                            data:{cardNumber:cardNumber},
                            context: this,
                            dataType: 'json',
                            showLoader: true,
                            success: function (response) {
                                if (response.success) {
                                    document.getElementById("installplace").disabled = false;
                                    $.each(response.plansArr,function (key, values) {
                                        sliderText += '<div class="slide instaslider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + values.number_of_installment + '" data-code="' + values.issuer_code + '" data-plan="' + values.plan_code + '" data-intvalue="'+ values.amountPerMonth +'" data-interest="'+ values.interest +'"><span class="tenure">' + values.number_of_installment+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' + values.amountPerMonth + '</span> <span class="emitext">'+$.mage.__(values.currency_code) + '/'+$.mage.__('Month')+'</span><br><span class="interestrate">' + values.interest + "% "+$.mage.__('interest')+"</span></div>";
                                    });
                                    
                                    $('.instaform [data-action="issuer-name"]').html(response.issuer_text);
                                    $('.instaform [data-action="plan-info"]').html(response.plan_info);
                                    $('.instaform [data-action="widget-insta-grid"]').html(sliderText);
                                    $('.show-insta-slider .widget-insta-grid').not('.slick-initialized').slick({
                                        dots: false,
                                        infinite: false,
                                        centerMode: false,
                                        slidesToShow: 4,
                                        slidesToScroll: 2
                                    });
                                } else {
                                    flag = 1;
                                    $('.instaform [data-action="widget-insta-grid"]').html(response.error_message);
                                    document.getElementById("installplace").disabled = true;
                                }
                            }
                        });
                    }
                } else {
                    $('[data-action="widget-insta-grid"]').html(sliderText);
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_installment.getInstallmentPlans,
                        type: 'get',
                        data:{vaultSelected:vaultSelected},
                        context: this,
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            if (response.success) {
                                document.getElementById("installplace").disabled = false;
                                $.each(response.plansArr,function (key, values) {
                                    sliderText += '<div class="slide instaslider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + values.number_of_installment + '" data-code="' + values.issuer_code + '" data-plan="' + values.plan_code + '" data-intvalue="'+ values.amountPerMonth +'" data-interest="'+ values.interest +'"><span class="tenure">' + values.number_of_installment+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' + values.amountPerMonth + '</span> <span class="emitext">'+$.mage.__(values.currency_code + '/Month')+'</span><br><span class="interestrate">' + values.interest + "% "+$.mage.__('interest')+"</span></div>";
                                });
                                $('.instaform [data-action="issuer-logo"]').attr("src", response.issuer_logo);
                                $('.instaform [data-action="issuer-name"]').html(response.issuer_text);
                                $('.instaform [data-action="plan-info"]').html(response.plan_info);
                                $('.instaform [data-action="widget-insta-grid"]').html(sliderText);
                                $('.show-insta-slider .widget-insta-grid').not('.slick-initialized').slick({
                                    dots: false,
                                    infinite: false,
                                    centerMode: false,
                                    slidesToShow: 4,
                                    slidesToScroll: 2
                                });
                            } else {
                                flag = 1;
                                $('.instaform [data-action="widget-insta-grid"]').html(response.error_message);
                                document.getElementById("installplace").disabled = true;
                            }
                        }
                    });
                }
            },
            afterPlaceOrder: function () {
                if (vaultSelected == 'newCard') {
                    var getCode = this.getCode();
                    var cardInfo = {
                        number: this.creditCardNumber(),
                        expirationMonth: this.creditCardExpMonth(),
                        expirationYear: this.creditCardExpYear(),
                        cvv: this.creditCardVerificationNumber(),
                        holderName: $('#'+ getCode +'_cc_holder_name').val()
                    };
                    var expMonth = cardInfo.expirationMonth;
                    if (expMonth.length == 1) {
                        expMonth = '0'+expMonth;
                    }
                    var rememberMe = 'NO';
                    
                    if ($('.instaform .input-text.remember_me:checked').val() == 'YES') {
                        rememberMe = 'YES';
                    }
                    var expYear = cardInfo.expirationYear;
                    expYear = expYear.substr(expYear.length - 2);
                    var expiryDate = expYear+''+expMonth;
                    cardInfo.expiryDate = expiryDate;
                    
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_installment.ajaxUrl,
                        type: 'get',
                        context: this,
                        data:{tenure:tenure,issuer_code:issuer_code,plan_code:plan_code,installment_amount:instaValue,installment_interest:instaInterest},
                        dataType: 'json',
                        success: function (response) {
                            var preparedData,
                                msg;
                            if (response.success) {
                                var formId = 'frm_aps_fort_payment';
                                if (jQuery("#"+formId).size()) {
                                    jQuery("#"+formId).remove();
                                }
                                $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                                response.params.card_number = cardInfo.number;
                                response.params.card_holder_name = cardInfo.holderName;
                                response.params.card_security_code = cardInfo.cvv;
                                response.params.expiry_date = cardInfo.expiryDate;
                                response.params.remember_me = rememberMe;
                                $.each(response.params, function (k, v) {
                                    $('<input>').attr({
                                        type: 'hidden',
                                        id: k,
                                        name: k,
                                        value: v
                                    }).appendTo($('#'+formId));
                                });
                                
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
                } else {
                    var cvv = $("input[value='"+vaultSelected+"']").parent('.vaultinsta').find('.input-text').val();
                    $.ajax({
                        url: window.checkoutConfig.payment.apsFort.aps_installment.vaultInstallment,
                        type: 'get',
                        context: this,
                        data:{vaultSelected:vaultSelected,tenure:tenure,issuer_code:issuer_code,plan_code:plan_code,cvv:cvv,installment_amount:instaValue,installment_interest:instaInterest},
                        dataType: 'json',
                        success: function (response) {
                            var preparedData,
                                msg;
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
                return false;
            },
        });
    }
);