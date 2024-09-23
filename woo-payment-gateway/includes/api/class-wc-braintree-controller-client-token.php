<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Client_Token extends WC_Braintree_Controller_Frontend {

	protected $namespace = 'client-token/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'create',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_client_token' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'currency' => array(
							'required' => true
						)
					)
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_client_token( $request ) {
		$client_token = $this->generate_client_token( $request['currency'] );
		if ( is_wp_error( $client_token ) ) {
			return $client_token;
		}

		return rest_ensure_response( $client_token );
	}

	/**
	 * Generate a client token
	 *
	 * @param $currency
	 *
	 * @return string
	 */
	private function generate_client_token( $currency ) {
		try {
			$args = array();
			if ( ( $merchant_account = wc_braintree_get_merchant_account( $currency ) ) ) {
				$args['merchantAccountId'] = $merchant_account;
			}
			$gateway = new \Braintree\Gateway( wc_braintree_connection_settings() );

			return $gateway->clientToken()->generate( $args );
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'client-token-error', __( 'Error creating client token.', 'woo-payment-gateway' ), array( 'status' => 400 ) );
		}
	}
}
