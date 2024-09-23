<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Settings_API' ) ) {
	return;
}

/**
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 *
 */
abstract class WC_Braintree_Advanced_Settings_API extends WC_Braintree_Settings_API {

	protected $braintree_documentation_id;

	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_braintree_advanced_settings_tabs', array( $this, 'advanced_settings_tabs' ) );
		add_action( 'woocommerce_settings_checkout_' . $this->id, array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_settings_checkout_' . $this->id, array( $this, 'output' ) );
		add_action( 'wc_braintree_localize_' . $this->id . '_settings', array( $this, 'get_admin_localized_params' ) );
	}

	public function advanced_settings_tabs( $tabs ) {
		$tabs[ $this->id ] = $this->tab_title;

		return $tabs;
	}

	public function output_settings_nav() {
		global $current_section;
		parent::output_settings_nav();
		include braintree()->plugin_path() . 'includes/admin/views/html-advanced-settings-nav.php';
	}

	public function get_braintree_documentation_url() {
		return sprintf( 'https://docs.paymentplugins.com/wc-braintree/config/#/braintree_advanced?id=%s', $this->braintree_documentation_id );
	}
}
