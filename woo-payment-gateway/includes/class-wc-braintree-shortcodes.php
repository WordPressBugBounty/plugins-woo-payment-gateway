<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Braintree_Shortcodes
 * @since 3.2.19
 */
class WC_Braintree_Shortcodes {

	public static function init() {
		$shortcodes = array(
			'wc_braintree_payment_buttons' => array( 'WC_Braintree_Shortcodes', 'payment_buttons' ),
		);

		foreach ( $shortcodes as $key => $function ) {
			add_shortcode( $key, apply_filters( 'wc_braintree_shortcode_function', $function ) );
		}
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	public static function payment_buttons( $atts ) {
		$method  = '';
		$wrapper = array(
			'class' => 'wc-braintree-shortcode'
		);
		if ( is_product() ) {
			$method           = 'output_product_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wc-braintree-shortcode-product-buttons';
		} else if ( ! is_null( WC()->cart ) && ( is_cart() || ( isset( $atts['page'] ) && 'cart' === $atts['page'] ) ) ) {
			$method           = 'output_cart_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wc-braintree-shortcode-cart-buttons';
		}
		if ( ! $method ) {
			return '';
		}
		include_once braintree()->plugin_path() . 'includes/shortcodes/class-wc-braintree-shortcode-payment-buttons.php';

		return WC_Shortcodes::shortcode_wrapper( array( 'WC_Braintree_Shortcode_Payment_Buttons', $method ), $atts, $wrapper );
	}
}

WC_Braintree_Shortcodes::init();