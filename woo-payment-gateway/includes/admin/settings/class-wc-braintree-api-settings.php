<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Admin
 */
if ( ! class_exists( 'WC_Settings_API' ) ) {
	return;
}

class WC_Braintree_API_Settings extends WC_Braintree_Settings_API {

	public function __construct() {
		$this->id        = 'braintree_api';
		$this->tab_title = __( 'API Settings', 'woo-payment-gateway' );
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_braintree_api_form_fields', include 'api-settings.php' );
	}

	public function process_admin_options() {
		$settings = $this->settings;
		parent::process_admin_options();
		$this->fetch_merchant_accounts( $settings );
	}

	/**
	 * Retrieve merchant accounts from the Braintree Gateway if the API keys have changed.
	 *
	 * @param array $settings
	 */
	public function fetch_merchant_accounts( $settings = array() ) {
		$env      = $this->get_option( 'environment' );
		$old_keys = array( $settings["{$env}_public_key"], $settings["{$env}_private_key"], $settings["{$env}_merchant_id"] );
		$new_keys = array( $this->settings["{$env}_public_key"], $this->settings["{$env}_private_key"], $this->settings["{$env}_merchant_id"] );
		$old_hash = implode( '_', $old_keys );
		$new_hash = implode( '_', $new_keys );

		// keys have changed, fetch merchant accounts
		if ( $old_hash !== $new_hash && ! in_array( "", $new_keys, true ) ) {
			braintree()->rest_api->force( true );
			rest_get_server();
			$request = new WP_REST_Request( 'POST', '/wc-braintree/v1/merchant-accounts' );
			$request->add_header( 'Content-Type', 'application/json' );
			$request->set_body_params( array( 'environment' => $env ) );
			rest_do_request( $request );
		}
	}

	/**
	 * Returns an array of parameters for use in localization
	 *
	 * @return array
	 */
	public function get_localized_params() {
		return array_merge(
			parent::get_localized_params(),
			array(
				'routes' => array( 'connection_test' => braintree()->rest_api->settings->rest_url() . 'connection-test' ),
			)
		);
	}
}

