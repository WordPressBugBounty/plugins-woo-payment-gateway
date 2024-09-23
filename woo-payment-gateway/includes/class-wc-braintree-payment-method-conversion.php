<?php

defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 * Class that retreives Braintree payment methods periodically and updates the WC payment tokens table.
 *
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Payment_Method_Conversion {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'update_user_payment_tokens' ), 100 );
		add_filter( 'woocommerce_order_get_payment_method', array( __CLASS__, 'get_new_payment_method' ), 10, 2 );
		add_filter( 'woocommerce_subscription_get_payment_method', array( __CLASS__, 'get_new_payment_method' ), 10, 2 );
	}

	/**
	 * Hooked in to init action.
	 * Retrieves the user's payment methods stored in Braintree and stores them
	 * in the WC payment tokens table if it doesn't already exist.
	 */
	public static function update_user_payment_tokens( $user_id = '' ) {
		if ( is_user_logged_in() && did_action( 'woocommerce_loaded' ) ) {
			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;
			self::sync_payment_method_tokens( $user_id, wc_braintree_environment() );
		}
	}

	/**
	 *
	 * @param int    $user_id
	 * @param string $env
	 *
	 * @since 3.0.7
	 */
	public static function sync_payment_method_tokens( $user_id, $env ) {
		$customer_id = wc_braintree_get_customer_id( $user_id, $env );
		$next_update = get_user_meta( $user_id, Constants::TOKEN_CHECK, true );
		if ( $customer_id && ( $next_update < time() ) ) {
			$payment_gateways = WC()->payment_gateways()->payment_gateways();
			try {
				$gateway         = new \Braintree\Gateway( wc_braintree_connection_settings() );
				$customer        = $gateway->customer()->find( $customer_id );
				$payment_methods = $customer->paymentMethods;
				// remove WCS listeners if it's active.
				if ( wcs_braintree_active() ) {
					remove_action( 'woocommerce_payment_token_set_default', 'WCS_My_Account_Payment_Methods::display_default_payment_token_change_notice' );
				}
				foreach ( $payment_methods as $payment_method ) {
					$gateway_id = '';
					if ( $payment_method instanceof \Braintree\CreditCard ) {
						$gateway_id = Constants::BRAINTREE_CC;
					}
					if ( $payment_method instanceof \Braintree\PayPalAccount ) {
						$gateway_id = Constants::BRAINTREE_PAYPAL;
					}
					if ( $payment_method instanceof \Braintree\ApplePayCard ) {
						$gateway_id = Constants::BRAINTREE_APPLEPAY;
					}
					if ( $payment_method instanceof \Braintree\GooglePayCard ) {
						$gateway_id = Constants::BRAINTREE_GOOGLEPAY;
					}
					if ( $payment_method instanceof \Braintree\VenmoAccount ) {
						$gateway_id = Constants::BRAINTREE_VENMO;
					}
					/**
					 *
					 * @var WC_Braintree_Payment_Gateway $wc_gateway
					 */
					$wc_gateway = isset( $payment_gateways[ $gateway_id ] ) ? $payment_gateways[ $gateway_id ] : null;
					if ( $wc_gateway && ! $wc_gateway->token_exists( $payment_method->token, $user_id ) ) {
						$token = $wc_gateway->get_payment_token( $payment_method );
						$token->set_user_id( $user_id );
						$token->set_environment( $env );
						$token->save();
					}
				}
			} catch ( \Braintree\Exception $e ) {
				wc_braintree_log_error( sprintf( __( 'Error comparing payment methods in Braintree with WordPress. User ID: %1$s. Exception: %2$s', 'woo-payment-gateway' ), $user_id, get_class( $e ) ) );
			} catch ( Exception $e ) {
				wc_braintree_log_error( sprintf( __( 'Error comparing payment methods in Braintree with WordPress. User ID: %1$s. Exception: %2$s', 'woo-payment-gateway' ), $user_id, get_class( $e ) ) );
			}
			update_user_meta( $user_id, 'wc_braintree_token_check', self::get_next_update() );
		}
	}

	private static function get_next_update() {
		return apply_filters( 'wc_braintree_next_token_update', time() + ( MINUTE_IN_SECONDS * 60 * 24 * 14 ) );
	}

	/**
	 * Return the new payment method to prevent errors when orders reference deprecated gateway ID's.
	 * The order meta is updated if a deprecated gateway ID is used.
	 *
	 * @param string   $payment_method
	 * @param WC_Order $order
	 */
	public static function get_new_payment_method( $payment_method, $order ) {
		$new_method = '';
		switch ( $payment_method ) {
			case 'braintree_payment_gateway':
				/**
				 * This is the credit card gateway ID for PayPal Powered by Braintree
				 */
			case 'braintree_credit_card':
				$new_method = Constants::BRAINTREE_CC;
				break;
			case 'braintree_paypal_payments':
			case 'braintree_paypal_credit_payments':
				$new_method = Constants::BRAINTREE_PAYPAL;
				break;
			case 'braintree_googlepay_gateway':
				$new_method = Constants::BRAINTREE_GOOGLEPAY;
				break;
			case 'braintree_applepay_payments':
				$new_method = Constants::BRAINTREE_APPLEPAY;
				break;
		}
		if ( ! empty( $new_method ) ) {
			$payment_method = $new_method;
			// Don't update postmeta in case merchant switches back to old plugin.
			//update_post_meta( $order->get_id(), '_payment_method', $new_method );
		}

		return $payment_method;
	}

}

WC_Braintree_Payment_Method_Conversion::init();
