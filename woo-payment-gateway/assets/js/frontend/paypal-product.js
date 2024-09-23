(function ($, wc_braintree) {
    /**
     * @constructor
     */
    function PayPal() {
        wc_braintree.PayPal.call(this);
    }

    /**
     *
     */
    PayPal.prototype = $.extend({}, wc_braintree.PayPal.prototype,
        wc_braintree.ProductGateway.prototype, {
            params: wc_braintree_paypal_product_params
        });

    /**
     *
     */
    PayPal.prototype.initialize = function () {
        wc_braintree.ProductGateway.call(this);
        $(document.body).on('change', '[name="quantity"]', this.quantity_change.bind(this));
    }

    /**
     *
     */
    PayPal.prototype.render_options = function () {
        var options = wc_braintree.PayPal.prototype.render_options.apply(this, arguments);
        var onInit = options.onInit,
            createOrder = options.createOrder,
            createBillingAgreement = options.createBillingAgreement;
        options.onInit = function () {
            onInit.apply(this, arguments);
            if ($('[name="variation_id"]').length) {
                var variation_id = $('[name="variation_id"]').val();
                if (variation_id == "0" || variation_id == "") {
                    this.actions.disable();
                }
            }
        }.bind(this)
        if (options.createOrder) {
            options.createOrder = function (resolve, reject) {
                return this.add_to_cart().then(function () {
                    return createOrder.apply(this, arguments);
                }.bind(this))
            }.bind(this)
        } else if (options.createBillingAgreement) {
            options.createBillingAgreement = function (resolve, reject) {
                return this.add_to_cart().then(function () {
                    return createBillingAgreement.apply(this, arguments);
                }.bind(this))
            }.bind(this)
        }
        return options;
    }

    /**
     *
     */
    PayPal.prototype.create_button = function () {
        wc_braintree.PayPal.prototype.create_button.call(this).then(function () {
            $('ul.wc_braintree_product_gateways').addClass('paypal-active');
            $(this.container).parent().show();
            this.create_bnpl_msg('product', 'p.price');
            this.get_button_container().addClass(this.params.display_type);
            if (this.params.options.offerCredit) {
                $('.paypal-buttons').each(function () {
                    $(this).css('min-width', "0px");
                });
            }
            if (this.is_variable_product()) {
                if (!this.is_variable_product_selected()) {
                    this.disable_payment_button();
                } else {
                    this.enable_payment_button();
                }
            }
        }.bind(this))
    }

    /**
     * Returns the jQuery element that the PayPal button should be appended to.
     *
     * @returns {jQuery}
     */
    PayPal.prototype.get_button_container = function () {
        return $('.wc-braintree-paypal-button');
    }

    /**
     * Wrapper for ProductGateway.prototype.found_variation. Enables the PayPal
     * button since a variation has been selected.
     */
    PayPal.prototype.found_variation = function () {
        wc_braintree.ProductGateway.prototype.found_variation.apply(this, arguments);
        this.create_bnpl_msg('product', 'p.price');
        if (this.actions) {
            this.actions.enable();
        }
    }

    /**
     * Wrapper for ProductGateway.prototype.reset_variation_data. Disables the
     * PayPal button since no variation is selected.
     */
    PayPal.prototype.reset_variation_data = function () {
        wc_braintree.ProductGateway.prototype.reset_variation_data.apply(this, arguments);
        if (this.actions) {
            this.actions.disable();
        }
    }

    PayPal.prototype.get_funding = function () {
        var funding = wc_braintree.PayPal.prototype.get_funding.apply(this, arguments);
        if (this.is_credit_enabled('product')) {
            funding.push(paypal.FUNDING.PAYLATER);
            funding.push(paypal.FUNDING.CREDIT);
        }
        return funding;
    }

    PayPal.prototype.on_payment_method_received = function (response) {
        if (this.needs_shipping() && !this.is_checkout_flow()) {
            var address = this.map_shipping_address(response.details.shippingAddress);
            address = $.extend({
                first_name: this.fields.get('shipping_first_name'),
                last_name: this.fields.get('shipping_last_name')
            }, address);
            this.open_shipping_modal(address);
        } else {
            this.process_checkout();
        }
    }

    PayPal.prototype.quantity_change = function () {
        this.create_bnpl_msg('product', 'p.price');
    }

    wc_braintree.register(PayPal);

}(jQuery, wc_braintree))