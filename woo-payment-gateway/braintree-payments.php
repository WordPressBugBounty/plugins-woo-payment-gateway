<?php
/**
 * Plugin Name: Payment Plugins Braintree For WooCommerce
 * Plugin URI: https://docs.paymentplugins.com/wc-braintree/config
 * Description: Official partner of Braintree & PayPal. Accept Credit Cards, PayPal, Google Pay, Apple Pay, Venmo, and Local Payments.
 * Version: 3.2.57
 * Author: Payment Plugins, support@paymentplugins.com
 * Author URI: https://docs.paymentplugins.com/wc-braintree/config
 * Text Domain: woo-payment-gateway
 * Domain Path: /i18n/languages/
 * Tested up to: 6.6
 * WC requires at least: 3.0.0
 * WC tested up to: 9.1
 */
defined( 'ABSPATH' ) || exit ();

function wc_braintree_version_check_error() {
	$message = sprintf( __( 'Your PHP version is %s but Braintree For WooCommerce requires version 5.6+.', 'woo-payment-gateway' ), PHP_VERSION );
	echo '<div class="notice notice-error"><p style="font-size: 16px">' . $message . '</p></div>';
}

if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
	add_action( 'admin_notices', 'wc_braintree_version_check_error' );

	return;
}

define( 'WC_BRAINTREE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_BRAINTREE_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'WC_BRAINTREE_PLUGIN_NAME', plugin_basename( __FILE__ ) );
require_once( WC_BRAINTREE_PATH . 'vendor/autoload.php' );
require_once( WC_BRAINTREE_PATH . 'includes/class-braintree.php' );