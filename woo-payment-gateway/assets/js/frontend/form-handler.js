(function ($) {

    var handler = {
        init: function () {
            $(document.body).on('click', '[name="payment_method"]', this.payment_method_selected);
            if (!$(document.body).hasClass('woocommerce-checkout')) {
                $('input[name="payment_method"]').closest('form').on('submit', this.submit_form);
            } else {
                //this is a trigger that originates in the the WC checkout.js file
                $(document.body).on('payment_method_selected', this.payment_method_selected);
                $(document.body).on('updated_checkout', this.payment_method_selected);
            }

            $(document.body).on('click', '#place_order', this.pre_form_submit);

            $(document.body).on('updated_wc_div, updated_cart_totals', this.terms_update);
            $(document.body).on('updated_wc_div, updated_cart_totals', this.cart_html);
            $(document.body).on('change', '.cart_totals [name="terms"]', function () {
                handler.cart_terms_checked = $(this).is(':checked');
            });
            if (!$(document.body).hasClass('wc-braintree-body')) {
                $(document.body).addClass('wc-braintree-body');
            }
            if ($(document.body).is('.woocommerce-cart')) {
                $(window).on('resize', this.cart_html);
                this.cart_html();
            }
        },
        submit_form: function (e) {
            var $form = $(this),
                payment_gateway = $('input[name="payment_method"]').length ? $('input[name="payment_method"]:checked').val() : '';

            if ($form.triggerHandler('woocommerce_form_submit_' + payment_gateway) !== false) {
                return true;
            } else {
                e.preventDefault();
                return false;
            }
        },
        pre_form_submit: function (e) {
            $(document.body).triggerHandler('wc_braintree_pre_form_submit_' + handler.get_gateway());
        },
        get_gateway: function () {
            return $('input[name="payment_method"]').length > 0 ? $('input[name="payment_method"]:checked').val() : '';
        },
        payment_method_selected: function () {
            $(document.body).triggerHandler('wc_braintree_payment_method_selected', handler.get_gateway());
            if (handler.get_gateway().indexOf('braintree_') == -1) {
                $("#place_order").removeClass('wc-braintree-hide');
            }
        },
        cart_checkout_button: function () {
            $('.wc_braintree_cart_gateways').hide();
            $('#wc_braintree_place_order').show().addClass('active');
        },
        terms_update: function () {
            $('[name="terms"]').prop('checked', handler.cart_terms_checked);
        },
        cart_html: function () {
            var $button = $('.checkout-button'),
                width = $button.outerWidth();
            if (width && $('.wc_braintree_cart_gateways').length) {
                $('.wc_braintree_cart_gateways').width(width);
            }
            if ($button.css('float') !== 'none') {
                $('.wc_braintree_cart_gateways ').css('float', $button.css('float'));
            }
        }
    }

    handler.init();
}(jQuery));