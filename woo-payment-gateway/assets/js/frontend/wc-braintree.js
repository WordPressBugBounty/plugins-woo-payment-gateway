(function ($) {
        window.wc_braintree = {
            clazzes: [],
            gateways: []
        };

        if (typeof wc_braintree_global_params === 'undefined') {
            wc_braintree_global_params = {};
        }

        wc_braintree.register = function (clazz) {
            this.clazzes.push(clazz);
            if (this.isReady()) {
                this.create_instance(clazz);
            }
        }

        wc_braintree.isReady = function () {
            return typeof wc_braintree.clientInstance !== 'undefined';
        }

        wc_braintree.create_instance = function (clazz) {
            var instance = new clazz();
            Promise.resolve(instance.create_instance(this.clientInstance, wc_braintree_client_token)).catch(function (error) {
                console.log(error);
            });
            this.gateways.push(instance);
        }

        wc_braintree.triggerClientReady = function (client) {
            this.clientInstance = client;
            this.clazzes.forEach(function (clazz) {
                this.create_instance(clazz);
            }.bind(this));
        }

        wc_braintree.triggerClientUpdate = function (client) {
            this.clientInstance = client;
            this.gateways.forEach(function (instance) {
                Promise.resolve(instance.create_instance(this.clientInstance, wc_braintree_client_token, this.merchant_account)).catch(function (error) {
                    console.log(error);
                });
            }.bind(this))
        }

        if (typeof wc_braintree_checkout_fields === 'undefined') {
            wc_braintree_checkout_fields = [];
        }

        /**
         * BaseGateway class that all gateways should extend.
         *
         * @constructor
         */
        wc_braintree.BaseGateway = function () {
            this.gateway_id = this.params.gateway;
            this.container = 'li.payment_method_' + this.gateway_id;
            this.nonce_selector = '[name="' + this.params.nonce_selector + '"]';
            this.device_data_selector = '[name="' + this.params.device_data_selector + '"]';
            this.tokenized_response_selector = '[name="' + this.params.tokenized_response_selector + '"]';
            this.fields = checkoutFields;
            this.initialize();
        }

        /**
         * Function that should be overridden by gateways so they can create
         * instances of the Braintree objects used for tokenization.
         *
         * @param {event}
         * @param {client}
         * @param {String}
         * @return {Promise}
         */
        wc_braintree.BaseGateway.prototype.create_instance = function (client, client_token) {

        }

        wc_braintree.BaseGateway.prototype.get_page = function () {
            var page = wc_braintree_client_manager_params.page_id;
            if ('cart' === page && $(document.body).is('.woocommerce-checkout')) {
                page = 'checkout';
            }
            return page;
        }

        wc_braintree.BaseGateway.prototype.is_checkout_page = function () {
            return this.get_page() === 'checkout';
        }

        wc_braintree.BaseGateway.prototype.get_gateway_data = function () {
            var data = $(this.container).find('.woocommerce_' + this.gateway_id + '_data').data('gateway');
            if (typeof data === 'undefined' && this.is_checkout_page()) {
                data = $('form.checkout').find('.woocommerce_' + this.gateway_id + '_data').data('gateway');
                if (typeof data === 'undefined') {
                    data = $('.woocommerce_' + this.gateway_id + '_data').data('gateway');
                }
            }
            return typeof data === 'undefined' ? {} : data;
        }

        wc_braintree.BaseGateway.prototype.set_gateway_data = function (data) {
            $(this.container).find('.woocommerce_' + this.gateway_id + '_data').data('gateway', data);
        }

        wc_braintree.BaseGateway.prototype.needs_shipping = function () {
            return this.get_gateway_data().needs_shipping;
        }

        wc_braintree.BaseGateway.prototype.get_items = function () {
            return this.get_gateway_data().items;
        }

        wc_braintree.BaseGateway.prototype.set_items = function (items) {
            var data = this.get_gateway_data();
            data.items = items;
            this.set_gateway_data(data);
        }

        wc_braintree.BaseGateway.prototype.set_cart_shipping = function (value) {
            var data = this.get_gateway_data();
            data.cart_shipping = value;
            this.set_gateway_data(data);
        }

        wc_braintree.BaseGateway.prototype.get_shipping_options = function () {
            return this.get_gateway_data().shipping_options;
        }

        wc_braintree.BaseGateway.prototype.set_shipping_options = function (shipping_options) {
            var data = this.get_gateway_data();
            data.shipping_options = shipping_options;
            this.set_gateway_data(data);
        }

        wc_braintree.BaseGateway.prototype.get_currency = function () {
            return this.get_gateway_data().currency;
        }

        wc_braintree.BaseGateway.prototype.get_total = function () {
            return this.get_gateway_data().total;
        }

        wc_braintree.BaseGateway.prototype.set_total = function (total) {
            var data = this.get_gateway_data();
            data.total = total;
            this.set_gateway_data(data);
        }

        wc_braintree.BaseGateway.prototype.get_order_total = function () {
            return this.get_gateway_data().order_total;
        }

        wc_braintree.BaseGateway.prototype.get_price_label = function () {
            return this.get_gateway_data().price_label;
        }

        wc_braintree.BaseGateway.prototype.get_3ds_amount = function () {
            if (this.is_checkout_page()) {
                return this.get_order_total()
            }
            if (this.get_page() === 'add_payment_method') {
                return '0.00';
            }
            return this.get_total();
        }

        /**
         * Wrapper for the jQuery blockUI plugin. This function blocks the view.
         */
        wc_braintree.BaseGateway.prototype.block = function () {
            if ($().block) {
                $.blockUI({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            }
        }

        /**
         * Wrapper for the jQuery blockUI plugin. This function unblocks the view.
         */
        wc_braintree.BaseGateway.prototype.unblock = function () {
            if ($().block) {
                $.unblockUI();
            }
        }

        /**
         * Return the gateway's button if it exists.
         */
        wc_braintree.BaseGateway.prototype.get_button = function () {
            return this.$button;
        }

        /**
         * Return the merchant account for the store.
         *
         * @returns {String}
         */
        wc_braintree.BaseGateway.prototype.get_merchant_account = function () {
            return this.get_gateway_data().merchant_account;
        }

        /**
         * Sets the device data for the gateway.
         */
        wc_braintree.BaseGateway.prototype.set_device_data = function () {
            if (this.dataCollectorInstance) {
                $(this.device_data_selector).val(this.dataCollectorInstance.deviceData);
                this.fields.set(this.gateway_id + '_device_data', this.dataCollectorInstance.deviceData);
            }
        }

        /**
         * Sets the nonce value to the hidden input field identified by the gateway.
         *
         * @param {String}
         *            value the nonce value which represents a tokenized payment
         *            method.
         */
        wc_braintree.BaseGateway.prototype.set_nonce = function (value) {
            $(this.nonce_selector).val(value);
            this.fields.set(this.gateway_id + '_nonce_key', value);
        }

        wc_braintree.BaseGateway.prototype.set_tokeninzed_response = function (value) {
            this.fields.set(this.gateway_id + '_tokenized_response', value);
        }

        wc_braintree.BaseGateway.prototype.set_payment_method = function (value) {
            this.fields.set('payment_method', value);
        }

        /**
         * Function that returns a jQuery element containing a form.
         *
         * @return {Element}
         */
        wc_braintree.BaseGateway.prototype.get_form = function () {
            return $(this.container).closest('form');
        }

        /**
         * Initialize any additional functionality.
         */
        wc_braintree.BaseGateway.prototype.initialize = function () {

        }

        /**
         * @return {bool}
         */
        wc_braintree.BaseGateway.prototype.is_valid_checkout = function () {
            return true;
        }

        wc_braintree.BaseGateway.prototype.get_address_hash = function (prefix) {
            var fields = ['_first_name', '_last_name', '_address_1', '_address_2', '_postcode', '_city', '_state', '_country'];
            var hash = '';
            fields.forEach(function (key) {
                hash += this.fields.get(prefix + key);
            }.bind(this));
            return hash;
        }

        wc_braintree.BaseGateway.prototype.is_valid_address = function (address, prefix, exclude) {
            if ($.isEmptyObject(address)) {
                return false;
            }

            var mappings = this.get_address_mappings();
            if (typeof exclude !== 'undefined') {
                exclude.forEach(function (k) {
                    if (mappings.indexOf(k) > -1) {
                        mappings.splice(mappings.indexOf(k), 1);
                    }
                });
            }
            for (var i = 0; i < mappings.length; i++) {
                var k = mappings[i];
                var required = this.fields[k]().required.call(this.fields, prefix);
                if (required) {
                    if (!address[k] || typeof address[k] === 'undefined' || !this.fields.isValid(k, address[k], address)) {
                        return false;
                    }
                }
            }
            return true;
        }

        wc_braintree.BaseGateway.prototype.get_address_object = function (prefix, exclude) {
            var address = {};
            exclude = !exclude ? [] : exclude;
            this.get_address_mappings().filter(function (value) {
                return exclude.indexOf(value) < 0;
            }).forEach(function (k) {
                address[k] = this.fields.get(k, prefix);
            }.bind(this));
            return address;
        }

        /**
         * Convert serialized form data to an object.
         *
         * @param {Array}
         */
        wc_braintree.BaseGateway.prototype.form_to_data = function ($form) {
            var formData = $form.find('input').filter(function (i, e) {
                    if ($(e).is('[name^="add-to-cart"]')) {
                        return false;
                    }
                    return true;
                }.bind(this)).serializeArray(),
                data = {};

            for (var i in formData) {
                var obj = formData[i];
                data[obj.name] = obj.value;
            }
            return data;
        }

        wc_braintree.BaseGateway.prototype.ajax_before_send = function (xhr) {
            if (this.params.user_id > 0) {
                xhr.setRequestHeader('X-WP-Nonce', this.params._wp_rest_nonce);
            }
        }

        /**
         * Add a product to the WC cart via Ajax.
         *
         * @param {event}
         */
        wc_braintree.BaseGateway.prototype.add_to_cart = function (e) {
            if (e) {
                e.preventDefault();
            }
            return new Promise(function (resolve, reject) {
                this.block();
                var data = {
                    product_id: this.get_product_data().id,
                    variation_id: $('[name="variation_id"]').val(),
                    qty: this.get_product_quantity(),
                    payment_method: this.gateway_id,
                    wc_braintree_currency: this.get_currency(),
                    variation: this.get_product_variation_data()
                }
                // add custom values from 3rd party plugins to fields.
                var fields = this.get_form().find(':not([name="add-to-cart"],[name="quantity"],[name^="attribute_"])').serializeArray();
                if (fields) {
                    for (var i in fields) {
                        data[fields[i].name] = fields[i].value;
                    }
                }
                $.ajax({
                    url: this.params.routes.add_to_cart,
                    method: 'POST',
                    dataType: 'json',
                    data: data,
                    beforeSend: this.ajax_before_send.bind(this),
                    success: function (response) {
                        this.unblock();
                        if (!response.success) {
                            this.submit_error(response.messages);
                            return;
                        }
                        this.set_total(response.data.total);
                        this.set_cart_shipping(response.data.needs_shipping);
                        resolve(response);
                    }.bind(this),
                    error: function (jqXHR, textStatus, errorThrown) {
                        this.unblock();
                        this.submit_error(errorThrown);
                        reject(errorThrown);
                    }.bind(this)
                });
            }.bind(this));

        }

        /**
         * Processes the WC order using Ajax.
         */
        wc_braintree.BaseGateway.prototype.process_checkout = function () {
            if (!this.is_valid_checkout()) {
                this.submit_error(this.params.messages.terms);
                return;
            }
            this.block();
            $.ajax({
                method: 'POST',
                url: this.params.routes.checkout,
                data: $.extend({},
                    this.fields.toJson(),
                    {
                        payment_method: this.gateway_id,
                        page_id: this.get_page(),
                        wc_braintree_currency: this.get_currency()
                    }
                ),
                dataType: 'json',
                beforeSend: this.ajax_before_send.bind(this),
                success: function (result, status, jqXHR) {
                    if (result.reload) {
                        window.location.reload();
                        return;
                    }
                    if (result.result === 'success') {
                        window.location = result.redirect;
                    } else {
                        if (result.messages) {
                            this.submit_error(result.messages);
                        }
                        this.set_nonce("");
                        this.unblock();
                    }
                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    this.set_nonce("");
                    this.submit_error(errorThrown);
                    this.unblock();
                }.bind(this)
            });
        }

        /**
         * Updates the customer's shipping address.
         *
         * @param {Object}
         */
        wc_braintree.BaseGateway.prototype.update_shipping_address = function (address) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    method: 'POST',
                    url: this.params.routes.shipping_address,
                    data: {
                        payment_method: this.gateway_id,
                        address: address,
                        wc_braintree_currency: this.get_currency()
                    },
                    dataType: 'json',
                    beforeSend: this.ajax_before_send.bind(this),
                    success: function (result) {
                        if (result.code) {
                            reject(result);
                        } else {
                            this.set_total(result.data.total);
                            resolve(result);
                        }
                    }.bind(this),
                    error: function (jqXHR, textStatus, errorThrown) {
                        reject(jqXHR, textStatus, errorThrown);
                    }.bind(this)
                })
            }.bind(this))
        }

        /**
         * Update the shipping method for the customer.
         *
         * @param {String}
         */
        wc_braintree.BaseGateway.prototype.update_shipping_method = function (method) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    method: 'POST',
                    url: this.params.routes.shipping_method,
                    data: {
                        payment_method: this.gateway_id,
                        shipping_method: method,
                        wc_braintree_currency: this.get_currency()
                    },
                    dataType: 'json',
                    beforeSend: this.ajax_before_send.bind(this),
                    success: function (result, status, jqXHR) {
                        if (result.code) {
                            reject(result);
                        } else {
                            this.set_selected_shipping_method(result.data.chosen_shipping_methods);
                            this.set_total(result.data.total);
                            resolve(result);
                        }
                    }.bind(this),
                    error: function (jqXHR, textStatus, errorThrown) {
                        reject();
                    }.bind(this)
                })
            }.bind(this))
        }

        /**
         * Updates the customer's shipping address and shipping method.
         *
         * @param {Object}
         */
        wc_braintree.BaseGateway.prototype.update_shipping = function (address, shipping_method) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    method: 'POST',
                    url: this.params.routes.shipping,
                    data: {
                        payment_method: this.gateway_id,
                        address: address,
                        shipping_method: shipping_method,
                        wc_braintree_currency: this.get_currency()
                    },
                    dataType: 'json',
                    beforeSend: this.ajax_before_send.bind(this),
                    success: function (result, status, jqXHR) {
                        if (result.code) {
                            reject(result);
                        } else {
                            this.set_selected_shipping_method(result.data.chosen_shipping_methods);
                            this.set_total(result.data.total);
                            resolve(result);
                        }
                    }.bind(this),
                    error: function (xhr) {
                        reject(xhr);
                    }.bind(this)
                })
            }.bind(this))
        }

        /**
         * Map a shipping address from the format gateway format to the generic
         * plugin format.
         *
         * @returns {Object}
         */
        wc_braintree.BaseGateway.prototype.map_shipping_address = function (address) {

        }

        /**
         * [set_selected_shipping_method description]
         */
        wc_braintree.BaseGateway.prototype.set_selected_shipping_method = function (shipping_methods) {
            if (shipping_methods) {
                if (this.get_page() !== 'checkout') {
                    this.fields.set('shipping_method', shipping_methods);
                }
                if ($('[name^="shipping_method"]').length) {
                    for (var i in shipping_methods) {
                        $('[name="shipping_method[' + i + ']"][value="' + shipping_methods[i] + '"]').prop('checked', true).trigger('change');
                    }
                }
            }
        }

        /**
         *
         */
        wc_braintree.BaseGateway.prototype.initialize_fraud_tools = function () {
            if (this.params.advanced_fraud.enabled) {
                braintree.dataCollector.create(this.get_fraud_tool_options(),
                    function (err, dataCollectorInstance) {
                        if (err) {
                            if (err.code === 'DATA_COLLECTOR_KOUNT_NOT_ENABLED') {
                                return;
                            }
                            this.submit_error(err);
                            return;
                        }
                        this.dataCollectorInstance = dataCollectorInstance;
                        this.set_device_data();
                    }.bind(this))
            }
        }

        /**
         *
         */
        wc_braintree.BaseGateway.prototype.initialize_3d_secure = function () {
            if (this._3ds_enabled()) {
                braintree.threeDSecure.create({
                    version: 2,
                    client: this.clientInstance
                }, function (err, threeDSecureInstance) {
                    if (err) {
                        this.threeds_error = err;
                        this.submit_error(err);
                        return;
                    }
                    this.threeDSecureInstance = threeDSecureInstance;
                }.bind(this))
            }
        }

        wc_braintree.BaseGateway.prototype._3ds_enabled = function () {
            return 'config' in this && this.config.threeDSecureEnabled;
        }

        /**
         * Returns an object of fraud tool options.
         *
         * @returns {Object}
         */
        wc_braintree.BaseGateway.prototype.get_fraud_tool_options = function () {
            return {
                client: this.clientInstance,
                kount: true
            }
        }

        /**
         * Function that is called after successful tokenization. This method should
         * be extended by other classes.
         *
         * @param {object}
         *            response the response from Braintree when tokenizing a payment
         *            method.
         */
        wc_braintree.BaseGateway.prototype.on_payment_method_received = function (response) {
        }

        wc_braintree.BaseGateway.prototype.submit_error = function (error, ignore) {
            this.clear_error();
            $(document.body).triggerHandler('wc_braintree_submit_error', {
                error: error,
                element: this.message_container,
                ignore: ignore
            });
        }

        wc_braintree.BaseGateway.prototype.clear_error = function () {
            $('#wc_braintree_checkout_error').remove();
        }

        /**
         *
         */
        wc_braintree.BaseGateway.prototype.terms_updated = function () {

        }

        /**
         * [get_first_name description]
         * @return {[type]} [description]
         */
        wc_braintree.BaseGateway.prototype.get_first_name = function (prefix) {
            return this.fields.exists(prefix + '_first_name') ? this.fields.get(prefix + '_first_name') : '';
        }

        /**
         * [get_first_name description]
         * @return {[type]} [description]
         */
        wc_braintree.BaseGateway.prototype.get_last_name = function (prefix) {
            return this.fields.exists(prefix + '_last_name') ? this.fields.get(prefix + '_last_name') : '';
        }

        wc_braintree.BaseGateway.prototype.get_shipping_prefix = function () {
            return 'shipping';
        }

        wc_braintree.BaseGateway.prototype.populate_address_fields = function (address, prefix) {
            for (var k in address) {
                this.fields.set(k, address[k], prefix);
            }
        }

        wc_braintree.BaseGateway.prototype.populate_billing_address = function (address) {
            this.populate_address_fields(address, 'billing');
        }

        wc_braintree.BaseGateway.prototype.populate_shipping_address = function (address) {
            this.populate_address_fields(address, 'shipping');
        }

        wc_braintree.BaseGateway.prototype.get_product_variation_data = function () {
            var variations = {};
            if (this.is_variable_product()) {
                $('.variations [name^="attribute_"]').each(function (index, el) {
                    var $el = $(el);
                    var name = $el.data('attribute_name') || $el.attr('name');
                    variations[name] = $el.val();
                });
            }
            return variations;
        }

        /**
         * Cart gateway that contains overridden functions specific to the cart
         * page.
         */
        wc_braintree.CartGateway = function () {
            this.container = 'li.wc_braintree_cart_gateway_' + this.gateway_id;
            this.message_container = '.shop_table';
            this.form_id = '#wc-braintree-cart-fields-form';
            $(document.body).on('change', '[name="terms"]', this.terms_updated.bind(this));
        }

        wc_braintree.CartGateway.prototype.on_payment_method_received = function (response) {
            this.process_checkout();
        }

        /**
         * Return the form that contains all the cart payment data.
         */
        wc_braintree.CartGateway.prototype.get_form = function () {
            return $(this.form_id);
        }

        /**
         * Validate the cart page.
         *
         * @return {bool}
         */
        wc_braintree.CartGateway.prototype.is_valid_checkout = function () {
            if ($('[name="terms"]').length) {
                if (!$('[name="terms"]').is(':checked')) {
                    return false;
                }
            }
            return true;
        }

        wc_braintree.CartGateway.prototype.add_cart_totals_class = function () {
            $('.cart_totals').addClass('braintree_cart_gateway_active');
        }

        /**
         * Class that inherits from wc_braintree.BaseGateway. Provides functionality
         * for product page gateways.
         */
        wc_braintree.ProductGateway = function () {
            this.container = 'li.wc_braintree_product_gateway_' + this.gateway_id;
            this.message_container = 'div.product';
            this.buttonWidth = $('form div.quantity').outerWidth(true) + $('.single_add_to_cart_button').outerWidth();

            // for variation products when they are selected.
            this.get_form().on('found_variation', this.found_variation.bind(this));
            this.get_form().on('reset_data', this.reset_variation_data.bind(this));
            setTimeout(function () {
                if (this.is_variable_product() && this.is_variable_product_selected()) {
                    this.enable_payment_button();
                }
            }.bind(this), 3000);
            $(this.container).css('max-width', this.buttonWidth + 'px');
        }

        wc_braintree.ProductGateway.prototype.is_variable_product = function () {
            return $('[name="variation_id"]').length > 0;
        };

        wc_braintree.ProductGateway.prototype.is_variable_product_selected = function () {
            var val = $('input[name="variation_id"]').val();
            return !!val && "0" != val;
        }

        /**
         *
         */
        wc_braintree.ProductGateway.prototype.on_payment_method_received = function () {
            this.process_checkout();
        }

        wc_braintree.ProductGateway.prototype.get_product_data = function () {
            return this.get_gateway_data().product;
        }

        wc_braintree.ProductGateway.prototype.set_product_data = function (product) {
            var data = this.get_gateway_data();
            data.product = product;
            this.set_gateway_data(data);
        }

        wc_braintree.ProductGateway.prototype.get_product_quantity = function () {
            return parseInt($('[name="quantity"]').val());
        }

        /**
         * Returns the shopping cart amount for the product. Calculation is based on
         * product price and cart qty.
         *
         * @returns {Number}
         */
        wc_braintree.ProductGateway.prototype.get_product_amount = function () {
            return this.get_product_data().price * this.get_product_quantity();
        }

        /**
         * Called when the "found_variation" event is fired. The product data is
         * updated to use the variation's price.
         *
         * @param {Event}
         * @param {Object}
         */
        wc_braintree.ProductGateway.prototype.found_variation = function (e, variation) {
            // update the product attributes with the variation.
            var data = this.get_gateway_data();
            data.product.price = variation.display_price;
            data.needs_shipping = !variation.is_virtual;
            data.product.variation = variation;
            this.set_gateway_data(data);
            if (this.$button) {
                this.get_button().prop('disabled', false).removeClass('disabled');
            }
        }

        wc_braintree.ProductGateway.prototype.reset_variation_data = function () {
            var data = this.get_product_data();
            data.variation = false;
            this.set_product_data(data);
            this.disable_payment_button();
        }

        wc_braintree.ProductGateway.prototype.disable_payment_button = function () {
            if (this.$button) {
                this.get_button().prop('disabled', true).addClass('disabled');
            }
        }

        wc_braintree.ProductGateway.prototype.enable_payment_button = function () {
            if (this.$button) {
                this.get_button().prop('disabled', false).removeClass('disabled');
            }
        }

        wc_braintree.ProductGateway.prototype.needs_shipping = function () {
            return wc_braintree.BaseGateway.prototype.needs_shipping.apply(this, arguments) || this.get_gateway_data().cart_shipping;
        }

        wc_braintree.CheckoutGateway = function () {
            this.payment_method_received = false;
            this.token_selector = '[name="' + this.params.token_selector + '"]';
            this.payment_type_selector = '[name="' + this.params.payment_type_selector + '"]';
            this.container = this.message_container = 'li.payment_method_' + this.gateway_id;
            this.banner_container = '.wc_braintree_banner_gateway_' + this.gateway_id;
            this.list_container = 'li.payment_method_' + this.gateway_id;
            this.banner_enabled = this.params.banner_enabled === "1";
            this.last_error = '';

            $(this.container).closest('form').on('checkout_place_order_' + this.gateway_id, this.pre_submit_validations.bind(this));
            $(this.container).closest('form').on('checkout_place_order_' + this.gateway_id, this.woocommerce_form_submit.bind(this));

            $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
            $(document.body).on('cfw_updated_checkout', this.updated_checkout.bind(this));
            $(document.body).on('checkout_error', this.checkout_error.bind(this));
            $(document.body).on('change', '[name="terms"]', this.terms_updated.bind(this));

            if (this.banner_enabled) {
                if ($('.woocommerce-billing-fields').length) {
                    $(this.banner_container).css('max-width', $('.woocommerce-billing-fields').outerWidth(true));
                }
            }

            this.hasOrderReviewParams();

            this.container_styling();
        }

        /**
         * Function that is called when the client receives the tokenized payment
         * method from Braintree
         *
         * @param {Object}
         *
         */
        wc_braintree.CheckoutGateway.prototype.on_payment_method_received = function (response) {
            this.tokenize_response = response;
            this.payment_method_received = true;
            this.set_nonce(response.nonce);
            this.hide_payment_button();
            this.show_place_order();
        }

        wc_braintree.CheckoutGateway.prototype.hasOrderReviewParams = function () {
            var url = window.location.search;
            var matches = url.match(/_braintree_order_review=(.+)/);
            if (matches && matches.length > 1) {
                try {
                    var obj = JSON.parse(window.atob(decodeURIComponent(matches[1])));
                    if (this.gateway_id === obj.payment_method) {
                        this.payment_method_received = true;
                        this.set_nonce(obj.payment_nonce);
                        this.set_use_nonce_option(true);
                        history.pushState({}, '', window.location.pathname);
                    }
                } catch (err) {
                    console.log(err);
                }
            }
        }

        /**
         * Function that can be implemented by gateways. It is called when the
         * payment nonce is received from Braintree.
         */
        wc_braintree.CheckoutGateway.prototype.hide_payment_button = function () {
            if (this.$button) {
                this.$button.hide();
            }
        }

        wc_braintree.CheckoutGateway.prototype.show_payment_button = function () {
            if (this.$button) {
                this.$button.show();
            }
        }

        wc_braintree.CheckoutGateway.prototype.hide_place_order = function () {
            $('#place_order').addClass('wc-braintree-hide');
        }

        wc_braintree.CheckoutGateway.prototype.show_place_order = function () {
            $('#place_order').removeClass('wc-braintree-hide');
        }

        /**
         * Customer may have chosen to use billing address for shipping or to use unique shipping. Return
         * the prefix for the address type customer has chosen.
         * @return {[type]} [description]
         */
        wc_braintree.CheckoutGateway.prototype.get_shipping_prefix = function () {
            if (this.needs_shipping() && $('[name="ship_to_different_address"]').is(':checked')) {
                return "shipping";
            } else {
                return "billing";
            }
        }

        wc_braintree.CheckoutGateway.prototype.woocommerce_form_submit = function () {
            if (this.is_payment_method_selected()) {
                return true;
            } else {
                return this.payment_method_received;
            }
        }

        /**
         * Validate the cart page.
         *
         * @return {bool}
         */
        wc_braintree.CheckoutGateway.prototype.is_valid_checkout = function () {
            if ($('[name="terms"]').length) {
                if (!$('[name="terms"]').is(':checked')) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Validate the checkout page before the form is submitted.
         */
        wc_braintree.CheckoutGateway.prototype.pre_submit_validations = function (e) {
            if (!this.is_valid_checkout()) {
                if (typeof e !== 'undefined') {
                    e.stopImmediatePropagation();
                }
                this.submit_error(this.params.messages.terms);
                return false;
            }
            return true;
        }

        /**
         * Function that is called when the [name="payment_method"] radio button is
         * clicked.
         *
         * @param {Event}
         * @param {String}
         */
        wc_braintree.CheckoutGateway.prototype.payment_gateway_changed = function (e, payment_gateway) {
            if (payment_gateway === this.gateway_id) {
                if (this.is_payment_method_selected() || this.payment_method_received) {
                    this.hide_payment_button();
                    this.show_place_order();
                } else {
                    this.show_payment_button();
                    this.hide_place_order();
                }
            } else {
                this.hide_payment_button();
            }
        }

        /**
         * Gateways should use this function to add all button creation code.
         */
        wc_braintree.CheckoutGateway.prototype.create_button = function () {
        }

        wc_braintree.CheckoutGateway.prototype.update_checkout = function () {

        }

        /**
         * Function that is called on the WC updated_checkout event. Gateway's
         * should use this function to perform actions like rendering button html
         * that's been refreshed by WC.
         *
         * @param {event}
         */
        wc_braintree.CheckoutGateway.prototype.updated_checkout = function (e) {
            this.create_button();
            this.container_styling();
            if (!this.fields.isEmpty(this.gateway_id + '_nonce_key')) {
                this.set_nonce(this.fields.get(this.gateway_id + '_nonce_key'));
            }
            if (this.payment_method_received) {
                $('#' + this.gateway_id + '_use_nonce').trigger('click');
            }
        }

        /**
         * @param {event}
         */
        wc_braintree.CheckoutGateway.prototype.checkout_error = function (e) {
            if (this.is_gateway_selected() && this.has_checkout_error()) {
                this.payment_method_received = false;
                this.tokenize_response = null;
                this.payment_gateway_changed(null, this.gateway_id);
            }
        }

        /**
         * Returns true if the gateway is currently selected on the checkout page.
         *
         * @returns {bool}
         */
        wc_braintree.CheckoutGateway.prototype.is_gateway_selected = function () {
            return this.get_selected_gateway() === this.gateway_id;
        }

        /**
         * Return the selected payment gateway identified by [name="payment_method"]
         *
         * @returns {String}
         */
        wc_braintree.CheckoutGateway.prototype.get_selected_gateway = function () {
            return $('input[name="payment_method"]:checked').val();
        }

        /**
         * Returns true if there are errors being shown on the checkout page.
         *
         * @returns {bool}
         */
        wc_braintree.CheckoutGateway.prototype.has_checkout_error = function () {
            return $('#wc_braintree_checkout_error').length > 0;
        }

        /**
         * Returns true if the customer has selected a saved payment method.
         *
         * @returns {bool}
         */
        wc_braintree.CheckoutGateway.prototype.is_payment_method_selected = function () {
            if ($(this.token_selector).length) {
                if ($(this.payment_type_selector + ':checked').val() === 'token') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Function that is called when the display saved methods event is
         * triggered.
         */
        wc_braintree.CheckoutGateway.prototype.display_saved_methods = function (e, gateway_id) {
            if (this.gateway_id == gateway_id && this.$button && this.is_payment_method_selected()) {
                this.hide_payment_button();
                this.show_place_order();
            }
        }

        /**
         * Function that is triggered when the display new payment method event is
         * fired.
         */
        wc_braintree.CheckoutGateway.prototype.display_new_payment_method_container = function (e, gateway_id) {
            if (this.gateway_id == gateway_id && this.$button) {
                if (!this.payment_method_received && !this.is_payment_method_selected()) {
                    this.$button.show();
                    this.hide_place_order();
                }
            }
        }

        /**
         *
         */
        wc_braintree.CheckoutGateway.prototype.get_payment_token = function () {
            return $(this.token_selector).val();
        }

        wc_braintree.blockUI = {
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        }

        /**
         * [set_use_nonce_option description]
         * @param {[type]} bool [description]
         */
        wc_braintree.CheckoutGateway.prototype.set_use_nonce_option = function (bool) {
            $('#' + this.gateway_id + '_use_nonce').prop("checked", bool).trigger('click');
        }

        /**
         * [set_payment_method description]
         * @param {[type]} id [description]
         */
        wc_braintree.CheckoutGateway.prototype.set_payment_method = function (payment_method) {
            $('[name="payment_method"][value="' + payment_method + '"]').prop("checked", true).trigger('click');
        }

        wc_braintree.CheckoutGateway.prototype.show_checkout_gateway = function () {
            $(this.container).show();
        }

        wc_braintree.CheckoutGateway.prototype.hide_checkout_gateway = function () {
            $(this.container).hide();
        }

        /**
         * [maybe_set_ship_to_different description]
         * @return {[type]} [description]
         */
        wc_braintree.CheckoutGateway.prototype.maybe_set_ship_to_different = function () {
            // if shipping and billing address are different,
            // set the ship to different address option.
            if ($('[name="ship_to_different_address"]').length) {
                $('[name="ship_to_different_address"]').prop('checked', this.get_address_hash("billing") !== this.get_address_hash("shipping")).trigger('change');
            }
        }

        wc_braintree.CheckoutGateway.prototype.validate_checkout_fields = function () {
            this.last_error = '';
            if (['checkout', 'order_pay'].indexOf(this.get_page()) < 0) {
                return true;
            }
            var valid = true;
            if (!this.fields.validateFields('billing')) {
                valid = false;
                this.last_error = 'billing';
            } else if (this.needs_shipping() && $('#ship-to-different-address-checkbox').is(':checked')) {
                if (!this.fields.validateFields('shipping')) {
                    valid = false;
                    this.last_error = 'shipping';
                }
            } else if (!this.is_valid_checkout()) {
                valid = false;
                this.last_error = 'terms';
            }
            if (this.last_error === 'terms') {
                this.submit_error(this.params.messages.terms);
            } else if (['billing', 'shipping'].indexOf(this.last_error) > -1) {
                this.submit_error(this.params.messages.required_field);
            }
            return valid;
        }

        wc_braintree.CheckoutGateway.prototype.description_styling = function () {
            if ($(this.container).find('p').length == 0) {
                $(this.container).addClass('no-description');
            }
        }

        wc_braintree.CheckoutGateway.prototype.container_styling = function () {
            this.description_styling();
            if ($(this.container).find('.wc-braintree-payment-gateway').is('.has_methods')) {
                $(this.container).find('div.payment_box').addClass('has-methods');
            }
        }

        wc_braintree.CheckoutGateway.prototype.disable_place_order = function () {
            $('#place_order').prop('disabled', true);
        }

        wc_braintree.CheckoutGateway.prototype.enable_place_order = function () {
            $('#place_order').prop('disabled', false);
        }

        /** ********** PayPal ****************** */

        /**
         * @constructor
         */
        wc_braintree.PayPal = function () {
            wc_braintree.BaseGateway.call(this);
            this.actions = new wc_braintree.PayPal.Actions();
            this.buttons = new wc_braintree.PayPal.Buttons();
        }

        wc_braintree.PayPal.prototype = Object.create(wc_braintree.BaseGateway.prototype);

        wc_braintree.PayPal.prototype.set_selected_shipping_method = function (shipping_methods) {
            if (shipping_methods) {
                if (!this.is_checkout_page()) {
                    this.fields.set('shipping_method', shipping_methods);
                }
            }
        }

        wc_braintree.PayPal.Actions = function () {
            this.actions = {};
        }

        wc_braintree.PayPal.Actions.prototype.enable = function () {
            for (var k in this.actions) {
                this.actions[k].enable();
            }
        }

        wc_braintree.PayPal.Actions.prototype.disable = function () {
            for (var k in this.actions) {
                this.actions[k].disable();
            }
        }

        wc_braintree.PayPal.Actions.prototype.set_actions = function (actions, source) {
            this.actions[source] = actions;
        }

        wc_braintree.PayPal.Buttons = function () {
            this.buttons = {};
        }

        wc_braintree.PayPal.Buttons.prototype.set_button = function (button, source) {
            this.buttons[source] = button;
        }

        wc_braintree.PayPal.Buttons.prototype.get_button = function (source) {
            return this.buttons && this.buttons[source] ? this.buttons[source] : false;
        }

        wc_braintree.PayPal.Buttons.prototype.clear_buttons = function (buttons) {
            buttons.forEach(function (source) {
                if (source in this.buttons) {
                    this.buttons[source].close();
                }
            }.bind(this))
        }

        wc_braintree.PayPal.Buttons.prototype.clear = function (source) {
            if (source in this.buttons) {
                this.buttons[source].close();
            }
        }


        /**
         * @param {Event}
         * @param {Object}
         * @param {String}
         */
        wc_braintree.PayPal.prototype.create_instance = function (client, client_token) {
            return new Promise(function (resolve, reject) {
                this.clientInstance = client;
                this.config = client.getConfiguration().gatewayConfiguration;
                this.client_token = client_token;
                if (this.config.paypal || !this.config.paypalEnabled) {
                    if (!this.config.paypalEnabled || this.config.paypal.clientId === null) {
                        this.submit_error({code: 'PAYPAL_LINKED_ACCOUNT_' + this.config.environment.toUpperCase()});
                        return reject();
                    } else if (this.config.paypal.currencyIsoCode.toLowerCase() !== this.get_currency().toLowerCase()) {
                        this.last_error = 'NO_MERCHANT_ACCOUNT';
                        var msg = 'No Braintree merchant account was found for currency %s. Ensure you have imported'.replace('%s', this.get_currency()) +
                            ' your merchant accounts in the plugin settings and that you have a merchant account for this currency.';
                        console.log(msg);
                        return reject(msg);
                    }
                }
                if (typeof window.paypal === 'undefined') {
                    this.submit_error('There was an error loading the PayPal SDK. Please provide the following details to your plugin developer.' +
                        'PayPal config: ' + JSON.stringify(this.config.paypal) + 'PayPal SDK query: ' + $('#wc-braintree-paypal-checkout-js').attr('src'));
                    return reject();
                }
                // don't render if checkout page. That will happen when 'updated_checkout' is triggered
                if (!this.is_checkout_page()) {
                    this.create_button();
                }
                this.initialize_fraud_tools();
                var options = (function () {
                    var options = {
                        client: client,
                        authorization: client_token
                    }
                    if (this.get_merchant_account()) {
                        options.merchantAccountId = this.get_merchant_account();
                    } else if (this.config.paypal.merchantAccountId) {
                        options.merchantAccountId = this.config.paypal.merchantAccountId;
                    }
                    return options;
                }.bind(this)())
                braintree.paypalCheckout.create(options, function (err, paypalInstance) {
                    if (err) {
                        this.submit_error(err);
                        reject();
                        return;
                    }
                    this.paypalInstance = paypalInstance;
                    resolve();
                }.bind(this));
            }.bind(this))
        }

        /**
         * Return fraud options specific to PayPal.
         */
        wc_braintree.PayPal.prototype.get_fraud_tool_options = function () {
            return $.extend({}, wc_braintree.BaseGateway.prototype.get_fraud_tool_options.call(this), {
                paypal: true
            })
        }

        /**
         * Creates the PayPal SmartButton then returns a Promise.
         *
         * @returns {Promise}
         */
        wc_braintree.PayPal.prototype.create_button = function () {
            return new Promise(function (resolve, reject) {
                // remove any existing PayPal buttons.
                var $buttonContainer = this.get_button_container();
                if ($buttonContainer.find('.paypal-buttons').length) {
                    return resolve(true);
                }
                if ($buttonContainer.length) {
                    this.get_funding().sort(function (a, b) {
                        return this.params.button_sorting.indexOf(a) - this.params.button_sorting.indexOf(b);
                    }.bind(this)).forEach(function (source) {
                        this.buttons.set_button(paypal.Buttons(this.render_options(source)), source);
                        if (this.buttons.get_button(source).isEligible()) {
                            this.buttons.get_button(source).render($buttonContainer[0]);
                            $buttonContainer.addClass(source + '-active');
                        }
                    }.bind(this));
                    this.$button = $buttonContainer.find('.paypal-buttons');
                    resolve(true);
                } else {
                    resolve(false);
                }
            }.bind(this))
        }

        /**
         * @returns {Object}
         */
        wc_braintree.PayPal.prototype.render_options = function (fundingSource) {
            var options = {
                fundingSource: fundingSource,
                style: this.get_button_style(fundingSource),
                onInit: function (data, actions) {
                    this.actions.set_actions(actions, fundingSource);
                }.bind(this),
                onClick: function (data) {

                }.bind(this),
                onApprove: function (data, actions) {
                    return this.paypalInstance.tokenizePayment(data).then(function (response) {
                        this.handle_tokenize_response(response);
                    }.bind(this)).catch(function (err) {

                    }.bind(this));
                }.bind(this),
                onCancel: function (data) {

                }.bind(this),
                onError: function (err) {
                    if (typeof err === 'object' && 'toString' in err && err.toString().indexOf('Expected an order id') > -1) {
                        err = {code: 'PAYPAL_INVALID_CLIENT'};
                    } else if (this.last_error && this.last_error === 'NO_MERCHANT_ACCOUNT') {
                        err = {code: 'NO_MERCHANT_ACCOUNT'};
                    }
                    this.submit_error(err);
                }.bind(this),
                onDestroy: function () {

                }.bind(this)
            }
            if (this.needs_shipping()) {
                options.onShippingChange = function (data, actions) {
                    if (this.is_checkout_page()) {
                        return actions.resolve();
                    }
                    var address = data.shipping_address;
                    var shipping_method = data.selected_shipping_option ? data.selected_shipping_option.id : null;
                    return this.update_shipping({
                        city: address.city,
                        state: address.state,
                        postcode: address.postal_code,
                        country: address.country_code
                    }, shipping_method).then(function (response) {
                        if (this.is_checkout_flow() && response.data[this.gateway_id]) {
                            return actions.order.patch(response.data[this.gateway_id].patch).then(function () {
                                actions.resolve();
                            }.bind(this));
                        } else {
                            actions.resolve();
                        }
                    }.bind(this)).catch(function (response) {
                        if (response.code && response.code === 'shipping-error') {
                            return actions.reject();
                        }
                        return actions.resolve();
                    });
                }.bind(this)
            }
            if (this.is_checkout_flow()) {
                options.createOrder = this.create_payment.bind(this);
            } else {
                options.createBillingAgreement = this.create_payment.bind(this)
            }
            return options;
        }

        wc_braintree.PayPal.prototype.create_payment = function () {
            var options = this.get_options();
            if (this.is_checkout_page() && this.needs_shipping()) {
                this.shipping_address = this.get_address_object(this.get_shipping_prefix(), ['phone']);
            }
            return this.paypalInstance.createPayment(options).then(function (id) {
                return id;
            }.bind(this)).catch(function (err) {
                if (this.get_merchant_account() == "") {
                    err.code = "PAYPAL_MERCHANT_ACCOUNT_EMPTY";
                } else if (options.shippingAddressOverride) {
                    err.code = "PAYPAL_INVALID_ADDRESS";
                }
                this.submit_error(err);
            }.bind(this));
        }

        wc_braintree.PayPal.prototype.handle_tokenize_response = function (response) {
            this.tokenize_response = response;
            this.set_nonce(response.nonce);
            this.set_device_data();
            this.payment_method_received = true;
            this.update_addresses(response.details);
            this.fields.set('payment_method', this.gateway_id);
            this.on_payment_method_received(response);
        }

        wc_braintree.PayPal.prototype.get_button_style = function (source) {
            if (paypal.FUNDING.CARD === source) {
                return Object.assign({}, this.params.button_style, this.params.card_button);
            }
            if (paypal.FUNDING.CREDIT === source) {
                return Object.assign({}, this.params.button_style, this.params.credit_button);
            }
            if (paypal.FUNDING.PAYLATER === source) {
                return Object.assign({}, this.params.button_style, this.params.bnpl.button);
            }
            return this.params.button_style;
        }

        /**
         *
         */
        wc_braintree.PayPal.prototype.get_options = function () {
            var options = {
                amount: this.get_total(),
                enableShippingAddress: this.needs_shipping(),
                shippingAddressEditable: this.needs_shipping()
            };
            if (this.needs_shipping()) {
                var prefix = this.get_shipping_prefix();
                if (this.is_valid_address(this.get_address_object(prefix), prefix, ['phone'])) {
                    options.shippingAddressOverride = this.get_address_object(prefix);
                }
            }
            options = $.extend({}, this.params.options, options);
            return options;
        }

        /**
         * @returns {Object}
         */
        wc_braintree.PayPal.prototype.get_funding = function () {
            var funding = [paypal.FUNDING.PAYPAL];
            return funding;
        }

        /**
         * Returns a jQuery element that the PayPal button should be appended to.
         *
         * @returns {jQuery}
         */
        wc_braintree.PayPal.prototype.get_button_container = function () {
            var $parent = $("#place_order").parent();
            if (!$parent.find('.wc-braintree-paypal-button-container').length) {
                $("#place_order").after('<div class="wc-braintree-paypal-button-container"></div>');
            }
            return $parent.find('.wc-braintree-paypal-button-container');
        }

        wc_braintree.PayPal.prototype.is_credit_enabled = function (page) {
            if (['cart', 'checkout'].indexOf(page) > -1) {
                return this.params.bnpl.enabled && this.params.bnpl.sections.indexOf(page) > -1;
            } else if ('product' === page) {
                return this.params.bnpl.enabled;
            }
            return false;
        }

        wc_braintree.PayPal.prototype.is_bnpl_msg_enabled = function (page) {
            if (['cart', 'checkout'].indexOf(page) > -1) {
                return this.params.bnpl.msg.can_show && this.params.bnpl.msg.sections.indexOf(page) > -1 && this.get_order_total() > 0;
            } else if ('product' === page) {
                return this.params.bnpl.msg.enabled;
            }
            return false;
        }

        wc_braintree.PayPal.prototype.create_bnpl_msg = function (section, container) {
            if (this.is_bnpl_msg_enabled(section) && $(container).length) {
                if (!$(container).parent().find('.wc-braintree-pay-later-msg').length) {
                    $(container).after('<div class="wc-braintree-pay-later-msg"></div>');
                }
                paypal.Messages({
                    amount: (function () {
                        if (section === 'product') {
                            return this.get_product_amount();
                        }
                        return this.get_total();
                    }.bind(this)()),
                    currency: this.get_currency(),
                    placement: this.get_message_placement(),
                    style: {
                        layout: 'text',
                        logo: {
                            type: 'primary',
                            position: 'left'
                        },
                        text: {color: this.params.bnpl.txt_color}
                    }
                }).render($(container).parent().find('.wc-braintree-pay-later-msg')[0]);
            }
        }

        wc_braintree.PayPal.prototype.get_message_placement = function () {
            switch (this.get_page()) {
                case 'checkout':
                    return 'payment';
                default:
                    return this.get_page();
            }
        }

        /**
         * Update the WC billing and shipping address fields if required.
         *
         * @param {Object{
         */
        wc_braintree.PayPal.prototype.update_addresses = function (details, silent) {
            // only update billing address if it was empty when PayPal was initiated
            // or the billingAddress is returned from PayPal:
            if (!this.is_valid_address(this.get_address_object('billing'), 'billing') || details.billingAddress) {
                if (details.billingAddress || (details.shippingAddress && !$('[name="ship_to_different_address"]').is(':checked'))) {
                    var billingAddress = details.billingAddress ? details.billingAddress : details.shippingAddress;
                    if (billingAddress && !$.isEmptyObject(billingAddress)) {
                        this.populate_billing_address(billingAddress);
                    }
                }
            }
            this.populate_detail_fields(details);
            if (details.shippingAddress && !$.isEmptyObject(details.shippingAddress)) {
                this.populate_shipping_address(details.shippingAddress);
            }
        }

        wc_braintree.PayPal.prototype.get_address_mappings = function () {
            return ['recipientName', 'line1', 'line2', 'city', 'countryCode', 'postalCode', 'state', 'phone'];
        }

        wc_braintree.PayPal.prototype.populate_detail_fields = function (details) {
            [['first_name', 'firstName'], ['last_name', 'lastName'], ['email', 'email'], ['phone', 'phone']].forEach(function (group) {
                if (this.get_page() === 'checkout') {
                    if (details[group[1]] && this.fields.isEmpty('billing_' + group[0])) {
                        this.fields.set(group[0], details[group[1]], 'billing');
                    }
                } else {
                    this.fields.set(group[0], details[group[1]], 'billing');
                }
            }.bind(this));
            if (details.phone && this.fields.exists('shipping_phone') && this.fields.isEmpty('shipping_phone')) {
                this.fields.set('shipping_phone', details.phone);
            }
        }

        wc_braintree.PayPal.prototype.open_shipping_modal = function (params, options) {
            this.block()
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.params.routes.paypal_shipping,
                data: params,
                success: function (response) {
                    this.unblock();
                    openModal.call(this, response.html);
                }.bind(this),
                error: function () {
                    this.unblock();
                }.bind(this)
            })

            function openModal(html) {
                var $overlay = $('<div>', {'class': 'wc-braintree-shipping-modal-overlay'}),
                    $modal = $('<div>', {'class': 'wc-braintree-shipping-modal'});
                $modal.append(html).show();
                $('body').addClass('shipping-modal-active').append($overlay).append($modal);

                $('.wc-braintree-shipping-modal').on('click', '[name^="wc_shipping_method"]', update_shipping_method.bind(this));
                $('.wc-braintree-shipping-modal').on('click', '#place_order', process_checkout.bind(this));
                $('.wc-braintree-shipping-modal').on('click', '#close', closeModal);
            }

            function closeModal(e) {
                e.preventDefault();
                $('.wc-braintree-shipping-modal-overlay').remove();
                $('.wc-braintree-shipping-modal').remove();
                $('body').removeClass('shipping-modal-active');
            }

            function process_checkout(e) {
                closeModal(e);
                this.process_checkout();
            }

            function block() {
                $('#preloaderSpinner').height($('.paypal-shipping-methods').outerHeight() + 20).show();
                var height = $('.wc-braintree-shipping-modal').height();
                var scrollTop = $('.wc-braintree-shipping-modal').scrollTop();
                var top = (height / 2 + scrollTop);
                $('.spinWrap').css('top', top);
            }

            function unblock() {
                $('#preloaderSpinner').hide();
            }

            function update_shipping_method(e) {
                block();
                var method = $(e.target).data('index') + ':' + $(e.target).val();
                this.update_shipping_method(method).then(function (response) {
                    unblock();
                    // update the cart totals html
                    $('.cart-totals').replaceWith(response.data.cart_totals);
                    if (options.onShippingMethodSelected) {
                        options.onShippingMethodSelected.call(this, response.data);
                    }
                }.bind(this)).catch(function () {
                    unblock();
                }.bind(this))
            }
        }

        /**
         * @param {Object}
         */
        wc_braintree.PayPal.prototype.map_shipping_address = function (address) {
            return {
                address_1: address.line1,
                address_2: address.line2,
                city: address.city,
                postcode: address.postalCode,
                state: address.state,
                country: address.countryCode
            }
        }

        wc_braintree.PayPal.prototype.is_checkout_flow = function () {
            return this.params.options.flow === 'checkout';
        }

        /** ************** Google Pay *************************** */

        wc_braintree.GooglePay = function () {
            wc_braintree.BaseGateway.call(this);
            this.create_payments_client();
            this.create_button();
        }

        wc_braintree.GooglePay.prototype = Object.create(wc_braintree.BaseGateway.prototype);

        wc_braintree.GooglePay.prototype.get_environment = function () {
            return this.params.google_environment;
        }

        wc_braintree.GooglePay.prototype.get_merchant_id = function () {
            return this.params.google_merchant;
        }

        wc_braintree.GooglePay.prototype.get_merchant_info = function () {
            var info = {
                merchantName: this.params.google_merchant_name,
                merchantId: this.get_merchant_id()
            }
            if (!info.merchantId) {
                delete info.merchantId;
            }
            return info;
        }

        /**
         * Called after the payment data in the Google payment sheet is updated.
         */
        wc_braintree.GooglePay.prototype.after_payment_data_callback = function () {

        }

        wc_braintree.GooglePay.prototype.create_payments_client = function () {
            var args = {
                environment: this.get_environment(),
                merchantInfo: this.get_merchant_info()
            }
            args.paymentDataCallbacks = {
                onPaymentAuthorized: function (paymentData) {
                    return new Promise(function (resolve, reject) {
                        resolve({transactionState: "SUCCESS"})
                    }.bind(this))
                }.bind(this)
            }
            if (this.needs_shipping()) {
                var prefix = this.get_shipping_prefix();
                if (!this.is_checkout_page() || !this.is_valid_address(this.get_address_object(prefix), prefix, ['phoneNumber'])) {
                    args.paymentDataCallbacks.onPaymentDataChanged = function (data) {
                        return new Promise(function (resolve, reject) {
                            this.payment_data_callback(data).then(function (response) {
                                resolve(response.requestUpdate);
                                this.after_payment_data_callback(response);
                            }.bind(this)).catch(function (response) {
                                resolve(response);
                            }.bind(this));
                        }.bind(this));
                    }.bind(this)
                }
            }
            this.paymentsClient = new google.payments.api.PaymentsClient(args);
        }

        wc_braintree.GooglePay.prototype.create_instance = function (client, client_token) {
            return new Promise(function (resolve, reject) {
                this.clientInstance = client;
                this.config = client.getConfiguration().gatewayConfiguration;
                this.initialize_3d_secure();
                braintree.googlePayment.create({
                    client: client,
                    googlePayVersion: 2,
                    googleMerchantId: this.get_merchant_id()
                }, function (err, googlePayInstance) {
                    if (err) {
                        this.submit_error(err);
                        return;
                    }
                    this.googlePayInstance = googlePayInstance;
                    this.initialize_fraud_tools();
                    this.paymentsClient.isReadyToPay({
                        apiVersion: 2,
                        apiVersionMinor: 0,
                        allowedPaymentMethods: googlePayInstance.createPaymentDataRequest().allowedPaymentMethods
                    }).then(function (response) {
                        if (!response.result) {
                            this.cannot_pay = true;
                            reject(response);
                        } else {
                            this.cannot_pay = false;
                            resolve(response);
                        }
                    }.bind(this)).catch(function (err) {
                        this.submit_error({
                            message: err.statusMessage
                        });
                        return;
                    }.bind(this));
                }.bind(this))
            }.bind(this));
        }

        /**
         *
         */
        wc_braintree.GooglePay.prototype.create_button = function () {
            return new Promise(function (resolve, reject) {
                if (this.$button) {
                    this.$button.remove();
                }
                if (this.paymentsClient) {
                    this.$button = $(this.paymentsClient.createButton($.extend({
                        onClick: this.tokenize.bind(this)
                    }, this.params.button_options)));
                    if (this.is_rectangle_button()) {
                        this.$button.find('button').removeClass('new_style');
                    } else {
                        this.$button.find('button').addClass('gpay-button-round');
                    }
                    resolve();
                }
            }.bind(this))
        }

        wc_braintree.GooglePay.prototype.is_rectangle_button = function () {
            return this.params.button_shape === 'rect';
        }

        /**
         *
         */
        wc_braintree.GooglePay.prototype.tokenize = function () {
            this.create_payments_client();
            var paymentDataRequest = this.googlePayInstance.createPaymentDataRequest(this.get_payment_data_request());
            var cardPaymentMethodDetails = paymentDataRequest.allowedPaymentMethods[0];
            cardPaymentMethodDetails.parameters.assuranceDetailsRequired = true;
            cardPaymentMethodDetails.parameters.billingAddressRequired = true;
            cardPaymentMethodDetails.parameters.billingAddressParameters = {
                format: 'FULL',
                phoneNumberRequired: this.fields.requestFieldInWallet('billing_phone')
            }
            this.billing_checkout_address = this.get_address_object('billing');
            try {
                this.paymentsClient.loadPaymentData(paymentDataRequest).then(function (paymentData) {
                    this.paymentData = paymentData;
                    this.update_addresses(paymentData);
                    return this.googlePayInstance.parseResponse(paymentData);
                }.bind(this)).then(function (response) {
                    this.set_tokeninzed_response(JSON.stringify(response));
                    this.set_nonce(response.nonce);
                    this.set_device_data();
                    //check if 3D secure is enabled
                    if (this.params._3ds.enabled && response.details.isNetworkTokenized === false && 'threeDSecureEnabled' in this.config && this.config.threeDSecureEnabled) {
                        wc_braintree.CreditCard.prototype.process_3dsecure.call(this, response);
                    } else {
                        this.on_payment_method_received(response);
                    }
                }.bind(this)).catch(function (err) {
                    if (err.statusCode === 'CANCELED') {
                        return;
                    }
                    var error = {
                        code: err.statusMessage.indexOf('whitelisted') > -1 ? "DEVELOPER_ERROR_WHITELIST" : null,
                        message: err.statusMessage
                    };
                    this.submit_error(error);
                    return;
                }.bind(this));
            } catch (err) {
                this.submit_error({
                    message: err
                });
            }
        }

        wc_braintree.GooglePay.prototype.get_payment_data_request = function () {
            var request = {
                merchantInfo: this.get_merchant_info(),
                transactionInfo: {
                    countryCode: this.params.country_code,
                    currencyCode: this.get_currency(),
                    totalPriceStatus: 'FINAL',
                    totalPrice: this.get_total().toString(),
                    totalPriceLabel: this.get_price_label(),
                    displayItems: this.get_items()
                },
                emailRequired: this.fields.requestFieldInWallet('billing_email'),
            }
            if (this.needs_shipping()) {
                var prefix = this.get_shipping_prefix();
                if (!this.is_checkout_page() || !this.is_valid_address(this.get_address_object(prefix), prefix, ['phoneNumber'])) {
                    request.shippingAddressParameters = {};
                    request.shippingAddressRequired = true;
                    request.callbackIntents = ['PAYMENT_AUTHORIZATION', 'SHIPPING_ADDRESS', 'SHIPPING_OPTION'];
                    request.shippingOptionRequired = true;
                    var shipping_options = this.get_shipping_options();
                    if (shipping_options && shipping_options.length > 0) {
                        request.shippingOptionParameters = {
                            shippingOptions: shipping_options,
                            defaultSelectedOptionId: shipping_options[0].id
                        }
                    }
                } else {
                    request.callbackIntents = ['PAYMENT_AUTHORIZATION'];
                }
            } else {
                request.callbackIntents = ['PAYMENT_AUTHORIZATION'];
            }
            this.payment_request_options = request;
            return request;
        }

        wc_braintree.GooglePay.prototype.update_addresses = function (paymentData) {
            if (paymentData.paymentMethodData.info.billingAddress) {
                var address = paymentData.paymentMethodData.info.billingAddress;
                if (this.is_checkout_page()) {
                    // address is valid but phone was requested
                    if (this.is_valid_address(this.billing_checkout_address, 'billing', ['phoneNumber']) && address.phoneNumber) {
                        address = {phoneNumber: address.phoneNumber};
                        this.populate_billing_address(address);
                    } else if (!this.is_valid_address(this.billing_checkout_address, 'billing', ['phoneNumber'])) {
                        this.populate_billing_address(address);
                    }
                } else {
                    this.populate_billing_address(address);
                }
            }
            if (paymentData.shippingAddress) {
                this.populate_shipping_address(paymentData.shippingAddress);
            }
            if (paymentData.email) {
                this.fields.set('email', paymentData.email, 'billing');
            }
        }

        wc_braintree.GooglePay.prototype.payment_data_callback = function (data) {
            return new Promise(function (resolve, reject) {
                var shipping_method = data.shippingOptionData.id === 'shipping_option_unselected' ? null : data.shippingOptionData.id;
                if (data.callbackTrigger === 'SHIPPING_ADDRESS') {
                    shipping_method = null;
                }
                this.update_shipping({
                    country: data.shippingAddress.countryCode,
                    state: data.shippingAddress.administrativeArea,
                    city: data.shippingAddress.locality,
                    postcode: data.shippingAddress.postalCode
                }, shipping_method).then(function (response) {
                    resolve(response.data[this.gateway_id]);
                }.bind(this)).catch(function (response) {
                    reject(response.data[this.gateway_id]);
                }.bind(this))
            }.bind(this))
        }

        wc_braintree.GooglePay.prototype.get_address_mappings = function () {
            return ['name', 'postalCode', 'countryCode', 'phoneNumber', 'address1', 'address2', 'locality', 'administrativeArea'];
        }

        wc_braintree.GooglePay.prototype.block = function () {
            if ($().block) {
                $.blockUI({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            }
        }

        wc_braintree.GooglePay.prototype.unblock = function () {
            if ($().block) {
                $.unblockUI();
            }
        }

        wc_braintree.GooglePay.prototype.clear_error = function () {
            this.paymentData = null;
            $('#wc_braintree_checkout_error').remove();
        }

        /** ***************** Credit Cards ************************** */

        wc_braintree.CreditCard = function () {
            wc_braintree.BaseGateway.call(this);
            this._3ds_vaulted_nonce_selector = '[name="' + this.params._3ds_vaulted_nonce_selector + '"]';
            this.config_selector = '[name="' + this.params.config_selector + '"]';
        }

        wc_braintree.CreditCard.prototype = Object.create(wc_braintree.BaseGateway.prototype);

        /**
         * @param {Event}
         * @param {Object}
         * @param {String}
         */
        wc_braintree.CreditCard.prototype.create_instance = function (client, client_token) {
            this.clientInstance = client;
            this.client_token = client_token;
            this.config = client.getConfiguration().gatewayConfiguration;
            this.initialize_fraud_tools();
            this.initialize_3d_secure();
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.set_config_data = function () {
            $(this.config_selector).val(JSON.stringify(this.config));
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.get_hosted_fields = function () {
            this.hosted_fields = {};
            var self = this;
            $.each(this.params.custom_fields, function (key, field) {
                if ($('#' + field.id).length && field.type) {
                    self.hosted_fields[field.type] = {
                        selector: '#' + field.id,
                        placeholder: field.placeholder
                    };
                }
            });
            $.each(this.hosted_fields, function (key, value) {
                var remove = false;
                switch (key) {
                    case 'cvv':
                        if (self.config.challenges.indexOf('cvv') === -1) {
                            remove = true;
                        }
                        break;
                    case 'postalCode':
                        if (!$('#wc-braintree-postal-code').length && $('#billing_postcode').length) {
                            remove = true;
                        } else if (self.config.challenges.indexOf('postal_code') === -1 && !$('#billing_postcode').length) {
                            remove = true;
                        }
                        break;
                }
                if (remove) {
                    // remove field from html
                    $(value.selector).closest('.' + key + '-container').remove();
                    $(document.body).triggerHandler('wc_braintree_hosted_field_removed', key);
                    delete self.hosted_fields[key];
                }
            });
            this.sorted_fields = [];
            $('.wc-braintree-cc-container').find('[id^="wc-braintree-"]').each(function (idx, el) {
                this.sorted_fields.push('#' + $(el).attr('id'));
            }.bind(this));
            return this.hosted_fields;
        }

        /**
         * @returns {Boolean}
         */
        wc_braintree.CreditCard.prototype._3ds_enabled = function () {
            return this.get_gateway_data()._3ds.enabled && this.config.threeDSecureEnabled;
        }

        /**
         * @returns {Boolean}
         */
        wc_braintree.CreditCard.prototype._3ds_active = function () {
            return this.get_gateway_data()._3ds.active && this.config.threeDSecureEnabled;
        }

        /**
         * Request a payment nonce for use in 3DS vaulted requests.
         */
        wc_braintree.CreditCard.prototype.payment_nonce_request = function (token) {
            return $.ajax({
                type: 'POST',
                url: this.params.urls._3ds.vaulted_nonce,
                data: {
                    _wpnonce: this.params._wp_rest_nonce,
                    token: token,
                    version: 2
                },
            });
        }

        /**
         * Return options specific to the tokenization process.
         *
         * @returns {Object}
         */
        wc_braintree.CreditCard.prototype.get_tokenization_options = function () {
            var options = {
                billingAddress: {}
            }
            if (this.fields.exists('billing_address_1')) {
                options.billingAddress.streetAddress = this.fields.get('billing_address_1');
            }
            if (this.fields.exists('billing_postcode')) {
                options.billingAddress.postalCode = this.fields.get('billing_postcode');
            }
            return options;
        }

        wc_braintree.CreditCard.prototype.teardown_3ds = function () {
            this.threeDSecureInstance.teardown().then(this.initialize_3d_secure.bind(this)).catch(this.initialize_3d_secure.bind(this))
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.process_3dsecure = function (response, isVaulted) {
            if (this.threeDSecureInstance) {
                var prefix = this.get_shipping_prefix();
                this.threeDSecureInstance.verifyCard({
                    amount: this.get_3ds_amount(),
                    nonce: response.nonce,
                    bin: response.details.bin,
                    email: this.fields.get('billing_email', ''),
                    challengeRequested: this.params._3ds.challengeRequested,
                    collectDeviceData: true,
                    billingAddress: {
                        givenName: this.fields.get('billing_first_name', '').replace(/[^\x00-\x7f]/g, ""),
                        surname: this.fields.get('billing_last_name', '').replace(/[^\x00-\x7f]/g, ""),
                        phoneNumber: this.fields.get('billing_phone', ''),
                        streetAddress: this.fields.get('billing_address_1', '').slice(0, 50),
                        extendedAddress: this.fields.get('billing_address_2', '').slice(0, 50),
                        locality: this.fields.get('billing_city', '').slice(0, 50),
                        region: this.fields.required('billing_state') ? (this.fields.get('billing_state', '').length > 3 ? '' : this.fields.get('billing_state', '')) : '',
                        postalCode: this.fields.get('billing_postcode', ''),
                        countryCodeAlpha2: this.fields.get('billing_country', '')
                    },
                    additionalInformation: this.needs_shipping() ? {
                        shippingGivenName: this.fields.get(prefix + '_first_name', '').replace(/[^\x00-\x7f]/g, ""),
                        shippingSurname: this.fields.get(prefix + '_last_name', '').replace(/[^\x00-\x7f]/g, ""),
                        shippingAddress: {
                            streetAddress: this.fields.get(prefix + '_address_1', '').slice(0, 50),
                            extendedAddress: this.fields.get(prefix + '_address_2', '').slice(0, 50),
                            locality: this.fields.get(prefix + '_city', '').slice(0, 50),
                            region: this.fields.required(prefix + '_state') ? (this.fields.get(prefix + '_state', '').length > 3 ? '' : this.fields.get(prefix + '_state', '')) : '',
                            postalCode: this.fields.get(prefix + '_postcode', ''),
                            countryCodeAlpha2: this.fields.get(prefix + '_country', '')
                        },
                        ipAddress: this.params.ipAddress
                    } : {
                        ipAddress: this.params.ipAddress
                    },
                    onLookupComplete: function (data, next) {
                        if ('remove_loader' in this) {
                            this.remove_loader();
                        }
                        next();
                    }.bind(this)
                }, function (err, payload) {
                    if (this.enable_place_order) {
                        this.enable_place_order();
                    }
                    if (err) {
                        this.submit_error(err);
                        this.remove_loader();
                        return;
                    }
                    if (payload.threeDSecureInfo && payload.threeDSecureInfo.status === 'challenge_required') {
                        return this.teardown_3ds();
                    }
                    if (isVaulted) {
                        $(this._3ds_vaulted_nonce_selector).val('true');
                    }
                    if (!this.is_checkout_page()) {
                        // set new nonce since old one was consumed.
                        this.set_nonce(payload.nonce);
                    }
                    this.on_payment_method_received(payload);
                }.bind(this));
                if (isVaulted) {
                    this.unblock_form();
                }
            }
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.process_3dsecure_vaulted = function () {
            this.block_form();
            $.when(this.payment_nonce_request(this.get_payment_token())).done(
                function (response) {
                    if (response.success) {
                        this.process_3dsecure(response.data, true);
                    } else {
                        this.submit_error(response.message);
                        this.unblock_form();
                    }
                }.bind(this)).fail(function (jqXHR, textStatus, errorThrown) {
                this.submit_error({
                    message: errorThrown
                });
                this.unblock_form();
            }.bind(this));
        }

        wc_braintree.CreditCard.prototype.add_icon_class = function (e, data, container) {
            $(container).addClass(this.params.icon_style);
        }

        wc_braintree.CreditCard.prototype.add_icon_type = function () {
            $(this.container).find('.wc-braintree-payment-methods-container').addClass(this.params.icon_style + '-icons');
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.block_form = function () {
            $(this.container).closest('form').block(wc_braintree.blockUI);
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.unblock_form = function () {
            $(this.container).closest('form').unblock();
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.display_loader = function () {
            if (this.params.loader.enabled) {
                $('.wc-braintree-payment-loader').fadeIn(200);
            }
        }

        /**
         *
         */
        wc_braintree.CreditCard.prototype.remove_loader = function () {
            $('.wc-braintree-payment-loader').fadeOut(200);
        }

        wc_braintree.CreditCard.prototype.submit_card_error = function (error) {
            return wc_braintree.BaseGateway.prototype.submit_error.call(this, error, true);
        }

        wc_braintree.CreditCard.prototype.handle_create_account_change = function () {
            if ($('#createaccount').length) {
                if ($('#createaccount').is(':checked')) {
                    if ($('.wc-braintree-save-card-container').length) {
                        $('.wc-braintree-save-card-container').show();
                    } else {
                        $('#braintree_cc_save_method').parent().show();
                    }
                } else {
                    if ($('.wc-braintree-save-card-container').length) {
                        $('.wc-braintree-save-card-container').hide();
                    } else {
                        $('#braintree_cc_save_method').parent().hide();
                    }
                }
            }
        }

        /** ********************************************************* */

        /** **************** Apple Pay ***************************** */

        wc_braintree.ApplePay = function () {
            wc_braintree.BaseGateway.call(this);
        }

        wc_braintree.ApplePay.prototype = Object.create(wc_braintree.BaseGateway.prototype);

        /**
         *
         */
        wc_braintree.ApplePay.prototype.create_instance = function (client, client_token) {
            this.clientInstance = client;
            this.initialize_fraud_tools();
            return new Promise(function (resolve, reject) {
                if (this.can_initialize_applepay()) {
                    braintree.applePay.create({
                        client: this.clientInstance
                    }, function (err, applePayInstance) {
                        if (err) {
                            reject();
                            this.submit_error(err);
                            return;
                        }
                        this.applePayInstance = applePayInstance;
                        resolve();
                    }.bind(this));
                } else {
                    reject();
                }
            }.bind(this))
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.open_wallet = function () {
            this.applePaySession.begin();
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.init_wallet = function () {
            this.paymentRequest = this.applePayInstance.createPaymentRequest(this.get_payment_request());
            try {
                var applePaySession = new ApplePaySession(this.get_applepay_version(), this.paymentRequest);
                this.applePaySession = applePaySession;
                applePaySession.onvalidatemerchant = this.onvalidatemerchant.bind(this);
                if (!this.needs_shipping() && 'order_pay' !== this.get_page()) {
                    applePaySession.onpaymentmethodselected = this.onpaymentmethodselected.bind(this);
                }
                // only listen for these events if shipping address is required in the Apple Wallet.
                if (this.needs_shipping()) {
                    if (this.paymentRequest.requiredShippingContactFields.indexOf('postalAddress') > -1) {
                        applePaySession.onshippingcontactselected = this.onshippingcontactselected.bind(this);
                        applePaySession.onshippingmethodselected = this.onshippingmethodselected.bind(this);
                    } else {
                        this.can_ship = true;
                    }
                }
                applePaySession.onpaymentauthorized = this.onpaymentauthorized.bind(this);
            } catch (err) {
                this.submit_error(err);
            }
        }

        /**
         * Returns the ApplePay version that the iOS device supports.
         * @return {[type]} [description]
         */
        wc_braintree.ApplePay.prototype.get_applepay_version = function () {
            if (ApplePaySession.supportsVersion(4)) {
                return 4;
            } else if (ApplePaySession.supportsVersion(3)) {
                return 3;
            }
        }

        /**
         * Function that returns a payment request object used in establishing an
         * ApplePaySession.
         *
         * @returns {Object}
         */
        wc_braintree.ApplePay.prototype.get_payment_request = function () {
            var request = {
                total: {
                    label: this.params.store_name,
                    amount: this.get_total(),
                    type: 'final'
                },
                lineItems: this.get_items(),
                currencyCode: this.get_currency(),
                requiredBillingContactFields: this.get_billing_fields_array(),
                requiredShippingContactFields: this.get_shipping_fields_array(),
            }
            if (this.needs_shipping() && request.requiredShippingContactFields.indexOf('postalAddress') > -1) {
                request.shippingMethods = this.get_shipping_options();
            }
            return request;
        }

        wc_braintree.ApplePay.prototype.get_address_mappings = function () {
            return ["givenName", "familyName", "addressLines", "administrativeArea", "locality", "postalCode", "phoneNumber", "emailAddress", "countryCode"];
        }

        wc_braintree.ApplePay.prototype.populate_address_fields = function (address, prefix) {
            for (var k in address) {
                if (prefix === 'shipping') {
                    // populate billing phone and email.
                    if (['phoneNumber', 'emailAddress'].indexOf(k) > -1) {
                        this.fields.set(k, address[k], 'billing');
                    }
                }
                this.fields.set(k, address[k], prefix);
            }
        }

        wc_braintree.ApplePay.prototype.get_contact_field_from_key = function (k) {
            if (['givenName', 'familyName'].indexOf(k) > -1) {
                k = 'name';
            }
            return k;
        }

        wc_braintree.ApplePay.prototype.validate_response = function (response) {
            var errors = [], messages = this.params.messages.errors,
                mappings = this.get_address_mappings();
            var k;
            if (response.shippingContact) {
                var shippingContact = this.needs_shipping() ? response.shippingContact : (function (address) {
                    var shippingContact = {};
                    ['emailAddress', 'phoneNumber'].forEach(function (k) {
                        if (typeof address[k] !== 'undefined') {
                            shippingContact[k] = address[k];
                        }
                    })
                    return shippingContact;
                }(response.shippingContact));
                for (k in shippingContact) {
                    if (mappings.indexOf(k) > -1 && shippingContact[k] !== '') {
                        if (!this.fields.isValid(k, shippingContact[k], shippingContact)) {
                            errors.push(new ApplePayError('shippingContactInvalid', this.get_contact_field_from_key(k)));
                        }
                    }
                }
            }
            if (response.billingContact) {
                for (k in response.billingContact) {
                    if (mappings.indexOf(k) > -1 && response.billingContact[k] !== '') {
                        if (!this.fields.isValid(k, response.billingContact[k], response.billingContact)) {
                            var mk = 'invalid_' + this.get_contact_field_from_key(k);
                            var message = typeof messages[mk] !== 'undefined' ? messages[mk] : '';
                            errors.push(new ApplePayError('billingContactInvalid', this.get_contact_field_from_key(k), message));
                        }
                    }
                }
            }
            return errors;
        }

        wc_braintree.ApplePay.prototype.get_billing_fields_array = function () {
            if (this.is_checkout_page()) {
                if (!this.is_valid_address(this.get_address_object('billing'), 'billing', ['phoneNumber', 'emailAddress'])) {
                    return ['postalAddress'];
                }
                return [];
            }
            return ['postalAddress'];
        }

        wc_braintree.ApplePay.prototype.get_shipping_fields_array = function () {
            var fields = [];
            if (this.needs_shipping()) {
                if (!this.is_checkout_page()) {
                    fields.push("postalAddress");
                } else {
                    // only require the shipping address if the customer hasn't already entered one.
                    var address = this.get_address_object(this.get_shipping_prefix());
                    if (!this.is_valid_address(address, this.get_shipping_prefix(), ['phoneNumber', 'emailAddress'])) {
                        fields.push("postalAddress");
                    }
                }
            }
            if (this.fields.requestFieldInWallet('billing_email')) {
                fields.push("email");
            }
            if (this.fields.requestFieldInWallet('billing_phone')) {
                fields.push('phone');
            }
            return fields;
        }

        wc_braintree.ApplePay.prototype.populate_address_data = function (response) {
            return new Promise(function (resolve, reject) {
                if (typeof response.shippingContact !== 'undefined') {
                    // populate the shipping fields
                    this.populate_address_fields(response.shippingContact, 'shipping');
                }
                if (typeof response.billingContact !== 'undefined') {
                    // populate the billing fields
                    this.populate_address_fields(response.billingContact, 'billing');
                }
                resolve(response);
            }.bind(this))
        }

        wc_braintree.ApplePay.prototype.can_initialize_applepay = function () {
            return window.ApplePaySession && ApplePaySession.canMakePayments();
        }

        wc_braintree.ApplePay.prototype.has_nonce = function () {
            return $(this.nonce_selector).val().length > 0;
        }

        wc_braintree.ApplePay.prototype.onvalidatemerchant = function (event) {
            this.applePayInstance.performValidation({
                validationURL: event.validationURL,
                displayName: this.params.store_name
            }, function (err, merchantSession) {
                if (err) {
                    this.submit_error(err);
                    this.applePaySession.abort();
                    return;
                }
                this.applePaySession.completeMerchantValidation(merchantSession);
            }.bind(this))
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.onshippingcontactselected = function (event) {
            this.update_shipping_address({
                country: event.shippingContact.countryCode.toUpperCase(),
                state: event.shippingContact.administrativeArea.toUpperCase(),
                postcode: event.shippingContact.postalCode,
                city: event.shippingContact.locality
            }).then(function (response) {
                this.can_ship = true;
                this.applePaySession.completeShippingContactSelection(response.data.shippingContactUpdate);
                this.fields.set('shipping_method', response.data.chosen_shipping_methods);
            }.bind(this)).catch(function (response) {
                if (response.code === 'addressUnserviceable') {
                    this.can_ship = false;
                    this.applePaySession.completeShippingContactSelection(response.data.shippingContactUpdate);
                } else {
                    this.applePaySession.completeShippingContactSelection({
                        errors: [new ApplePayError(
                            response.code,
                            response.data.contactField,
                            response.message)],
                        newTotal: response.data.newTotal,
                        newShippingMethods: response.data.newShippingMethods
                    });
                }
            }.bind(this))
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.onshippingmethodselected = function (event) {
            var identifier = event.shippingMethod.identifier;
            this.update_shipping_method(identifier).then(function (response) {
                if (response.code) {
                    this.applePaySession.abort();
                    this.submit_error(response.messages);
                } else {
                    this.applePaySession.completeShippingMethodSelection(response.data.shippingMethodUpdate);
                }
            }.bind(this)).catch(function (err) {
                this.applePaySession.abort();
                this.submit_error(err);
            }.bind(this))
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.onpaymentmethodselected = function (event) {
            $.ajax({
                url: this.params.routes.applepay_payment_method,
                method: 'POST',
                dataType: 'json',
                data: {
                    wc_braintree_currency: this.get_currency()
                },
                beforeSend: this.ajax_before_send.bind(this),
                success: function (response, status, jqXHR) {
                    if (response.success) {
                        this.applePaySession.completePaymentMethodSelection(response.data);
                    } else {
                        this.applePaySession.abort();
                        this.submit_error(response.messages);
                        return;
                    }
                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    this.applePaySession.abort();
                    this.submit_error(errorThrown);
                }.bind(this)
            })
        }

        /**
         *
         */
        wc_braintree.ApplePay.prototype.onpaymentauthorized = function (event) {
            return new Promise(function (resolve, reject) {
                this.applePayInstance.tokenize({
                    token: event.payment.token
                }, function (err, response) {
                    if (err) {
                        this.submit_error(err);
                        this.applePaySession.completePayment(ApplePaySession.STATUS_FAILURE);
                        return;
                    }
                    var errors = this.validate_response(event.payment);
                    if (errors.length > 0) {
                        this.applePaySession.completePayment({
                            status: ApplePaySession.STATUS_FAILURE,
                            errors: errors
                        });
                        reject(response);
                    } else if (this.needs_shipping() && !this.can_ship) {
                        this.applePaySession.completePayment({
                            status: ApplePaySession.STATUS_FAILURE,
                            errors: [new ApplePayError('addressUnserviceable')]
                        });
                        reject(response);
                    } else {
                        this.applePaySession.completePayment(ApplePaySession.STATUS_SUCCESS);
                        this.set_nonce(response.nonce);
                        this.set_device_data();
                        this.populate_address_data(event.payment);
                        this.set_payment_method(this.gateway_id);
                        this.on_payment_method_received(response);
                        resolve(response);
                    }
                }.bind(this))
            }.bind(this))
        }

        function CheckoutFields(params, page) {
            this.params = params;
            this.page = page;
            this.fields = new Map(Object.keys(this.params).map(function (k) {
                var value = this.params[k].value;
                if (value === null) {
                    value = "";
                }
                return [k, value];
            }.bind(this)));
            if ('checkout' === page || ('cart' === page && $(document.body).is('.woocommerce-checkout'))) {
                $('form.checkout').on('change', '.input-text, select', this.onChange.bind(this));
                $('form.checkout').on('change', '[name="ship_to_different_address"]', this.on_ship_to_address_change.bind(this));
                this.init_i18n();
                if ($('[name="ship_to_different_address"]').is(':checked')) {
                    this.update_required_fields($('#shipping_country').val(), 'shipping_country');
                } else {
                    this.update_required_fields($('#billing_country').val(), 'billing_country');
                }
            }
        }

        CheckoutFields.prototype.onChange = function (e) {
            try {
                var name = e.currentTarget.name, value = e.currentTarget.value;
                this.fields.set(name, value);

                if (name === 'billing_country' || name === 'shipping_country') {
                    this.update_required_fields(value, name);
                }
            } catch (err) {
                console.log(err);
            }
        }

        CheckoutFields.prototype.init_i18n = function () {
            if (typeof wc_address_i18n_params !== 'undefined') {
                this.locales = JSON.parse(wc_address_i18n_params.locale.replace(/&quot;/g, '"'));
            } else {
                this.locales = null;
            }
        }

        CheckoutFields.prototype.set = function (k, v, prefix) {
            if (this[k] && typeof this[k] === 'function') {
                this[k]().set.call(this, v, prefix);
            } else {
                this.fields.set(k, v);
            }
        }

        CheckoutFields.prototype.get = function (k, prefix) {
            var value;
            if (this[k] && typeof this[k] === 'function') {
                value = this[k]().get.call(this, prefix);
            } else {
                value = this.fields.get(k);
                if (typeof value === 'undefined' || value === null || value === '') {
                    // prefix is default value here
                    if (typeof prefix !== 'undefined') {
                        value = prefix;
                    }
                }

            }
            return (typeof value === 'undefined' || value === null) ? '' : value;
        }

        CheckoutFields.prototype.exists = function (key) {
            return typeof this.params[key] !== 'undefined';
        }

        CheckoutFields.prototype.required = function (key) {
            if (this.params[key]) {
                if (typeof this.params[key].required !== 'undefined') {
                    return this.params[key].required;
                }
            }
            return false;
        }

        CheckoutFields.prototype.isValid = function (k) {
            if (this[k] && typeof this[k] === 'function') {
                return this[k]().isValid.apply(this, Array.prototype.slice.call(arguments, 1));
            }
        }

        CheckoutFields.prototype.isEmpty = function (k) {
            if (this.fields.has(k)) {
                var value = this.fields.get(k);
                return typeof value === 'undefined' || value === null || (typeof value === 'string' && value.trim().length === 0);
            }
            return true;
        }

        CheckoutFields.prototype.requestFieldInWallet = function (key) {
            if ('checkout' === this.page) {
                return this.required(key) && this.isEmpty(key);
            } else if ('order_pay' === this.page) {
                return false;
            }

            return this.required(key);
        }

        CheckoutFields.prototype.update_required_fields = function (country, name) {
            if (this.locales) {
                var prefix = name.indexOf('billing_') > -1 ? 'billing_' : 'shipping_';
                var locale = typeof this.locales[country] !== 'undefined' ? this.locales[country] : this.locales['default'];
                var fields = $.extend(true, {}, this.locales['default'], locale);
                for (var k in fields) {
                    var k2 = prefix + k;
                    if (this.params[k2]) {
                        this.params[k2] = $.extend(true, {}, this.params[k2], fields[k]);
                    }
                }
            }
        }

        CheckoutFields.prototype.on_ship_to_address_change = function (e) {
            if ($(e.currentTarget).is(':checked')) {
                this.update_required_fields($('#shipping_country').val(), 'shipping_country');
            }
        }

        CheckoutFields.prototype.first_name = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_first_name', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_first_name');
                },
                required: function (prefix) {
                    return this.required(prefix + '_first_name');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "";
                }
            }
        }

        CheckoutFields.prototype.last_name = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_last_name', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_last_name');
                },
                required: function (prefix) {
                    return this.required(prefix + '_last_name');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "";
                }
            }
        }

        CheckoutFields.prototype.name = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_full_name', v);
                    var name = v.split(" ");
                    if (name.length > 1) {
                        this.fields.set(prefix + '_last_name', name.pop());
                        this.fields.set(prefix + '_first_name', name.join(' '));
                    } else if (name.length == 1) {
                        this.fields.set(prefix + '_first_name', name[0]);
                    }
                },
                get: function (prefix) {
                    var name = this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
                    if (typeof name === 'string' && name.match(/^\s+$/)) {
                        name = '';
                    }
                    return name;
                },
                required: function (prefix) {
                    return this.required(prefix + '_first_name') && this.required(prefix + '_last_name');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "";
                }
            }
        }

        CheckoutFields.prototype.country = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_country', v.toUpperCase());
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_country');
                },
                required: function (prefix) {
                    return this.required(prefix + '_country');
                },
                isValid: function (v) {
                    return typeof v === 'string' && v.length === 2;
                }
            }
        }

        CheckoutFields.prototype.address_1 = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_address_1', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_address_1');
                },
                required: function (prefix) {
                    return this.required(prefix + '_address_1');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.address_2 = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_address_2', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_address_2');
                },
                required: function (prefix) {
                    return this.required(prefix + '_address_2');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.city = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_city', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_city');
                },
                required: function (prefix) {
                    return this.required(prefix + '_city');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.state = function () {
            return {
                set: function (v, prefix) {
                    v = v.toUpperCase();
                    if (v.length > 2 && this.page === 'checkout') {
                        $('#' + prefix + '_state option').each(function () {
                            var $option = $(this);
                            var state = $option.text().toUpperCase();
                            if (v === state) {
                                v = $option.val();
                            }
                        });
                    }
                    this.fields.set(prefix + '_state', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_state');
                },
                required: function (prefix) {
                    return this.required(prefix + '_state');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.postcode = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_postcode', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_postcode');
                },
                required: function (prefix) {
                    return this.required(prefix + '_postcode');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.phone = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_phone', v);
                },
                get: function (prefix) {
                    if (this.fields.has(prefix + '_phone')) {
                        return this.fields.get(prefix + '_phone');
                    }
                    return this.fields.get('billing_phone');
                },
                required: function (prefix) {
                    return this.required(prefix + '_phone');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }

        CheckoutFields.prototype.email = function () {
            return {
                set: function (v, prefix) {
                    this.fields.set(prefix + '_email', v);
                },
                get: function (prefix) {
                    return this.fields.get(prefix + '_email');
                },
                required: function (prefix) {
                    return this.required(prefix + '_email');
                },
                isValid: function (v) {
                    return typeof v !== 'undefined' && v !== "" && v !== null;
                }
            }
        }


        CheckoutFields.prototype.recipientName = function () {
            return this.name.apply(this, arguments);
        }

        CheckoutFields.prototype.line1 = function () {
            return this.address_1.apply(this, arguments);
        }

        CheckoutFields.prototype.line2 = function () {
            return this.address_2.apply(this, arguments);
        }

        CheckoutFields.prototype.countryCode = function () {
            return this.country.apply(this, arguments);
        }

        CheckoutFields.prototype.postalCode = function () {
            return this.postcode.apply(this, arguments);
        }

        CheckoutFields.prototype.phoneNumber = function () {
            return this.phone.apply(this, arguments);
        }

        CheckoutFields.prototype.address1 = function () {
            return this.address_1.apply(this, arguments);
        }

        CheckoutFields.prototype.address2 = function () {
            return this.address_2.apply(this, arguments);
        }

        CheckoutFields.prototype.locality = function () {
            return this.city.apply(this, arguments);
        }

        CheckoutFields.prototype.administrativeArea = function () {
            return this.state.apply(this, arguments);
        }

        CheckoutFields.prototype.givenName = function () {
            return this.first_name.apply(this, arguments);
        }

        CheckoutFields.prototype.familyName = function () {
            return this.last_name.apply(this, arguments);
        }

        CheckoutFields.prototype.addressLines = function () {
            return {
                set: function (v, prefix) {
                    this.address_1().set.call(this, v[0], prefix);
                    if (v.length > 1) {
                        this.address_2().set.call(this, v[1], prefix);
                    }
                },
                get: function (prefix) {
                    var address = [];
                    address.push(this.fields.get(prefix + '_address_1'));
                    if (this.fields.get(prefix + '_address_2')) {
                        address.push(this.fields.get(prefix + '_address_2'));
                    }
                    return address;
                },
                required: function (prefix) {
                    return this.required(prefix + '_address_1');
                },
                isValid: function (v) {
                    if (v.length > 0) {
                        return typeof v[0] !== 'undefined' && v[0] !== null && v[0].trim() !== '';
                    }
                    return false;
                }
            }
        }

        CheckoutFields.prototype.emailAddress = function () {
            return this.email.apply(this, arguments);
        }

        CheckoutFields.prototype.toJson = function () {
            var data = {};
            this.fields.forEach(function (value, key) {
                data[key] = value;
            });
            return data;
        }

        CheckoutFields.prototype.fromFormToFields = function () {
            this.fields.forEach(function (value, key) {
                var name = '[name="' + key + '"]';
                var val = $(name).val();
                if ($(name).length && val !== '' && value !== val) {
                    this.fields.set(key, val);
                }
            }.bind(this));
        }

        CheckoutFields.prototype.toFormFields = function (args) {
            var changes = [];
            this.fields.forEach(function (value, key) {
                var name = '[name="' + key + '"]';
                if ($(name).length && value !== '') {
                    if ($(name).val() !== value && $(name).is('select')) {
                        changes.push(name);
                    }
                    $(name).val(value);
                }
            });
            if (changes.length > 0) {
                $(changes.join(',')).trigger('change');
            }
            // this will override the previous changes trigger
            if (typeof args !== 'undefined') {
                $(document.body).trigger('update_checkout', args);
            }
        }

        CheckoutFields.prototype.validateFields = function (prefix) {
            for (var k in this.params) {
                var field = this.params[k];
                if (k.indexOf(prefix) > -1 && field.required) {
                    if ($('#' + k).length && $('#' + k).is(':visible')) {
                        var val = $('#' + k).val();
                        if (typeof val === 'undefined' || val === null || val.length === 0) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }

        var checkoutFields = new CheckoutFields(wc_braintree_checkout_fields, wc_braintree_global_params.page);

    }(jQuery)
)