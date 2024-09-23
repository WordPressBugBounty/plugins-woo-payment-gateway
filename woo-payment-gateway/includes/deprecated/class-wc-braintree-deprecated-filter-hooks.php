<?php
defined( 'ABSPATH' ) || exit();

/**
 * Deprecated filter hooks
 * @since 3.0.0
 * @package Braintree/Classes
 * @author User
 *
 */
class WC_Braintree_Deprecated_Filter_Hooks extends WC_Deprecated_Filter_Hooks {

	protected $deprecated_hooks = array(
		'wc_braintree_print_scripts'                      => 'bfwc_enqeue_scripts_for_page',
		'wc_braintree_get_custom_form_fields'             => 'braintree_woocommerce_custom_form_fields',
		'wcs_braintree_billing_interval_string'           => 'bfwcs_billing_interval_string',
		'wcs_braintree_billing_intervals'                 => 'braintree_subscription_internvals',
		'wcs_braintree_get_price_string'                  => 'bfwcs_get_price_string',
		'wcs_braintree_subscription_length_string'        => 'braintree_subscription_lengths',
		'wcs_braintree_billing_periods_string'            => 'bfwc_billing_periods_string',
		'wcs_braintree_get_subscriptions_for_order'       => 'bfwcs_get_subscriptions_for_order',
		'wcs_braintree_get_subscription'                  => 'bfwcs_get_subscription',
		'wcs_braintree_cart_subtotal_string'              => 'bfwcs_cart_subtotal_string',
		'wcs_braintree_get_interval_string'               => 'bfwcs_get_interval_string',
		'wcs_braintree_get_length_string'                 => 'bfwcs_get_length_string',
		'wcs_braintree_get_product_price_html'            => 'bfwcs_get_product_price_html',
		'wcs_braintree_calculate_next_payment_date'       => 'bfwcs_calculate_next_payment_date',
		'wcs_braintree_get_subscription_statuses'         => 'bfwcs_subscription_statuses',
		'wcs_braintree_get_subscription_actions'          => 'bfwc_get_subscription_actions',
		'wcs_braintree_subscription_user_actions'         => 'bfwc_subscription_user_actions',
		'wc_braintree_get_error_messages'                 => 'bfwc_get_error_messages',
		'wc_braintree_errors_from_object'                 => 'bfwc_get_error_message',
		'wc_braintree_payment_method_title'               => 'braintree_get_payment_method_title_from_method',
		'wc_braintree_recurring_payment_success'          => 'wcs_braintree_recurring_payment_success',
		'wc_braintree_recurring_payment_failure'          => 'wcs_braintree_recurring_payment_failure',
		'wc_braintree_subscription_submit_for_settlement' => 'wcs_braintree_subscription_submit_for_settlement'
	);

	protected $deprecated_version = array(
		'braintree_woocommerce_braintree_payment_gateway_order_attributes'   => '3.0.0',
		'braintree_woocommerce_braintree_paypal_payments_order_attributes'   => '3.0.0',
		'braintree_woocommerce_braintree_googlepay_gateway_order_attributes' => '3.0.0',
		'braintree_woocommerce_braintree_applepay_payments_order_attributes' => '3.0.0',
		'braintree_woocommerce_custom_form_fields'                           => '3.0.0',
		'bfwcs_billing_interval_string'                                      => '3.0.0',
		'braintree_subscription_internvals'                                  => '3.0.0',
		'braintree_subscription_lengths'                                     => '3.0.0',
		'bfwc_billing_periods_string'                                        => '3.0.0',
		'bfwcs_get_subscriptions_for_order'                                  => '3.0.0',
		'bfwcs_get_subscription'                                             => '3.0.0',
		'bfwcs_cart_subtotal_string'                                         => '3.0.0',
		'bfwcs_get_interval_string'                                          => '3.0.0',
		'bfwcs_get_length_string'                                            => '3.0.0',
		'bfwcs_get_product_price_html'                                       => '3.0.0',
		'bfwcs_calculate_next_payment_date'                                  => '3.0.0',
		'bfwcs_subscription_statuses'                                        => '3.0.0',
		'bfwc_get_subscription_actions'                                      => '3.0.0',
		'bfwc_subscription_user_actions'                                     => '3.0.0',
		'bfwc_get_error_messages'                                            => '3.0.0',
		'bfwc_get_error_message'                                             => '3.0.0',
		'bfwc_get_error_message'                                             => '3.0.0',
		'bfwc_enqeue_scripts_for_page'                                       => '3.0.0',
		'wcs_braintree_recurring_payment_success'                            => '3.2.1',
		'wcs_braintree_subscription_submit_for_settlement'                   => '3.2.1'
	);
}

new WC_Braintree_Deprecated_Filter_Hooks();
