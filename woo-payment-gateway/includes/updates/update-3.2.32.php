<?php

defined( 'ABSPATH' ) || exit();

/**
 * Convert the GPay buttonStyle to the new format.
 */
if ( function_exists( 'WC' ) ) {
	$payment_methods = WC()->payment_gateways()->payment_gateways();
	if ( isset( $payment_methods['braintree_googlepay'] ) ) {
		/**
		 * @var \WC_Braintree_Payment_Gateway $payment_method
		 */
		$payment_method = $payment_methods['braintree_googlepay'];
		$button_style   = $payment_method->get_option( 'button_type', 'long' );
		if ( $button_style === 'long' ) {
			$button_style = 'buy';
		} elseif ( $button_style === 'short' ) {
			$button_style = 'plain';
		}
		$payment_method->update_option( 'button_type', $button_style );
	}
}