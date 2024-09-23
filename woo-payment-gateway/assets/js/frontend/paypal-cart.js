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
        wc_braintree.CartGateway.prototype, {
            params: wc_braintree_paypal_cart_params
        });

    /**
     *
     */
    PayPal.prototype.initialize = function () {
        wc_braintree.CartGateway.call(this);
        $(document.body).on('updated_wc_div, updated_cart_totals', this.dom_refresh.bind(this));
        $('.wc-braintree-cart-gateways-container').addClass('active');
        this.add_cart_totals_class();
    }

    /**
     *
     */
    PayPal.prototype.dom_refresh = function () {
        this.create_button();
        this.create_bnpl_msg('cart', '.cart_totals .shop_table');
        if (this.tokenize_response) {
            $('.wc-braintree-cart-gateways-container').addClass('active');
            this.set_nonce(this.tokenize_response.nonce);
            this.set_device_data();
            this.update_addresses(this.tokenize_response.details);
            $('[name="payment_method"]').val(this.gateway_id);
        }
        $('.wc-braintree-cart-gateways-container').addClass('active');
        this.add_cart_totals_class();
    }

    /**
     *
     */
    PayPal.prototype.render_options = function () {
        var options = wc_braintree.PayPal.prototype.render_options.apply(this, arguments);
        var onInit = options.onInit;
        options.onInit = function (data, actions) {
            onInit.call(this, data, actions);
            if (!this.is_valid_checkout()) {
                this.actions.disable();
            }
        }.bind(this);
        options.onClick = function () {
            if (!this.is_valid_checkout()) {
                this.actions.disable();
                this.submit_error(this.params.messages.terms);
            } else {
                this.actions.enable();
            }
        }.bind(this)
        return options;
    }

    /**
     *
     */
    PayPal.prototype.create_button = function () {
        wc_braintree.PayPal.prototype.create_button.call(this).then(function () {
            $('ul.wc_braintree_cart_gateways').addClass('paypal-active');
            this.create_bnpl_msg('cart', '.cart_totals .shop_table');
            if (this.is_credit_enabled('cart')) {
                $('.paypal-buttons').each(function () {
                    $(this).css('min-width', "0px");
                });
                if ($(this.container).width() <= 415) {
                    this.get_button_container().addClass('wrap-415');
                }
            }
        }.bind(this))
    }

    PayPal.prototype.on_payment_method_received = function (response) {
        if (this.needs_shipping() && !this.is_checkout_flow()) {
            var address = this.map_shipping_address(response.details.shippingAddress);
            address = $.extend({
                first_name: this.fields.get('shipping_first_name'),
                last_name: this.fields.get('shipping_last_name')
            }, address);
            this.open_shipping_modal(address, {
                onShippingMethodSelected: function () {
                    $(document.body).trigger('wc_update_cart');
                }
            });
        } else {
            this.process_checkout();
        }
    }

    /**
     *
     */
    PayPal.prototype.terms_updated = function (e) {
        if ($(e.target).is(':checked')) {
            this.actions.enable();
        } else {
            this.actions.disable();
        }
    }

    /**
     *
     */
    PayPal.prototype.get_button_container = function () {
        return $('.paypal-button-container');
    }

    PayPal.prototype.get_funding = function () {
        var funding = wc_braintree.PayPal.prototype.get_funding.apply(this, arguments);
        if (this.is_credit_enabled('cart')) {
            funding.push(paypal.FUNDING.PAYLATER);
            funding.push(paypal.FUNDING.CREDIT);
        }
        return funding;
    }

    wc_braintree.register(PayPal);

}(jQuery, wc_braintree));