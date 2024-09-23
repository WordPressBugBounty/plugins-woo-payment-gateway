<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Rest_Webook_Authentication {

	public function __construct() {
		add_filter( 'rest_pre_dispatch', array( $this, 'check_authentication' ), 10, 3 );
	}

	/**
	 *
	 * @param WP_REST_Response $response
	 * @param WP_REST_Server $server
	 * @param WP_REST_Request $request
	 * @return unknown
	 */
	public function check_authentication( $response, $server, $request ) {
		// If errors exists pass them through.
		if ( ! empty( $response ) ) {
			return $response;
		}
		if ( $this->is_request_to_webook_api( $request->get_route() ) ) {
			return $this->authenticate_request( $request );
		}
		return $response;
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	private function authenticate_request( $request ) {
		$post = $request->get_body_params();

		$signature = isset( $post['bt_signature'] ) ? $post['bt_signature'] : '';
		$payload   = isset( $post['bt_payload'] ) ? str_replace( array( "\\n", "\\r" ), '', $post['bt_payload'] ) : '';
		try {
			$env          = wc_braintree_environment();
			$notification = braintree()->gateway()->webhookNotification()->parse( $signature, $payload );
			$request->set_param( 'wc_braintree_notification', $notification );
			wc_braintree_log_info( sprintf( __( 'Webhook received. Kind: %1$s.', 'woo-payment-gateway' ), $notification->kind ) );
			return null;
		} catch ( Exception $e ) {
			wc_braintree_log_error( sprintf( __( 'Error authenticating Braintree webhook request. Environment: %1$s. Reason: %2$s', 'woo-payment-gateway' ), $env, $e->getMessage() ) );
			return new WP_Error( 'wc_braintree_webook_authentication_error', __( 'Invalid webhook request.', 'woo-payment-gateway' ), array( 'status' => 401 ) );
		}
	}

	private function is_request_to_webook_api( $route ) {
		$routes = get_option( 'wc_braintree_authenticated_routes', array() );
		foreach ( $routes as $auth_route ) {
			if ( preg_match( '@^' . $auth_route . '$@i', $route ) ) {
				return true;
			}
		}
		return false;
	}
}
new WC_Braintree_Rest_Webook_Authentication();
