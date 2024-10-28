var apsFort = (function () {
    return {
        validateCardHolderName: function (element) {
            jQuery(element).val(this.trimString(element.val()));
            var cardHolderName = jQuery(element).val();
            return cardHolderName.length <= 50;

        },
        translate: function (key, category, replacments) {
            if (!this.isDefined(category)) {
                category = 'aps_fort';
            }
            var message = (arr_messages[category + '.' + key]) ? arr_messages[category + '.' + key] : key;
            if (this.isDefined(replacments)) {
                jQuery.each(replacments, function (obj, callback) {
                    message = message.replace(obj, callback);
                });
            }
            return message;
        },
        isDefined: function (variable) {
            return !(typeof (variable) === 'undefined' || typeof (variable) === null);
        },
        isTouchDevice: function () {
            return 'ontouchstart' in window        // works on most browsers
                || navigator.maxTouchPoints;       // works on IE10/11 and Surface
        },
        trimString: function (str) {
            return str.trim();
        },
        isPosInteger: function (data) {
            var objRegExp  = /(^\d*$)/;
            return objRegExp.test(data);
        }
    };
})();
 
 var apsFortHosted = (function () {
     var hostedFormId = 'frm_aps_fort_payment';
     return {
            validateCcForm: function () {
                this.hideError();
                var isValid = apsFort.validateCardHolderName($('#aps_fort_card_holder_name'));
                if (!isValid) {
                    this.showError(apsFort.translate('error_invalid_card_holder_name'));
                    return false;
                }
                var expDate = jQuery('#aps_fort_expiry_year').val()+''+jQuery('#aps_fort_expiry_month').val();
                jQuery('#aps_fort_expiry').val(expDate);
                return true;
            },
            showError: function (msg) {
                jQuery('#aps_fort_msg').html(msg);
                jQuery('#aps_fort_msg').show();
            },
            hideError: function () {
                jQuery('#aps_fort_msg').hide();
            }
        };
 })();
 