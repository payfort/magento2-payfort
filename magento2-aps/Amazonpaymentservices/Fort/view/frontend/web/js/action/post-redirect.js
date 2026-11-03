/**
 * Navigate to a local state-changing endpoint with a keyed POST instead of a GET.
 *
 * @category    APS
 * @package     APS
 */
/*global define*/
define(['jquery', 'mage/cookies'], function ($) {
    'use strict';

    return function (url) {
        var form = $('<form method="POST"></form>').attr('action', url).css('display', 'none');

        $('<input>').attr({type: 'hidden', name: 'form_key', value: $.mage.cookies.get('form_key')}).appendTo(form);
        form.appendTo('body');
        form.submit();
    };
});
