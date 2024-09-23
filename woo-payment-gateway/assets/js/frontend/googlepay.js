(function ($, wc_braintree) {

    if (typeof wc_braintree_googlepay_params === 'undefined') {
        return;
    }

    /**
     * @constructor
     */
    function GooglePay() {
        wc_braintree.GooglePay.call(this);
    }

    /**
     *
     */
    GooglePay.prototype = $.extend({}, wc_braintree.GooglePay.prototype,
        wc_braintree.CheckoutGateway.prototype, {
            params: wc_braintree_googlepay_params,
            cannot_pay: false
        });

    /**
     *
     */
    GooglePay.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
        this.maybe_show_gateway();
    }

    /**
     *
     */
    GooglePay.prototype.create_instance = function () {
        wc_braintree.GooglePay.prototype.create_instance.apply(this, arguments).then(function () {
            $(this.container).show();
            if (this.banner_enabled) {
                var $button = $(this.paymentsClient.createButton($.extend({onClick: this.banner_checkout.bind(this)}, this.params.button_options)));
                if (this.is_rectangle_button()) {
                    $button.find('button').removeClass('new_style');
                } else {
                    $button.find('button').addClass('gpay-button-round');
                }
                $button.addClass('wc-braintree-googlepay-top-container');
                $(this.banner_container).empty().prepend($button);
            }
            $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
            $(document.body).on('wc_braintree_display_saved_methods', this.display_saved_methods.bind(this));
            $(document.body).on('wc_braintree_display_new_payment_method', this.display_new_payment_method_container.bind(this));
            setInterval(this.maybe_show_gateway.bind(this), 2000);
        }.bind(this)).catch(function () {
            $(this.container).hide();
        }.bind(this))
    }

    /**
     *
     */
    GooglePay.prototype.create_button = function () {
        wc_braintree.GooglePay.prototype.create_button.call(this).then(function () {
            this.hide_payment_button();
            $("#place_order").after(this.$button);
            this.payment_gateway_changed(null, this.get_selected_gateway());
        }.bind(this));
    }

    GooglePay.prototype.tokenize = function () {
        this.fields.fromFormToFields();
        wc_braintree.GooglePay.prototype.tokenize.apply(this, arguments);
    }

    /**
     * Update the gateway when WC calls the updated_checkout trigger.
     */
    GooglePay.prototype.updated_checkout = function (e) {
        wc_braintree.CheckoutGateway.prototype.updated_checkout.apply(this, arguments);
        this.maybe_show_gateway();
    }

    /**
     *
     */
    GooglePay.prototype.banner_checkout = function (e) {
        this.tokenize();
        this.set_payment_method(this.gateway_id);
        this.set_use_nonce_option(true);
        $('[name="terms"]').prop('checked', true);
    }

    /**
     *
     */
    GooglePay.prototype.maybe_show_gateway = function () {
        if (!this.cannot_pay) {
            this.show_checkout_gateway();
        }
    }

    GooglePay.prototype.on_payment_method_received = function () {
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
        if (this.payment_request_options.shippingAddressRequired) {
            this.maybe_set_ship_to_different();
        }
        this.fields.toFormFields({update_shipping_method: false});
        if (this.validate_checkout_fields()) {
            this.get_form().trigger('submit');
        }
    }

    GooglePay.prototype.update_shipping = function (address) {
        return wc_braintree.BaseGateway.prototype.update_shipping.apply(this, arguments).then(function (response) {
            this.populate_address_fields(address, this.get_shipping_prefix());
            this.fields.toFormFields({update_shipping_method: false});
            return response;
        }.bind(this));
    }

    wc_braintree.register(GooglePay);

}(jQuery, wc_braintree));