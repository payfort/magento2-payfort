/**
 * Aps_Fort Magento JS component
 *
 * @category    APS
 * @package     Aps_Fort
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
        'mage/translate',
        'slick',
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder) {
        'use strict';
        var tenure = 0;
        
        var valuInterest = 0;
        var valuAmount = 0;
        $("body").on("click",".slider-tenure", function () {
            tenure = $(this).attr('data-attr');
            valuInterest = $(this).attr('data-valuint');
            valuAmount = $(this).attr('data-valuamount');
            $(".slider-tenure").removeClass('slide-selected');
            $(this).addClass('slide-selected');
        });
        $("body").on("click",".btn-accept", function () {
            $('[data-action="valtc"]').prop("checked", true);
        });
        $("body").on("click",".btn-decline", function () {
            $('[data-action="valtc"]').prop("checked", false);
        });
        var quoteTotal = quote.totals();
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
                $('#frm_aps_valu_payment input[name=form_key]').remove();
                $('#frm_aps_valu_payment input[name=form_key]').attr("disabled", "disabled");
            }
        );
        return Component.extend({
            defaults: {
                template: 'Amazonpaymentservices_Fort/payment/aps-valu-form'
            },
            getCode: function () {
                return 'aps_fort_valu';
            },
            getTitle: function () {
                return $.mage.__(window.checkoutConfig.payment.apsFort.aps_fort_valu.title);
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_valu.instructions;
            },
            /**
             * @param {Function} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            setPopup: function () {
                var lang = window.checkoutConfig.payment.apsFort.configParams.storeLanguage;
                $('[data-action="valu-'+ lang +'"]').removeClass('_pf-hidden');
            },
            setLogo: function () {
                $('[data-action="setLogo"]').attr('src',window.checkoutConfig.payment.apsFort.aps_fort_valu.valuLogo);
            },
            checkAfterKoRender: function () {
                
            },
            
            VerifyCustomer: function () {
                $('[data-action="aps-otp-error"]').text('');
                var mobileNumber = $('[data-action="pf-mobileNumber"]').val();
                var otpCheck = "customerVerify";
                $('.aps-valu .checkout-agreements .valu-check-err').remove('');
                if ($(".aps-valu .checkout-agreements .required-entry").length) {
                    if (!$(".aps-valu .checkout-agreements .required-entry").is(':checked')) {
                        $('.aps-valu .checkout-agreements .checkout-agreement').after('<div class="valu-check-err error-val">'+$.mage.__('This is a required field.')+'</div>');
                        return false;
                    }
                }
                if (!Number.isInteger(parseInt(mobileNumber))) {
                    $('[data-action="aps-otp-error"]').text($.mage.__('Please enter mobile number.'));
                    return false;
                }
                if (mobileNumber.length > 19 || mobileNumber.length < 11) {
                    $('[data-action="aps-otp-error"]').text($.mage.__('Mobile number is not valid.'));
                    return false;
                }
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_valu.ajaxOtpUrl,
                    type: 'post',
                    context: this,
                    data:{mobileNumber:mobileNumber,otpCheck:otpCheck},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.response_code != '90000') {
                            $('[data-action="aps-otp-error"]').text($.mage.__(response.response_message));
                        } else {
                            this.placeOrder();
                        }
                    }
                });
            },
            
            afterPlaceOrder: function () {
                var mobileNumber = $('[data-action="pf-mobileNumber"]').val();
                var otpCheck = "requestOtp";
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_valu.ajaxOtpUrl,
                    type: 'post',
                    context: this,
                    data:{mobileNumber:mobileNumber,otpCheck:otpCheck},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.response_code != '88000') {
                            $('[data-action="aps-otp-error"]').text($.mage.__(response.response_message));
                        } else {
                            $('[data-action="aps-otp-msg"]').text($.mage.__('OTP has been sent to you on your mobile number : +20') + mobileNumber);
                            $('[data-action="pf-mobile-verify"]').addClass('_pf-hidden');
                            var sliderText = '';

                            $.each(response.installment_detail.plan_details, function ( key, value ) {
                                sliderText += '<div class="slide slider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + value.number_of_installments + '" data-valuint="'+ value.fees_amount +'" data-valuamount="'+ value.amount_per_month +'"><span class="tenure">' + value.number_of_installments+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' +((value.amount_per_month/100).toFixed(2)) + '</span> <span class="emitext">'+$.mage.__('EGP/Month')+'</span><span class="interestrate">' +"</span></div>";
                            });
                            $('[data-action="show-valu-slider"]').removeClass('_pf-hidden');
                            $('[data-action="widget-valu-grid"]').html(sliderText);
                            $('.show-valu-slider .widget-valu-grid').not('.slick-initialized').slick({
                                dots: false,
                                infinite: false,
                                centerMode: false,
                                slidesToShow: 4,
                                slidesToScroll: 2
                            });
                            $('[data-action="valu-place-order"]').removeClass('_pf-hidden');
                            $('[data-action="valu-tc"]').removeClass('_pf-hidden');

                           // $('[data-action="aps-otp-verify"]').removeClass('_pf-hidden');
                        }
                    }
                });
            },
            
            VerifyOtp: function () {
                $('[data-action="pf-error-verify-otp"]').text('');
                $('.aps-valu .checkout-agreements .valu-check-err').remove('');
                var mobileNumber = $('[data-action="pf-mobileNumber"]').val();
                var otp = $('[data-action="otp"]').val();
                if ($(".aps-valu .checkout-agreements .required-entry").length) {
                    if (!$(".aps-valu .checkout-agreements .required-entry").is(':checked')) {
                        $('.aps-valu .checkout-agreements .checkout-agreement').after('<div class="valu-check-err error-val">'+$.mage.__('This is a required field.')+'</div>');
                        return false;
                    }
                }
                if (otp.length > 10 || otp.length < 1) {
                    $('[data-action="pf-error-verify-otp"]').text($.mage.__('Invalid Valu OTP'));
                    return false;
                }
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_valu.ajaxOtpVerifyUrl,
                    type: 'post',
                    context: this,
                    data:{mobileNumber:mobileNumber,otp:otp},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        var sliderText = '';
                        if (response.status  == '92') {
                            $('[data-action="dv-otp-verify"]').removeClass('_pf-hidden');
                            $('[data-action="dv-otp-verify"]').text($.mage.__('OTP Verified Successfully, Please select your installment plan!'));
                            $.each(response.tenure.TENURE_VM, function ( key, value ) {
                                sliderText += '<div class="slide slider-tenure"  data-bind = "click: ValuPurchase" data-attr="' + value.TENURE + '" data-valuint="'+ value.InterestRate +'" data-valuamount="'+ value.EMI +'"><span class="tenure">' + value.TENURE+" " + $.mage.__('MONTHS')+'</span><br><span class="emi">' + value.EMI + '</span> <span class="emitext">'+$.mage.__('EGP/Month')+'</span><br><span class="interestrate">' + value.InterestRate + "% "+$.mage.__('interest')+"</span></div>";
                            });
                            $('[data-action="show-valu-slider"]').removeClass('_pf-hidden');
                            $('[data-action="widget-valu-grid"]').html(sliderText);
                            $('[data-action="aps-otp-verify"]').addClass('_pf-hidden');
                            $('.show-valu-slider .widget-valu-grid').not('.slick-initialized').slick({
                                dots: false,
                                infinite: false,
                                centerMode: false,
                                slidesToShow: 4,
                                slidesToScroll: 2
                            });
                            $('[data-action="valu-place-order"]').removeClass('_pf-hidden');
                            $('[data-action="valu-tc"]').removeClass('_pf-hidden');
                        } else {
                            $('[data-action="pf-error-verify-otp"]').text($.mage.__('Invalid Valu OTP'));
                        }
                    }
                });
            },
            ValuPurchase: function () {
                $('[data-action="pf-error-verify-otp"]').text('');
                $('[data-action="error-purchase"]').text('');
                $('[data-action="valu-check-err"]').html('');
                $('.aps-valu .checkout-agreements .valu-check-err').remove('');
                var mobileNumber = $('[data-action="pf-mobileNumber"]').val();
                var otp = $('[data-action="otp"]').val();

                if (otp.length > 10 || otp.length < 1) {
                    $('[data-action="pf-error-verify-otp"]').text($.mage.__('Invalid Valu OTP'));
                    return false;
                }

                if (!$('[data-action="valtc"]').is(':checked')) {
                    $('[data-action="valu-check-err"]').html($.mage.__('This is a required field.'));
                    return false;
                }
                
                if ($(".aps-valu .checkout-agreements .required-entry").length) {
                    if (!$(".aps-valu .checkout-agreements .required-entry").is(':checked')) {
                        $('.aps-valu .checkout-agreements .checkout-agreement').after('<div class="valu-check-err error-val">'+$.mage.__('This is a required field.')+'</div>');
                        return false;
                    }
                }
                if (tenure === 0) {
                    $('[data-action="error-purchase"]').text($.mage.__('Please select your installment plan.'));
                    return false;
                }
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_valu.ajaxPurchaseUrl,
                    type: 'post',
                    context: this,
                    data:{mobileNumber:mobileNumber,otp:otp,tenure:tenure,valu_tenure_amount:valuAmount,valu_tenure_interest:valuInterest},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.response_code) {
                            $('[data-action="purchase-btn"]').attr('disabled','disabled');
                            var formId = 'frm_aps_valu_payment';
                            $('[data-action="error-purchase"]').text("");
                            //$(".purchase-btn,.show-valu-slider").css('display','none');
                            
                            $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                            $.each(response, function (k, v) {
                                $('<input>').attr({
                                    type: 'hidden',
                                    id: k,
                                    name: k,
                                    value: v
                                }).appendTo($('#'+formId));
                            });
                            $('#'+formId +' input[name=form_key]').attr("disabled", "disabled");
                            
                            $('#'+formId).attr('action', window.checkoutConfig.payment.apsFort.aps_fort_valu.response);
                            $('#'+formId).submit();

                        } else {
                            var msg = response.error_messages;
                            if (typeof (msg) === 'object') {
                                content: msg.join("\n")
                            }
                            if (msg) {
                                $('[data-action="error-purchase"]').text($.mage.__(response.response_message));
                            }
                        }
                    }
                });
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            isChecked: ko.computed(function () {
                var checked = quote.paymentMethod() ? quote.paymentMethod().method : null;
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
