(function($, wc_braintree) {
    if (typeof wc_braintree_applepay_cart_params === 'undefined') {
        return;
    }

    /**
     * @constructor
     */
    function ApplePay() {
        wc_braintree.ApplePay.call(this);
    }

    ApplePay.prototype = $.extend({}, wc_braintree.ApplePay.prototype,
        wc_braintree.CartGateway.prototype, {
            params: wc_braintree_applepay_cart_params,
            applepay_button: '.apple-pay-button'
        });

    /**
     * 
     */
    ApplePay.prototype.initialize = function() {
        wc_braintree.CartGateway.call(this);
    }

    /**
     * Called when the "updated_wc_div" and "updated_cart_totals" events are
     * fired by WC.
     */
    ApplePay.prototype.updated_page = function() {
        $(this.container).show();
        $('.wc-braintree-cart-gateways-container').addClass('active');
    }

    /**
     * 
     */
    ApplePay.prototype.create_instance = function() {
        wc_braintree.ApplePay.prototype.create_instance.apply(this, arguments).then(function() {
            $(document.body).on('click', this.applepay_button, this.start.bind(this));
            $(this.container).show();
            $('.wc-braintree-cart-gateways-container').addClass('active');
            this.add_cart_totals_class();
            $(document.body).on('updated_wc_div, updated_cart_totals', this.updated_page.bind(this));
        }.bind(this)).catch(function() {
            $(this.container).hide();
        }.bind(this))
    }

    /**
     * Function that is called when the Apple button is clicked. Creates the
     * payment request and then opens the wallet.
     */
    ApplePay.prototype.start = function(e) {
        e.preventDefault();
        if (this.is_valid_checkout()) {
            this.init_wallet();
            this.open_wallet();
        } else {
            this.submit_error(this.params.messages.terms);
        }
    }

    /**
     * 
     * 
     */
    ApplePay.prototype.onshippingmethodselected = function(event) {
        wc_braintree.ApplePay.prototype.onshippingmethodselected.apply(this, arguments).then(function(response) {
            $(document.body).trigger('wc_update_cart');
        }.bind(this))
    }

    wc_braintree.register(ApplePay);

}(jQuery, wc_braintree));