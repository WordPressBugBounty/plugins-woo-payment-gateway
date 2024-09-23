<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.10
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_PayPal extends WC_Braintree_Controller_Frontend {

	protected $namespace = 'paypal/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'shipping',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_shipping_html' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_shipping_html( $request ) {
		$keys = array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );

		foreach ( $keys as $key ) {
			$address[ $key ] = $request->get_param( $key );
		}
		wc_braintree_update_customer_location( $address );

		WC()->cart->calculate_totals();

		$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];

		$html = wc_braintree_get_template_html(
			'paypal-shipping-methods.php',
			array(
				'gateway'                 => $gateway,
				'packages'                => $gateway->get_shipping_packages(),
				'chosen_shipping_methods' => WC()->session->get(
					'chosen_shipping_methods',
					array()
				),
			)
		);

		return rest_ensure_response( array( 'html' => $html ) );
	}
}
