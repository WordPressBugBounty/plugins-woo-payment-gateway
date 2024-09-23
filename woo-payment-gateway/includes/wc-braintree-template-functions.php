<?php
defined( 'ABSPATH' ) || exit();

use PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since 3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_cart_checkout_template() {
	$gateways = array();
	foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
		if ( $gateway->supports( 'wc_braintree_cart_checkout' ) && $gateway->is_cart_checkout_enabled() ) {
			$gateways[ $gateway->id ] = $gateway;
		}
	}
	if ( count( apply_filters( 'wc_braintree_cart_payment_gateways', $gateways ) ) > 0 ) {
		wc_braintree_get_template(
			'cart/cart-fields.php',
			array(
				'gateways'   => $gateways,
				'priority'   => apply_filters( 'wc_braintree_cart_buttons_priority', 30 ),
				'cart_total' => WC()->cart->total,
			)
		);
	}
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_banner_checkout_template() {
	$gateways = array();
	foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
		if ( $gateway->supports( 'wc_braintree_banner_checkout' ) && $gateway->banner_checkout_enabled() ) {
			$gateways[ $id ] = $gateway;
		}
	}
	if ( count( $gateways ) > 0 ) {
		wc_braintree_get_template( 'checkout/checkout-banner.php', array( 'gateways' => $gateways ) );
	}
}

/**
 *
 * @since 3.0.0
 * @deprecated
 *
 * @package Braintree/Functions
 */
function wc_braintree_show_product_checkout_gateways() {

}

/**
 * Check the file for version tag and send notice if deprecated template is being used.
 *
 * @param string $template_name
 * @param string $template_path
 * @param string $located
 *
 * @package Braintree/Functions
 * @since 3.0.0
 */
function wc_braintree_deprecated_template_check( $template_name, $template_path, $located ) {
	if ( $template_path === braintree()->template_path() ) {
		$data = wc_braintree_get_file_data( $located, array( 'version' => 'version' ) );
		if ( $data && ! empty( $data['version'] ) ) {
			if ( version_compare( $data['version'], '3.0.0', '<' ) ) {
				_deprecated_file( $located, '3.0.0', braintree()->template_path() . $template_name, 'Please update your theme templates to use the new plugin files' );
			}
		}
	}
}

/**
 *
 * @param string $filename
 * @param array $headers
 *
 * @return array
 * @package Braintree/Functions
 * @since 3.0.0
 */
function wc_braintree_get_file_data( $filename, $headers ) {
	$file = fopen( $filename, 'r' );
	$data = fread( $file, 8192 );
	fclose( $file );
	// read the file data.
	foreach ( $headers as $key => $regex ) {
		if ( preg_match( '/[ \t\/*#@]' . preg_quote( $regex, '/' ) . '\s+([\w\.]+)/', $data, $match ) ) {
			$headers[ $key ] = $match[1];
		} else {
			$headers[ $key ] = '';
		}
	}

	return $headers;
}
