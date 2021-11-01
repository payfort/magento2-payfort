var apsFort = (function () {
    return {
        validateCardHolderName: function (element) {
            jQuery(element).val(this.trimString(element.val()));
            var cardHolderName = jQuery(element).val();
            if (cardHolderName.length > 50) {
                return false;
            }
            return true;
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
            if (typeof (variable) === 'undefined' || typeof (variable) === null) {
                return false;
            }
            return true;
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
 
 var apsFortStandard = (function () {
     return {
            showStandard: function (gatewayUrl) {
                if (jQuery("#aps_merchant_page").size()) {
                    jQuery("#aps_merchant_page").remove();
                }
                jQuery("#review-buttons-container .btn-checkout").hide();
                jQuery("#review-please-wait").show();
                jQuery('<iframe  name="aps_merchant_page" id="aps_merchant_page"height="640px" frameborder="0" scrolling="no" onload="apsFortStandard.iframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
                jQuery('.pf-iframe-spin').show();
                jQuery('.pf-iframe-close').hide();
                jQuery("#aps_merchant_page").attr("src", gatewayUrl);
                //jQuery( "#aps_payment_form" ).attr("action",gatewayUrl);
                jQuery("#aps_payment_form").attr("target","aps_merchant_page");
                jQuery("#aps_payment_form").submit();
                //fix for touch devices
                if (apsFort.isTouchDevice()) {
                    setTimeout(function () {
                        jQuery("html, body").animate({ scrollTop: 0 }, "slow");
                    }, 1);
                }
                jQuery("#div-pf-iframe").show();
            },
            closePopup: function () {
                jQuery("#div-pf-iframe").hide();
                jQuery("#aps_merchant_page").remove();
                window.location = jQuery("#aps_cancel_url").val();
            },
            iframeLoaded: function () {
                jQuery('.pf-iframe-spin').hide();
                jQuery('.pf-iframe-close').show();
                jQuery('#aps_merchant_page').show();
            },
        };
 })();