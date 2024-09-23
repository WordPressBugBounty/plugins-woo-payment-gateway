<?php
defined( 'ABSPATH' ) || exit();

/**
 * remove PayPal option from credit cards icons
 */
$cc_options = get_option( 'woocommerce_braintree_cc_settings', array() );

if ( ! empty( $cc_options['payment_methods'] ) && is_array( $cc_options['payment_methods'] ) ) {
	$index = array_search( 'paypal', $cc_options['payment_methods'] );
	if ( false !== $index ) {
		unset( $cc_options['payment_methods'][ $index ] );
		update_option( 'woocommerce_braintree_cc_settings', $cc_options );
	}
}
