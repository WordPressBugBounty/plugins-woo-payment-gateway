<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Advanced_Settings_API' ) ) {
	return;
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Merchant_Account_Settings extends WC_Braintree_Advanced_Settings_API {

	public function __construct() {
		$this->id                         = 'braintree_merchant_account';
		$this->tab_title                  = __( 'Merchant Accounts', 'woo-payment-gateway' );
		$this->braintree_documentation_id = 'merchant-accounts';
		parent::__construct();

		// set to current environment so settings page loads with correct environment selected.
		$this->settings['merchant_environment'] = wc_braintree_environment();
	}

	public function enqueue_admin_scripts() {
		wp_enqueue_script(
			'wc-braintree-merchant-settings',
			braintree()->assets_path() . 'js/admin/merchant-settings.js',
			array(
				'wc-braintree-admin-settings',
				'wc-enhanced-select',
				'underscore',
				'backbone',
			),
			braintree()->version,
			true
		);
	}

	public function get_localized_params() {
		return array_merge(
			$this->settings,
			array(
				'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				'routes'   => array( 'merchant_account' => braintree()->rest_api->merchant_account->rest_url() . 'merchant-accounts' ),
			)
		);
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_braintree_merchant_account_form_fields',
			array(
				'merchant_accounts'            => array(
					'type'        => 'title',
					'title'       => __( 'Merchant Accounts', 'woo-payment-gateway' ),
					'description' => __(
						'Merchant Accounts determine which currencies you can accept in your Braintree account.
						The merchant account ID is a unique identifier for a specific merchant account in your gateway, and is used to specify which merchant account to use when creating a transaction.',
						'woo-payment-gateway'
					),
				),
				'merchant_environment'         => array(
					'title'   => __( 'Environment', 'woo-payment-gateway' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => array(
						'production' => __( 'Production', 'woo-payment-gateway' ),
						'sandbox'    => __( 'Sandbox', 'woo-payment-gateway' ),
					),
					'default' => 'production',
				),
				'production_merchant_accounts' => array(
					'title'             => __( 'Production Merchant Accounts', 'woo-payment-gateway' ),
					'type'              => 'merchant_account',
					'default'           => array(),
					'environment'       => 'production',
					'button_text'       => __( 'Add Production Account', 'woo-payment-gateway' ),
					'environment_text'  => __( 'Production', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'data-show-if' => array( 'merchant_environment' => 'production' ) ),
				),
				'sandbox_merchant_accounts'    => array(
					'title'             => __( 'Sandbox Merchant Accounts', 'woo-payment-gateway' ),
					'type'              => 'merchant_account',
					'default'           => array(),
					'environment'       => 'sandbox',
					'button_text'       => __( 'Add Sandbox Account', 'woo-payment-gateway' ),
					'environment_text'  => __( 'Sandbox', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'data-show-if' => array( 'merchant_environment' => 'sandbox' ) ),
				),
				'instructions'                 => array(
					'type'        => 'title',
					'description' => include braintree()->plugin_path() . 'includes/admin/views/merchant-account-instructions.php',
				),
			)
		);
	}

	public function generate_merchant_account_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args(
			$data,
			array(
				'environment' => '',
				'desc_tip'    => false,
				'button_text' => '',
			)
		);
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/merchant-account-template.php';

		return ob_get_clean();
	}

	public function validate_merchant_account_field( $key, $value ) {
		$value     = empty( $value ) ? array() : $value;
		$old_value = $this->get_option( $key );
		$value     = $this->validate_multiselect_field( $key, $value );
		$old_value = is_string( $old_value ) ? array() : $old_value;
		ksort( $old_value );
		ksort( $value );
		if ( ! empty( $value ) && ( $old_value != $value ) ) {
			// make sure merchant account exists in Braintree.
			try {
				$env               = strpos( $key, 'sandbox' ) === false ? 'production' : 'sandbox';
				$gateway           = new \Braintree\Gateway( wc_braintree_connection_settings( $env ) );
				$accounts_iterator = $gateway->merchantAccount()->all();
				foreach ( $value as $currency => $account ) {
					$exists = false;
					foreach ( $accounts_iterator as $merchant_account ) {
						if ( $merchant_account->id === $account ) {
							$iso_code = @$merchant_account->currencyIsoCode ? $merchant_account->currencyIsoCode : '';
							if ( ! empty( $iso_code ) && $iso_code !== $currency ) {
								throw new Exception( sprintf( __( 'Merchant account error: <b>%1$s</b> is the correct currency for merchant account <b>%2$s</b>. You entered <b>%3$s</b>.', 'woo-payment-gateway' ), $merchant_account->currencyIsoCode, $merchant_account->id, $currency ) );
							}
							$exists = true;
						}
					}
					if ( ! $exists ) {
						throw new Exception( sprintf( __( 'Merchant account error: %1$s is not a valid merchant account in your %2$s environment.', 'woo-payment-gateway' ), $account, $env ) );
					}
				}
			} catch ( \Braintree\Exception $e ) {
				throw new Exception( sprintf( __( 'Error fetching your merchant accounts from Braintree for validation. Exception: %1$s', 'woo-payment-gateway' ), get_class( $e ) ) );
			}
		}

		return $value;
	}
}
