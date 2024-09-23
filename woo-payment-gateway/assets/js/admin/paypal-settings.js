jQuery(function ($) {
    var settings = {
        sources: [paypal.FUNDING.PAYPAL, paypal.FUNDING.PAYLATER, paypal.FUNDING.CARD],
        buttons: {},
        init: function () {
            this.render_button();
            $('[class*="wc-braintree-smartbutton"]').on('change', this.render_button.bind(this));
            this.init_slider();
        },
        render_button: function () {
            var sources = this.get_funding();
            this.sources.forEach(function (source) {
                if (source in this.buttons && 'close' in this.buttons[source]) {
                    this.buttons[source].close();
                }
                if (sources.indexOf(source) > -1) {
                    this.buttons[source] = paypal.Buttons(this.get_button(source));
                    this.buttons[source].render('#wc-braintree-button-demo');
                }
            }.bind(this))
        },
        get_funding: function () {
            var funding = [paypal.FUNDING.PAYPAL];
            if ($('#woocommerce_braintree_paypal_bnpl_enabled').is(':checked')) {
                funding.push(paypal.FUNDING.PAYLATER);
            }
            if ($('#woocommerce_braintree_paypal_smartbutton_cards').is(':checked')) {
                funding.push(paypal.FUNDING.CARD);
            }
            return funding;
        },
        get_button: function (source) {
            var options = {
                fundingSource: source,
                style: {
                    layout: 'vertical', //$('#woocommerce_braintree_paypal_smartbutton_layout').val(),
                    color: $('#woocommerce_braintree_paypal_smartbutton_color').val(),
                    shape: $('#woocommerce_braintree_paypal_smartbutton_shape').val(),
                    height: parseInt($('#woocommerce_braintree_paypal_button_height').val())
                },
                onInit: function (data, actions) {
                    actions.disable();
                }
            };
            if (source == paypal.FUNDING.CARD) {
                options.style.color = $('#woocommerce_braintree_paypal_card_button_color').val();
            } else if (source == paypal.FUNDING.PAYLATER) {
                options.style.color = $('#woocommerce_braintree_paypal_bnpl_button_color').val();
            } else {
                options.style.label = $('#woocommerce_braintree_paypal_smartbutton_label').val();
            }
            return options;
        },
        init_slider: function () {
            var $slider = $('.wc-braintree-slider');
            $slider.slider($slider.data('options'));
            $slider.on('slidechange', function (e, ui) {
                $('#woocommerce_braintree_paypal_button_height').val(ui.value);
                $('.wc-braintree-slider-val').text(ui.value + 'px');
                settings.render_button();
            })
            $slider.on('slide', function (e, ui) {
                $('.wc-braintree-slider-val').text(ui.value + 'px');
            })
        }
    };
    settings.init();
});