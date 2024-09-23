import $ from 'jquery';
import {loadScript} from '@paypal/paypal-js';

/**
 * @constructor
 */
function PayPal() {
    wc_braintree.PayPal.call(this);
}

/**
 *
 */
PayPal.prototype = $.extend(
    {},
    wc_braintree.PayPal.prototype,
    wc_braintree.CheckoutGateway.prototype,
    {
        params: wc_braintree_paypal_checkout_params
    }
);

/**
 *
 */
PayPal.prototype.initialize = function () {
    wc_braintree.CheckoutGateway.call(this);
}

/**
 *
 */
PayPal.prototype.create_instance = function () {
    return loadScript(this.params.query_params).then(paypal => {
        return wc_braintree.PayPal.prototype.create_instance.apply(this, arguments).then(function () {
            if (this.banner_enabled && $(this.banner_container).length) {
                $('.wc-braintree-paypal-top-container').remove();
                $(this.banner_container).prepend('<div class="wc-braintree-paypal-top-container"></div>');
                var render_options = this.render_options();
                paypal.Buttons($.extend({}, this.render_options(paypal.FUNDING.PAYPAL), {
                    onInit: function () {

                    }.bind(this),
                    onClick: function () {
                        this.set_payment_method(this.gateway_id);
                        this.set_use_nonce_option(true);
                        $('[name="terms"]').prop('checked', true).trigger('change');
                        render_options.onClick.apply(this, arguments);
                        this.processingBannerCheckout = true;
                    }.bind(this),
                    onCancel: function () {
                        this.processingBannerCheckout = false;
                    }.bind(this)
                })).render('.wc-braintree-paypal-top-container');
            }
            $('.wc_braintree_banner_gateways').addClass('paypal-active');
            $(document.body).on('wc_braintree_payment_method_selected', this.payment_gateway_changed.bind(this));
            $(document.body).on('wc_braintree_display_saved_methods', this.display_saved_methods.bind(this));
            $(document.body).on('wc_braintree_display_new_payment_method', this.display_new_payment_method_container.bind(this));
            $(document.body).on('change', '[name="terms"]', this.handle_terms_click.bind(this));
            $(document.body).on('change', '[type="checkbox"]', this.handle_checkbox_change.bind(this));
            setTimeout(this.create_button.bind(this), 5000);
            setInterval(wc_braintree.PayPal.prototype.create_button.bind(this), 5000);
        }.bind(this)).catch(function (error) {
            throw error;
        }.bind(this));
    }).catch(error => {
        console.log(error);
    })
}

/**
 *
 */
PayPal.prototype.create_button = function () {
    wc_braintree.PayPal.prototype.create_button.call(this).then(function () {
        this.payment_gateway_changed(null, this.get_selected_gateway());
        this.create_bnpl_msg('checkout', 'form.checkout .shop_table');
    }.bind(this))
}

/**
 *
 */
PayPal.prototype.render_options = function () {
    var render_options = wc_braintree.PayPal.prototype.render_options.apply(this, arguments);
    var options = $.extend({}, render_options, {
        onInit: function () {
            render_options.onInit.apply(this, arguments);
            this.handle_terms_click();
        }.bind(this),
        onClick: function () {
            this.processingBannerCheckout = false;
            this.fields.fromFormToFields();
            if (!this.is_valid_checkout()) {
                return this.submit_error(this.params.messages.terms);
            }
            render_options.onClick.apply(this, arguments);
        }.bind(this)
    });
    return options;
}

PayPal.prototype.handle_tokenize_response = function (response) {
    if (this.needs_shipping()) {
        // set nonce and other data just in case other plugins prevent updated_checkout from firing
        this.tokenize_response = response;
        this.set_nonce(response.nonce);
        this.set_device_data();
        this.payment_method_received = true;
        $(document.body).one('updated_checkout', function () {
            this.on_payment_method_received.call(this, response);
        }.bind(this));
        this.update_addresses(response.details);
        this.maybe_set_ship_to_different();
        this.fields.toFormFields({update_shipping_method: false});
        $(document.body).trigger('update_checkout', {update_shipping_method: false});
    } else {
        wc_braintree.PayPal.prototype.handle_tokenize_response.call(this, response);
    }
}

/**
 * Return allowed and disallowed payment methods for the PayPal button.
 */
PayPal.prototype.get_funding = function () {
    var funding = [];
    if (this.params.card_icons === "1") {
        funding.push(paypal.FUNDING.CARD);
    }
    if (this.is_credit_enabled('checkout')) {
        funding.push(paypal.FUNDING.PAYLATER);
        funding.push(paypal.FUNDING.CREDIT);
    }
    return funding.concat(wc_braintree.PayPal.prototype.get_funding.apply(this, arguments));
}

PayPal.prototype.on_payment_method_received = function () {
    wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this, arguments);
    if (this.validate_checkout_fields()) {
        if (!this.needs_shipping()) {
            this.get_form().trigger('submit');
        } else {
            var address = this.get_address_object(this.get_shipping_prefix(), ['phone']);
            if ($('[name^="shipping_method"]').length < 2 || JSON.stringify(this.shipping_address) == JSON.stringify(address)) {
                this.get_form().trigger('submit');
            } else {
                if (this.processingBannerCheckout) {
                    this.scroll_to_place_order();
                }
            }
        }
    }
}

PayPal.prototype.scroll_to_place_order = function () {
    $('html, body').animate({
        scrollTop: $('#place_order').offset().top - 100
    }, 1000);
}

PayPal.prototype.handle_terms_click = function () {
    if ($('[name="terms"]').length) {
        var checked = $('[name="terms"]').is(':checked');
        if (checked) {
            this.actions.enable();
        } else {
            this.actions.disable();
        }
    }
}

PayPal.prototype.handle_checkbox_change = function () {
    setTimeout(this.handle_terms_click.bind(this), 250);
}

PayPal.prototype.needs_shipping = function () {
    if (this.processingBannerCheckout) {
        return wc_braintree.BaseGateway.prototype.needs_shipping.call(this);
    }
    return false;
}

PayPal.prototype.updated_checkout = function (e, data) {
    if (data?.fragments?.paypal_query_args) {
        const args = data.fragments.paypal_query_args;
        const oldValues = `${this.params.query_params.vault}-${this.params.query_params.currency}`;
        const newValues = `${args.vault}-${args.currency}`;
        this.params.query_params = {...this.params.query_params, ...data.fragments.paypal_query_args};
        this.params.options = {...this.params.options, ...data.fragments.paypal_options};
        if (oldValues !== newValues) {
            return loadScript(this.params.query_params).then(() => {
                wc_braintree.CheckoutGateway.prototype.updated_checkout.call(this);
            });
        }
    }
    wc_braintree.CheckoutGateway.prototype.updated_checkout.call(this);
}

wc_braintree.register(PayPal);