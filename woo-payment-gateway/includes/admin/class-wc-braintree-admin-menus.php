<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_Menus {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'sub_menu' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'remove_submenu' ), 30 );
	}

	public static function admin_menu() {
		add_menu_page( __( 'Braintree Gateway', 'woo-payment-gateway' ), __( 'Braintree Gateway', 'woo-payment-gateway' ), 'administrator', 'wc_braintree', null, null, '7.258' );
	}

	public static function sub_menu() {
		add_submenu_page( 'wc_braintree', __( 'Settings', 'woo-payment-gateway' ), __( 'Settings', 'woo-payment-gateway' ), 'manage_woocommerce', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=braintree_api' ) );
		add_submenu_page( 'wc_braintree', __( 'Logs', 'woo-payment-gateway' ), __( 'Logs', 'woo-payment-gateway' ), 'manage_woocommerce', admin_url( 'admin.php?page=wc-status&tab=logs' ) );
		add_submenu_page(
			'wc_braintree',
			__( 'Data Migration', 'woo-payment-gateway' ),
			__( 'Data Migration', 'woo-payment-gateway' ),
			'manage_woocommerce',
			'wc-braintree-data-migration',
			array(
				__CLASS__,
				'data_migration_page',
			)
		);
		add_submenu_page( 'wc_braintree', __( 'Documentation', 'woo-payment-gateway' ), __( 'Documentation', 'woo-payment-gateway' ), 'manage_woocommerce', 'https://docs.paymentplugins.com/wc-braintree/config' );
	}

	public static function remove_submenu() {
		global $submenu;
		if ( isset( $submenu['wc_braintree'] ) ) {
			unset( $submenu['wc_braintree'][0] );
		}
	}

	public static function data_migration_page() {
		include 'views/html-data-migration.php';
	}
}

WC_Braintree_Admin_Menus::init();
