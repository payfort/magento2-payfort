/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'jquery',
    'mageUtils'
], function ($, utils) {
    'use strict';
    var apsConfig = window.checkoutConfig.payment.apsFort.aps_fort_cc || window.checkoutConfig.payment.apsFort.aps_installment || {};
    var jaywaPattern = "^(" + (apsConfig.jaywabin || '') + ")";
    var meezaPattern = apsConfig.meezabin || '';
    var madaPattern = "/^" + (apsConfig.madabin || '') + "/";
    var types = [
        {
            title: 'Jaywan',
            type: 'JW',
            pattern: jaywaPattern,
            gaps: [4, 10],
            lengths: [16],
            code: {
                name: 'CVV',
                size: 3
            }
    },
        {
            title: 'Meeza',
            type: 'MZ',
            pattern: meezaPattern,
            gaps: [4, 10],
            lengths: [16,19],
            code: {
                name: 'CVV',
                size: 3
            }
    },
        {
            title: 'Mada',
            type: 'MD',
            pattern: madaPattern,
            gaps: [4, 10],
            lengths: [16],
            code: {
                name: 'CVV',
                size: 3
            }
    },
        {
            title: 'Visa',
            type: 'VI',
            pattern: '^4\\d*$',
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: 'CVV',
                size: 3
            }
    },
        {
            title: 'MasterCard',
            type: 'MC',
            pattern: '^5[0-5][0-9]{0,16}$|^2[2-7][0-9]{0,16}$',
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: 'CVC',
                size: 3
            }
    },
        {
            title: 'American Express',
            type: 'AE',
            pattern: '^3$|^3[47][0-9]{0,13}$',
            isAmex: true,
            gaps: [4, 10],
            lengths: [15],
            code: {
                name: 'CID',
                size: 4
            }
    }
    ];

    return {
        /**
         * @param {*} cardNumber
         * @return {Array}
         */
        getCardTypes: function (cardNumber) {
            var i, value,
                result = [];

            if (utils.isEmpty(cardNumber)) {
                return result;
            }

            if (cardNumber === '') {
                return $.extend(true, {}, types);
            }

            for (i = 0; i < types.length; i++) {
                value = types[i];
                if (new RegExp(value.pattern).test(cardNumber)) {
                    var cardType = value.type;
                    var flag = 0;
                    $.each(window.checkoutConfig.payment.apsFort.logoImg, function ( key, val ) {
                        $('body .card-logo').removeClass('card-'+key);
                        if (key === cardType) {
                            flag = 1;
                            $('body .card-logo').attr('src',val).css('display','');
                        }
                    });
                    if (flag===1) {
                        $('body .card-logo').addClass('card-'+cardType);
                    }
                    result.push($.extend(true, {}, value));
                    break;
                }
            }

            return result;
        }
    };
});
