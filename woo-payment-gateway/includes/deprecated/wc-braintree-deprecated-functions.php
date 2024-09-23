<?php
defined( 'ABSPATH' ) || exit();

/**
 * Deprecated functions that were used prior to version 3.0.0.
 * All functions issue a warning along with
 * the new function that replaces them.
 */

/**
 * Return the merchant account id configured for the WooCommerce currency.
 * If there is no merchant account, return an empty string.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 *
 */
function bwc_get_merchant_account( $currency = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_merchant_account' );
	return wc_braintree_get_merchant_account( $currency );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_get_merchant_accounts() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_merchant_accounts' );
	return wc_braintree_get_merchant_accounts();
}

/**
 * Return true if dynamic descriptors have been enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_descriptors_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no in use.' );
	return false;
}

/**
 * Return true if custom forms are enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_custom_form() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::is_custom_form_active' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_custom_form_active();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_3ds_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::_3ds_enabled' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->_3ds_enabled();
}

/**
 * Return true if 3DS is active.
 * If WC subscriptions is active and
 * the request is for a payment method change, this function will return false
 * regardless of
 * the 3DS setting.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_3ds_active() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::_3ds_active' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->_3ds_active();
}

/**
 * Return true if credit card payments are enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_card_payments_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::is_available' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_available();
}

/**
 * Return true if Apple Pay is enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_applepay_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_ApplePay_Payment_Gateway::is_available' );
	/**
	 *
	 * @var WC_Braintree_ApplePay_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_applepay'];
	return $gateway->is_available();
}

/**
 * Return true of the order contains a transaction Id.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 */
function bwc_can_refund_order( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0' );
	$id = $order->get_transaction_id();
	return ! empty( $id );
}

/**
 * Return an array of custom form fields used in the custom payment form.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bwc_get_custom_form_fields() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::get_custom_form_fields' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->get_custom_form_fields();
}

/**
 * Return the configured custom form.
 * <strong>Example</strong>
 * array('html' => 'custom-forms/bootstrap-form.php',
 * 'css'=>'https:'//example.com/styles/mycss.css')
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return array
 */
function bwc_get_custom_form() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_custom_form' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return wc_braintree_get_custom_form( $gateway->get_option( 'custom_form_design' ) )['template'];
}

/**
 * Return an array of custom form.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return array
 */
function bwc_get_custom_forms() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_custom_form_options' );
	return wc_braintree_custom_form_options();
}

/**
 * Return the html for the 3DS modal html.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_get_3ds_modal_html() {
	wc_deprecated_function( __FUNCTION__, '3.0.0' );
	return '';
}

/**
 * Return true if the payment method icons should be displayed on the outside of
 * the gateway html.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_payment_icons_outside() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_template_html ( \'3ds-modal.php\' )' );
	return false;
}

/**
 * Return true if PayPal has been enabled as a payment gateway.
 * Custom forms
 * must be enabled as well in order for this function to return true.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_paypal_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::is_available' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->is_available();
}

/**
 *
 * @version 2.6.30
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_paypal_cart_checkout_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return in_array( 'checkout_banner', $gateway->get_option( 'sections' ) );
}

/**
 * Return the button that has been selected for use on the frontend.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return array
 */
function bwc_get_paypal_button() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return array(
		'name' => '',
		'css'  => '',
		'html' => 'checkout/paypal.php',
	);
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string[]
 */
function bwc_get_paypal_credit_button() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return array(
		'name' => '',
		'css'  => '',
		'html' => 'checkout/paypal.php',
	);
}

/**
 * Return the gateway id for the given gateway.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $gateway
 *          braintree | paypal
 * @return string
 */
function bwc_get_gateway_id( $gateway ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	switch ( $gateway ) {
		case 'braintree':
			return 'braintree_cc';
		case 'paypal':
		case 'paypal-credit':
			return 'braintree_paypal';
		case 'applepay':
			return 'braintree_applepay';
		case 'googlepay':
			return 'braintree_googlepay';
	}
}

/**
 * Return the html used for paypal methods.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_get_paypal_html() {
	wc_deprecated_function( __FUNCTION__, '3.0.0' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_get_paypal_credit_html() {
	wc_deprecated_function( __FUNCTION__, '3.0.0' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.21
 * @param string $template
 * @param array $args
 * @return string
 */
function bwc_get_template_html( $template, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_template_html' );
	return wc_braintree_get_template_html( $template, $args );
}

/**
 * Return true if the PayPal checkout flow is 'checkout.'
 * If WooCommerce Subscriptions is active, then check if the cart contains
 * subscriptions.
 * If there are subscriptions in the cart, then 'vault' must be active to ensure
 * PayPal payment methods are saved. If the page is the add payment page, then
 * false is returned as 'vault' flow
 * is needed for adding PayPal payment methods.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return bool
 */
function bwc_paypal_checkout_flow() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::get_paypal_flow' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->get_paypal_flow();
}

/**
 * Return true if PayPal Credit is enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_paypal_credit_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::is_paypal_credit_active' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->is_paypal_credit_active();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.26
 * @return string
 */
function bwc_paypal_credit_conditions() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::get_option(credit_conditions)' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->get_option( 'credit_conditions' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_paypal_credit_active() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::is_paypal_credit_active' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->is_paypal_credit_active();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $cond
 * @return boolean
 */
function bwc_execute_conditional_statement( $cond ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_evaluate_condition' );
	return wc_braintree_evaluate_condition( $cond );
}

/**
 * Return an array of conditional value keys and their values.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bwc_get_conditional_values() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_conditional_values' );
	return wc_braintree_get_conditional_values();
}

/**
 * Return a comma separated list of products.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_get_comma_separated_product_names() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_products_for_conditions' );
	return wc_braintree_get_products_for_conditions();
}

/**
 * Return true if the gateway is configured to reject duplicate payment methods.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_fail_on_duplicate() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::is_active(fail_on_duplicate)' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_active( 'fail_on_duplicate' );
}

/**
 * Return true if advanced fraud tools has been enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_advanced_fraud_tools() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->fraud_settings->is_active( )' );
	return braintree()->fraud_settings->is_active( 'enabled' );
}

/**
 * Include the given template.
 * The template is first checked in the theme's path.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $template
 * @param array $args
 */
function bwc_get_template( $template, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_template' );
	wc_braintree_get_template( $template, $args );
}

/**
 * Loate the template file in the theme and if not in the theme then in the
 * plugin.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $template_names
 */
function bwc_locate_template( $template_name ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_locate_template' );
	wc_locate_template( braintree()->template_path(), braintree()->plugin_path() . 'templates/' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_checkout() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return is_checkout();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 */
function bwc_get_user_payment_methods( $user_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use - returns empty array. Use WC_Payment_Tokens' );
	return array();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 */
function bwc_get_user_paypal_payment_methods( $user_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use - returns empty array. Use WC_Payment_Tokens' );
	return array();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 */
function bwc_get_user_applepay_payment_methods( $user_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use - returns empty array. Use WC_Payment_Tokens' );
	return array();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.22
 * @param unknown $user_id
 * @return mixed|boolean|string|unknown
 */
function bwc_get_googlepay_methods( $user_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use - returns empty array. Use WC_Payment_Tokens' );
	return array();
}

/**
 * Return an array of saved payment methods.
 * Most useful if called
 * when the payment-methods page is being rendered.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $methods
 * @return mixed
 */
function bwc_saved_payment_methods_list( $saved_methods, $methods, $gateway_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return apply_filters( 'braintree_saved_payment_methods_list', $saved_methods );
}

/**
 * Return true if dynamic card display is active.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_dynamic_card_display() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::get_option(dynamic_card_display)' );
	/**
	 *
	 * @var WC_Braintree_CC_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->get_option( 'dynamic_card_display' );
}

/**
 * Echo an input field for the payment method token.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $token
 */
function bwc_payment_method_token_field( $id, $token = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_token_field' );
	$gateway = WC()->payment_gateways()->payment_gateways()[ $id ];
	wc_braintree_payment_token_field( $gateway, $token );
}

/**
 * Return an array of braintree WC gateways.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return array
 */
function bwc_get_payment_gateways() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	$gateways = array();
	foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gateway ) {
		if ( $gateway instanceof WC_Braintree_Payment_Gateway ) {
			$gateways[ $id ] = $gateway;
		}
	}
	return $gateways;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param array $methods
 * @return WC_Payment_Token|NULL
 */
function bwc_get_default_method( $methods = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Payment_Tokens::get_customer_default_token' );
	return WC_Payment_Tokens::get_customer_default_token( get_current_user_id() );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_get_save_method_template() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return wc_braintree_get_template( 'checkout/save-method.php' );
}

/**
 * Return true if the save payment method checkbox should be displayed.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_display_save_payment_method() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_save_cc_enabled' );
	return wc_braintree_save_cc_enabled();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_enable_signup_from_checkout() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) === 'yes';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_saved_payment_method_style() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_refresh_payment_fragments() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $id
 * @param string $token
 */
function bwc_payment_token_field( $id, $token = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_token_field' );
	$gateway = WC()->payment_gateways()->payment_gateways()[ $id ];
	return wc_braintree_payment_token_field( $gateway, $token );
}

/**
 * Return true of V3 of the dropin is enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_dropin_v3_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Only V3 is supported. V2 deprecated.' );
	return true;
}

/**
 * Return true if V2 of the dropin is enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_dropin_v2_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Only V3 is supported. V2 deprecated.' );
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_get_loader_file() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use.' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return 'loaders/' . $gateway->get_option( 'loader_design' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwc_payment_loader_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::is_active(loader_enabled)' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_active( 'loader_enabled' );
}

/**
 * Return true if the postal code field has been enabled for custom forms.
 * 2.6.20 - true is always returned for the add payment method page since AVS rules might be enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_postal_code_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::is_postal_code_enabled' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_postal_code_enabled();
}

/**
 * Return true if the cvv field has been enabled for custom forms.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_cvv_field_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'CVV is rendered dynamically based on Control panel settings' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_paypal_send_shipping() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	$cart = WC()->cart;
	return $cart && $cart->needs_shipping();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_paypal_credit_send_shipping() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	$cart = WC()->cart;
	return $cart && $cart->needs_shipping();
}

/**
 * Return true if the WC version is 3.0.0 or greater.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_is_wc_3_0_0_or_more() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Plugin supports WC 3.0.0+' );
	return true;
}

/**
 * Wrapper for returning the provided order property.
 * Based on the WC version, the property is fetched differently. Backwards compatability for versions
 * less than WC 3.0.0 is needed, thus the implementation of a wrapper.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $prop
 * @param $order WC_Order
 * @param string $context
 */
function bwc_get_order_property( $prop, $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Access order properties using getters.' );
	$value = '';

	if ( bwc_is_wc_3_0_0_or_more() ) {
		if ( array_key_exists( $prop, bwc_get_3_0_0_updated_props() ) ) {
			$prop = bwc_get_3_0_0_updated_props()[ $prop ];
		}
		if ( is_callable( array( $order, "get_$prop" ) ) ) {
			$value = $order->{"get_$prop"}();
		} else {
			if ( ! $value = bwc_get_3_0_0_deprecated_order_prop( $prop, $order ) ) {
				/**
				 * If the getter method does not exist (for custom properties for example) then
				 * fetch the data directly from the post_meta of the order.
				 */
				$value = get_post_meta( bwc_get_order_property( 'id', $order ), "_{$prop}", true );
			}
		}
	} else {
		$value = $order->{$prop};
	}
	return $value;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string[]
 */
function bwc_get_3_0_0_updated_props() {
	return array(
		'customer_user'  => 'customer_id',
		'order_currency' => 'currency',
	);
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $prop
 * @param unknown $order
 * @return NULL|WP_Post
 */
function bwc_get_3_0_0_deprecated_order_prop( $prop, $order ) {
	$value = null;
	switch ( $prop ) {
		case 'post_status':
			$value = get_post_status( $order->get_id() );
			break;
		case 'id':
			$value = $order->get_id();
			break;
		case 'order_currency':
			$value = $order->get_currency();
			break;
		case 'post':
			$value = get_post( $order->get_id() );
			break;
	}
	return $value;
}

/**
 * Return true if the current request is an admin webhook test.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.2
 */
function bwc_is_admin_webhook_request() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Not in use' );
	return defined( 'BFWC_ADMIN_WEBHOOK_TEST' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $prop
 * @param WC_Product $product
 */
function bwc_get_product_property( $prop, $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Access order properties using getters.' );
	$value = '';
	if ( bwc_is_wc_3_0_0_or_more() ) {
		if ( is_callable( array( $product, "get_$prop" ) ) ) {
			$value = $product->{"get_$prop"}();
		} else {
			$value = get_post_meta( bwc_get_product_property( 'id', $product ), "_$prop", true );
		}
	} else {
		$value = $product->{$prop};
	}
	return $value;
}

/**
 * Return the billing agreement description.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_get_billing_agreement_desc() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::get_option(billing_agreement)' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->get_option( 'billing_agreement' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_payment_icons_enclosed_type() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return false;
}

/**
 * Return the icon type that is set for payment methods
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bwc_payment_icons_type() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $method
 * @return string
 */
function bwc_get_enclosed_icon_url( $method ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return '';
}

/**
 * Return true if icons should be displayed on the payment methods page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.7
 * @return boolean
 */
function bwc_display_icons_on_payment_methods_page() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use' );
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 *          Return true if vaulted payment methods should be verified with 3DS.
 */
function bwc_3ds_verify_vaulted_methods() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_CC_Payment_Gateway::get_option(3ds_enable_payment_token' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	return $gateway->is_active( '3ds_enabled' ) && $gateway->is_active( '3ds_enable_payment_token' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_3ds_no_action_needed() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'not in use - returns true' );
	return true;
}

/**
 * Return true if fees are enabled.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.7
 * @return boolean
 */
function bwc_fees_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->fee_settings->is_active(enabled)' );
	braintree()->fee_settings->is_active( 'enabled' );
}

/**
 * Return true if there is a fee configured for the gateway.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.7
 * @param string $gateway
 */
function bwc_fee_enabled_for_gateway( $gateway ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->fee_settings->is_active(enabled)' );
	$fees = bwc_get_gateway_fees();
	if ( $fees ) {
		foreach ( $fees as $fee ) {
			if ( ! empty( $fee['gateways'] ) ) {
				if ( in_array( $gateway, $fee['gateways'] ) ) {
					return true;
				}
			}
		}
		return false;
	} else {
		return false;
	}
}

/**
 * Evaluate the fee.
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.7
 * @param string $fee
 */
function bwc_calculate_fee( $fee, $args = array() ) {
	$fee['calculation'] = str_replace( array( '[qty]', '[cost]' ), array( $args['qty'], $args['cost'] ), $fee['calculation'] );
	if ( ! class_exists( 'WC_Eval_Math' ) ) {
		include_once( WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php' );
	}
	return $fee ? WC_Eval_Math::evaluate( $fee['calculation'] ) : 0;
}

/**
 * Return the gateway fee(s).
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.7
 * @return string
 */
function bwc_get_gateway_fees() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->fee_settings->get_option(fees)' );
	braintree()->fee_settings->get_option( 'fees' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $gateway
 * @return unknown[]
 */
function bwc_get_fees_for_gateway( $gateway ) {
	$fees         = bwc_get_gateway_fees();
	$fees         = $fees ? $fees : array();
	$gateway_fees = array();
	foreach ( $fees as $fee ) {
		if ( in_array( $gateway, $fee['gateways'] ) ) {
			$gateway_fees[] = $fee;
		}
	}
	return $gateway_fees;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.9
 * @param string $option
 * @return string
 */
function bwc_get_option_text( $option ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $placeholder
 * @param string $text
 * @param string $echo
 * @return string
 */
function bwc_custom_form_text( $key, $text, $echo = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported. Use provided filters for altering custom form labels.' );
	return $echo ? printf( $text ) : $text;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwc_use_admin_text_for_custom_form() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported. Use provided filters for altering custom form labels.' );
	return false;
}

/**
 * Return true if Kount is enabled in the plugin.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.16
 * @return boolean
 */
function bwc_kount_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree ()->fraud_settings->is_active ( kount_enabled )' );
	return braintree()->fraud_settings->is_active( 'kount_enabled' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.20
 * @param string $message
 * @param string $type
 */
function bwc_add_notice( $message, $type = 'success' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( $message, $type );
	}
}

/**
 * Return true if the order processing email should be sent.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.21
 * @return boolean
 */
function bwc_kount_send_processing_email() {
	// return bt_manager ()->is_active ( 'kount_send_processing_email' ) && bt_manager ()->get_option (
	// 'kount_review_action' ) === 'review';
	// when the order is marked as Kount review, still might want to send email to customer.
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.21
 * @return boolean
 */
function bwc_googlepay_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_GooglePay_Payment_Gateway::is_available()' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_googlepay'];
	return $gateway->is_available();
}

/**
 * Return true if the Smart Buttons integration is enabled for PayPal.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.26
 * @return bool
 */
function bwc_smart_buttons_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return true; // bt_manager ()->get_option ( 'paypal_button_type' ) === 'smart_button';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.26
 * @return array
 */
function bwc_get_smartbutton_style() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::get_button_options()' );
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->get_button_options();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.41
 */
function bwc_set_checkout_error() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_set_checkout_error' );
	wc_braintree_set_checkout_error();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $template_name
 */
function bwc_output_checkout_error( $template_name ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_output_checkout_error' );
	wc_braintree_output_checkout_error( $template_name );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $tags
 * @param unknown $context
 * @return boolean[]
 */
function bwc_add_allowed_html( $tags, $context ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_add_allowed_html' );
	return wc_braintree_add_allowed_html( $tags, $context );
}

/**
 * *
 * Return true if Apple Pay is enabled for the checkout banner section.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.41
 * @return boolean
 */
function bwc_applepay_checkout_banner_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_ApplePay_Payment_Gateway::get_option(sections) and check if in array' );
	/**
	 *
	 * @var WC_Braintree_ApplePay_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_applepay'];
	return $gateway->is_available() && in_array( 'checkout_banner', $gateway->get_option( 'sections' ) );
}

/**
 * *
 * Return true if PayPal is enabled for the checkout banner section.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.41
 * @return boolean
 */
function bwc_paypal_checkout_banner_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_PayPal_Payment_Gateway::get_option(sections) and check if in array' );
	/**
	 *
	 * @var WC_Braintree_PayPal_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_paypal'];
	return $gateway->is_available() && in_array( 'checkout_banner', $gateway->get_option( 'sections' ) );
}

/**
 * Return true if Apple Pay is enabled for the cart page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.41
 * @return boolean
 */
function bwc_applepay_cart_checkout_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_ApplePay_Payment_Gateway::get_option(sections) and check if in array' );
	/**
	 *
	 * @var WC_Braintree_ApplePay_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_applepay'];
	return $gateway->is_available() && in_array( 'cart', $gateway->get_option( 'sections' ) );
}

/**
 * Return true if Apple Pay is enabled on product pages
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @since 2.6.52
 * @return boolean
 */
function bwc_applepay_product_checkout_enabled() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_ApplePay_Payment_Gateway::product_checkout_enabled()' );
	/**
	 *
	 * @var WC_Braintree_ApplePay_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()['braintree_applepay'];
	return $gateway->product_checkout_enabled();
}

/**
 * Return an array of Apple Pay formatted line items to be shown in the Apple Wallet
 *
 * @since 2.6.41
 * @package Braintree/DeprecatedFunctions
 */
function bwc_applepay_get_line_items() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Braintree_ApplePay_Payment_Gateway::get_line_items' );
	return WC()->payment_gateways()->payment_gateways()['braintree_applepay']->get_line_items();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwc_get_error_messages() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_error_messages' );
	return wc_braintree_get_error_messages();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwc_get_combined_error_messages() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_error_messages' );
	return wc_braintree_get_error_messages();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $type
 * @return unknown
 */
function bfwc_error_code_type_nicename( $type ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return $type;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $result
 * @return mixed
 */
function bfwc_get_error_message( $result ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_errors_from_object' );
	return wc_braintree_errors_from_object( $result );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $transaction
 * @return string
 */
function braintree_get_payment_token_from_transaction( $transaction ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_create_payment_token_from_transaction' );
	return wc_braintree_create_payment_token_from_transaction( $transaction, '' )->get_token();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $transaction
 * @return mixed
 */
function braintree_get_payment_method_title_from_transaction( $transaction ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_method_title' );
	$token = wc_braintree_create_payment_token_from_transaction( $transaction, '' );
	return $token->get_payment_method_title();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $payment_method
 */
function braintree_get_payment_method_title_from_method( $payment_method ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_method_title' );
	$token = wc_braintree_create_payment_token( $payment_method, '' );
	return $token->get_payment_method_title();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $payment_method
 */
function braintree_get_payment_method_title_from_method_details( $payment_method ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_method_title' );
	$token = wc_braintree_create_payment_token( $payment_method, '' );
	return $token->get_payment_method_title();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param number $user_id
 * @param unknown $token
 * @param unknown $env
 * @return string
 */
function braintree_get_payment_title_from_token( $user_id, $token, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $method
 * @return string
 */
function braintree_get_payment_method_title_from_array( $method ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $token
 * @return string
 */
function braintree_get_payment_method_from_token( $token ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int $user_id
 * @param string $env
 */
function braintree_get_user_payment_methods( $user_id, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return array();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $env
 */
function braintree_delete_user_payment_methods( $user_id, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $token
 * @param unknown $env
 */
function braintree_delete_user_payment_method( $user_id, $token, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $payment_method
 * @param unknown $env
 */
function braintree_save_user_payment_method( $user_id, $payment_method, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $payment_method
 */
function braintree_payment_method_to_array( $payment_method ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return array();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $transaction
 * @param unknown $env
 */
function braintree_save_payment_method_from_transaction( $user_id, $transaction, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $methods
 * @param unknown $env
 */
function braintree_save_user_payment_methods( $user_id, $methods, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $methods
 * @return mixed
 */
function braintree_get_default_method( $methods ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return reset( $methods );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $user_id
 * @param unknown $env
 * @return mixed|boolean|string|unknown
 */
function braintree_get_customer_id( $user_id, $env = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_customer_id' );
	return wc_braintree_get_customer_id( $user_id, $env );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function braintree_get_payment_method_formats() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_payment_method_formats' );
	return wc_braintree_payment_method_formats();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string[]
 */
function braintree_get_method_uris() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$urls = array(
		'paypal'                       => 'payment-methods/paypal.svg',
		'visa'                         => 'payment-methods/visa.svg',
		'mastercard'                   => 'payment-methods/master_card.svg',
		'american_express'             => 'payment-methods/amex.svg',
		'discover'                     => 'payment-methods/discover.svg',
		'maestro'                      => 'payment-methods/maestro.svg',
		'jcb'                          => 'payment-methods/jcb.svg',
		'diners_club'                  => 'payment-methods/diners_club_international.svg',
		'apple_pay'                    => 'applepay/apple_pay_mark.svg',
		'apple_pay_-_visa'             => 'applepay/apple_pay_mark.svg',
		'apple_pay_-_mastercard'       => 'applepay/apple_pay_mark.svg',
		'apple_pay_-_american_express' => 'applepay/apple_pay_mark.svg',
		'apple_pay_-_discover'         => 'applepay/apple_pay_mark.svg',
		'googlepay_visa'               => 'googlepay/google_pay_standard.svg',
		'googlepay_american_express'   => 'googlepay/google_pay_standard.svg',
		'googlepay_discover'           => 'googlepay/google_pay_standard.svg',
		'googlepay_mastercard'         => 'googlepay/google_pay_standard.svg',
		'googlepay'                    => 'googlepay/google_pay_standard.svg',
	);
	return $urls;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $type
 * @param string $path
 * @return string
 */
function braintree_get_method_url( $type, $path = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$path = $path ? $path : braintree()->assets_path() . 'img/';
	$uris = braintree_get_method_uris();
	return isset( $uris[ $type ] ) ? $path . $uris[ $type ] : '';
}

if ( ! function_exists( 'braintree_nonce_field' ) ) {

	/**
	 *
	 * @deprecated
	 *
	 * @package Braintree/DeprecatedFunctions
	 *          Echo an input field for a braintree payment_method_nonce.
	 */
	function braintree_nonce_field( $name = 'payment_method_nonce', $value = '' ) {
		$field = '<input type="hidden" class="bfwc-nonce-value" id="' . $name . '" name="' . $name . '" value="' . $value . '"/>';
		echo $field;
	}
}
if ( ! function_exists( 'braintree_payment_token_field' ) ) {

	/**
	 *
	 * @deprecated
	 *
	 * @package Braintree/DeprecatedFunctions
	 * @param unknown $id
	 * @param string $token
	 */
	function braintree_payment_token_field( $id, $token = '' ) {
		$field = '<input type="hidden" class="bfwc-payment-method-token" id="' . $id . '" name="' . $id . '" value="' . $token . '"/>';
		echo $field;
	}
}
if ( ! function_exists( 'braintree_device_data_field' ) ) {

	/**
	 * Echo an input field for a braintree device data hidden field.
	 *
	 * @deprecated
	 *
	 * @package Braintree/DeprecatedFunctions
	 * @param string $name
	 */
	function braintree_device_data_field( $name = 'braintree_device_data' ) {
		$field = '<input type="hidden" class="bfwc-device-data" id="' . $name . '" name="' . $name . '"/>';
		echo $field;
	}
}
if ( ! function_exists( 'braintree_hidden_field' ) ) {

	/**
	 *
	 * @deprecated
	 *
	 * @package Braintree/DeprecatedFunctions
	 * @since 2.6.23
	 * @param string $name
	 * @param string $selector
	 */
	function braintree_hidden_field( $name, $selector ) {
		echo '<input type="hidden" class="' . $selector . '" name="' . $name . '"/>';
	}
}
