<?php
defined( 'ABSPATH' ) || exit();

/**
 * Deprecated functions that were used for the plugin's native subscription functionality.
 */

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 *
 *          Functions specific to Braintree Subscription functionality when WC subscriptions is not active.
 */
function bfwcs_billing_interval_string( $interval = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_billing_interval_string' );
	return wcs_braintree_billing_interval_string( $interval );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwcs_billing_intervals() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_billing_intervals' );
	return wcs_braintree_billing_intervals();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $total_string
 * @param unknown $interval
 * @param unknown $period
 * @return mixed
 */
function bfwcs_get_price_string( $total_string, $interval, $period ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_price_string' );
	return wcs_braintree_get_price_string( $total_string, $interval, $period );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwc_subscription_length_string() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_subscription_length_string' );
	return wcs_braintree_subscription_length_string();
}

/**
 * Return an array of options that can be used to represent formatted billing periods.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bfwc_billing_periods_string( $type = 'singular', $period = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_billing_periods_string' );
	return wcs_braintree_billing_periods_string( $type, $period );
}

/**
 * Given an order object or order_id, return true if there are Braintree subscriptions associated with the order.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed int|WC_Order $order
 */
function bfwcs_order_contains_subscription( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_order_contains_subscription' );
	return wcs_braintree_order_contains_subscription( $order );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int|WC_Order $order
 * @return WC_Braintree_Subscription[]
 */
function bfwcs_get_subscriptions_for_order( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscriptions_for_order' );
	return wcs_braintree_get_subscriptions_for_order( $order );
}

/**
 * Return a subscription object from the given id.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int $id
 * @return WC_Braintree_Subscription
 */
function bfwcs_get_subscription( $id ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscription' );
	return wcs_braintree_get_subscription( $id );
}

/**
 * Return true if the product is a Braintree Subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int|WC_Product $product
 */
function bfwcs_product_is_subscription( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_product_is_subscription' );
	return wcs_braintree_product_is_subscription( $product );
}

/**
 * Return the string for the subscription price.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Cart $cart
 * @return <div> <strong>$20 every month for 14 months</strong>
 *         </div>
 */
function bfwcs_cart_subtotal_string( $cart ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_subtotal_string' );
	return wcs_braintree_cart_subtotal_string( $cart );
}

/**
 * Returns the interval string for the given interval and period.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int $interval
 * @param string $period
 * @return string every 3rd month for 12 months. / month for 9 months.
 */
function bfwcs_get_interval_string( $interval, $period ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_interval_string' );
	return wcs_braintree_get_interval_string( $interval, $period );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $interval
 * @param unknown $period
 * @return string
 */
function bfwcs_frontend_interval_string( $interval, $period ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_frontend_interval_string' );
	return wcs_braintree_frontend_interval_string( $interval, $period );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $period
 * @return string the text representation of the period provided.
 */
function bfwcs_get_period_string( $period = 'month' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_period_string' );
	return wcs_braintree_get_period_string();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param int $length
 * @param string $period
 *          day, month, etc
 * @return string
 */
function bfwcs_get_length_string( $length, $period ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_period_string' );
	return wcs_braintree_get_length_string( $length, $period );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Product $product
 * @param string $price
 * @param int $quantity
 *          quantity of the products that should be included in the product price.
 */
function bfwcs_get_product_price_html( $product, $price = '', $quantity = 1 ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_product_price_html' );
	return wcs_braintree_get_product_price_html( $product, $price, $quantity );
}

/**
 * Create the subscription within the WordPress database.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $args
 */
function bfwcs_create_subscription( $args ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_create_subscription' );
	return wcs_braintree_create_subscription( $args );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $subscription
 * @return WP_Error|boolean|WC_Order
 */
function bfwcs_create_renewal_order( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_create_renewal_order' );
	return wcs_braintree_create_renewal_order( $subscription );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed int|WC_Braintree_Subscription $subscription
 */
function bfwcs_create_order_from_subscription( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_create_order_from_subscription' );
	return wcs_braintree_create_order_from_subscription( $subscription );
}

/**
 * Copy meta data from one order to another.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 *
 * @param WC_Order $from
 * @param WC_Order $to
 */
function bfwcs_copy_order_meta( $from, $to ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_copy_order_meta' );
	wcs_braintree_copy_order_meta( $from, $to );
}

/**
 * Return true if products that are the same can be combined into one subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bfwcs_can_combine_subscriptions() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'braintree()->subscription_settings->is_active(\'combine\')' );
	return braintree()->subscription_settings->is_active( 'combine' );
}

/**
 * Determine if the WC_Cart contains a Braintree subscription product.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bfwcs_cart_contains_subscriptions() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_contains_subscription' );
	return wcs_braintree_cart_contains_subscription();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Product $product
 * @return mixed string|WP_Error
 */
function bfwcs_get_plan_from_product( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_plan_from_product' );
	return wcs_braintree_get_plan_from_product( $product );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return DateTime
 */
function bfwcs_calculate_start_date() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_calculate_start_date' );
	return wcs_braintree_calculate_start_date();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Product $product
 * @return DateTime
 */
function bfwcs_calculate_first_payment_date( $trial_period = 'day', $trial_length = null ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_calculate_first_payment_date' );
	return wcs_braintree_calculate_first_payment_date( $trial_period, $trial_length );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $date
 * @param unknown $period
 * @param unknown $interval
 */
function bfwcs_calculate_next_payment_date( $date, $period, $interval ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_calculate_next_payment_date' );
	return wcs_braintree_calculate_next_payment_date( $date, $period, $interval );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param number $subscription_length
 * @param string $subscription_period
 * @param DateTime $first_payment
 * @return DateTime
 */
function bfwcs_calculate_end_date( $subscription_length = 0, $subscription_period = 'month', $trial_period = 'day', $trial_length = 0, $timezone = 'UTC' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_calculate_end_date' );
	return wcs_braintree_calculate_end_date( $subscription_length, $subscription_period, $trial_period, $trial_length );
}

/**
 * Generate the html for the cart shipping displayed on the cart page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Cart $cart
 */
function bfwcs_cart_shipping_total( $cart ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_shipping_total' );
	return wcs_braintree_cart_shipping_total( $cart );
}

/**
 * Return html for the recurring total portion displayed on the cart page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Cart $cart
 */
function bfwcs_cart_recurring_total_html( $cart ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_recurring_total_html' );
	return wcs_braintree_cart_recurring_total_html( $cart );
}

/**
 * Return html for the tax total portion displayed on the cart page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          $tax
 * @param WC_Cart $cart
 */
function bfwcs_cart_tax_total_html( $tax, $cart ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_tax_total_html' );
	return wcs_braintree_cart_tax_total_html( $tax, $cart );
}

/**
 * Return a formatted date.
 * The timezone given is the timezone in which the formatted string will be converted to. All given times
 * should be in UTC to ensure proper conversion.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed DateTime|string $date
 * @param string $timezone
 * @param string $format
 */
function bfwcs_cart_formatted_date( $date, $timezone = 'UTC', $format = 'F j, Y' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_cart_formatted_date' );
	return wcs_braintree_cart_formatted_date( $date, $timezone, $format );
}

/**
 * Return an array of subscription statuses.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwc_get_subscription_statuses() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscription_statuses' );
	return wcs_braintree_get_subscription_statuses();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $status
 * @param unknown $values
 */
function bfwc_register_subscription_status( $status, $values ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 * Return the nice name for the status provided.
 * If the status is invalid, then the status provided will
 * be returned.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $status
 */
function bfwc_get_subscription_status_name( $status ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscription_status_name' );
	return wcs_braintree_get_subscription_status_name( $status );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 * @param WC_Braintree_Subscription $subscription
 * @param string $address_type
 */
function bfwc_copy_address_from_order( $order, $subscription, $address_type = 'all' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_copy_address_from_order' );
	return wcs_braintree_copy_address_from_order( $order, $subscription, $address_type );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Product $product
 */
function bfwc_get_product_descriptors( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return array();
}

/**
 * Return the configured gateway timezone.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return string
 */
function bfwc_get_gateway_timezone() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_timezone_string' );
	return wc_timezone_string();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return mixed
 */
function bfwcs_get_subscription_statuses() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscription_statuses_for_registration' );
	return wcs_braintree_get_subscription_statuses_for_registration();
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $subscription
 * @return WC_Order[]
 */
function bfwcs_get_related_orders( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_related_orders' );
	return wcs_braintree_get_related_orders( $subscription );
}

/**
 * Return true if the order is actually a subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed WC_Order|int $order
 */
function bfwcs_order_is_subscription( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	if ( ! is_object( $order ) ) {
		$order = wc_get_order( $order );
	}
	return $order->get_type() === 'bfwc_subscription';
}

/**
 * Retrieve subscriptions for the given user.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param number $user_id
 * @return WC_Braintree_Subscription[]
 */
function bfwcs_get_subscriptions_for_user( $user_id = 0 ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscriptions_for_user' );
	return wcs_braintree_get_subscriptions_for_user( $user_id );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Braintree_Subscription $subscription
 */
function bfwcs_get_subscription_actions( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_subscription_actions' );
	return wcs_braintree_get_subscription_actions( $subscription );
}

/**
 * return an array of user actions that pertain to the subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Braintree_Subscription $subscription
 */
function bfwc_subscription_user_actions( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_subscription_user_actions' );
	return wcs_braintree_subscription_user_actions( $subscription );
}

/**
 * Return true if the current request is for the subscription change payment method.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bfwcs_is_change_payment_method() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_is_change_payment_method_request' );
	return wcs_braintree_is_change_payment_method_request();
}

/**
 * Check to see if a payment method can be deleted.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WP_Error $error
 * @param string $token
 */
function bfwc_can_delete_payment_method( $error, $token ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	if ( ! wcs_braintree_active() ) {
		global $wpdb;

		$statuses = bfwcs_get_subscription_statuses();
		unset( $statuses['wc-cancelled'] );
		$statuses = array_keys( $statuses );

		$in_array = vsprintf( implode( ',', array_fill( 0, count( $statuses ), "'%s'" ) ), $statuses );

		// query that finds subscriptions that use the provided payment method token.
		$query = $wpdb->prepare(
			"SELECT COUNT(ID) FROM $wpdb->posts AS posts INNER JOIN $wpdb->postmeta AS postmeta
				ON posts.ID = postmeta.post_id WHERE posts.post_type = 'bfwc_subscription' AND posts.post_status IN ($in_array)
				AND postmeta.meta_key = '_payment_method_token' AND postmeta.meta_value = %s",
			$token
		);
		$count = $wpdb->get_var( $query );

		if ( $count ) {
			$message = sprintf( _n( 'There is a subscription associated with this payment method.', 'There are %s subscriptions associated with this payment method.', $count, 'woo-payment-gateway' ), $count );
			$error->add( 'method-delete', $message );
		}
	}
	return $error;
}

/**
 * Return true if the current request is for the pay subscription page.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bfwcs_is_pay_for_subscription_request() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	global $wp;
	return ! empty( $wp->query_vars['pay-subscription'] );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $template
 * @param array $args
 */
function bfwc_get_template( $template, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_get_template' );
	wc_braintree_get_template( $template, $args );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param unknown $classes
 * @return unknown
 */
function bfwc_add_body_class( $classes ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return $classes;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 * @param WC_Braintree_Subscription[] $subscription
 */
function bfwcs_calculate_order_total( $order, $subscriptions ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $currency
 * @return mixed
 */
function bfwcs_get_currency_symbol( $currency ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return $currency;
}

/**
 * Return an array of UTC timezones
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bfwc_get_timezones() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$timezones     = array();
	$timezone_list = timezone_identifiers_list();
	foreach ( $timezone_list as $zone ) {
		try {
			$date_time_zone     = new DateTimeZone( $zone );
			$timezones[ $zone ] = sprintf( '%s - UTC Offset: %s hrs', $zone, $date_time_zone->getOffset( new DateTime() ) / 3600 );
		} catch ( Exception $e ) {
		}
	}
	return $timezones;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bfwcs_subscription_link_active() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$endpoint = braintree()->subscription_settings->get_option( 'subscriptions_endpoint' );
	return ! empty( $endpoint );
}
