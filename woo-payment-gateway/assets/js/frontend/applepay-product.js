(function ($, wc_braintree) {

    if (typeof wc_braintree_applepay_product_params === "undefined") {
        return;
    }

    /**
     * @constructor
     */
    function ApplePay() {
        wc_braintree.ApplePay.call(this);
    }

    ApplePay.prototype = $.extend({}, wc_braintree.ApplePay.prototype, wc_braintree.ProductGateway.prototype, {
        params: wc_braintree_applepay_product_params
    })

    /**
     *
     */
    ApplePay.prototype.initialize = function () {
        wc_braintree.ProductGateway.call(this);
        this.$button = $(this.container).find('.apple-pay-button');
        $(document.body).on('click', '.apple-pay-button', this.add_to_cart.bind(this));
    }

    /**
     *
     */
    ApplePay.prototype.create_instance = function () {
        wc_braintree.ApplePay.prototype.create_instance.apply(this, arguments).then(function () {
            $(this.container).show().parent().show();
            if (this.is_variable_product()) {
                if (!this.is_variable_product_selected()) {
                    this.disable_payment_button();
                } else {
                    this.enable_payment_button();
                }
            }
        }.bind(this)).catch(function () {
            $(this.container).hide();
        }.bind(this));
    }

    /**
     * @param {Event}
     */
    ApplePay.prototype.add_to_cart = function (e) {
        this.init_wallet();
        wc_braintree.BaseGateway.prototype.add_to_cart.apply(this, arguments).then(function (response) {
            this.set_items(response.data[this.gateway_id].lineItems);
            this.open_wallet();
        }.bind(this))
    }

    wc_braintree.register(ApplePay);

}(jQuery, wc_braintree))