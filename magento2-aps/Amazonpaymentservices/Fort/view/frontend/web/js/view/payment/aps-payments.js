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
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data'
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
                type: 'aps_fort_cc',
                component: window.checkoutConfig.payment.apsFort.aps_fort_cc.integrationType == 'hosted' ? 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_cc_merchant_page2-method' : 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_cc-method'
            },
            {
                type: 'aps_fort_naps',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_naps-method'
            },
            {
                type: 'aps_knet',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_knet-method'
            },
            {
                type: 'aps_apple',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_apple-method'
            },
            {
                type: 'aps_installment',
                component: window.checkoutConfig.payment.apsFort.aps_installment.integrationType == 'hosted' ? 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_installment_hosted-method' : 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_installment-method'
            },
            {
                type: 'aps_fort_valu',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_valu-method'
            },
            {
                type: 'aps_fort_visaco',
                component: window.checkoutConfig.payment.apsFort.aps_fort_visaco.integrationType == 'hosted' ? 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_visacheckout_merchant_page2-method' : 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_visacheckout_redirect'
            },
            {
                type: 'aps_fort_stc',
                component: window.checkoutConfig.payment.apsFort.aps_fort_stc.integrationType == 'hosted' ? 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_stc-merchant' : 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_stc'
            },
            {
                type: 'aps_omannet',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_omannet-method'
            },
            {
                type: 'aps_benefit',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_benefit-method'
            },
            {
                type: 'aps_fort_tabby',
                component: 'Amazonpaymentservices_Fort/js/view/payment/method-renderer/aps_fort_tabby'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({
            initialize : function () {
                this._super();
                return true;
            },
        });
    }
);
