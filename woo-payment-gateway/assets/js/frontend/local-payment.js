(function ($, wc_braintree) {

    /**
     * @constructor
     */
    function LocalPayment(params) {
        this.params = params;
        this.button_text = params.button_text;
        this.payment_type = params.payment_type;
        wc_braintree.BaseGateway.call(this);
        if (this.is_active()) {
            $(this.container).show();
        } else {
            $(this.container).hide();
        }
    }

    LocalPayment.prototype = Object.create(wc_braintree.BaseGateway.prototype);

    LocalPayment.prototype = $.extend({}, LocalPayment.prototype, wc_braintree.PayPal.prototype, wc_braintree.CheckoutGateway.prototype)

    /**
     *
     */
    LocalPayment.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
        this.payment_key = '[name="' + this.params.payment_key + '"]';
        $(document.body).on('click', '#place_order', this.start.bind(this));
        $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
        window.addEventListener('hashchange', this.hashchange.bind(this));
    }

    LocalPayment.prototype.is_active = function () {
        return $('#' + this.gateway_id + '_active').data('active') === 1;
    }

    LocalPayment.prototype.hashchange = function (e) {
        if (this.is_gateway_selected()) {
            var matches = window.location.hash.match(/local_payment=(.+)/);
            if (matches) {
                var data = JSON.parse(atob(matches[1]));
                this.order_id = data.order_id;
                this.order_key = data.order_key;
                window.history.pushState({}, '', window.location.pathname);
                this.tokenize();
            }
        }
    }

    LocalPayment.prototype.updated_checkout = function () {
        wc_braintree.CheckoutGateway.prototype.updated_checkout.apply(this, arguments);
        if (this.is_active()) {
            $(this.container).show();
        } else {
            $(this.container).hide();
        }
    }

    /**
     *
     */
    LocalPayment.prototype.create_instance = function (client, client_token) {
        this.clientInstance = client;
        braintree.localPayment.create({
            client: client,
            authorization: client_token,
            merchantAccountId: this.get_merchant_account()
        }, function (err, localPaymentInstance) {
            if (err) {
                this.submit_error(err);
                return;
            }
            this.localPaymentInstance = localPaymentInstance;

            if (this.localPaymentInstance.hasTokenizationParams() && this.is_gateway_selected()) {
                this.localPaymentInstance.tokenize().then(function (payload) {
                    this.on_payment_method_received(payload);
                }.bind(this)).catch(function (err) {
                    this.submit_error(err);
                }.bind(this));
            }
        }.bind(this));
    }

    LocalPayment.prototype.start = function (e) {
        if (this.is_gateway_selected()) {
            if ('order_pay' === this.get_page()) {
                e.preventDefault();
                this.order_id = wc_braintree_local_payment_params.order_id;
                this.order_key = wc_braintree_local_payment_params.order_key;
                this.tokenize();
            } else {
                this.fields.fromFormToFields();
                this.payment_method_received = true;
            }
        }
    }

    /**
     * Tokenize the payment method for the local payment.
     */
    LocalPayment.prototype.tokenize = function () {
        if (this.get_merchant_account() == "") {
            this.submit_error({
                code: 'LOCAL_GATEWAY_INVALID_MERCHANT_ACCOUNT'
            });
            return;
        }
        this.localPaymentInstance.startPayment(this.get_payment_args(), function (err, payload) {
            if (err) {
                if (err.code === "LOCAL_PAYMENT_WINDOW_CLOSED") {
                    this.get_form().removeClass('processing').unblock();
                    return;
                }
                this.submit_error(err);
                return;
            }
            this.on_payment_method_received(payload);
        }.bind(this));
    }

    LocalPayment.prototype.get_payment_args = function () {
        var args = {
            paymentType: this.payment_type,
            amount: this.get_total(),
            email: this.fields.get('billing_email'),
            givenName: this.fields.get('billing_first_name'),
            surname: this.fields.get('billing_last_name'),
            phone: this.fields.get('billing_phone').replaceAll(/[^\d]/g, ''),
            fallback: {
                url: this.params.return_url,
                buttonText: this.button_text
            },
            currencyCode: this.get_currency(),
            shippingAddressRequired: this.needs_shipping(),
            onPaymentStart: function (data, start) {
                // save the payment data to server
                // in case customer does not return to page.
                $(this.payment_key).val(data.paymentId);
                this.store_payment_data(data, this.order_id, this.order_key);
                start();
            }.bind(this)
        };
        var prefix = this.get_shipping_prefix();
        if (this.needs_shipping() && this.is_valid_address(this.get_address_object(prefix), prefix)) {
            args.address = {
                streetAddress: this.fields.get(prefix + '_address_1'),
                locality: this.fields.get(prefix + '_city'),
                region: this.fields.get(prefix + '_state'),
                postalCode: this.fields.get(prefix + '_postcode'),
                countryCode: this.fields.get(prefix + '_country')
            }
        } else {
            args.address = {
                countryCode: this.fields.get('billing_country')
            }
        }
        return args;
    }

    /**
     * [store_payment_data description]
     * @param  {[type]} data [description]
     * @return {[type]}      [description]
     */
    LocalPayment.prototype.store_payment_data = function (data, order_id, order_key) {
        $.ajax({
            url: this.params.routes.payment_data,
            dataType: 'json',
            method: 'POST',
            data: {payment_id: data.paymentId, order_id: order_id, order_key: order_key}
        }).done(function (response) {
            if (response.code) {
                this.submit_error(response.message);
            }
        }.bind(this)).fail(function (xhr, textStatus) {
            this.submit_error(textStatus);
        }.bind(this))
    }

    /**
     *
     */
    LocalPayment.prototype.payment_gateway_changed = function (e, payment_gateway) {
        if (payment_gateway == this.gateway_id) {
            this.show_place_order();
            if (this.payment_method_received) {
                $('#place_order').text($('#place_order').data('value'));
            }
        }
    }

    /**
     *
     */
    LocalPayment.prototype.on_payment_method_received = function (response) {
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
        wc_braintree.PayPal.prototype.update_addresses.call(this, response.details);
        if (this.is_checkout_page()) {
            this.complete_payment();
        } else {
            this.get_form().removeClass('processing');
            this.get_form().trigger('submit');
        }
        $('#place_order').text($('#place_order').data('value'));
    }

    LocalPayment.prototype.complete_payment = function () {
        $.ajax({
            method: 'POST',
            url: this.params.routes.complete_payment,
            dataType: 'json',
            data: $.extend({}, this.fields.toJson(), {_wpnonce: this.params._wp_rest_nonce})
        }).done(function (response) {
            if (response.result && response.result === 'success') {
                window.location = response.redirect;
            } else {
                this.submit_error(response.messages);
            }
        }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
            this.submit_error(errorThrown);
        }.bind(this))
    }

    /**
     * Creates all the local payment instances.
     */
    function Init_Gateways() {

    }

    Init_Gateways.prototype.gateways = {};

    Init_Gateways.prototype.create_instance = function (client, token) {
        for (var id in wc_braintree_local_payment_params.gateways) {
            var gateway;
            if (this.gateways.hasOwnProperty(id)) {
                gateway = this.gateways[id];
            } else {
                gateway = new LocalPayment(wc_braintree_local_payment_params.gateways[id]);
                this.gateways[id] = gateway;
            }
            gateway.create_instance(client, token);
        }
    }

    wc_braintree.register(Init_Gateways);

}(jQuery, wc_braintree))