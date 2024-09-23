<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_User_Edit {

	public static function init() {
		add_action( 'edit_user_profile', array( __CLASS__, 'output' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'output' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save' ) );
	}

	public static function output( $user ) {
		global $wpdb;
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$environments      = array( 'sandbox', 'production' );
		$sandbox_tokens    = array();
		$production_tokens = array();
		foreach ( $environments as $environment ) {
			${"{$environment}_tokens"} = self::get_payment_tokens( $user->ID, $environment );
		}
		include braintree()->plugin_path() . 'includes/admin/views/user-edit.php';
	}

	public static function save( $user_id ) {
		// only users with "manage_woocommerce" can update the user's Braintree customer ID's.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $wpdb;
		$old_ids = array(
			'production' => wc_braintree_get_customer_id( $user_id, 'production' ),
			'sandbox'    => wc_braintree_get_customer_id( $user_id, 'sandbox' ),
		);
		if ( isset( $_POST['wc_braintree_production_vault_id'] ) ) {
			wc_braintree_delete_customer( $user_id, 'production', true );
			wc_braintree_save_customer( $user_id, wc_clean( $_POST['wc_braintree_production_vault_id'] ), 'production' );
		}
		if ( isset( $_POST['wc_braintree_sandbox_vault_id'] ) ) {
			wc_braintree_delete_customer( $user_id, 'sandbox', true );
			wc_braintree_save_customer( $user_id, wc_clean( $_POST['wc_braintree_sandbox_vault_id'] ), 'sandbox' );
		}

		/**
		 * If the user's vault ID was changed, need to remove old payment tokens and import new ones.
		 */
		remove_action( 'woocommerce_payment_token_deleted', 'wc_braintree_woocommerce_payment_token_deleted' );
		WC()->payment_gateways();
		foreach ( $old_ids as $environment => $vault_id ) {
			if ( $vault_id !== wc_braintree_get_customer_id( $user_id, $environment ) ) {
				$results = self::get_payment_tokens( $user_id, $environment );
				if ( $results ) {
					foreach ( $results as $token ) {
						WC_Payment_Tokens::delete( $token->get_id() );
					}
				}
				// add new payment tokens.
				try {
					if ( ( $gateway = braintree()->gateway( $environment ) ) !== null ) {
						$customer = $gateway->customer()->find( wc_braintree_get_customer_id( $user_id, $environment ) );
						foreach ( $customer->paymentMethods as $payment_method ) {
							$gateway_id = '';
							if ( $payment_method instanceof \Braintree\CreditCard ) {
								$gateway_id = 'braintree_cc';
							}
							if ( $payment_method instanceof \Braintree\PayPalAccount ) {
								$gateway_id = 'braintree_paypal';
							}
							if ( $payment_method instanceof \Braintree\GooglePayCard ) {
								$gateway_id = 'braintree_googlepay';
							}
							if ( $payment_method instanceof \Braintree\ApplePayCard ) {
								$gateway_id = 'braintree_applepay';
							}
							if ( $payment_method instanceof \Braintree\VenmoAccount ) {
								$gateway_id = 'braintree_venmo';
							}
							/**
							 *
							 * @var WC_Braintree_Payment_Gateway $wc_gateway
							 */
							$wc_gateway = WC()->payment_gateways()->payment_gateways()[ $gateway_id ];
							$token      = $wc_gateway->get_payment_token( $payment_method );
							$token->set_user_id( $user_id );
							$token->save();
						}
					}
				} catch ( \Braintree\Exception $e ) {
					wc_braintree_log_error( sprintf( __( 'Error saving customer\'s payment methods on User Profile page. Reason: %1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
				} catch ( Exception $e ) {
					wc_braintree_log_error( sprintf( __( 'Error saving customer\'s payment methods on User Profile page. Reason: %1$s', 'woo-payment-gateway' ), $e->getMessage() ) );
				}
			}
		}
	}

	/**
	 *
	 * @param int $user_id
	 * @param string $environment
	 */
	public static function get_payment_tokens( $user_id, $environment ) {
		return wc_braintree_get_payment_tokens( $user_id, $environment );
	}
}

WC_Braintree_Admin_User_Edit::init();
