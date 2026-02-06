<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_3ds extends WC_Braintree_Rest_Controller {

	protected $namespace = '3ds/';

	public function __construct() {
	}

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'vaulted_nonce',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_vaulted_nonce' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_vaulted_nonce( $request ) {
		$token    = null;
		$response = new WP_REST_Response();
		try {
			if ( ! is_user_logged_in() ) {
				throw new \Exception( __( 'User must be logged in to access this endpoint.', 'woo-payment-gateway' ) );
			}

			$user_id = get_current_user_id();

			if ( isset( $request['token_id'] ) ) {
				$token = WC_Payment_Tokens::get( $request->get_param( 'token_id' ) );
			} elseif ( isset( $request['token'] ) ) {
				$payment_method = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
				$token          = $payment_method->get_token( $request->get_param( 'token' ), $user_id );
			}
			if ( ! $token ) {
				throw new \Exception( __( 'Invalid token provided.', 'woo-payment-gateway' ) );
			}

			if ( $user_id !== $token->get_user_id() ) {
				throw new \Exception( __( 'You do not have access to this token.', 'woo-payment-gateway' ) );
			}

			$gateway = new \Braintree\Gateway( wc_braintree_connection_settings() );
			$result  = $gateway->paymentMethodNonce()->create( $token->get_token() );
			$response->set_data(
				array(
					'success' => true,
					'data'    => array(
						'nonce'   => $result->paymentMethodNonce->nonce,
						'details' => $result->paymentMethodNonce->details,
					),
				)
			);
			$response->set_status( 200 );
		} catch ( \Braintree\Exception $e ) {
			$response->set_data(
				array(
					'success' => false,
					'message' => wc_braintree_errors_from_object( $e ),
				)
			);
		} catch ( \Exception $e ) {
			$response->set_data(
				array(
					'success' => false,
					'message' => $e->getMessage()
				)
			);
		}

		return $response;
	}
}
