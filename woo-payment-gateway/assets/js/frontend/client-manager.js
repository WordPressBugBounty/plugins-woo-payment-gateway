// wrap in jQuery so manager is called when DOM loads. This ensures
// all scripts listening for client creation receive it.
(function ($) {
    var manager = {
        params: wc_braintree_client_manager_params,
        init: function () {
            this.container = this.get_container_class();
            this.currency = this.params.currency;
            $(document.body).on('update_checkout', this.update_currency);
            $(document.body).on('updated_checkout', this.updated_checkout);
            $(document.body).on('removed_coupon_in_checkout', this.coupon_removed.bind(this));
            if (this.params.page_id === 'checkout') {
                if (typeof wc_braintree_cart_needs_payment === 'undefined') {
                    this.create_client();
                }
            } else {
                this.create_client();
            }
        },
        is_processing: function () {
            return this.processing;
        },
        create_client: function (update) {
            this.processing = true;
            if (typeof wc_braintree_client_token === 'undefined') {
                manager.submit_error({
                    code: 'INVALID_CLIENT_TOKEN'
                });
                return;
            }
            if (Array.isArray(wc_braintree_client_token)) {
                wc_braintree_client_token = wc_braintree_client_token[0];
            }
            braintree.client.create({
                authorization: wc_braintree_client_token
            }, function (err, clientInstance) {
                this.processing = false;
                if (err) {
                    if (err.code && err.code === 'CLIENT_AUTHORIZATION_INVALID') {
                        return manager.get_client_token();
                    }
                    return manager.submit_error(err);
                }
                manager.client = clientInstance;
                if (typeof update !== 'undefined') {
                    wc_braintree.triggerClientUpdate(clientInstance, manager.params.merchant_account);
                } else {
                    wc_braintree.triggerClientReady(clientInstance, manager.params.merchant_account);
                }
                // deprecated
                $(document.body).triggerHandler('wc_braintree_client_created', [clientInstance, wc_braintree_client_token, manager.params.merchant_account]);
            });
        },
        submit_error: function (error) {
            $(document.body).triggerHandler('wc_braintree_submit_error', {error: error, element: manager.container});
        },
        get_container_class: function () {
            if ($('body').hasClass('woocommerce-cart')) {
                return 'div.woocommerce';
            }
            if ($('body').hasClass('woocommerce-checkout')) {
                return 'ul.payment_methods';
            }
            if ($('body').hasClass('woocommerce-add-payment-method')) {
                return 'div.woocommerce';
            }
            if ($('body').hasClass('single-product')) {
                return 'div.woocommerce-notices-wrapper';
            }
        },
        update_currency: function () {
            manager.currency = manager.get_currency();
        },
        get_currency: function () {
            var $payment_method = $('[id^="payment_method_braintree_"][name="payment_method"]').first();
            if ($payment_method) {
                var data = $('.woocommerce_' + $payment_method.val() + '_data').data('gateway');
                return data ? data.currency : manager.currency;
            }
            return manager.currency;
        },
        updated_checkout: function () {
            if (manager.currency !== manager.get_currency()) {
                manager.get_client_token();
                manager.currency = manager.get_currency();
            }
            if (!manager.client && !manager.is_processing() && typeof wc_braintree_cart_needs_payment !== 'undefined' && $('[name="payment_method"]').length > 0) {
                manager.create_client();
            }
        },
        get_client_token: function () {
            $.ajax({
                method: 'POST',
                url: manager.params.url,
                data: {_wpnonce: this.params._wpnonce, currency: manager.get_currency()}
            }).done(function (response) {
                wc_braintree_client_token = response;
                manager.create_client(true);
            }).fail(function () {
                //fail gracefully.
            });
        },
        coupon_removed: function () {
            if (!manager.client && typeof wc_braintree_cart_needs_payment !== 'undefined') {
                $(document.body).one('updated_checkout', function () {
                    manager.create_client();
                });
            }
        }
    };
    manager.init();
}(jQuery, window.wc_braintree));