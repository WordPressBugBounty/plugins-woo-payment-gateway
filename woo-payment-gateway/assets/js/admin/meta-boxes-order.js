jQuery(function ($) {
    var meta_box = {
        params: wc_braintree_meta_boxes_order_params,
        init: function () {
            $(document.body).on('click', '.capture-charge', this.capture_charge)
                .on('keyup', '[name="capture_amount"]', this.validate_capture_amount)
                .on('click', '.do-api-capture', this.api_capture)
                .on('click', '.do-api-void', this.api_void)
                .on('click', '.do-braintree-transaction-view', this.transaction_view);

            $('#customer_user').on('change', this.api_customer_tokens);
            $('.wc-braintree-pay-order').on('click', this.pay_order);
            $(document.body).on('wc_backbone_modal_loaded', this.modal_loaded)
                .on('wc_backbone_pay_order_modal_response', this.process_payment)
                .on('order-totals-recalculate-success', this.clear_transaction_data)
                .on('items_saved', this.clear_transaction_data);
        },
        validate_capture_amount: function (e) {
            var value = $(this).val();
            value = parseFloat(value);
            if (!Number.isNaN(value) && meta_box.$viewButton) {
                var data = meta_box.$viewButton.data('transaction-data');
                if (value < parseFloat(data.order_total)) {
                    if (typeof woocommerce_admin !== 'undefined') {
                        woocommerce_admin.capture_notice = meta_box.params.messages.capture_amount;
                        $(document.body).triggerHandler('wc_add_error_tip', [$(this), 'capture_notice']);
                    }
                } else {
                    $(document.body).triggerHandler('wc_remove_error_tip', [$(this), 'capture_notice']);
                }
            }
        },
        clear_transaction_data: function () {
            $('.do-braintree-transaction-view').data('transaction-data', null);
        },
        capture_charge: function () {
            $('div.wc-order-capture-charge').slideDown();
            $('div.wc-braintree-buttons-container').slideUp();
            return false;
        },
        cancel: function () {
            $('div.wc-order-capture-charge').slideUp();
            $('div.wc-braintree-buttons-container').slideDown();
            return false;
        },
        pay_order: function (e) {
            e.preventDefault()
            $(this).WCBraintreePayOrderBackboneModal({
                template: 'wc-braintree-modal-pay-order',
                variable: {
                    customer_id: $('#customer_user').val(),
                    payment_methods: wc_braintree_order_pay_params.payment_methods,
                    transaction_id: wc_braintree_order_pay_params.transaction_id
                }
            });
        },
        modal_loaded: function (e, target) {
            switch (target) {
                case 'wc-braintree-modal-pay-order':
                    meta_box.init_pay_modal();
                    break;
            }
        },
        init_pay_modal: function () {
            braintree.dropin.create({
                authorization: wc_braintree_order_pay_params.client_token,
                selector: '#card-element'
            }, function (err, dropinInstance) {
                meta_box.dropinInstance = dropinInstance;
            });
        },
        process_payment: function (e, modal) {
            // tokenize the payment method.
            if (modal.use_token()) {
                meta_box.api_payment(modal, null);
            } else {
                meta_box.dropinInstance.requestPaymentMethod(function (err, payload) {
                    if (err) {
                        window.alert(err);
                        return;
                    }
                    modal.set_nonce(payload.nonce);
                    meta_box.api_payment(modal, payload);
                })
            }
        },
        api_payment: function (modal) {
            modal.block();
            // do api request
            $.ajax({
                url: meta_box.params.urls.process_payment,
                method: 'POST',
                dataType: 'json',
                data: $.extend(modal.getFormData(), {
                    _wpnonce: meta_box.params._wpnonce,
                    order_id: woocommerce_admin_meta_boxes.post_id,
                    customer_id: $('#customer_user').val()
                }),
            }).done(function (response) {
                if (!response.code) {
                    window.location.href = window.location.href;
                } else {
                    modal.unblock();
                    modal.add_messages(response.message);
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                modal.unblock();
                modal.add_messages(errorThrown);
            })
        },
        api_capture: function () {
            meta_box.block();

            var data = {
                _wpnonce: meta_box.params._wpnonce,
                order_id: woocommerce_admin_meta_boxes.post_id,
                amount: $('#capture_amount').val()
            };

            $.post(meta_box.params.urls.capture, data, function (response) {
                if (!response.code) {
                    window.location.href = window.location.href;
                } else {
                    meta_box.unblock();
                    window.alert(response.message);
                }
            }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
                meta_box.unblock();
                window.alert(errorThrown);
            });
        },
        api_void: function () {
            if (!confirm(meta_box.params.messages.void)) {
                return;
            }
            meta_box.block();
            var data = {
                _wpnonce: meta_box.params._wpnonce,
                order_id: woocommerce_admin_meta_boxes.post_id
            };

            $.post(meta_box.params.urls.void, data, function (response) {
                if (!response.code) {
                    window.location.href = window.location.href;
                } else {
                    meta_box.unblock();
                    window.alert(response.message);
                }
            }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
                meta_box.unblock();
                window.alert(errorThrown);
            });
        },
        api_customer_tokens: function (e) {
            // fetch user payment tokens
            var customer_id = $(e.target).val();
            wc_braintree_order_pay_params.payment_methods = [];
            if (customer_id > 0 && typeof wc_braintree_order_pay_params !== "undefined") {
                var data = {customer_id: customer_id, _wpnonce: meta_box.params._wpnonce};
                $('.wc-braintree-pay-order').prop('disabled', true);
                $.get(meta_box.params.urls.customer_payment_methods, data, function (response) {
                    wc_braintree_order_pay_params.payment_methods = response.payment_methods;
                    $('.wc-braintree-pay-order').prop('disabled', false);
                }, 'json');
            }
        },
        transaction_view: function (e) {
            e.preventDefault();
            var $viewButton = meta_box.$viewButton = $(this);
            if ($viewButton.data('transaction-data')) {
                $(this).WCBackboneModal({
                    template: 'wc-braintree-view-transaction',
                    variable: $(this).data('transaction-data')
                });
            } else {
                $viewButton.addClass('disabled');
                $.ajax({
                    method: 'GET',
                    dataType: 'json',
                    url: meta_box.params.urls.transaction,
                    data: {order_id: $(this).data('order'), _wpnonce: meta_box.params._wpnonce}
                }).done(function (response) {
                    $viewButton.removeClass('disabled');
                    if (response.code) {
                        window.alert(response.message);
                    } else {
                        $viewButton.data('transaction-data', response.data);
                        $(this).WCBackboneModal({
                            template: 'wc-braintree-view-transaction',
                            variable: response.data
                        });
                    }
                }).fail(function (xhr, textStatus, errorThrown) {
                    $viewButton.removeClass('disabled');
                    if (xhr.status == 404) {
                        window.alert("Resource not found. This error typically occurs if your WP site has access issues. Check your .htaccess file and ensure the WP rest url is enabled.");
                    } else if (xhr.status == 403) {
                        window.alert("Your user does not have permission to access the transaction view popup. Please make sure you have shop manager or admin rights.");
                    } else {
                        window.alert(errorThrown);
                    }
                })
            }
        },
        block: function () {
            $('.wc-transaction-data').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function () {
            $('.wc-transaction-data').unblock();
        }
    }
    meta_box.init();
})