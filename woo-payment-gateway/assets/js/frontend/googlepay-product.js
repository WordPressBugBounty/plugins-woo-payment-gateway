(function ($, wc_braintree) {

    var GooglePay = function () {
        wc_braintree.GooglePay.call(this);
    };

    GooglePay.prototype = $.extend({}, wc_braintree.GooglePay.prototype, wc_braintree.ProductGateway.prototype, {
        params: wc_braintree_googlepay_product_params,
    });

    /**
     *
     */
    GooglePay.prototype.initialize = function () {
        this.params.button_options.buttonSizeMode = 'fill';
        wc_braintree.ProductGateway.call(this);
    }

    /**
     *
     */
    GooglePay.prototype.create_instance = function () {
        wc_braintree.GooglePay.prototype.create_instance.apply(this, arguments).then(function () {
            $(this.container).show().parent().show();
        }.bind(this)).catch(function () {
            $(this.container).hide();
        })
    }

    /**
     *
     */
    GooglePay.prototype.create_button = function () {
        wc_braintree.GooglePay.prototype.create_button.call(this).then(function () {
            this.$button.addClass('wc-braintree-googlepay-button-container');
            $(this.container).append(this.$button);
            if (this.is_variable_product()) {
                if (!this.is_variable_product_selected()) {
                    this.disable_payment_button();
                } else {
                    this.enable_payment_button();
                }
            }
        }.bind(this));
    }

    /**
     *
     */
    GooglePay.prototype.tokenize = function (e) {
        this.add_to_cart(e).then(function (response) {
            this.set_items(response.data[this.gateway_id].displayItems);
            wc_braintree.GooglePay.prototype.tokenize.call(this);
        }.bind(this));
    }

    /**
     *
     */
    GooglePay.prototype.get_button = function () {
        return this.$button.find('button');
    }

    wc_braintree.register(GooglePay);

}(jQuery, wc_braintree))