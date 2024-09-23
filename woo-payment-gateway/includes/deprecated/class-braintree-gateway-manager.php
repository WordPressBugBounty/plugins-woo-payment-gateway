<?php
defined( 'ABSPATH' ) || exit();

/**
 * This class should not be used in the 3.0.0+ version. Use WC_Braintree_Manager instead.
 * @deprecated 3.0.0
 * @package Braintree/Classes/Deprecated
 *
 */
class Braintree_Gateway_Manager {

	/**
	 *
	 * @var Braintree_Gateway_Manager
	 */
	public static $_instance = null;

	public $version = '2.6.60';

	/**
	 *
	 * @var array
	 */
	public $settings = array();

	private $settings_name = 'braintree_payment_settings';

	public static function instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function plugin_name() {
		wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->plugin_name()' );
		return braintree()->plugin_name();
	}

	public function slug_name() {
		preg_match( '/(.*)\/.*/', $this->plugin_name(), $matches );
		return isset( $matches[1] ) ? $matches[1] : 'woo-payment-gateway';
	}

	public function template_path() {
		braintree()->template_path();
	}

	public function plugin_assets_path() {
		return braintree()->assets_path();
	}

	public function get_option( $key ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}
		if ( isset( $this->settings[ $key ] ) ) {
			$value = $this->settings[ $key ];
		} else {
			$value = isset( $this->default_settings[ $key ]['default'] ) ? $this->default_settings[ $key ]['default'] : '';
		}
		$this->settings[ $key ] = $value;
		return $value;
	}

	public function set_option( $key, $value = '' ) {
		$this->settings[ $key ] = $value;
	}

	private function init_settings() {
		$this->settings = get_option( $this->settings_name, array() );
	}

	public function is_woocommerce_active() {
		return function_exists( 'WC' );
	}

	public function is_woocommerce_subscriptions_active() {
		return wcs_braintree_active();
	}

	public function get_partner_code() {
		return 'PaymentPlugins_BT';
	}

	public function get_client_token( $params = array() ) {
		return braintree()->generate_client_token();
	}

	public function get_customer_id( $user_id, $env = null ) {
		return wc_braintree_get_customer_id( $user_id, $env );
	}
}
