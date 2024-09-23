<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_Settings {

	public static function init() {
		add_action( 'woocommerce_settings_checkout', array( __CLASS__, 'output' ) );
		add_filter( 'wc_braintree_admin_settings_tabs', array( __CLASS__, 'admin_settings_tabs' ), 20 );
		add_action( 'woocommerce_update_options_checkout', array( __CLASS__, 'save' ) );
	}

	public static function output() {
		global $current_section;
		if ( ! did_action( 'woocommerce_settings_checkout_' . $current_section ) ) {
			do_action( 'woocommerce_settings_checkout_' . $current_section );
		}
	}

	/**
	 * @deprecated
	 */
	public static function output_advanced_settings() {
		self::output_custom_section( 'braintree_merchant_account' );
	}

	/**
	 * @deprecated
	 */
	public static function output_local_gateways() {
		self::output_custom_section( 'braintree_ideal' );
	}

	public static function output_custom_section( $sub_section = '' ) {
		global $current_section, $wc_braintree_subsection;
		$wc_braintree_subsection = isset( $_GET['sub_section'] ) ? sanitize_title( $_GET['sub_section'] ) : $sub_section;
		do_action( 'woocommerce_settings_checkout_' . $current_section . '_' . $wc_braintree_subsection );
	}

	/**
	 * @deprecated
	 */
	public static function save_advanced_settings() {
		self::save_custom_section( 'braintree_merchant_account' );
	}

	/**
	 * @deprecated
	 */
	public static function save_local_gateway() {
		self::save_custom_section( 'braintree_ideal' );
	}

	/**
	 * @deprecated
	 */
	public static function save_custom_section( $sub_section = '' ) {
		global $current_section, $wc_braintree_subsection;
		$wc_braintree_subsection = isset( $_GET['sub_section'] ) ? sanitize_title( $_GET['sub_section'] ) : $sub_section;
		do_action( 'woocommerce_update_options_checkout_' . $current_section . '_' . $wc_braintree_subsection );
	}

	public static function save() {
		global $current_section;
		if ( $current_section && ! did_action( 'woocommerce_update_options_checkout_' . $current_section ) ) {
			do_action( 'woocommerce_update_options_checkout_' . $current_section );
		}
	}

	public static function admin_settings_tabs( $tabs ) {
		$tabs['braintree_ideal']            = __( 'Local Gateways', 'woo-payment-gateway' );
		$tabs['braintree_merchant_account'] = __( 'Advanced Settings', 'woo-payment-gateway' );

		return $tabs;
	}
}

WC_Braintree_Admin_Settings::init();
