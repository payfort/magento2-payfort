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
        'slick'
    ],
    function (ko, $, Component, quote, _, fullScreenLoader, setPaymentInformationAction, placeOrderAction, additionalValidators, messageList, $t,customer) {
        'use strict';
        var vault = '';
        //Validation.creditCartTypes.set('Meeza', [new RegExp(window.checkoutConfig.payment.apsFort.aps_fort_cc.meezabin), new RegExp('^[0-9]{3}$'), true]);
        if (!customer.isLoggedIn()) {
            vault = 'newCard';
        }
        $('.ccform .error-ccform').html('');
        $('.cvv-error').remove();
        $(".vault .label-cvv").addClass('cvv-hide');
        $("#cc-co-transparent-form").css('display','none');
        var flagInstaCheck = 0;
        var tenure = 0;
        var issuer_code = '';
        var plan_code = '';
        var instaValue = '';
        var instaInterest = '';
        $("body").on("click",".instaslider-tenure", function () {
            $(".ccform .plan-info").css('display','block');
            tenure = $(this).attr('data-attr');
            issuer_code = $(this).attr('data-code');
            plan_code = $(this).attr('data-plan');
            instaValue = $(this).attr('data-intvalue');
            instaInterest = $(this).attr('data-interest');
            $("body .ccform .instaslider-tenure").removeClass('slide-selected');
            $(this).addClass('slide-selected');
            if (tenure == 'New') {
                $(".ccform .plan-info").css('display','none');
            }
            $(".ccform .plan-error").html('');
        });
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
            
            placeOrderHandler: null,
            validateHandler: null,
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-form-merchant-page2',
                isCcFormShown: true,
            },
            getTitle: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_cc.mada == 'yes' || window.checkoutConfig.payment.apsFort.aps_fort_cc.meeza == 'yes') {
                    return $.mage.__('mada debit card / Credit Cards');
                }
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_fort_cc.title);
            },
            getCode: function () {
                return 'aps_fort_cc';
            },
            
            isActive: function () {
                return true;
            },

            afterVaultRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == false) {
                    $('.ccform .newcardtype').css('display', 'none');
                    $('.ccform #cc-co-transparent-form').css('display', '');
                    vault = 'newCard';
                }
                if (window.checkoutConfig.payment.apsFort.aps_fort_vault.active == 1 && customer.isLoggedIn()) {
                    $(".ccform .remember_me").css('display','');
                    $(".ccform .remember_me .input-text").prop('checked','true');
                } else {
                    $(".ccform .remember_me").css('display','none');
                }
            },
            
            /**
             * @returns {exports.context}
             */
            context: function () {
                return this;
            },

            getImages: function () {
                return window.checkoutConfig.payment.apsFort.cardImg;
            },
            
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_cc.instructions;
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

            getData: function () {
                if (vault == 'newCard' || vault == '') {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                        }
                    };
                } else {
                    return {
                        'method': 'aps_fort_vault'
                    };
                }
            },

            disablePaste: function () {
                return false;
            },

            getVault : function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_vault.data;
            },

            newCard: function () {
                $('.cvv-error').remove();
                $("#aps_fort_cc_cc_number").val('');
                $("#aps_fort_cc_cc_holder_name").val('');
                $("#aps_fort_cc_expiration").val('');
                $("#aps_fort_cc_expiration_yr").val('');
                $("#aps_fort_cc_cc_cid").val('');
                $(".ccform .plan-error").html('');

                tenure = '';
                issuer_code = '';
                plan_code = '';
                instaValue = '';
                instaInterest = '';
                $("body .ccform .instaslider-tenure").removeClass('slide-selected');
                $('.ccform [data-action="issuer-name"]').html('');
                $('.ccform [data-action="plan-info"]').html('');
                $('.ccform [data-action="widget-cc-insta-grid"]').html('');

                vault = $("input[name='vaultHash']:checked").val();
                $(".vault .label-cvv").addClass('cvv-hide');
                if (vault == 'newCard') {
                    $("#cc-co-transparent-form").css('display','');
                } else {
                    $("input[value='"+vault+"']").parent('.vault').find('.label-cvv').removeClass('cvv-hide');
                    $("input[value='"+vault+"']").parent('.vault').find('.input-text').prop("disabled", false);
                    $("input[value='"+vault+"']").parent('.vault').find('.input-text').val('');
                    $("#cc-co-transparent-form").css('display','none');
                }
                return true;
            },

            /**
             * Prepare and process payment information
             */
            preparePayment: function () {
                $('.ccform .error-ccform').html('');
                $('.cvv-error').remove();
                $('.cc-form-error').remove();
                if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == true && $("input[name='vaultHash']:checked").val() == undefined) {
                    $('.error-ccform').html($.mage.__('Please select a card.'));
                    this.removeLoader();
                    return false;
                } else if (customer.isLoggedIn() && window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == false) {
                    vault = 'newCard' ;
                }
                if (window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'embeded' && (tenure == 'New' || flagInstaCheck == 0)) {
                    if (window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == true) {
                        if (vault != 'newCard') {
                            if ($("input[value='"+vault+"']").parent('.vault').find('.input-text').val().length === 0) {
                                $("input[value='"+vault+"']").parent('.vault').find('.input-text').after('<span class="cvv-error">'+$.mage.__('CVV is mandatory')+'</span>');
                                return false;
                            } else {
                                this.placeOrder();
                                return false;
                            }
                        }
                    }
                    if (vault == 'newCard') {
                        var self = this,
                            cardInfo = null;
                        var flagVali = this.validateHandler();
                        var str = $("#aps_fort_cc_cc_holder_name").val();
                        var patt = new RegExp(/^[a-zA-Zء-ي ]+$/);
                        var res = patt.test(str);
                        if (res == false) {
                            $("#aps_fort_cc_cc_holder_name").after('<div class="mage-error cc-form-error" style="display: block;">Please enter a valid name in this field.</div>');
                        }
                        if (flagVali == true && res == true) {
                            this.messageContainer.clear();
                            this.quoteBaseGrandTotals = quote.totals()['base_grand_total'];
                            this.placeOrder();
                        }
                    }
                } else if (window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'embeded' && tenure != 'New') {
                    if (vault == 'newCard') {
                        var flagVali = this.validateHandler();
                        var str = $("#aps_fort_cc_cc_holder_name").val();
                        var patt = new RegExp(/^[a-zA-Zء-ي ]+$/);
                        var res = patt.test(str);
                        if (res == false) {
                            $("#aps_fort_cc_cc_holder_name").after('<div class="mage-error cc-form-error" style="display: block;">Please enter a valid name in this field.</div>');
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
                            if ($(".ccform #installment_term").prop('checked') == false) {
                                $('.ccform [data-action="plan-error"]').html($.mage.__('Kindly check terms and conditions'));
                                flag = 1;
                            }
                            if (tenure.length == 0 || issuer_code.length == 0 || plan_code.length == 0) {
                                $('.ccform [data-action="plan-error"]').html($.mage.__('Please select your installment plan.'));
                                flag = 1;
                            }
                            if (flag == 0) {
                                this.placeOrder();
                            }
                        }
                    } else {
                        var flag = 0;
                        $('.cvv-error').remove();
                        var cvv = $("input[value='"+vault+"']").parent('.vault').find('.input-text').val();
                        if ( cvv.length < 1) {
                            $('input[name="instacvv"]').after('<span class="cvv-error">'+$.mage.__('CVV is mandatory')+'</span>');
                            return false;
                        }
                        if ($(".ccform #installment_term").prop('checked') == false) {
                            $('.ccform [data-action="plan-error"]').html('Kindly check terms and conditions');
                            flag = 1;
                        }
                        if (tenure.length == 0 || issuer_code.length == 0 || plan_code.length == 0) {
                            $('.ccform [data-action="plan-error"]').html('Please select your installment plan.');
                            flag = 1;
                        }
                        if (flag == 0) {
                            this.placeOrder();
                        }
                    }
                } else {
                    if (window.checkoutConfig.payment.apsFort.aps_fort_vault.hasOwnProperty('data') == true) {
                        if (vault != 'newCard') {
                            if ($("input[value='"+vault+"']").parent('.vault').find('.input-text').val().length === 0) {
                                $("input[value='"+vault+"']").parent('.vault').find('.input-text').after('<span class="cvv-error">'+$.mage.__('CVV is mandatory')+'</span>');
                                this.removeLoader();
                                return false;
                            } else {
                                this.placeOrder();
                                return false;
                            }
                        }
                    }
                    if (vault == 'newCard') {
                        var self = this,
                            cardInfo = null;
                        var flagVali = this.validateHandler();
                        var str = $("#aps_fort_cc_cc_holder_name").val();
                        var patt = new RegExp(/^[a-zA-Zء-ي ]+$/);
                        var res = patt.test(str);
                        if (res == false) {
                            $("#aps_fort_cc_cc_holder_name").after('<div class="mage-error cc-form-error" style="display: block;">Please enter a valid name in this field.</div>');
                        }
                        if (flagVali == true && res == true) {
                            this.messageContainer.clear();
                            this.quoteBaseGrandTotals = quote.totals()['base_grand_total'];
                            this.placeOrder();
                        }
                    }
                }
            },
            removeLoader: function(){
                var loadingMask = document.querySelector('.loading-mask');
                if (loadingMask) {
                    loadingMask.style.display = 'none';
                }
            },
            getInstallment: function () {
                if (window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'embeded') {
                    $('.cvv-error').remove();
                    var sliderText = '';
                    $(".show-cc-insta-slider .widget-cc-insta-grid").removeClass('slick-initialized');

                    if (vault == 'newCard') {
                        var getCode = this.getCode();
                        var cardId = "#" + getCode + "_cc_number";
                        var cardNumber = $(cardId).val();
                        cardNumber = cardNumber.substring(0, 6);
                        if ($(cardId).val().length >= 15) {
                            $('[data-action="widget-cc-insta-grid"]').html(sliderText);
                            $.ajax({
                                url: window.checkoutConfig.payment.apsFort.aps_fort_cc.getInstallmentPlans,
                                type: 'post',
                                data:{cardNumber:cardNumber},
                                context: this,
                                dataType: 'json',
                                showLoader: true,
                                success: function (response) {
                                    if (response.success) {
                                        document.getElementById("ccorder").disabled = false;
                                        sliderText += '<div class="slide instaslider-tenure" data-bind = "click: ValuPurchase" data-attr="New" data-code="New" data-plan="New" data-intvalue="New" data-interest="New"><span class="full">'+$.mage.__('Proceed with full amount')+'</span></div>';
                                        $.each(response.plansArr,function (key, values) {
                                            flagInstaCheck = 1;
                                            sliderText += '<div class="slide instaslider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + values.number_of_installment + '" data-code="' + values.issuer_code + '" data-plan="' + values.plan_code + '" data-intvalue="'+ values.amountPerMonth +'" data-interest="'+ values.interest +'"><span class="tenure">' + values.number_of_installment+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' + values.amountPerMonth + '</span> <span class="emitext">'+$.mage.__(values.currency_code) + '/'+$.mage.__('Month')+'</span><br><span class="interestrate">' + values.interest + "% "+$.mage.__('interest')+"</span></div>";
                                        });
                                        
                                        $('.ccform [data-action="issuer-name"]').html(response.issuer_text);
                                        $('.ccform [data-action="plan-info"]').html(response.plan_info);
                                        $('.ccform [data-action="widget-cc-insta-grid"]').html(sliderText);
                                        $('.ccform .instaslider-tenure:eq(0)').height($('.ccform .instaslider-tenure:eq(1)').height());
                                        $('.show-cc-insta-slider .widget-cc-insta-grid').not('.slick-initialized').slick({
                                            dots: false,
                                            infinite: false,
                                            centerMode: false,
                                            slidesToShow: 4,
                                            slidesToScroll: 2
                                        });
                                        $(".ccform .plan-info").css('display','none');
                                    } else {
                                        $(".ccform .issuer").css('display','none');
                                    }
                                }
                            });
                        }
                    } else {
                        $('[data-action="widget-cc-insta-grid"]').html(sliderText);
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_fort_cc.getInstallmentPlans,
                            type: 'post',
                            data:{vaultSelected:vault},
                            context: this,
                            dataType: 'json',
                            showLoader: true,
                            success: function (response) {
                                if (response.success) {
                                    document.getElementById("ccorder").disabled = false;
                                    sliderText += '<div class="slide instaslider-tenure"  data-bind = "click: ValuPurchase" data-attr="New" data-code="New" data-plan="New" data-intvalue="New" data-interest="New"><span class="full">'+$.mage.__('Proceed with full amount')+'</span></div>';
                                    $.each(response.plansArr,function (key, values) {
                                        flagInstaCheck = 1;
                                        sliderText += '<div class="slide instaslider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + values.number_of_installment + '" data-code="' + values.issuer_code + '" data-plan="' + values.plan_code + '" data-intvalue="'+ values.amountPerMonth +'" data-interest="'+ values.interest +'"><span class="tenure">' + values.number_of_installment+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' + values.amountPerMonth + '</span> <span class="emitext">'+$.mage.__(values.currency_code + '/Month')+'</span><br><span class="interestrate">' + values.interest + "% "+$.mage.__('interest')+"</span></div>";
                                    });
                                    $('.ccform [data-action="issuer-logo"]').attr("src", response.issuer_logo);
                                    $('.ccform [data-action="issuer-name"]').html(response.issuer_text);
                                    $('.ccform [data-action="plan-info"]').html(response.plan_info);
                                    $('.ccform [data-action="widget-cc-insta-grid"]').html(sliderText);

                                    $('.ccform .instaslider-tenure:eq(0)').height($('.ccform .instaslider-tenure:eq(1)').height());
                                    $('.show-cc-insta-slider .widget-cc-insta-grid').not('.slick-initialized').slick({
                                        dots: false,
                                        infinite: false,
                                        centerMode: false,
                                        slidesToShow: 4,
                                        slidesToScroll: 2
                                    });
                                    $(".ccform .plan-info").css('display','none');
                                } else {
                                    $(".ccform .issuer").css('display','none');
                                }
                            }
                        });
                    }
                }
            },
            
            afterPlaceOrder: function () {
                if (window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'embeded' && (tenure == 'New' || flagInstaCheck == 0)) {
                    if (vault == 'newCard') {
                        var getCode = this.getCode();
                        var cardInfo = {
                            number: this.creditCardNumber(),
                            expirationMonth: this.creditCardExpMonth(),
                            expirationYear: this.creditCardExpYear(),
                            cvv: this.creditCardVerificationNumber(),
                            holderName: $('#aps_fort_cc_cc_holder_name').val()
                        };
                        var expMonth = cardInfo.expirationMonth;
                        if (expMonth.length == 1) {
                            expMonth = '0'+expMonth;
                        }
                        var expYear = cardInfo.expirationYear;
                        expYear = expYear.substr(expYear.length - 2);
                        var expiryDate = expYear+''+expMonth;
                        cardInfo.expiryDate = expiryDate;
                        var rememberMe = 'NO';
                        if ($('.ccform .remember_me:checked').val() == 'YES') {
                            rememberMe = 'YES';
                        }
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_fort_cc.ajaxUrl,
                            type: 'post',
                            context: this,
                            dataType: 'json',
                            success: function (response) {
                                var preparedData,
                                    msg;
                                if (response.success) {
                                    var formId = 'frm_aps_fort_payment';
                                    if (jQuery("#"+formId).length) {
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
                    } else {
                        var publicHash = vault;
                        var cvv = $("input[value='"+publicHash+"']").parent('.vault').find('.input-text').val();
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_fort_vault.ajaxVaultUrl,
                            type: 'post',
                            context: this,
                            data:{publicHash:publicHash,cvv:cvv},
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
                } else if (window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'embeded' && tenure != 'New') {
                    if (vault == 'newCard') {
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
                        
                        if ($('.ccform .remember_me:checked').val() == 'YES') {
                            rememberMe = 'YES';
                        }
                        var expYear = cardInfo.expirationYear;
                        expYear = expYear.substr(expYear.length - 2);
                        var expiryDate = expYear+''+expMonth;
                        cardInfo.expiryDate = expiryDate;
                        
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_installment.ajaxInstallmentUrl,
                            type: 'post',
                            context: this,
                            data:{tenure:tenure,issuer_code:issuer_code,plan_code:plan_code,installment_amount:instaValue,installment_interest:instaInterest},
                            dataType: 'json',
                            success: function (response) {
                                var preparedData,
                                    msg;
                                if (response.success) {
                                    var formId = 'frm_aps_fort_payment';
                                    if (jQuery("#"+formId).length) {
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
                    } else {
                        var cvv = $("input[value='"+vault+"']").parent('.vault').find('.input-text').val();
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_installment.vaultInstallment,
                            type: 'post',
                            context: this,
                            data:{vaultSelected:vault,tenure:tenure,issuer_code:issuer_code,plan_code:plan_code,cvv:cvv,installment_amount:instaValue,installment_interest:instaInterest},
                            dataType: 'json',
                            success: function (response) {
                                var preparedData,
                                    msg;
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
                } else {
                    if (vault == 'newCard') {
                        var getCode = this.getCode();
                        var cardInfo = {
                            number: this.creditCardNumber(),
                            expirationMonth: this.creditCardExpMonth(),
                            expirationYear: this.creditCardExpYear(),
                            cvv: this.creditCardVerificationNumber(),
                            holderName: $('#aps_fort_cc_cc_holder_name').val()
                        };
                        var expMonth = cardInfo.expirationMonth;
                        if (expMonth.length == 1) {
                            expMonth = '0'+expMonth;
                        }
                        var expYear = cardInfo.expirationYear;
                        expYear = expYear.substr(expYear.length - 2);
                        var expiryDate = expYear+''+expMonth;
                        cardInfo.expiryDate = expiryDate;
                        var rememberMe = 'NO';
                        if ($('.ccform .remember_me:checked').val() == 'YES') {
                            rememberMe = 'YES';
                        }
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_fort_cc.ajaxUrl,
                            type: 'post',
                            context: this,
                            dataType: 'json',
                            success: function (response) {
                                var preparedData,
                                    msg;
                                if (response.success) {
                                    var formId = 'frm_aps_fort_payment';
                                    if (jQuery("#"+formId).length) {
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
                    } else {
                        var publicHash = vault;
                        var cvv = $("input[value='"+publicHash+"']").parent('.vault').find('.input-text').val();
                        $.ajax({
                            url: window.checkoutConfig.payment.apsFort.aps_fort_vault.ajaxVaultUrl,
                            type: 'post',
                            context: this,
                            data:{publicHash:publicHash,cvv:cvv},
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
                return false;
            },
            isChecked: ko.computed(function () {
                var method = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if (method == 'aps_fort_cc' || method == 'aps_fort_vault') {
                    return 'aps_fort_cc';
                }
                return method;
            })
        });
    }
);