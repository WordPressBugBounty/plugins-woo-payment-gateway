<?php
defined( 'ABSPATH' ) || exit();

/**
 * @since 3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Install {

	public static function init() {
		add_filter( 'plugin_action_links_' . WC_BRAINTREE_PLUGIN_NAME, array( __CLASS__, 'plugin_action_links' ) );
		register_activation_hook( WC_BRAINTREE_PLUGIN_NAME, array( __CLASS__, 'install' ) );
	}

	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=braintree_api' ), esc_html__( 'Settings', 'woo-payment-gateway' ) ),
			'docs'     => sprintf( '<a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config">%s</a>', __( 'Documentation', 'woo-payment-gateway' ) ),
		);

		return array_merge( $action_links, $links );
	}

	public static function install() {
		if ( get_option( 'braintree_wc_version', false ) !== false ) {
			return;
		}
		// check if plugins that cause conflicts are active.
		if ( is_plugin_active( 'woocommerce-gateway-paypal-powered-by-braintree/woocommerce-gateway-paypal-powered-by-braintree.php' ) ) {
			throw new Exception( 'Please deactivate all other Braintree plugins to proceed with installation' );
		}

		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );

		update_option( 'braintree_wc_version', braintree()->version );
	}
}

WC_Braintree_Install::init();
