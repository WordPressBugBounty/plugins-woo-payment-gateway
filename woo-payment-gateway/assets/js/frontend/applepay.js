(function ($, wc_braintree) {

    if (typeof wc_braintree_applepay_params === 'undefined') {
        return;
    }

    /**
     * @constructor
     */
    function ApplePay() {
        wc_braintree.ApplePay.call(this);
    }

    ApplePay.prototype = $.extend({}, wc_braintree.ApplePay.prototype,
        wc_braintree.CheckoutGateway.prototype, {
            params: wc_braintree_applepay_params,
            applepay_button: '.apple-pay-button'
        });

    /**
     *
     */
    ApplePay.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
    }

    /**
     *
     */
    ApplePay.prototype.create_instance = function () {
        wc_braintree.ApplePay.prototype.create_instance.apply(this, arguments).then(function () {
            this.create_button();
            $(document.body).on('click', this.applepay_button, this.start.bind(this));
            this.show_checkout_gateway();
            $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
            $(document.body).on('wc_braintree_display_saved_methods', this.display_saved_methods.bind(this));
            $(document.body).on('wc_braintree_display_new_payment_method', this.display_new_payment_method_container.bind(this));
            if (this.banner_enabled) {
                var $button = $('<div class="applepay-top-container">' + this.params.button_html + '</div>');
                $button.addClass('applepay-express-checkout');
                $(this.banner_container).empty().prepend($button);
            }
        }.bind(this)).catch(function () {
            $(this.container).hide();
        }.bind(this))
    }

    /**
     * Start the Apple Pay session.
     */
    ApplePay.prototype.start = function (e) {
        e.preventDefault();
        this.fields.fromFormToFields();
        if ($(e.currentTarget).is('.applepay-express-checkout')) {
            this.set_use_nonce_option(true);
            this.set_payment_method(this.gateway_id);
            $('[name="terms"]').prop('checked', true);
        }

        this.init_wallet();
        this.open_wallet();
    }

    /**
     * Create the Apple Pay button.
     */
    ApplePay.prototype.create_button = function () {
        var $container = $('#place_order').parent();
        // remove existing button just in case it wasn't refreshed.
        $container.find('.apple-pay-button').remove();
        this.$button = $(this.params.button_html);
        $('#place_order').after(this.$button);
        this.payment_gateway_changed(null, this.get_selected_gateway());
    }

    /**
     * Wrapper for wc_braintree.CheckoutGateway.prototype.updated_checkout.
     * Displays the Apple Pay gateway if Apple Pay is supported.
     */
    ApplePay.prototype.updated_checkout = function () {
        if (this.can_initialize_applepay()) {
            this.show_checkout_gateway();
            wc_braintree.CheckoutGateway.prototype.updated_checkout.apply(this, arguments);
        }
    }

    ApplePay.prototype.on_payment_method_received = function () {
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
        if (this.paymentRequest.requiredShippingContactFields.indexOf('postalAddress') > -1) {
            this.maybe_set_ship_to_different();
        }
        this.fields.toFormFields({update_shipping_method: false});
        if (this.validate_checkout_fields()) {
            this.get_form().trigger('submit');
        }
    }

    ApplePay.prototype.update_shipping_address = function () {
        return wc_braintree.BaseGateway.prototype.update_shipping_address.apply(this, arguments).then(function (response) {
            this.populate_address_fields(response.data.address, this.get_shipping_prefix());
            this.fields.toFormFields({update_shipping_method: false});
            return response;
        }.bind(this));
    }

    wc_braintree.register(ApplePay);

}(jQuery, wc_braintree))