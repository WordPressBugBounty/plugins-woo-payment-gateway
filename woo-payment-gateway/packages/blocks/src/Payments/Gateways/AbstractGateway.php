<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;


use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Assets\Api as AssetsApi;

/**
 * Class AbstractGateway
 *
 * @package PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways
 */
class AbstractGateway extends AbstractPaymentMethodType {

	protected $name = '';

	protected $assets_api;

	public function __construct( AssetsApi $assets_api ) {
		$this->assets_api = $assets_api;
	}

	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", [] );
	}

	public function is_active() {
		return $this->get_setting( 'enabled', 'no' ) === 'yes';
	}

	public function get_payment_method_data() {
		return [
			'name'                 => $this->get_name(),
			'title'                => $this->get_setting( 'title_text' ),
			'description'          => $this->get_setting( 'description' ),
			'countryCode'          => WC()->countries ? WC()->countries->get_base_country() : '',
			'features'             => $this->get_supported_features(),
			'icon'                 => $this->get_payment_method_icon(),
			'threeDSecureEnabled'  => $this->is_three_d_secure_enabled(),
			'challengeRequested'   => wc_string_to_bool( $this->get_setting( '3ds_challenge_requested', 'no' ) ),
			'advancedFraudEnabled' => braintree()->fraud_settings->is_active( 'enabled' ),
			'routes'               => $this->get_rest_routes(),
			'ipAddress'            => \WC_Geolocation::get_ip_address()
		];
	}

	public function get_supported_features() {
		return [
			'tokenization',
			'subscriptions',
			'products',
			'add_payment_method',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer'
		];
	}

	protected function get_payment_method_icon() {
		return [];
	}

	protected function is_three_d_secure_enabled() {
		return $this->get_setting( '3ds_enabled', 'no' ) === 'yes';
	}

	protected function get_rest_routes() {
		return [
			'shipping'         => \WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping' ),
			'shipping_address' => \WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping-address' ),
			'shipping_method'  => \WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping-method' )
		];
	}

	protected function get_payment_sections() {
		$option = $this->get_setting( 'sections', [] );
		if ( ! \is_array( $option ) ) {
			$option = [];
		}

		return $option;
	}

}