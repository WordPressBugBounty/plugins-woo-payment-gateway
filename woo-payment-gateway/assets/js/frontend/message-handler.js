(function ($) {

    var handler = {
        init: function () {
            $(document.body).on('wc_braintree_submit_error', this.handle_error_message);
        },
        handle_error_message: function (e, data) {
            handler.data = data;
            handler.element = data.element;
            var message = '';
            if (typeof data.error === "string") {
                message = data.error;
            } else {
                message = data.error.message;
            }
            var code = handler.get_code(data);

            if (code) {
                message = wc_braintree_message_handler_params.messages[code] ? wc_braintree_message_handler_params.messages[code] : data.error.message;
                if (code === 'THREEDS_LOOKUP_VALIDATION_ERROR') {
                    try {
                        message += ' ' + data.error.details.originalError.details.originalError.error.message;
                    } catch (error) {
                    }
                }
            }
            if (message.indexOf('woocommerce-notice') !== -1) {
                handler.submit_message(message);
            } else if (message.indexOf('woocommerce-info') !== -1) {
                handler.submit_message(message);
            } else if (message.indexOf('woocommerce-error') !== -1) {
                handler.submit_message(message);
            } else {
                handler.submit_error(message);
            }
        },
        submit_error: function (message) {
            if (message.indexOf('</ul>') < 0) {
                message = '<ul class="woocommerce-error"><li>' + message + '</li></ul>';
                var classes = (function () {
                    var classes = 'woocommerce-NoticeGroup';
                    if ($(document.body).is('.woocommerce-checkout')) {
                        classes += ' woocommerce-NoticeGroup-checkout';
                    }
                    return classes;
                }.bind(this)());
                message = '<div class="' + classes + '">' + message + '</div>';
            }
            handler.submit_message(message);
        },
        submit_message: function (message) {
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
            var $container = $(handler.element);
            if ((!handler.data.ignore && $(handler.element).closest('form').length) || !$container.length) {
                $container = $(handler.element).closest('form');
            }
            $container.prepend(message);
            $container.removeClass('processing').unblock();
            $container.find('.input-text, select, input:checkbox').trigger('blur');
            if ($.scroll_to_notices) {
                $.scroll_to_notices($container);
            } else {
                $('html, body').animate({
                    scrollTop: ($container.offset().top - 100)
                }, 1000);
            }
        },
        get_code: function (data) {
            if (data.error.code) {
                return data.error.code;
            } else if (data.error.type) {
                return data.error.type;
            } else if (data.error.name) {
                return data.error.name;
            } else if (data.error.message) {
                return false;
            }
        }
    }
    handler.init();
}(jQuery))