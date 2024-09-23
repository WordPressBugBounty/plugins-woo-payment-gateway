<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Form_Handler {

	public static function init() {
		add_action( 'wp', array( __CLASS__, 'cancel_subscription_action' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'change_payment_method' ) );
	}

	public static function cancel_subscription_action() {
		global $wp;
		if ( isset( $wp->query_vars['cancel-subscription'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$id           = $wp->query_vars['cancel-subscription'];
			$subscription = wcs_braintree_get_subscription( $id );
			$gateways     = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways[ $subscription->get_payment_method() ] ) ) {
				$gateway = $gateways[ $subscription->get_payment_method() ];
				$result  = $gateway->cancel_braintree_subscription( $subscription );
				if ( is_wp_error( $result ) ) {
					wc_add_notice( $result->get_error_message(), 'error' );
					wp_safe_redirect( $subscription->get_view_subscription_url() );
					exit();
				} else {
					wc_add_notice( sprintf( __( 'Subscription %1$s has been cancelled.', 'woo-payment-gateway' ), $subscription->get_id() ), 'success' );
					wp_safe_redirect( wc_get_account_endpoint_url( braintree()->subscription_settings->get_option( 'subscriptions_endpoint' ) ) );
					exit();
				}
			}
		}
	}

	public static function change_payment_method() {
		if ( isset( $_POST['wc_braintree_change_method_nonce'] ) && wp_verify_nonce( $_POST['wc_braintree_change_method_nonce'], 'change-payment-method' ) ) {
			$subscription   = wc_get_order( absint( wc_clean( $_POST['wc_braintree_subscription'] ) ) );
			$payment_method = $_POST['payment_method'];
			/**
			 *
			 * @var WC_Braintree_Payment_Gateway $gateway
			 */
			$gateway = WC()->payment_gateways()->get_available_payment_gateways()[ $payment_method ];
			$result  = $gateway->change_subscription_payment_method( $subscription );
			if ( wc_notice_count( 'error' ) > 0 ) {
				return;
			} else {
				// @since 3.1.0
				do_action( 'wcs_braintree_change_payment_method_success', $result, $subscription, $gateway );

				wc_add_notice( __( 'Your payment method has been updated.', 'woo-payment-gateway' ), 'success' );
				wp_safe_redirect( wc_get_endpoint_url( 'view-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ) );
				exit();
			}
		}
	}

}

WC_Braintree_Form_Handler::init();
