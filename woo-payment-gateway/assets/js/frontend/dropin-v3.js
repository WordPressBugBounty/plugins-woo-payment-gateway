(function ($, wc_braintree) {

    if (typeof wc_braintree_dropin_v3_params === 'undefined') {
        return;
    }

    /**
     * @constructor
     */
    function CreditCard() {
        wc_braintree.CreditCard.call(this);
    }

    CreditCard.prototype = $.extend({}, wc_braintree.CreditCard.prototype,
        wc_braintree.CheckoutGateway.prototype, {
            params: wc_braintree_dropin_v3_params,
            dropin_id: '#wc-braintree-dropin-form',
            interval_set: false
        });

    /**
     *
     */
    CreditCard.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
        $(document.body).on('click', '#place_order', this.process.bind(this));
        $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
        $(document.body).on('wc_braintree_payment_method_template', this.add_icon_class.bind(this));
        $(document.body).on('wc_braintree_pre_form_submit_' + this.gateway_id, this.set_config_data.bind(this));
        $(document.body).on('change', '#createaccount', this.handle_create_account_change.bind(this));
        this.add_icon_type();
    }

    /**
     *
     */
    CreditCard.prototype.create_instance = function () {
        wc_braintree.CreditCard.prototype.create_instance.apply(this, arguments);
        this.initialize_dropin_instance().then(function () {
            if (!this.interval_set) {
                setInterval(this.check_dropin.bind(this), 2000);
                this.interval_set = true;
            }
        }.bind(this));
        this.handle_create_account_change();
    }

    /**
     *
     */
    CreditCard.prototype.initialize_dropin_instance = function () {
        return new Promise(function (resolve, reject) {
            if ($(this.dropin_id).length) {
                braintree.dropin.create({
                    authorization: this.client_token,
                    container: this.dropin_id,
                    locale: this.params.locale
                }, function (err, dropinInstance) {
                    if (err) {
                        if (err.message.match(/empty DOM node/) ||
                            err.message.match(/All payment options failed to load./)) {
                            return;
                        }
                        this.submit_card_error(err);
                        reject();
                        return;
                    }
                    this.dropinInstance = dropinInstance;
                    resolve();
                }.bind(this))
            }
        }.bind(this))
    }

    /**
     * Process the click event from #place_order.
     */
    CreditCard.prototype.process = function (e) {
        if (this.is_gateway_selected()) {
            if (!this.is_payment_method_selected()) {
                e.preventDefault();
                if (this.validate_checkout_fields()) {
                    this.fields.fromFormToFields();
                    this.tokenize();
                } else {
                    return false;
                }
            } else {
                if (this._3ds_active() && this.params._3ds.verify_vault) {
                    e.preventDefault();
                    this.process_3dsecure_vaulted();
                } else {
                    return true;
                }
            }
        }
    }

    CreditCard.prototype.woocommerce_form_submit = function () {
        // not using saved payment method and have not received nonce yet.
        if (!this.is_payment_method_selected() && !this.payment_method_received) {
            this.process.apply(this, arguments);
            return false;
        } else if (this.is_payment_method_selected()) {
            return true;
        } else {
            return this.payment_method_received;
        }
    }


    CreditCard.prototype.tokenize = function () {
        this.disable_place_order();
        this.dropinInstance.requestPaymentMethod(function (err, payload) {
            if (err) {
                this.submit_card_error(err);
            } else {
                if (this._3ds_active()) {
                    return this.process_3dsecure(payload);
                } else {
                    this.on_payment_method_received(payload);
                }
            }
            this.enable_place_order();
        }.bind(this))
    }

    /**
     * Wrapper for
     * wc_braintree.CheckoutGateway.prototype.on_payment_method_received
     */
    CreditCard.prototype.on_payment_method_received = function () {
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
        this.get_form().trigger('submit');
    }

    /**
     *
     */
    CreditCard.prototype.check_dropin = function () {
        var frames = $(this.container).find(this.dropin_id).find('iFrame');
        if (!frames.length && (typeof this.client_token !== 'undefined')) {
            this.initialize_dropin_instance();
        }
    }

    /**
     *
     */
    CreditCard.prototype.updated_checkout = function () {
        this.check_dropin();
        this.add_icon_type();
        this.handle_create_account_change();
    }

    /**
     *
     */
    CreditCard.prototype.payment_gateway_changed = function (e, payment_gateway) {
        if (payment_gateway === this.gateway_id) {
            this.show_place_order();
        }
    }

    CreditCard.prototype.checkout_error = function (e) {
        if (this.is_gateway_selected()) {
            this.dropinInstance.clearSelectedPaymentMethod();
            wc_braintree.CheckoutGateway.prototype.checkout_error.apply(this, arguments);
        }
    }

    CreditCard.prototype.teardown_3ds = function () {
        this.dropinInstance.clearSelectedPaymentMethod();
        wc_braintree.CreditCard.prototype.teardown_3ds.apply(this, arguments);
    }

    wc_braintree.register(CreditCard);

}(jQuery, wc_braintree))