<?php

defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since   3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Order_Actions extends WC_Braintree_Controller_Frontend {

	protected $namespace = 'order~action/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'capture',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'capture' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'int',
						'validate_callback' => array( $this, 'validate_order_id' ),
					),
					'amount'   => array(
						'required' => true,
						'type'     => 'float',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'void',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'void' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'process-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'customer-payment-methods',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'customer_payment_methods' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'customer_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'transaction',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_transaction' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function order_actions_permission_check( $request ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
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
	 * Return true if the order_id is a valid post.
	 *
	 * @param int $order_id
	 */
	public function validate_order_id( $order_id ) {
		if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
			$ids = wc_get_orders( array(
				'type'   => 'shop_order',
				'limit'  => 1,
				'id'     => $order_id,
				'return' => 'ids'
			) );

			return ! empty( $ids );
		} else {
			return null !== get_post( $order_id );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function capture( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( absint( $order_id ) );
		$amount   = $request->get_param( 'amount' );
		if ( ! is_numeric( $amount ) ) {
			return new WP_Error(
				'invalid_data',
				__( 'Invalid amount entered.', 'woo-payment-gateway' ),
				array(
					'success' => false,
					'status'  => 200,
				)
			);
		}
		$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		$gateway->connect( wc_braintree_get_order_environment( $order ) );
		/**
		 * @var WC_Braintree_Payment_Gateway $gateway
		 */
		$result = $gateway->capture_charge( $amount, $order );
		$gateway->connect();
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 200 ) );
		}

		$response = rest_ensure_response( $result );

		return $response;
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function void( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( absint( $order_id ) );
		$gateway  = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		$gateway->connect( wc_braintree_get_order_environment( $order ) );
		$result = $gateway->void_charge( $order );
		$gateway->connect();
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 200 ) );
		}

		$response = rest_ensure_response( $result );

		return $response;
	}

	/**
	 * Process a payment as an admin.
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_payment( $request ) {
		$this->frontend_includes();

		$order_id     = absint( $request->get_param( 'order_id' ) );
		$payment_type = $request->get_param( 'payment_type' );
		$order        = wc_get_order( $order_id );
		$token        = null;
		$use_token    = $payment_type === 'token';
		try {
			// perform some validations
			if ( $order->get_total() == 0 ) {
				if ( ! wcs_braintree_active() ) {
					throw new Exception( __( 'Order total must be greater than zero.', 'woo-payment-gateway' ) );
				} else {
					if ( ! wcs_order_contains_subscription( $order ) ) {
						throw new Exception( __( 'Order total must be greater than zero.', 'woo-payment-gateway' ) );
					}
				}
			}

			if ( $order->get_transaction_id() && $request->get_param( 'allow_order' ) != 'yes' ) {
				throw new Exception( sprintf( __( 'This order has already been processed. Transaction ID: %1$s. Payment method: %2$s', 'woo-payment-gateway' ), $order->get_transaction_id(),
					$order->get_payment_method_title() ) );
			}

			// update the order's customer ID if it has changed.
			if ( $order->get_customer_id() != $request->get_param( 'customer_id' ) ) {
				$order->set_customer_id( $request->get_param( 'customer_id' ) );
			}

			if ( ! $use_token ) {
				// only credit card payments are allowed for one off payments as an admin.
				$payment_method = 'braintree_cc';
			} else {
				$token_id = intval( $request->get_param( 'payment_token_id' ) );
				$token    = WC_Payment_Tokens::get( $token_id );
				if ( $token->get_user_id() !== $order->get_customer_id() ) {
					throw new Exception( __( 'Order customer Id and payment method customer Id do not match.', 'woo-payment-gateway' ) );
				}
				$payment_method = $token->get_gateway_id();
			}

			/**
			 *
			 * @var WC_Braintree_Payment_Gateway $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
			// temporarily set the charge type of the gateway to whatever the admin has selected.
			$gateway->settings['charge_type'] = $request->get_param( 'wc_braintree_charge_type' );
			// set the payment gateway to the order.
			$order->set_payment_method( $gateway->id );
			$order->save();
			if ( ! $use_token ) {
				if ( $this->order_is_subscription( $order ) || $request->get_param( 'save_card' ) == 'yes' ) {
					// save payment method then proceed.
					$result = $this->save_payment_method( $order, $gateway, $request );
					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				} else {
					$gateway->set_payment_method_nonce( $request->get_param( 'payment_nonce' ) );
				}
			} else {
				$gateway->set_payment_method_token( $token->get_token() );
			}
			// Process transaction without device data since admin is processing this payment
			add_action(
				'wc_braintree_order_transaction_args',
				function ( $args ) {
					$args['options']['skipAdvancedFraudChecking'] = true;
					// always set 3dsecure to false for admin order since the customer can't authenticate.
					$args['options']['threeDSecure'] = array( 'required' => false );
					$args['transactionSource']       = 'moto';

					return $args;
				}
			);
			$result = $gateway->process_payment( $order_id );
			if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
				return rest_ensure_response( array( 'success' => true ) );
			} else {
				$order->update_status( 'pending' );

				return new WP_Error(
					'order-error',
					$this->get_error_messages(),
					array(
						'status'  => 200,
						'success' => false,
					)
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'order-error', '<div class="woocommerce-error">' . $e->getMessage() . '</div>', array( 'status' => 200 ) );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_payment_methods( $request ) {
		$customer_id = $request->get_param( 'customer_id' );
		$tokens      = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof WC_Braintree_Payment_Gateway ) {
				$tokens = array_merge( $tokens, WC_Payment_Tokens::get_customer_tokens( $customer_id, $gateway->id ) );
			}
		}
		$tokens = array_values( WC_Payment_Tokens::get_customer_tokens( $customer_id, 'braintree_cc' ) );

		return rest_ensure_response(
			array(
				'payment_methods' => array_map(
					function ( $payment_method ) {
						return $payment_method->to_json();
					},
					$tokens
				),
			)
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	private function order_is_subscription( $order ) {
		if ( wcs_braintree_active() && wcs_order_contains_subscription( $order ) ) {
			return true;
		}
		if ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Save the payment method in Braintree.
	 * If the user does not have a Braintree vault ID, then
	 * a new customer is created in Braintree and associated with the user.
	 *
	 * @param WC_Order                     $order
	 * @param WC_Braintree_Payment_Gateway $gateway
	 * @param WP_REST_Request              $request
	 */
	private function save_payment_method( $order, $gateway, $request ) {
		if ( ( $braintree = braintree()->gateway() ) != null ) {
			try {
				$user_id     = $order->get_user_id();
				$customer_id = wc_braintree_get_customer_id( $order->get_user_id() );
				if ( ! $customer_id ) {
					// create the customer in Braintree vault;
					$response = $braintree->customer()->create(
						array(
							'firstName' => $order->get_billing_first_name(),
							'lastName'  => $order->get_billing_last_name(),
							'company'   => $order->get_billing_company(),
							'email'     => $order->get_billing_email(),
							'phone'     => $order->get_billing_phone(),
						)
					);
					if ( $response->success ) {
						wc_braintree_save_customer( $user_id, $response->customer->id );
					} else {
						throw new Exception( wc_braintree_errors_from_object( $response ) );
					}
				}
				$args     = array(
					'customerId'         => wc_braintree_get_customer_id( $order->get_user_id() ),
					'paymentMethodNonce' => $request->get_param( 'payment_nonce' ),
					'cardholderName'     => sprintf( '%s %s', $order->get_billing_first_name(), $order->get_billing_last_name() ),
					'billingAddress'     => array( 'streetAddress' => $order->get_billing_address_1() ),
				);
				$response = $braintree->paymentMethod()->create( $args );
				if ( $response->success ) {
					$token = $gateway->get_payment_token( $response->paymentMethod );
					$token->set_user_id( $order->get_user_id() );
					$token->save();
					WC_Payment_Tokens::set_users_default( $order->get_user_id(), $token->get_id() );
					$gateway->set_payment_method_token( $token->get_token() );

					// if this a renewal order, then save the payment method token
					if ( wcs_braintree_active() && wcs_order_contains_renewal( $order ) ) {
						$subscription_ids = WCS_Related_Order_Store::instance()->get_related_subscription_ids( $order, 'renewal' );
						if ( $subscription_ids ) {
							$subscription_id = current( $subscription_ids );
							update_post_meta( $subscription_id, '_payment_method_token', $token->get_token() );
						}
					}

					return true;
				} else {
					throw new Exception( wc_braintree_errors_from_object( $response ) );
				}
			} catch ( Exception $e ) {
				return new WP_Error( 'vault-error', $e->getMessage() );
			}
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_transaction( $request ) {
		$order_id       = $request->get_param( 'order_id' );
		$order          = wc_get_order( absint( $order_id ) );
		$transaction_id = $order->get_transaction_id();
		/**
		 *
		 * @var WC_Braintree_Payment_Gateway $gateway
		 */
		$gateway     = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		$transaction = $gateway->fetch_transaction( $transaction_id, wc_braintree_get_order_environment( $order ) );
		if ( is_wp_error( $transaction ) ) {
			return new WP_Error( 'order-error', $transaction->get_error_message(), array( 'status' => 200 ) );
		}
		// update status for order
		$order->update_meta_data( Constants::TRANSACTION_STATUS, $transaction->status );
		$order->save();
		$token_id     = $order->get_meta( '_payment_method_token' );
		$payment_type = __( 'one time use', 'woo-payment-gateway' );
		if ( $token_id ) {
			$token        = $gateway->get_token( $token_id, $order->get_user_id() );
			$payment_type = __( 'saved method', 'woo-payment-gateway' );
		}
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/meta-boxes/views/html-order-transaction-view.php';
		$html = ob_get_clean();

		return rest_ensure_response(
			array(
				'data' => array(
					'order_id'     => $order->get_id(),
					'order_number' => $order->get_order_number(),
					'order_total'  => $order->get_total(),
					'transaction'  => $transaction->jsonSerialize(),
					'html'         => $html,
				),
			)
		);
	}

}
