define([
    'jquery'
], function ($) {
    'use strict';

    return function (originalValidator) {
        $.validator.addMethod(
            'validate-cc-cvn',
            function (value, element, params) {
                var ccType,
                    customTypes = {
                        'JW': [new RegExp('^(669010|669009|978450|47878000|622454|650053|650483)[0-9]*$'), new RegExp('^[0-9]{3}$'), true],
                        'MZ': [new RegExp('^[0-9]{16,19}$'), new RegExp('^[0-9]{3}$'), true]
                    };

                if (value && params) {
                    ccType = $(params).val();

                    if (customTypes[ccType] && customTypes[ccType][1]) {
                        return customTypes[ccType][1].test(value);
                    }
                }

                return originalValidator.call(this, value, element, params);
            },
            $.mage.__('Please enter a valid credit card verification number.')
        );

        return originalValidator;
    };
});
