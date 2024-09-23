<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Payment_Tokens extends WC_Braintree_Rest_Controller {

	protected $namespace = 'payment-tokens/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'(?P<token_id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => array( $this, 'check_user_permissions' ),
				'args'                => array(
					'token_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
	}

	public function check_user_permissions( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'permission-error',
				__( 'You do not have permissions to access this resource.', 'woo-payment-gateway' ),
				array(
					'status' => 403,
				)
			);
		}

		return true;
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( $request ) {
		$token_id = $request->get_param( 'token_id' );
		WC()->payment_gateways();
		WC_Payment_Tokens::delete( $token_id );

		return rest_ensure_response( array( 'success' => true ) );
	}
}
