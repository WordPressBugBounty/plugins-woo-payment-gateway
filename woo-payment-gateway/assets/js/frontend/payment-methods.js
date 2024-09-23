jQuery(function ($) {
    var payment_method = {
        params: wc_braintree_payment_methods_params,
        token_selector: '.wc-braintree-payment-token',
        container: '.wc-braintree-payment-gateway',
        new_method_container: '.wc-braintree-new-payment-method-container',
        saved_method_container: '.wc-braintree-payment-methods-container',
        payment_types: [],
        init: function () {
            if (this.has_payment_methods() && !this.init_complete) {

                //setInterval(this.update_elements, 2000);

                $(document.body).on('updated_checkout', this.update_elements);
                $(document.body).on('update_checkout', this.update_checkout);

                $(document.body).on('click', '.wc-braintree-payment-type', payment_method.display_payment_container);

                $(document.body).on('change', 'select.wc-braintree-payment-method', payment_method.update_method_from_select);

                this.initialize_elements();

                this.init_complete = true;

            }
        },
        initialize_elements: function () {

            if ($().selectWoo) {
                if ($('select.wc-braintree-payment-method').length && !$('select.wc-braintree-payment-method').hasClass('select2-hidden-accessible')) {
                    $('select.wc-braintree-payment-method').selectWoo({
                        width: "100%",
                        templateResult: payment_method.output_template,
                        templateSelection: payment_method.output_template,
                        language: {
                            noResults: function () {
                                return payment_method.params.no_results;
                            }.bind(this)
                        }
                    }).trigger('change');
                }
            }

        },
        update_checkout: function () {
            payment_method.payment_types = $('.wc-braintree-payment-type').filter(':checked');
        },
        update_elements: function () {
            payment_method.initialize_elements();
            $.each(payment_method.payment_types, function (i, el) {
                var id = "#" + $(el).attr('id');
                $(id).prop('checked', true).trigger('click');
            })
        },
        display_payment_container: function (e) {
            if ($('[name="' + e.currentTarget.name + '"]:checked').val() === 'token') {
                $(this).closest(payment_method.container).find(payment_method.new_method_container).slideUp(400, function () {
                    $(this).closest(payment_method.container).find(payment_method.saved_method_container).slideDown(400);
                    $(document.body).triggerHandler('wc_braintree_display_saved_methods', $('[name="payment_method"]:checked').val());
                });
            } else {
                $(this).closest(payment_method.container).find(payment_method.saved_method_container).slideUp(400, function () {
                    $(this).closest(payment_method.container).find(payment_method.new_method_container).slideDown(400);
                    $(document.body).triggerHandler('wc_braintree_display_new_payment_method', $('[name="payment_method"]:checked').val());
                });
            }
        },
        has_payment_methods: function () {
            return $('.wc-braintree-payment-methods-container').length;
        },
        update_method_from_select: function () {
            $(this).closest(payment_method.container).find(payment_method.token_selector).val($(this).val());
        },
        output_template: function (data, container) {
            var card = $(data.element).data('method-type'),
                gateway = $(data.element).data('gateway');
            if (card) {
                $.each(payment_method.params.cards, function (i, card) {
                    $(container).removeClass(card);
                })
                $(container).addClass('wc-braintree-select2-container ' + card);
            }
            $(document.body).triggerHandler('wc_braintree_payment_method_template', [data, container]);
            return data.text;
        }
    }
    payment_method.init();

    $(document.body).on('updated_checkout', payment_method.init.bind(payment_method));
})