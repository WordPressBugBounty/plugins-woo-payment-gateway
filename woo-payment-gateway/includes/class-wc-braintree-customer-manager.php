<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class that manages customer create and update within the Braintree vault.
 *
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Customer_Manager {

	public function __construct() {
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'checkout_update_customer' ), 10, 2 );
		add_filter( 'wc_braintree_get_customer_id', array( $this, 'handle_user_migration' ), 10, 3 );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 * @param array       $data
	 */
	public function checkout_update_customer( $customer, $data ) {
		if ( $this->should_create_customer( $customer->get_id() ) ) {
			$result = $this->create_customer( $customer );
			if ( is_wp_error( $result ) ) {
				wc_add_notice( sprintf( __( 'Error creating customer in Braintree. Reason: %s', 'woo-payment-gateway' ), $result->get_error_message() ), 'error' );
			}
		} elseif ( $this->should_update_customer( $customer ) ) {
			$result = $this->update_customer( $customer );
			if ( is_wp_error( $result ) ) {
				wc_add_notice( sprintf( __( 'Error updating customer in Braintree. Reason: %s', 'woo-payment-gateway' ), $result->get_error_message() ), 'error' );
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 *
	 * @since 3.0.4
	 */
	public function create_customer( $customer ) {
		if ( braintree()->gateway() ) {
			try {
				$args     = apply_filters( 'wc_braintree_create_customer_args', $this->get_customer_args( $customer ) );
				$response = braintree()->gateway()->customer()->create( $args );
				if ( $response->success ) {
					wc_braintree_save_customer( $customer->get_id(), $response->customer->id );

					return $response->customer->id;
				} else {
					return new WP_Error( 'customer-error', wc_braintree_errors_from_object( $response ) );
				}
			} catch ( \Braintree\Exception $e ) {
				wc_braintree_log_error( sprintf( 'Error creating Braintree customer. User ID: %s. Exception: %s', $customer->get_id(), get_class( $e ) ) );

				return new WP_Error( 'customer-error', sprintf( '%1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) );
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 *
	 * @since 3.0.4
	 */
	public function update_customer( $customer ) {
		if ( braintree()->gateway() ) {
			try {
				$vault_id = wc_braintree_get_customer_id( $customer->get_id() );
				$response = braintree()->gateway()->customer()->update( $vault_id, apply_filters( 'wc_braintree_update_customer_args', $this->get_customer_args( $customer ) ) );
				if ( ! $response->success ) {
					wc_braintree_log_error( sprintf( __( 'Error updating customer %1$s in gateway. Reason: %2$s', 'woo-payment-gateway' ), $customer->get_id(), wc_braintree_errors_from_object( $response ) ) );
					throw new Exception( wc_braintree_errors_from_object( $response ) );
				}

				return true;
			} catch ( \Braintree\Exception $e ) {
				wc_braintree_log_error( sprintf( 'Error updating Braintree customer. User ID: %s. Exception: %s', $customer->get_id(), get_class( $e ) ) );

				return new WP_Error( 'customer-error', wc_braintree_errors_from_object( $e ) );
			} catch ( \Exception $e ) {
				return new WP_Error( 'customer-error', $e->getMessage() );
			}
		}
	}

	/**
	 * Returns true if a vault ID should be created in Braintree for the customer.
	 *
	 * @param int $user_id
	 */
	private function should_create_customer( $user_id ) {
		$vault_id = wc_braintree_get_customer_id( $user_id );

		return empty( $vault_id ) && $user_id > 0;
	}

	/**
	 * Should the customer be updated in Braintree?
	 *
	 * @param WC_Customer $customer
	 */
	private function should_update_customer( $customer ) {
		$vault_id = wc_braintree_get_customer_id( $customer->get_id() );
		if ( $vault_id ) {
			$changes = $customer->get_changes();
			if ( ! empty( $changes['billing'] ) && array_intersect_key( $changes['billing'], array_flip( $this->get_customer_keys() ) ) ) {
				return true;
			}
		}

		return false;
	}

	private function get_customer_keys() {
		return array( 'first_name', 'last_name', 'email' );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	private function get_customer_args( $customer ) {
		return array(
			'firstName' => $customer->get_first_name(),
			'lastName'  => $customer->get_last_name(),
			'company'   => $customer->get_billing_company(),
			'email'     => $customer->get_email(),
			'phone'     => $customer->get_billing_phone(),
		);
	}

	/**
	 * Migrate the customer ID if one exists from another plugin.
	 *
	 * @param string $customer_id
	 * @param int    $user_id
	 * @param string $env
	 */
	public function handle_user_migration( $customer_id, $user_id, $env ) {
		if ( ! $customer_id && $env === 'production' ) {
			$user = get_userdata( $user_id );
			if ( $user && $user->has_prop( 'wc_braintree_customer_id' ) ) {
				$customer_id = $user->get( 'wc_braintree_customer_id' );
				if ( $customer_id ) {
					wc_braintree_save_customer( $user_id, $customer_id, $env );
				}
			}
		}

		return $customer_id;
	}

}
