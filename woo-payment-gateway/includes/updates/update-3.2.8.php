<?php
/**
 * Convert credit options to Pay Later options
 */
if ( function_exists( 'WC' ) ) {
	// get paypal gateway
	$gateways = WC()->payment_gateways()->payment_gateways();
	if ( isset( $gateways['braintree_paypal'] ) ) {
		/**
		 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
		 */
		$gateway = $gateways['braintree_paypal'];

		// add pay later button options
		$gateway->settings['bnpl_button_color'] = $gateway->settings['smartbutton_color'];
		$gateway->settings['bnpl_enabled']      = $gateway->settings['credit_enabled'];
		$gateway->settings['bnpl_sections']     = $gateway->settings['credit_sections'];
		update_option( $gateway->get_option_key(), $gateway->settings );
	}
}