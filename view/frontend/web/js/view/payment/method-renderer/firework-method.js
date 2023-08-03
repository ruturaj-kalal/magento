/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Firework_Firework/payment/firework'
        },
        getMailingAddress: function () {
            return window.checkoutConfig.payment.checkmo.mailingAddress;
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },
    });
});
