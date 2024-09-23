(function ($, wc_braintree) {

    /**
     *
     * @param container
     * @constructor
     */
    function MiniCart() {
        this.message_container = '.widget_shopping_cart_content';
        this.container = container;
    }

    /**
     * This is the mini-cart so always return "cart" for the page.
     * @returns {string}
     */
    MiniCart.prototype.get_page = function () {
        return 'cart';
    }

    /**
     *
     */
    MiniCart.prototype.on_payment_method_received = function () {
        this.block_cart();
        this.process_checkout();
    }

    MiniCart.prototype.block_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wc-braintree-mini-cart-overlay').addClass('active');
    }

    MiniCart.prototype.unblock_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wc-braintree-mini-cart-overlay').removeClass('active');
    }

    /*---------------------------- GPay ----------------------------*/

    function GooglePay(params) {
        this.params = params;
        wc_braintree.BaseGateway.apply(this, arguments);
    }

    GooglePay.prototype = $.extend({}, wc_braintree.GooglePay.prototype, MiniCart.prototype);

    GooglePay.prototype.initialize = function () {
        MiniCart.apply(this, arguments);
        this.create_payments_client();
    }

    GooglePay.prototype.create_instance = function () {
        wc_braintree.GooglePay.prototype.create_instance.apply(this, arguments).then(function () {
            this.create_button().then(function () {
                $(this.container).find('.wc-braintree-googlepay-mini-cart-button').empty();
                $(this.container).find('.wc-braintree-googlepay-mini-cart-button').append(this.$button).show();
            }.bind(this));
        }.bind(this));
    }

    /*---------------------------- PayPal ----------------------------*/

    function PayPal(params) {
        this.params = params;
        wc_braintree.PayPal.apply(this, arguments);
    }

    PayPal.prototype = $.extend({}, wc_braintree.PayPal.prototype, MiniCart.prototype);

    PayPal.prototype.initialize = function () {
        MiniCart.apply(this, arguments);
    }

    PayPal.prototype.create_instance = function () {
        wc_braintree.PayPal.prototype.create_instance.apply(this, arguments).then(function () {
            this.create_button();
        }.bind(this))
    }

    PayPal.prototype.create_button = function () {
        wc_braintree.PayPal.prototype.create_button.apply(this, arguments).then(function () {
            this.get_button_container().show();
        }.bind(this))
    }

    PayPal.prototype.get_button_container = function () {
        return $(this.container + ' .wc-braintree-paypal-mini-cart-button');
    }

    /*---------------------------- ApplePay ----------------------------*/

    function ApplePay(params) {
        this.params = params;
        wc_braintree.BaseGateway.apply(this, arguments);
    }

    ApplePay.prototype = $.extend({}, wc_braintree.ApplePay.prototype, MiniCart.prototype);

    ApplePay.prototype.initialize = function () {
        MiniCart.apply(this, arguments);
    }

    ApplePay.prototype.create_instance = function () {
        wc_braintree.ApplePay.prototype.create_instance.apply(this, arguments).then(function () {
            $(this.container).find('.wc-braintree-applepay-mini-cart-button').show();
            $(this.container).find('.apple-pay-button').on('click', this.start.bind(this));
        }.bind(this));
    }

    ApplePay.prototype.start = function (e) {
        e.preventDefault();
        this.init_wallet();
        this.open_wallet();
    }

    /* -------------------------------------------------------------------------------- */

    var gateways = [], container = null;
    var client = null, client_token = null;

    if (typeof wc_braintree_googlepay_mini_cart_params !== 'undefined') {
        gateways.push([GooglePay, wc_braintree_googlepay_mini_cart_params]);
    }
    if (typeof wc_braintree_applepay_mini_cart_params !== 'undefined') {
        gateways.push([ApplePay, wc_braintree_applepay_mini_cart_params]);
    }
    if (typeof wc_braintree_paypal_mini_cart_params !== 'undefined') {
        gateways.push([PayPal, wc_braintree_paypal_mini_cart_params]);
    }

    function load_mini_cart() {
        $('.widget_shopping_cart_content').not(':empty').each(function (idx, el) {
            if ($(el).find('.wc_braintree_mini_cart_gateways').length) {
                $(el).find('.wc-braintree-mini-cart-overlay').remove();
                if (!client) {
                    setTimeout(load_mini_cart, 200);
                    return;
                }
                var $parent = $(el).parent();
                if ($parent.length) {
                    var class_name = 'wc-braintree-mini-cart-idx-' + idx;
                    $parent.addClass(class_name);
                    $parent.find('.widget_shopping_cart_content').prepend('<div class="wc-braintree-mini-cart-overlay"></div>');
                    container = '.' + class_name + ' .widget_shopping_cart_content p.buttons';
                    gateways.forEach(function (gateway) {
                        var instance = new gateway[0](gateway[1]);
                        instance.create_instance(client, client_token);
                    });
                }
            }
        });
    }

    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', load_mini_cart);

    function InitClient() {

    }

    InitClient.prototype.create_instance = function (clientInstance, token) {
        client = clientInstance;
        client_token = token;
    }

    wc_braintree.register(InitClient);

}(jQuery, window.wc_braintree));