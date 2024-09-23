<?php
defined( 'ABSPATH' ) || exit();

$old_settings = get_option( 'braintree_payment_settings', array() );
if ( empty( $old_settings ) ) {
	return;
}
if ( function_exists( 'wc_set_time_limit' ) ) {
	wc_set_time_limit( 0 ); // don't want script to stop executing due timeout.
}
/**
 * *************** API Settings ********************
 */
$api_settings = array(
	'environment'            => '',
	'production_public_key'  => 'production_public_key',
	'production_private_key' => 'production_private_key',
	'production_merchant_id' => 'production_merchant_id',
	'sandbox_public_key'     => 'sandbox_public_key',
	'sandbox_private_key'    => 'sandbox_private_key',
	'sandbox_merchant_id'    => 'sandbox_merchant_id',
);
$api_options  = array( 'debug' => 'yes' );
foreach ( $api_settings as $new_key => $old_key ) {
	$value = '';
	switch ( $new_key ) {
		case 'environment':
			if ( $old_settings['production_environment'] === 'yes' ) {
				$value = 'production';
			} else {
				$value = 'sandbox';
			}
			break;
		default:
			$value = $old_settings[ $old_key ];
			break;
	}
	$api_options[ $new_key ] = $value;
}

update_option( 'woocommerce_braintree_api_settings', $api_options );

/**
 * **************** Advanced Settings ***********************
 */
// merchant accounts, Kount, Fraud

$advanced_settings = array(
	'merchant_account' => array(
		'production_merchant_accounts' => 'woocommerce_braintree_production_merchant_account_id',
		'sandbox_merchant_accounts'    => 'woocommerce_braintree_sandbox_merchant_account_id',
	),
	'fraud'            => array(
		'enabled'              => 'advanced_fraud_enabled',
		'kount_review_action'  => 'kount_review_action',
		'kount_decline_action' => 'kount_decline_action',
	),
	'descriptor'       => array(
		'enabled'          => 'dynamic_descriptors',
		'descriptor_name'  => 'dynamic_descriptor_name',
		'descriptor_phone' => 'dynamic_descriptor_phone',
		'descriptor_url'   => 'dynamic_descriptor_url',
	),
	'fee'              => array(
		'enabled' => 'checkout_fee_enabled',
		'fees'    => 'checkout_fees',
	),
);
foreach ( $advanced_settings as $id => $settings ) {
	$advanced_options = array();
	foreach ( $settings as $new_key => $old_key ) {
		$value = '';
		switch ( $new_key ) {
			default:
				$value = $old_settings[ $old_key ];
				break;
		}
		$advanced_options[ $new_key ] = $value;
	}
	update_option( "woocommerce_braintree_{$id}_settings", $advanced_options );
}
/**
 * ****************** CC Settings ***********************
 */
$cc_settings = array(
	'enabled'                   => 'enabled',
	'title_text'                => 'title_text',
	'method_format'             => 'creditcard_format',
	'order_prefix'              => 'order_prefix',
	'order_status'              => 'order_status',
	'charge_type'               => 'authorize_transaction',
	'payment_methods'           => 'payment_methods',
	'form_type'                 => 'checkout_form',
	'dropin_form_version'       => 'dropin_form_version',
	'dropin_postal_enabled'     => 'dropin_postal_enabled',
	'custom_form_design'        => 'custom_form_design',
	'custom_form_styles'        => 'custom_form_styles',
	'dynamic_card_display'      => 'dynamic_card_display',
	'postal_field_enabled'      => 'postal_field_enabled',
	'cvv_field_enabled'         => 'cvv_field_enabled',
	'loader_enabled'            => 'enable_loader',
	'loader_design'             => 'custom_form_loader_file',
	'3ds_enabled'               => '3ds_enabled',
	'3ds_conditions'            => '3ds_conditions',
	'3ds_enable_payment_token'  => '3ds_enabled_payment_token',
	'3ds_liability_not_shifted' => '3ds_liability_not_shifted',
	'3ds_card_ineligible'       => '3ds_card_ineligible',
	'save_card_enabled'         => 'save_payment_methods',
);
$cc_options  = array();

foreach ( $cc_settings as $new_key => $old_key ) {
	$value = '';
	switch ( $new_key ) {
		case 'charge_type':
			$value = $old_settings[ $old_key ] === 'yes' ? 'authorize' : 'capture';
			break;
		case 'custom_form_design':
			$value = $old_settings[ $old_key ];
			if ( $value === '3d_card_form' ) {
				$value = 'bootstrap_form';
			}
			break;
		case 'dropin_form_version':
			$value = 'v3';
		default:
			$value = $old_settings[ $old_key ];
			break;
	}
	$cc_options[ $new_key ] = $value;
}
update_option( 'woocommerce_braintree_cc_settings', $cc_options );

/**
 * ***************** PayPal Settings ******************************
 */
$paypal_settings = array(
	'enabled'            => 'enable_paypal',
	'title_text'         => 'paypal_gateway_title',
	'method_format'      => 'paypal_format',
	'order_prefix'       => 'order_prefix',
	'order_status'       => 'order_status',
	'charge_type'        => 'authorize_transaction',
	'sections'           => 'paypal_sections_enabled',
	'billing_agreement'  => 'paypal_billing_agreement_desc',
	'submit_form'        => 'paypal_submit_form',
	'smartbutton_size'   => 'paypal_smartbutton_style_size',
	'smartbutton_color'  => 'paypal_smartbutton_style_color',
	'smartbutton_shape'  => 'paypal_smartbutton_style_shape',
	'smartbutton_layout' => 'paypal_smartbutton_style_layout',
	'credit_enabled'     => 'paypal_credit',
	'display_name'       => 'paypal_display_name',
	'credit_conditions'  => 'paypal_credit_conditions',
);
$paypal_options  = array();
foreach ( $paypal_settings as $new_key => $old_key ) {
	$value = '';
	switch ( $new_key ) {
		case 'charge_type':
			$value = $old_settings[ $old_key ] === 'yes' ? 'authorize' : 'capture';
			break;
		default:
			$value = $old_settings[ $old_key ];
			break;
	}
	$paypal_options[ $new_key ] = $value;
}
update_option( 'woocommerce_braintree_paypal_settings', $paypal_options );

/**
 * ********************* Google Pay Settings **********************
 */
$googlepay_settings = array(
	'enabled'       => 'googlepay_enabled',
	'merchant_id'   => 'googlepay_merchant_id',
	'title_text'    => 'googlepay_gateway_title',
	'method_format' => 'googlepay_format',
	'order_prefix'  => 'order_prefix',
	'order_status'  => 'order_status',
	'charge_type'   => 'authorize_transaction',
	'icon'          => 'googlepay_icon',
	'button_color'  => 'googlepay_button_color',
	'button_type'   => 'googlepay_button_type',
);
$googlepay_options  = array();
foreach ( $googlepay_settings as $new_key => $old_key ) {
	$value = '';
	switch ( $new_key ) {
		case 'charge_type':
			$value = $old_settings[ $old_key ] === 'yes' ? 'authorize' : 'capture';
			break;
		case 'icon':
			if ( $old_settings[ $old_key ] === 'google_pay_white_outline' ) {
				$value = 'google_pay_outline';
			}
			break;
		default:
			$value = $old_settings[ $old_key ];
			break;
	}
	$googlepay_options[ $new_key ] = $value;
}
$googlepay_options['dynamic_price'] = 'no';
update_option( 'woocommerce_braintree_googlepay_settings', $googlepay_options );

/**
 * ******************* Apple Pay Settings *******************
 */
$applepay_settings = array(
	'enabled'       => 'enable_applepay',
	'title_text'    => 'applepay_gateway_title',
	'order_prefix'  => 'order_prefix',
	'order_status'  => 'order_status',
	'charge_type'   => 'authorize_transaction',
	'sections'      => 'applepay_sections_enabled',
	'method_format' => 'applepay_format',
	'store_name'    => 'applepay_store_name',
	'button'        => 'applepay_button',
);
$applepay_options  = array();
foreach ( $applepay_settings as $new_key => $old_key ) {
	$value = '';
	switch ( $new_key ) {
		case 'charge_type':
			$value = $old_settings[ $old_key ] === 'yes' ? 'authorize' : 'capture';
			break;
		default:
			$value = $old_settings[ $old_key ];
			break;
	}
	$applepay_options[ $new_key ] = $value;
}
update_option( 'woocommerce_braintree_applepay_settings', $applepay_options );

/**
 * ** Kount ***
 */
update_option( 'wc_braintree_kount_api_key', get_option( 'bfwc_kount_key' ), false );

/**
 * Enabled plugin subscriptions for users of old plugin.
 * That way funcionality is loaded by default
 */
if ( ! wcs_braintree_active() ) {
	update_option(
		'woocommerce_braintree_subscription_settings',
		array(
			'enabled'                        => 'yes',
			'combine'                        => isset( $old_settings['braintree_subscription_combine_same_products'] ) ? $old_settings['braintree_subscription_combine_same_products'] : 'yes',
			'subscriptions_endpoint'         => isset( $old_settings['bfwcs_subscriptions_endpoint'] ) ? $old_settings['bfwcs_subscriptions_endpoint'] : 'subscriptions',
			'view_subscription_endpoint'     => isset( $old_settings['bfwcs_view-subscription_endpoint'] ) ? $old_settings['bfwcs_view-subscription_endpoint'] : 'view-subscription',
			'change_payment_method_endpoint' => isset( $old_settings['bfwcs_change-payment-method_endpoint'] ) ? $old_settings['bfwcs_change-payment-method_endpoint'] : 'change-payment-method',
		)
	);
}

/**
 * **************** Database update ***********************
 */
global $wpdb;
/*
 * $query1 = $wpdb->prepare ( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_key=%s AND meta_value=%s",
 * 'braintree_cc', '_payment_method', 'braintree_payment_gateway' );
 * wc_braintree_log_info ( sprintf ( 'Version 3.0.0 update - CC gateway conversion query: %1$s', $query1 ) );
 * $rows = $wpdb->query ( $query1 );
 * wc_braintree_log_info ( sprintf ( 'CC rows updated: %1$s', $rows ) );
 *
 * $query2 = $wpdb->prepare ( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_key=%s AND meta_value=%s",
 * 'braintree_paypal', '_payment_method', 'braintree_paypal_payments' );
 * $rows = $wpdb->query ( $query2 );
 * wc_braintree_log_info ( sprintf ( 'PayPal rows updated: %1$s', $rows ) );
 *
 * $query3 = $wpdb->prepare ( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_key=%s AND meta_value=%s",
 * 'braintree_applepay', '_payment_method', 'braintree_applepay_payments' );
 * $rows = $wpdb->query ( $query3 );
 * wc_braintree_log_info ( sprintf ( 'Apple Pay rows updated: %1$s', $rows ) );
 *
 * $query4 = $wpdb->prepare ( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_key=%s AND meta_value=%s",
 * 'braintree_googlepay', '_payment_method', 'braintree_googlepay_gateway' );
 * $rows = $wpdb->query ( $query4 );
 * wc_braintree_log_info ( sprintf ( 'Google Pay rows updated: %1$s', $rows ) );
 */
/**
 * ****** Database End *******
 */

update_option( 'wc_braintree_production_plans', get_option( 'braintree_wc_production_plans' ) );
update_option( 'wc_braintree_sandbox_plans', get_option( 'braintree_wc_sandbox_plans' ) );

$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_braintree_sandbox_plans' ) );
foreach ( $post_ids as $post ) {
	$plans     = get_post_meta( $post->post_id, '_braintree_sandbox_plans', true );
	$new_plans = array();
	foreach ( $plans as $plan_id ) {
		$new_plans[] = $plan_id;
	}
	update_post_meta( $post->post_id, '_braintree_sandbox_plans', $new_plans );
}

$post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_braintree_production_plans' ) );
foreach ( $post_ids as $post ) {
	$plans     = get_post_meta( $post->post_id, '_braintree_production_plans', true );
	$new_plans = array();
	foreach ( $plans as $plan_id ) {
		$new_plans[] = $plan_id;
	}
	update_post_meta( $post->post_id, '_braintree_production_plans', $new_plans );
	update_post_meta( $post->post_id, '_production_subscription_period_interval', get_post_meta( $post->post_id, '_subscription_period_interval', true ) );
}

/**
 * The templates used in 3.0.0 use different functions and in some cases HTML.
 * To avoid warning messages, the woo-payment-gateway folder
 * will be renamed so the plugin's base templates are used after the update.
 */
require_once( ABSPATH . 'wp-admin/includes/file.php' );
WP_Filesystem();
/**
 *
 * @var WP_Filesystem_Base $wp_filesystem
 */
global $wp_filesystem;

// rename the woo-payment-gateway directory if it exists in the active theme.
$theme_path  = get_stylesheet_directory();
$parent_path = get_template_directory();
if ( file_exists( $theme_path . '/' . braintree()->template_path() ) ) {
	$template_path = $theme_path . '/' . braintree()->template_path();
	// rename the plugin's theme directory.
	if ( ! $wp_filesystem->move( $template_path, $theme_path . '/woo-payment-gateway_old', false ) ) {
		wc_braintree_log_error( 'Template files could not be moved to woo-payment-gateway_old' );
	}
} elseif ( file_exists( $parent_path . '/' . braintree()->template_path() ) ) {
	$template_path = $parent_path . '/' . braintree()->template_path();
	if ( ! $wp_filesystem->move( $template_path, $parent_path . '/woo-payment-gateway_old', false ) ) {
		wc_braintree_log_error( 'Template files could not be moved to woo-payment-gateway_old' );
	}
}

/**
 * ** Delete Old Data ***
 */
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'braintree_production_payment_methods' ) );
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'braintree_next_payment_update' ) );

delete_option( 'wc_braintree_show_3_0_notice' );
delete_option( 'bfwc_kount_key' );
delete_option( 'bfwc_show_deprecated_donations' );
delete_option( 'bfwc_error_messages' );
