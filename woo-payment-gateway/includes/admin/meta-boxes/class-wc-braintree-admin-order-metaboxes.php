<?php

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since   3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_Order_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 40, 2 );
	}

	/**
	 *
	 * @param string  $post_type
	 * @param WP_Post $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		// only add meta box if shop_order and braintree gateway was used.
		if ( $post_type === 'shop_order' || $post instanceof \WC_Order ) {
			add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'pay_order_section' ) );

			$order          = $post instanceof \WC_Order ? $post : wc_get_order( $post->ID );
			$payment_method = $order->get_payment_method();
			if ( $payment_method ) {
				$gateways = WC()->payment_gateways()->payment_gateways();
				$gateway  = isset( $gateways[ $payment_method ] ) ? $gateways[ $payment_method ] : null;
				if ( ! is_null( $gateway ) && $gateway instanceof WC_Braintree_Payment_Gateway ) {
					add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'transaction_data_view' ) );
				}
			}
		}
	}

	/**
	 *
	 * @param WP_Post $post
	 */
	public static function capture_charge_view( $post ) {
		$order  = wc_get_order( $post->ID );
		$status = $order->get_meta( Constants::TRANSACTION_STATUS );
		include 'views/html-order-actions.php';
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function pay_order_section( $order ) {
		if ( $order->get_type() === 'shop_order' && ( $order->has_status( 'pending' ) || $order->has_status( 'failed' ) ) ) {
			include 'views/html-order-pay.php';
			$payment_methods = array_values( wc_braintree_get_payment_tokens( $order->get_user_id(), wc_braintree_environment() ) );
			wp_enqueue_script( 'wc-braintree-admin-dropin', 'https://js.braintreegateway.com/web/dropin/1.18.0/js/dropin.min.js', array(), braintree()->version, true );
			wp_localize_script(
				'wc-braintree-admin-dropin',
				'wc_braintree_order_pay_params',
				array(
					'client_token'    => braintree()->generate_client_token(),
					'payment_methods' => array_map(
						function ( $payment_method ) {
							return $payment_method->to_json();
						},
						$payment_methods
					),
					'order_status'    => $order->get_status(),
					'transaction_id'  => $order->get_transaction_id(),
				)
			);
			wp_enqueue_script( 'wc-braintree-admin-modals', braintree()->assets_path() . 'js/admin/modals.js', array(
				'wc-backbone-modal',
				'jquery-blockui'
			), braintree()->version, true );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function transaction_data_view( $order ) {
		if ( 'shop_order' === $order->get_type() && ( $transaction_id = $order->get_transaction_id() ) ) {
			include 'views/html-order-braintree-data.php';
		}
	}

}

WC_Braintree_Admin_Order_Metaboxes::init();
