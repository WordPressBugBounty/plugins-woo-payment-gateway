<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Braintree_Controller_Frontend
 * @since 3.2.5
 * @package Braintree/Abstracts
 */
abstract class WC_Braintree_Controller_Frontend extends WC_Braintree_Rest_Controller {

	protected function cart_includes() {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		wc_load_cart();
		// loads cart from session
		WC()->cart->get_cart();
		WC()->payment_gateways();
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
		wc_maybe_define_constant( 'DOING_AJAX', true );
	}

	protected function frontend_includes() {
		WC()->frontend_includes();
		wc_load_cart();
		WC()->cart->get_cart();
		WC()->payment_gateways();
		wc_maybe_define_constant( 'DOING_AJAX', true );
	}
}