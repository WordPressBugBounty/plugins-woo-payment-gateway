(function ($, wc_braintree) {

    /**
     * @constructor
     */
    function CreditCard() {
        wc_braintree.CreditCard.call(this);
    }

    CreditCard.prototype = $.extend({},
        wc_braintree.CreditCard.prototype,
        wc_braintree.CheckoutGateway.prototype, {
            params: wc_braintree_hosted_fields_params,
            events: {
                'validityChange': 'braintree_field_validity_change',
                'cardTypeChange': 'braintree_card_type_change',
                'empty': 'braintree_field_empty',
                'notEmpty': 'braintree_field_not_empty',
                'focus': 'braintree_field_focus',
                'blur': 'braintree_field_blur',
                'inputSubmitRequest': 'braintree_card_input_submit_request'
            }
        })

    /**
     *
     */
    CreditCard.prototype.initialize = function () {
        wc_braintree.CheckoutGateway.call(this);
        $(document.body).on('click', '#place_order', this.process.bind(this));
        $(document.body).on('wc_braintree_pre_form_submit_' + this.gateway_id, this.set_config_data.bind(this));
        $(document.body).on('wc_braintree_payment_method_template', this.add_icon_class.bind(this));
        $(document.body).on('wc_braintree_get_hosted_fields_instance', this.get_instance_of_hosted_fields.bind(this));
        $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
        $(document.body).on('change', '#createaccount', this.handle_create_account_change.bind(this));
        if (this.params.dynamic_card_display.enabled) {
            $(document.body).on('braintree_card_type_change', this.card_type_change.bind(this));
        }

        setInterval(this.check_hosted_fields.bind(this), 2000);

        this.add_icon_type();
    }

    /**
     *
     */
    CreditCard.prototype.create_instance = function () {
        wc_braintree.CreditCard.prototype.create_instance.apply(this, arguments);
        this.initialize_hosted_instance();
        this.handle_create_account_change();
        $(window).on('resize', this.container_size.bind(this));
    }

    /**
     * Create an instance of Braintree's hostedFieldsInstance
     */
    CreditCard.prototype.initialize_hosted_instance = function () {
        return new Promise(function (resolve, reject) {
            var fields = this.get_hosted_fields();
            if (Object.keys(fields).length > 0) {
                braintree.hostedFields.create({
                    client: this.clientInstance,
                    styles: this.params.form_styles,
                    fields: fields
                }, function (err, hostedFieldsInstance) {
                    if (err) {
                        if (err.code === 'HOSTED_FIELDS_FIELD_DUPLICATE_IFRAME' ||
                            err.code === 'HOSTED_FIELDS_TIMEOUT') {
                            return;
                        }
                        this.submit_card_error(err);
                        reject();
                        return;
                    }
                    this.hostedFieldsInstance = hostedFieldsInstance;
                    this.hostedFieldsInstance.on('validityChange', this.validity_change.bind(this));
                    this.hostedFieldsInstance = hostedFieldsInstance;
                    $.each(this.events, function (index, value) {
                        hostedFieldsInstance.on(index, function (event) {
                            $(document.body).triggerHandler(value, event);
                        })
                    });
                    this.container_size();
                    resolve();
                }.bind(this));
            }
        }.bind(this))
    }

    /**
     * Process the click event from #place_order.
     */
    CreditCard.prototype.process = function (e) {
        if (this.is_gateway_selected()) {
            if (this.pre_submit_validations(e)) {
                if (!this.is_payment_method_selected()) {
                    if (!this.payment_method_received) {
                        e.preventDefault();
                        this.display_loader();
                        this.fields.fromFormToFields();
                        this.tokenize();
                    } else {
                        return true;
                    }
                } else {
                    if (this._3ds_active() && this.params._3ds.verify_vault) {
                        e.preventDefault();
                        this.process_3dsecure_vaulted();
                    } else {
                        return true;
                    }
                }
            } else {
                e.preventDefault();
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

    /**
     *
     */
    CreditCard.prototype.tokenize = function () {
        this.disable_place_order();
        this.hostedFieldsInstance.tokenize(this.get_tokenization_options(),
            function (err, payload) {
                if (err) {
                    this.submit_card_error(err);
                    this.handle_tokenization_error(err);
                } else {
                    if (this._3ds_active()) {
                        return this.process_3dsecure(payload);
                    } else {
                        this.on_payment_method_received(payload);
                    }
                }
                this.enable_place_order();
            }.bind(this));
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
    CreditCard.prototype.handle_tokenization_error = function (err) {
        var self = this;
        if (err.code === 'HOSTED_FIELDS_FIELDS_INVALID') {
            $.each(err.details.invalidFieldKeys, function (i, value) {
                var field = self.get_hosted_fields()[value];
                $(field.selector).addClass('braintree-hosted-fields-invalid');
            });
        } else if (err.code === 'HOSTED_FIELDS_FIELDS_EMPTY') {
            $.each(this.get_hosted_fields(), function (index, value) {
                $(value.selector).addClass('braintree-hosted-fields-invalid');
            })
        }
        this.remove_loader();
    }

    /**
     * Create the credit card elements.
     */
    CreditCard.prototype.updated_checkout = function () {
        this.check_hosted_fields();
        this.handle_create_account_change();
        this.add_icon_type();
    }

    CreditCard.prototype.checkout_error = function () {
        wc_braintree.CheckoutGateway.prototype.checkout_error.apply(this, arguments);
        this.remove_loader();
    }

    /**
     * Check if the hosted fields instance has created the card field iframes.
     */
    CreditCard.prototype.check_hosted_fields = function () {
        var frames = $(this.container).find('iFrame');
        if (!$(this.container).find('iFrame').length && this.clientInstance) {
            this.initialize_hosted_instance();
        }
    }

    /**
     *
     */
    CreditCard.prototype.validity_change = function (event) {
        this.payment_method_received = false;
        var field = event.fields[event.emittedBy];
        if (field.isValid || (!field.isValid && !field.isPotentiallyValid)) {
            $(field.container).removeClass('braintree-hosted-fields-focused');
        } else {
            $(field.container).addClass('braintree-hosted-fields-focused');
        }

        if (field.isValid && ['expirationMonth', 'cardholderName'].indexOf(event.emittedBy) < 0) {
            var selector = this.hosted_fields[event.emittedBy].selector;
            var idx = this.sorted_fields.indexOf(selector);
            if (event.emittedBy === 'postalCode' && ((idx + 1) < this.sorted_fields.length)) {
                return;
            }
            //check if there is a field after so we can focus automatically
            if (typeof this.sorted_fields[idx + 1] !== 'undefined') {
                var focusField = this.sorted_fields[idx + 1];
                for (var k in this.hosted_fields) {
                    if (this.hosted_fields[k].selector === focusField) {
                        this.hostedFieldsInstance.focus(k);
                    }
                }
            }
        }
    }

    /**
     *
     */
    CreditCard.prototype.container_size = function () {
        if ($('.payment_methods').width() < 482) {
            $(this.container).find('div.wc-braintree-payment-gateway')
                .addClass('small-container');
        } else {
            $(this.container).find('div.wc-braintree-payment-gateway')
                .removeClass('small-container');
        }
        $(document.body).trigger('wc_braintree_container_size_check',
            this.get_hosted_fields());
    }

    /**
     *
     */
    CreditCard.prototype.card_type_change = function (e, event) {
        if (event.cards.length === 1) {
            if (this.params.html.cards[event.cards[0].type]) {
                $('.wc-braintree-card-type').empty().append(
                    this.params.html.cards[event.cards[0].type]);
            }
            this.current_card_type = event.cards[0].type;
        } else {
            $('.wc-braintree-card-type').empty();
        }
    }

    /**
     *
     */
    CreditCard.prototype.get_instance_of_hosted_fields = function () {
        return this.hostedFieldsInstance;
    }

    /**
     *
     */
    CreditCard.prototype.payment_gateway_changed = function (e, payment_gateway) {
        if (payment_gateway === this.gateway_id) {
            this.show_place_order();
        }
    }

    wc_braintree.register(CreditCard);

}(jQuery, wc_braintree));