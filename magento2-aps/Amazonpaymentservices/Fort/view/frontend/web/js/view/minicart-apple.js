define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'sidebar',
    'mage/translate',
    'mage/dropdown'
], function (Component, customerData, $, ko, _) {
    'use strict';
    var mixin = {
        isButtonEnable: function () {
            /*You can add your condition here based on your requirements.*/
            return true;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});