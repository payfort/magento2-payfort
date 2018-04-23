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
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
    ],
    function (
        $,
        Component,
        rendererList,
        selectPaymentMethodAction,
        checkoutData
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'payfort_fort_cc',
                component: window.checkoutConfig.payment.payfortFort.payfort_fort_cc.integrationType == 'merchantPage2' ? 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_cc_merchant_page2-method' : 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_cc-method'
            },
            {
                type: 'payfort_fort_installments',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_installments-method'
            },
            {
                type: 'payfort_fort_sadad',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_sadad-method'
            },
            {
                type: 'payfort_fort_naps',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_naps-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({
            initialize : function() {
                this._super();
                if(window.checkoutConfig.payment.payfortFort.configParams.gatewayCurrency == 'front') {
                    $(document).on('change', 'input[name="payment[method]"]', function (){
                        if($(this).val() == 'payfort_fort_cc' || $(this).val() == 'payfort_fort_sadad' || $(this).val() == 'payfort_fort_naps' || $(this).val() == 'payfort_fort_installments' ) {
                            $('.totals.charge').hide();
                        }
                        else {
                            $('.totals.charge').show();
                        }
                    });
                }
                return true;
            },
        });
    }
);