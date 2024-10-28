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
                template: 'Amazonpaymentservices_Fort/payment/aps-stc-merchant'
            },
            getTitle: function () {
                
                return window.checkoutConfig.payment.apsFort.aps_fort_stc.title
            },
            getStcCard : function () {
                return window.checkoutConfig.payment.apsFort.aps_fort_stc.data;
            },
            newCard: function () {
                stcToken = $("input[name='stctoken']:checked").val();
                if (stcToken == 'newCard') {
                    $('.stcpay .stcgroup').removeClass('stchide');
                } else {
                    $('.stcpay .stcgroup').addClass('stchide');
                }
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
            requestOtp: function () {
                $('[data-action="aps-stcotp-error"]').text('');
                var mobileNumber = $('[data-action="stcpf-mobileNumber"]').val();
                
                if (!Number.isInteger(parseInt(mobileNumber))) {
                    $('[data-action="aps-stcotp-error"]').text($.mage.__('Please enter mobile number.'));
                    return false;
                }
                if (mobileNumber.length > 19 || mobileNumber.length < 10) {
                    $('[data-action="aps-stcotp-error"]').text($.mage.__('Mobile number is not valid.'));
                    return false;
                }
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_stc.ajaxOtpUrl,
                    type: 'post',
                    context: this,
                    data:{mobileNumber:mobileNumber},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.response_code != "88000") {
                            $('[data-action="aps-stcotp-error"]').text($.mage.__(response.response_message));
                        } else {
                            $('[data-action="aps-stcotp-msg"]').text($.mage.__('OTP has been sent to you on your mobile number : +966') + mobileNumber);
                            $('.stcpay .stcphonenumber').addClass('stchide');
                            $('.stcpay .stcotp').removeClass('stchide');
                        }
                    }
                });
            },
            afterTokenRender: function () {
                if (window.checkoutConfig.payment.apsFort.aps_fort_stc.tokenstatus == 0) {
                    $('.apsstcpay .newcardtype').css('display', 'none');
                    $('.apsstcpay .stcgroup').removeClass('stchide');
                    stcToken = 'newCard';
                } else if (window.checkoutConfig.payment.apsFort.aps_fort_stc.hasOwnProperty('data') == false) {
                    $('.apsstcpay .newcardtype').css('display', 'none');
                    $('.apsstcpay .stcgroup').removeClass('stchide');
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
                var otp = $("input[name='otp']").val();
                var mobileNumber = $('[data-action="stcpf-mobileNumber"]').val();
                console.log(stcToken);
                console.log(window.checkoutConfig.payment.apsFort.aps_fort_stc.getStcData);
                $.ajax({
                    url: window.checkoutConfig.payment.apsFort.aps_fort_stc.getStcData,
                    type: 'post',
                    context: this,
                    data:{stcToken:stcToken,otp:otp,mobileNumber:mobileNumber},
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.success) {
                            var randomNum = new Date().getTime();
                            $.mage.redirect(response.url+"?id="+randomNum);
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
            },
            redirectAfterPlaceOrder: false
        });
    }
);