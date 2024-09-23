<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Cart extends WC_Braintree_Controller_Frontend {

	use WC_Braintree_Controller_Cart_Trait;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'cart',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product_id'     => array(
						'type'     => 'number',
						'required' => true,
					),
					'payment_method' => array( 'required' => true ),
					'qty'            => array( 'required' => true ),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'cart/shipping-method',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_method' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'shipping_method' => array( 'required' => true ),
					'payment_method'  => array( 'required' => true ),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'cart/shipping-address',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_address' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'payment_method' => array( 'required' => true ),
					'address'        => array( 'validate_callback' => array( $this, 'validate_address' ) )
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'cart/shipping',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'address'         => array( 'validate_callback' => array( $this, 'validate_address' ) ),
					'payment_method'  => array( 'required' => true ),
					'shipping_method' => array( 'required' => true ),
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function add_to_cart( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Braintree_Payment_Gateway $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];

		list( $product_id, $qty, $variation_id, $variation ) = $this->get_add_to_cart_args( $request );

		// Remove this item from cart before adding it again to keep quantities on product page accurate.
		WC()->cart->remove_cart_item( WC()->cart->generate_cart_id( $product_id, $variation_id, $variation ) );

		if ( WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation ) == false ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'messages' => $this->get_error_messages(),
				)
			);
		} else {
			return rest_ensure_response( array(
					'success' => true,
					'data'    => $gateway->add_to_cart_response( array(
						'total'          => WC()->cart->total,
						'needs_shipping' => WC()->cart->needs_shipping()
					) ),
				)
			);
		}
	}

	/**
	 * Add the selected shipping method to the WC session
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_shipping_method( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Braintree_Payment_Gateway $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
		// using the chosen shipping methods, add it to the session so it can be used during order creation.
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		try {
			$shipping_method = $this->get_shipping_method_from_request( $request );
			if ( $shipping_method ) {
				$chosen_shipping_methods[ $shipping_method['index'] ] = $shipping_method['id'];
			}
			/**
			 * Hack that keeps WCS shipping methods in sync with non-shippable products
			 */
			if ( $gateway->cart_contains_trial_period_subscription() ) {
				foreach ( $chosen_shipping_methods as $n => $method ) {
					if ( strlen( $n ) > 1 && ( substr( $n, - 1 ) == $shipping_method['index'] ) ) {
						$chosen_shipping_methods[ $n ] = $shipping_method['id'];
					}
				}
			}

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

			$this->add_ready_to_calc_shipping();

			WC()->cart->calculate_totals();

			return rest_ensure_response(
				apply_filters(
					'wc_braintree_update_shipping_method_response',
					array(
						'data' => $gateway->update_shipping_method_response( array(
							'chosen_shipping_methods' => $chosen_shipping_methods,
							'total'                   => WC()->cart->total
						) ),
					),
					$payment_method,
					$request
				)
			);
		} catch ( Exception $e ) {
			return apply_filters( 'wc_braintree_update_shipping_method_error', new WP_Error( 'shipping-address', $e->getMessage(), array( 'status' => 200 ) ), $payment_method, $request );
		}
	}

	/**
	 * Update the customer's shipping address.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_shipping_address( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Braintree_Payment_Gateway $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
		// update the shipping address so the list of shipping methods available can be sent back.
		$address = $request->get_param( 'address' );

		try {
			wc_braintree_update_customer_location( $address );

			$this->add_ready_to_calc_shipping();

			// re-calculate cart totals which includes shipping
			WC()->cart->calculate_totals();
			$packages = $gateway->get_shipping_packages();
			// if the rates are empty then this shipping address is not supported.
			if ( ! $this->has_shipping_methods( $packages ) ) {
				throw new Exception( __( 'No shipping methods available for address.', 'woo-payment-gateway' ) );
			}

			return rest_ensure_response(
				apply_filters(
					'wc_braintree_update_address_response',
					array(
						'data' => $gateway->update_shipping_address_response(
							array(
								'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods', array() ),
								'address'                 => $address,
								'total'                   => WC()->cart->total
							)
						),
					),
					$payment_method,
					$request
				)
			);
		} catch ( Exception $e ) {
			return apply_filters( 'wc_braintree_update_address_error', new WP_Error( 'shipping-address', $e->getMessage(), array( 'status' => 200 ) ), $payment_method, $request );
		}
	}

	/**
	 * Update the shipping address and shipping method.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 3.1.11
	 */
	public function update_shipping( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
		/**
		 *
		 * @var WC_Braintree_Payment_Gateway $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request->get_param( 'payment_method' ) ];

		try {
			// update the customer's address
			wc_braintree_update_customer_location( $request->get_param( 'address' ) );

			if ( ( $shipping_method = $this->get_shipping_method_from_request( $request ) ) ) {

				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

				$chosen_shipping_methods[ $shipping_method['index'] ] = $shipping_method['id'];

				// save the chosen shipping methods
				WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
			}

			$this->add_ready_to_calc_shipping();

			// calculate totals
			WC()->cart->calculate_totals();

			$packages = $payment_method->get_shipping_packages();

			if ( empty( $packages ) ) {
				throw new Exception( __( 'No shipping methods for provided address', 'woo-payment-gateway' ) );
			}

			return rest_ensure_response(
				apply_filters(
					'wc_braintree_update_shipping_response',
					array(
						'data' => $payment_method->update_shipping_response( array(
							'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods', array() ),
							'total'                   => WC()->cart->total
						), $request ),
					)
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'shipping-error', $e->getMessage(), $payment_method->update_shipping_error( $e, array( 'status' => 200 ) ) );
		}
	}

	/**
	 *
	 * @param array $address
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function validate_address( $address, $request ) {
		/**
		 * @var WC_Braintree_Payment_Gateway $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		if ( isset( $address['state'], $address['country'] ) ) {
			$address['state']   = $payment_method->filter_address_state( $address['state'], $address['country'] );
			$request['address'] = $address;
		}
		$result = true;
		if ( method_exists( $payment_method, 'validate_wallet_address' ) ) {
			$result = $payment_method->validate_wallet_address( $address );
		}

		return apply_filters( 'wc_braintree_cart_controller_validate_address', $result, $address, $request );
	}

	/**
	 * Return true if the provided packages have shipping methods.
	 *
	 * @param array $packages
	 *
	 * @return bool
	 */
	private function has_shipping_methods( $packages ) {
		foreach ( $packages as $i => $package ) {
			if ( ! empty( $package['rates'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param WP_Rest_Request $request
	 *
	 * @return array
	 * @throws Exception
	 * @since 3.2.4
	 */
	private function get_shipping_method_from_request( $request ) {
		if ( ( $method = $request->get_param( 'shipping_method' ) ) ) {
			if ( ! preg_match( '/^(?P<index>[\w]+)\:(?P<id>.+)$/', $method, $shipping_method ) ) {
				throw new Exception( __( 'Invalid shipping method format. Expected: index:id', 'woo-payment-gateway' ) );
			}

			return $shipping_method;
		}

		return false;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @since 3.2.17
	 * return array
	 */
	private function get_add_to_cart_args( $request ) {
		$args = array(
			'product_id'   => $request->get_param( 'product_id' ),
			'qty'          => $request->get_param( 'qty' ),
			'variation_id' => $request->get_param( 'variation_id' ) == null ? 0 : $request->get_param( 'variation_id' ),
			'variation'    => $request->get_param( 'variation' )
		);
		if ( ! $args['variation'] ) {
			$args['variation'] = array();
		}

		return array_values( $args );
	}
}
