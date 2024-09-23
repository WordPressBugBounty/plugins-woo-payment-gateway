(function ($, wc_braintree) {

    if (typeof wc_braintree_venmo_params === 'undefined') {
        return;
    }

    /**
     * Main Venmo class.
     *
     * @constructor
     */
    function Venmo() {
        wc_braintree.BaseGateway.call(this);
    }

    /**
     * Create base prototypes.
     */
    Venmo.prototype = Object.create(wc_braintree.BaseGateway.prototype);

    Venmo.prototype = $.extend(Venmo.prototype,
        wc_braintree.CheckoutGateway.prototype, {
            params: wc_braintree_venmo_params,
            can_pay: false,
            button_selector: '.wc-braintree-venmo-tokenize',
            button_class: 'wc-braintree-venmo-tokenize',
        });

    /**
     *
     */
    Venmo.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
        this.create_button();
        $(document.body).on('click', this.button_selector, this.start.bind(this));
    }

    /**
     *
     */
    Venmo.prototype.create_instance = function (client, client_token) {
        this.clientInstance = client;
        this.initialize_fraud_tools();
        braintree.venmo.create({
            client: this.clientInstance,
            allowNewBrowserTab: false
        }, function (err, venmoInstance) {
            if (err) {
                this.submit_error(err);
                return;
            }
            this.venmoInstance = venmoInstance;
            if (venmoInstance.isBrowserSupported()) {
                this.can_pay = true;
                this.create_button();
                // show gateway since it's supported.
                $(this.container).show();
                $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
                $(document.body).on('wc_braintree_display_saved_methods', this.display_saved_methods.bind(this));
                $(document.body).on('wc_braintree_display_new_payment_method', this.display_new_payment_method_container.bind(this));
            } else {
                this.hide_checkout_gateway();
                this.hide_payment_button();
            }
        }.bind(this));
    }

    /**
     * Start the Venmo payment sheet.
     */
    Venmo.prototype.start = function (e) {
        e.preventDefault();
        this.tokenize();
    }

    /**
     * Create the Venmo button for the Checkout page.
     */
    Venmo.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }
        this.$button = $(this.params.html.button);
        this.hide_payment_button();
        $('#place_order').after(this.$button);
        this.payment_gateway_changed(null, this.get_selected_gateway());
    }

    /**
     * Tokenize the venmo payment.
     */
    Venmo.prototype.tokenize = function () {
        this.venmoInstance.tokenize(function (err, payload) {
            if (err) {
                this.submit_error(err);
                return;
            }
            this.on_payment_method_received(payload);
        }.bind(this));
    }

    /**
     * Wrapper for wc_braintree.CheckoutGateway.prototype.updated_checkout.
     * Shows the Venmo gateway if it's supported by the browser/device.
     */
    Venmo.prototype.updated_checkout = function () {
        if (this.can_pay) {
            wc_braintree.CheckoutGateway.prototype.updated_checkout.apply(this, arguments);
            this.show_checkout_gateway();
        }
    }

    Venmo.prototype.on_payment_method_received = function () {
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
        if (this.validate_checkout_fields()) {
            this.get_form().trigger('submit');
        }
    }

    wc_braintree.register(Venmo);

}(jQuery, wc_braintree))