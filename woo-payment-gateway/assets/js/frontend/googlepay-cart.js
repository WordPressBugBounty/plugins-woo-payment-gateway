(function ($, wc_braintree) {

    /**
     * @constructor
     */
    function GooglePay() {
        wc_braintree.GooglePay.call(this, wc_braintree.CartGateway);
    }

    // define GooglePay class
    GooglePay.prototype = $.extend({}, wc_braintree.GooglePay.prototype, wc_braintree.CartGateway.prototype, {
        params: wc_braintree_googlepay_cart_params,
    });

    GooglePay.prototype.initialize = function () {
        wc_braintree.CartGateway.call(this);
    }

    /**
     * Calls the wc_braintree.GooglePay.payment_data_callback function then
     * updates the selected shipping method on the cart page.
     */
    GooglePay.prototype.after_payment_data_callback = function (data) {
        $(document.body).trigger('wc_update_cart');
    }

    /**
     * Wrapper for tokenize. Check if cart page is valid before tokenizing.
     */
    GooglePay.prototype.tokenize = function () {
        if (this.is_valid_checkout()) {
            wc_braintree.GooglePay.prototype.tokenize.apply(this, arguments);
        } else {
            this.submit_error(this.params.messages.terms);
        }
    }

    /**
     * Create the Braintree GooglePay instance by calling the parent class, then
     * perform cart page specific operations.
     */
    GooglePay.prototype.create_instance = function () {
        wc_braintree.GooglePay.prototype.create_instance.apply(this, arguments).then(function () {
            $(this.container).show();
            this.add_cart_totals_class();
            $('.wc-braintree-cart-gateways-container').addClass('active');
            $(document.body).on('updated_wc_div, updated_cart_totals', this.updated_cart_totals.bind(this));
        }.bind(this)).catch(function () {
            $(this.container).hide();
        }.bind(this));
    }

    /**
     * Override the standard create_button function so the Google Pay button can
     * be decorate with additional classes.
     */
    GooglePay.prototype.create_button = function () {
        wc_braintree.GooglePay.prototype.create_button.call(this).then(function () {
            this.$button.addClass('wc-braintree-googlepay-button-container');
            $(this.container).append(this.$button);
        }.bind(this));
    }

    GooglePay.prototype.updated_cart_totals = function () {
        this.create_payments_client();
        this.dom_refresh();
    }

    /**
     * Refresh the Google Pay elements after the cart totals have been
     * calculated.
     */
    GooglePay.prototype.dom_refresh = function () {
        this.create_button();
        $(this.container).show();
        $('.wc-braintree-cart-gateways-container').addClass('active');
        this.add_cart_totals_class();
    }

    GooglePay.prototype.on_payment_method_received = function (response) {
        this.paymentData.nonce = response.nonce;
        this.process_checkout();
    }

    wc_braintree.register(GooglePay);

}(jQuery, wc_braintree))