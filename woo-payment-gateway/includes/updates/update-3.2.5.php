<?php

if ( function_exists( 'WC' ) ) {
	$gateways = WC()->payment_gateways()->payment_gateways();
	/**
	 * @var WC_Braintree_Payment_Gateway $gateway
	 */
	$gateway = isset( $gateways['braintree_cc'] ) ? $gateways['braintree_cc'] : false;
	if ( $gateway ) {
		$styles = $gateway->get_option( 'custom_form_styles' );
		if ( is_string( $styles ) ) {
			$styles = json_decode( $styles, true );
			if ( ! $styles ) {
				$styles = $gateway->form_fields['custom_form_styles']['default'];
			}
			$gateway->update_option( 'custom_form_styles', $styles );
		}
	}
}