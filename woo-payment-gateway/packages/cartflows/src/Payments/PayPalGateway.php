<?php


namespace PaymentPlugins\CartFlows\Braintree\Payments;

/**
 * Class PayPalGateway
 * @package PaymentPlugins\CartFlows\Braintree\Payments
 */
class PayPalGateway extends BasePaymentGateway {

	public function __construct( \Braintree\Gateway $client, \Cartflows_Logger $logger ) {
		parent::__construct( $client, $logger );
		add_action( 'wc_braintree_get_paypal_flow', array( $this, 'get_paypal_flow' ), 10, 3 );
	}

	/**
	 * @param $type
	 * @param $payment_method
	 * @param $page
	 *
	 * @return mixed|string
	 */
	public function get_paypal_flow( string $type, \WC_Braintree_Payment_Gateway $payment_method, string $page ) {
		if ( $type !== 'vault' && is_checkout() && _is_wcf_checkout_type() ) {
			$type = 'vault';
		}

		return $type;
	}
}