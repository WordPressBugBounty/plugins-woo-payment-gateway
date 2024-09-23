<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 *
 */
abstract class WC_Braintree_Rest_Controller {

	protected $namespace = '';

	/**
	 *
	 * @param string $route
	 */
	protected function register_authenticated_route( $route ) {
		$routes                  = get_option( 'wc_braintree_authenticated_routes', array() );
		$route                   = '/' . trim( $route, '/' );
		$routes[ md5( $route ) ] = $route;
		update_option( 'wc_braintree_authenticated_routes', $routes );
	}

	/**
	 * Register all routes that the controller uses.
	 */
	abstract public function register_routes();

	public function rest_uri() {
		return trim( braintree()->rest_api->rest_uri() . ( ! empty( $this->namespace ) ? $this->namespace : '' ), '/' );
	}

	public function rest_url() {
		return braintree()->rest_api->rest_url() . ( ! empty( $this->namespace ) ? trailingslashit( $this->namespace ) : '' );
	}

	/**
	 * Return the deprecated rest uri.
	 */
	public function deprecated_rest_uri() {
		return 'braintree-gateway/v1';
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function admin_permission_check( $request ) {
		if ( ! current_user_can( 'administrator' ) ) {
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
	 * @param $request
	 *
	 * @return bool|WP_Error
	 * @since 3.2.8
	 */
	public function shop_manager_permission_check( $request ) {
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

	protected function get_error_messages() {
		return $this->get_messages( 'error' );
	}

	protected function get_messages( $types = 'all' ) {
		$notices = wc_get_notices();
		if ( $types !== 'all' ) {
			$types = (array) $types;
			foreach ( $notices as $type => $notice ) {
				if ( ! in_array( $type, $types ) ) {
					unset( $notices[ $type ] );
				}
			}
		}
		wc_set_notices( $notices );
		ob_start();
		$messages = wc_print_notices();

		return ob_get_clean();
	}
}
