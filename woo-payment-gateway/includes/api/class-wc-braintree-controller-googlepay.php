<?php
defined( 'ABSPATH' ) || exit();

/**
 * Controller class for the Google Pay payment gateway.
 *
 * @since 3.0.0
 * @deprecated 3.2.4
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_GooglePay extends WC_Braintree_Rest_Controller {

	use WC_Braintree_Controller_Cart_Trait;

	protected $namespace = 'googlepay/';

	private $shipping_method_id;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'shipping-address',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Update the shipping address and shipping method selected in the Google Pay payment sheet.
	 * This method
	 * was created because we can't use the cart conttroller's api since Google sends shipping address and
	 * shipping method updates in one call.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_shipping_data( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
		$customer = WC()->customer;
		/**
		 *
		 * @var WC_Braintree_GooglePay_Payment_Gateway $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()['braintree_googlepay'];

		// set the customer's location so shipping can be calculated.
		$address          = array(
			'country'  => $request->get_param( 'address' )['countryCode'],
			'state'    => $request->get_param( 'address' )['administrativeArea'],
			'city'     => $request->get_param( 'address' )['locality'],
			'postcode' => $request->get_param( 'address' )['postalCode'],
		);
		$shipping_options = $request->get_param( 'shippingOptions' );
		try {
			// update the customer's location
			wc_braintree_update_customer_location( $address );

			$chosen_shipping_methods = null;

			// set the chosen package if one has been selected.
			if ( $shipping_options['id'] !== 'shipping_option_unselected' ) {

				$this->shipping_method_id = $gateway->shipping_method_id = $shipping_options['id'];

				preg_match( '/([\w]+)\:(.+)/', $this->shipping_method_id, $shipping_methods );
				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
				if ( is_array( $shipping_methods ) ) {
					$chosen_shipping_methods[ $shipping_methods[1] ] = $shipping_methods[2];
					/**
					 * Hack that ensures the WCS shipping method stays in sync with the non subscription shipping method.
					 * This is done because Google only supports the display of a single package for shipping.
					 */
					if ( $gateway->cart_contains_trial_period_subscription() ) {
						foreach ( $chosen_shipping_methods as $i => $method_id ) {
							if ( strlen( $i ) > 1 ) {
								$chosen_shipping_methods[ $i ] = $shipping_methods[2];
							}
						}
					}
				}

				WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
			}

			$this->add_ready_to_calc_shipping();

			// calculate all cart totals, including shipping
			WC()->cart->calculate_totals();
			// now that totals have been calculated, return the items to the client.

			$shipping_data = $gateway->get_shipping_options();

			if ( empty( $shipping_data['shippingOptions'] ) ) {
				throw new Exception( __( 'No shipping methods available for your shipping address.', 'woo-payment-gateway' ) );
			}

			$response = array(
				'data' => array(
					'chosen_shipping_methods' => $chosen_shipping_methods,
					'requestUpdate'           => array(
						'newTransactionInfo'          => array(
							'currencyCode'     => get_woocommerce_currency(),
							'totalPriceStatus' => 'FINAL',
							'totalPrice'       => strval( WC()->cart->total ),
							'totalPriceLabel'  => __( 'Total', 'woo-payment-gateway' ),
							'displayItems'     => $gateway->get_display_items(),
						),
						'newShippingOptionParameters' => $shipping_data,
					),
				),
			);

			return rest_ensure_response( $response );
		} catch ( Exception $e ) {
			return new WP_Error(
				'shipping-error',
				$e->getMessage(),
				array(
					'status' => 200,
					'data'   => array(
						'error' => array(
							'reason'  => $e->getMessage(),
							'message' => $e->getMessage(),
							'intent'  => 'SHIPPING_ADDRESS',
						),
					),
				)
			);
		}
	}
}
