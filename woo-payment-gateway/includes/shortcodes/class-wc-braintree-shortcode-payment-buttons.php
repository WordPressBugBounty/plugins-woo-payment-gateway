<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Braintree_Shortcode_Payment_Buttons
 * @since 3.2.15
 */
class WC_Braintree_Shortcode_Payment_Buttons {

	public static function output_product_buttons( $atts ) {
		WC_Braintree_Field_Manager::output_product_checkout_fields();
	}

	public static function output_cart_buttons( $atts ) {
		wc_braintree_cart_checkout_template();
	}
}