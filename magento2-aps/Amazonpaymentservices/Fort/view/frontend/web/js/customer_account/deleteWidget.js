/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
    'Magento_Ui/js/modal/modalToggle',
    'mage/translate'
], function ($, modalToggle) {
    'use strict';
    return function (config, deleteButton) {
        config.buttons = [
            {
                text: $.mage.__('Cancel'),
                class: 'action secondary cancel'
        }, {
            text: $.mage.__('Delete'),
            class: 'action primary',

            /**
             * Default action on button click
             */
            click: function (event) {
 //eslint-disable-line no-unused-vars
                event.preventDefault();
                if (config.tokentype == 'aps_fort') {
                    $.ajax({
                        url: config.url,
                        type: 'post',
                        context: this,
                        data:{publicHash:config.publicHash},
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            if (response.response_code == '58000') {
                                $(deleteButton.form).submit();
                                return true;
                            } else if (response.response_code != '58000') {
                                alert("Token already removed from APS");
                                $(deleteButton.form).submit();
                                return true;
                            }
                        }
                        });
                    return false;
                } else {
                    return false;
                    //$(deleteButton.form).submit();
                }
            }
        }
        ];

        modalToggle(config, deleteButton);
    };
});
