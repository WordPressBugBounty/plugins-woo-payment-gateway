<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Merchant_Accounts extends WC_Braintree_Rest_Controller {

	protected $namespace = '';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'merchant-accounts',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_merchant_accounts' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' ),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_merchant_accounts( $request ) {
		try {
			$env               = $request->get_param( 'environment' );
			$gateway           = braintree()->gateway( $env );
			$accounts          = $gateway->merchantAccount()->all();
			$merchant_accounts = array();
			$settings          = braintree()->merchant_settings;
			foreach ( $accounts as $account ) {
				$merchant_accounts[ $account->currencyIsoCode ] = $account->id;
			}
			// save merchant account settings:
			$settings->settings["{$env}_merchant_accounts"] = $merchant_accounts;
			update_option( $settings->get_option_key(), $settings->settings );

			return rest_ensure_response( $merchant_accounts );
		} catch ( Exception $e ) {
			return new WP_Error(
				'merchant-account-error',
				sprintf( 'Error fetching merchant accounts in %1$s. Reason: %2$s', $env, wc_braintree_errors_from_object( $e ) ),
				array(
					'status' => 200,
				)
			);
		}
	}
}
