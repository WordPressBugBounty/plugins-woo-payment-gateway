<?php
defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 * Controller class that is called when a local payment gateway is used.
 * This controller stores
 * the payer data for later use by webhooks so the payment can be completed.
 *
 * @since   3.0.0
 * @package Braintree/API
 * @author  User
 *
 */
class WC_Braintree_Controller_Local_Payment extends WC_Braintree_Controller_Frontend {

	protected $namespace = 'local-payment/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(), 'payment-data', array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_payment_data' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route( $this->rest_uri(), 'payment/complete', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'complete_payment' ),
			'permission_callback' => '__return_true'
		) );
	}

	/**
	 * Store the payer Id against the order so it can be used later on to process the payment.
	 *
	 * @param WP_REST_Request $request
	 */
	public function save_payment_data( $request ) {
		try {
			// get the order;
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
			if ( ! hash_equals( $order->get_order_key(), $request['order_key'] ) ) {
				throw new Exception( __( 'Invalid order key', 'woo-payment-gateway' ) );
			}
			$order->update_meta_data( Constants::PAYMENT_ID, $request->get_param( 'payment_id' ) );
			$order->save();

			return rest_ensure_response( array( 'redirect_url' => $order->get_checkout_order_received_url() ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'order-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function complete_payment( $request ) {
		try {
			$order = wc_get_order( absint( WC()->session->get( 'order_awaiting_payment' ) ) );
			/**
			 * @var WC_Braintree_Payment_Gateway $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$result  = $gateway->process_payment( $order->get_id() );
			if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
				return rest_ensure_response( $result );
			} else {
				return rest_ensure_response( array( 'result' => 'failure', 'messages' => $this->get_error_messages() ) );
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'payment-error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

}
