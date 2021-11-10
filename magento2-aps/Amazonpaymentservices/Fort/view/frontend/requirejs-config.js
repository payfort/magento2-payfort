var config = {
    map: {
        '*': {
            "aps_fort": 'Amazonpaymentservices_Fort/js/aps_fort',
            'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator/credit-card-type':'Amazonpaymentservices_Fort/js/model/credit-card-validation/credit-card-type'
        }
    },
    paths: {
        slick: 'Amazonpaymentservices_Fort/js/slick.min',
        visa: 'Amazonpaymentservices_Fort/js/view/payment/visa-checkout'
    },
    shim: {
        slick: {
            deps: ['jquery']
        },
        "aps_fort": {
            deps: [
                'jquery' //dependency jquery will load first
            ]
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Amazonpaymentservices_Fort/js/view/minicart-apple': true
            }
        }
    }
};