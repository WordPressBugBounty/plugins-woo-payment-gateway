<?php
defined( 'ABSPATH' ) || exit();

/**
 * Deprecated WooCommerce Subscription functions that were used prior to version 3.0.0 when the plugin
 * supported the ablity to create a WooCommerce Subscription using Braintree's subscription API.
 */

/**
 * Return true if Braintree subscriptions is active.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 *
 */
function bwcs_subscriptions_active() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_active' );
	return wcs_braintree_active();
}

/**
 * Return true if subscriptions are to be created using Braintree's recurring
 * plan options.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwcs_braintree_subscriptions_active() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wc_braintree_subscriptions_active' );
	return wc_braintree_subscriptions_active();
}

/**
 * Return true if the WC_Order is a Braintree_Subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 */
function bwcs_order_is_subscription( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', '$order->get_meta ( \'subscription_type\', true ) === \'braintree\'' );
	return $order->get_meta( 'subscription_type', true ) === 'braintree';
}

/**
 * Function that determines if there are any subscriptions associated with the
 * payment method.
 * If so, then it returns false. Subscriptions with a status of cancelled are
 * not considered.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WP_Error $error
 * @param string $token
 * @return boolean
 */
function bwcs_can_delete_payment_method( $error, $token ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Warning is no longer issued.' );
	global $wpdb;

	$statuses = wcs_get_subscription_statuses();
	unset( $statuses['wc-cancelled'] );
	unset( $statuses['wc-pending-cancel'] );

	$in_statuses = implode( "','", array_fill( 0, count( $statuses ), '%s' ) );
	$in_statuses = vsprintf( $in_statuses, array_keys( $statuses ) );

	$query = $wpdb->prepare(
		"SELECT id FROM $wpdb->posts AS posts INNER JOIN $wpdb->postmeta AS postmeta
			ON posts.ID = postmeta.post_id WHERE posts.post_type = 'shop_subscription'
			AND posts.post_status IN ('{$in_statuses}') AND postmeta.meta_key = '_payment_method_token' AND postmeta.meta_value = %s",
		$token
	);

	$results = $wpdb->get_results( $query );

	if ( $results ) {
		$message = sprintf( _n( 'There is %s subscription associated with this payment method.', 'There are %s subscriptions associated with this payment method.', count( $results ), 'woo-payment-gateway' ), count( $results ) );
		$error->add( 'method-delete-error', $message );
	}
	return $error;
}

/**
 * Return true if the request is for a subscription payment method change.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwcs_is_change_payment_method() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment' );
	return wcs_braintree_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
	// return isset ( $_GET[ 'change_payment_method' ] );
}

/**
 * Return true if the request is for when a subscription payment method has been
 * changed.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 */
function bwcs_is_woocommerce_change_payment() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'did_action(\'woocommerce_subscriptions_pre_update_payment_method\')' );
	return did_action( 'woocommerce_subscriptions_pre_update_payment_method' );
	// return isset ( $_POST[ 'woocommerce_change_payment' ] );
}

/**
 * Return true if the cart item contains a Braintree Subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param array $cart_item
 * @return boolean
 */
function bwcs_cart_item_contains_subscription( $cart_item ) {
	$product = $cart_item['data'];

	return bwcs_product_is_subscription( $product );
}

/**
 * Returns true if a product is a Braintree Subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed int|WC_Product $product_id
 * @return boolean
 */
function bwcs_product_is_subscription( $product ) {
	if ( ! is_object( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( bwcs_subscriptions_active() ) {
		// backwards compatability.
		return WC_Subscriptions_Product::is_subscription( $product ) && bwc_get_product_property( 'braintree_subscription', $product ) === 'yes';
	}
	return $product->get_meta( 'braintree_subscription', true ) === 'yes';
}

/**
 * Returns true of the order contains a Braintree Subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed int|WC_Order $order
 */
function bwcs_order_contains_subscription( $order ) {
	if ( ! is_object( $order ) ) {
		$order = wc_get_order( $order );
	}

	foreach ( $order->get_items() as $k => $item ) {

		$product = $order->get_product_from_item( $item );
		if ( $product ) {
			if ( bwcs_product_is_subscription( $product ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Return the Braintree Subscription plan associated with the WC product.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param
 *          mixed int|Object $product_id
 * @return mixed boolean|string - false if plans doesn't exist.
 */
function bwcs_get_plan_from_product( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'wcs_braintree_get_plan_from_product' );
	return wcs_braintree_get_plan_from_product( $product );
}

/**
 * Calculate the WC_Order total.
 * If the order contains subscriptions which start immediately, then their totals
 * should be subtracted from the order.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 * @param WC_Subscription $subscription
 */
function bwcs_calculate_order_total( $order, $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'Do not use this function' );
}

/**
 * Return true if the order has a coupon.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $order
 */
function bwcs_order_has_coupon( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return $order->get_item_count( 'coupon' ) > 0;
}

/**
 * Remove the items associated with the subscription from the order.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 */
function bwcs_get_cart_item_key( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	foreach ( WC()->cart->cart_contents as $cart_item_key->$cart_contents ) {
		if ( $subscription->has_product( $cart_contents['product_id'] ) ) {
			return $cart_item_key;
		}
	}
	return '';
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 */
function bwcs_get_product_from_subscription( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	foreach ( $subscription->get_items() as $k => $item ) {
		if ( $product = $subscription->get_product_from_item( $item ) ) {
			return $product;
		}
	}
	return false;
}

/**
 * Return true of the subscription object is associated with a Braintree subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Order $subscription
 */
function bwcs_is_braintree_subscription( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	if ( is_object( $subscription ) ) {
		$id = $subscription->get_id();
	} else {
		$id = $subscription;
	}
	return get_post_meta( $id, '_subscription_type', true ) === 'braintree';
}

/**
 * Return true if products that are the same can be combined into one subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwcs_can_combine_subscriptions() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return false;
}

/**
 * Return the DateTime object that represents the subscription start date.
 * The DateTime object is created using the next_payment for synchronized subscriptions and the start_date for non trial subscriptions.
 * Date and the timezone of the site is then used to determine the local time. A new DateTime object is then creating using that localtime
 * and the timezone is set to UTC, which does not affect the time.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 * @param string $format
 *          - default 'Y-m-d H:i:s'
 * @return DateTime $date
 */
function bwcs_start_date_in_site_timezone( $subscription, $format = 'Y-m-d H:i:s' ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$synchronized = get_post_meta( $subscription->get_id(), '_contains_synced_subscription', true ) === 'true';
	$trial_date   = bwcs_get_subscription_date( 'trial_end', $subscription );

	if ( $synchronized || $trial_date ) {
		$date_type = 'next_payment';
	} else {
		$date_type = 'start';
	}

	$tz = wc_timezone_string();
	if ( $tz ) {
		// all subscription dates stored in the database are UTC
		$date = new DateTime( bwcs_get_subscription_date( $date_type, $subscription ), new DateTimeZone( 'UTC' ) );
		$date->setTimezone( new DateTimeZone( $tz ) );
		$date = new DateTimeZone( $date->format( $format ), new DateTimeZone( 'UTC' ) );
	} else {
		$offset      = get_option( 'gmt_offset' );
		$timestamp   = $subscription->get_time( $date_type );
		$date_string = gmdate( $format, $timestamp + $offset * HOUR_IN_SECONDS );
		$date        = DateTime::createFromFormat( $format, $date_string, new DateTimeZone( 'UTC' ) );
	}
	return $date;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 * @return DateTime
 */
function bwcs_get_start_date_in_utc( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$synchronized = get_post_meta( $subscription->get_id(), '_contains_synced_subscription', true ) === 'true';
	$trial_date   = bwcs_get_subscription_date( 'trial_end', $subscription );

	if ( $synchronized || $trial_date ) {
		$date_type = 'next_payment';
	} else {
		$date_type = 'start';
	}
	// returns date in UTC.
	$start_date = bwcs_get_subscription_date( $date_type, $subscription );
	return DateTime::createFromFormat( 'Y-m-d H:i:s', $start_date, new DateTimeZone( 'UTC' ) );
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return DateTime
 */
function bwcs_get_current_date_time() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$tz = wc_timezone_string();
	if ( $tz ) {
		$date = new DateTime( 'now', new DateTimeZone( $tz ) );
	} else {
		$offset      = get_option( 'gmt_offset' );
		$date_string = gmdate( 'Y-m-d H:i:s', time() + $offset * HOUR_IN_SECONDS );
		$date        = DateTime::createFromFormat( $format, $date_string, new DateTimeZone( 'UTC' ) );
	}
	return $date;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 */
function bwcs_subscription_has_trial( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return (bool) $subscription->get_time( 'trial_end' );
}

/**
 * Return true if a subscription contains a synced product.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Subscription $subscription
 */
function bwcs_subscription_is_synched( $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return $subscription->get_meta( 'contains_synced_subscription' ) === 'true';
}

/**
 * Return true if the current request is for a payment method update on a failed renewal order.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @return boolean
 */
function bwcs_is_paid_for_failed_renewal_request() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return (bool) did_action( 'woocommerce_subscriptions_paid_for_failed_renewal_order' );
}

/**
 * Return the number of billing cycles for the subscription.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param WC_Product $product
 * @return number
 */
function bwcs_get_num_of_billing_cycles( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return floor( WC_Subscriptions_Product::get_length( $product ) / WC_Subscriptions_Product::get_interval( $product ) );
}

/**
 * Return true if WC Subscription scheduled payment should be retried if there is an exception that
 * prevents the recurring payment from being processed.
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @deprecated - since 2.6.17
 *
 * @return boolean
 */
function bwcs_retry_after_exception() {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	return false;
}

/**
 *
 * @deprecated
 *
 * @package Braintree/DeprecatedFunctions
 * @param string $type
 * @param WC_Subscription $subscription
 */
function bwcs_get_subscription_date( $type, $subscription ) {
	wc_deprecated_function( __FUNCTION__, '3.0.0', 'no longer supported' );
	$type = wcs_normalise_date_type_key( $type, false );
	return $subscription->get_date( $type );
}
