<?php
if ( function_exists( 'WC' ) ) {
	$payment_gateways = WC()->payment_gateways()->payment_gateways();
	if ( isset( $payment_gateways['braintree_paypal'] ) ) {
		/**
		 * @var WC_Braintree_PayPal_Payment_Gateway $payment_gateway
		 */
		$payment_gateway = $payment_gateways['braintree_paypal'];
		// for existing merchants set pay later to empty array
		$payment_gateway->settings['pay_later_msg'] = array();
		update_option( $payment_gateway->get_option_key(), $payment_gateway->settings );
	}
}
