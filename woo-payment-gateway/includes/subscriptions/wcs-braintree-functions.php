<?php

defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @param unknown $interval
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wcs_braintree_billing_interval_string( $interval = null ) {
	$intervals = wcs_braintree_billing_intervals();
	$string    = array();
	foreach ( $intervals as $index => $text ) {
		$remainder = $index % 10;
		if ( ! in_array( $index, array( 11, 12, 13 ) ) ) {
			switch ( $remainder ) {
				case 1:
					$suffix = __( 'st', 'woo-payment-gateway' );
					break;
				case 2:
					$suffix = __( 'nd', 'woo-payment-gateway' );
					break;
				case 3:
					$suffix = __( 'rd', 'woo-payment-gateway' );
					break;
				default:
					$suffix = __( 'th', 'woo-payment-gateway' );
					break;
			}
		} else {
			$suffix = __( 'th', 'woo-payment-gateway' );
		}
		$string[ $index ] = sprintf( $intervals[ $index ], $index, $suffix );
	}

	return apply_filters( 'wcs_braintree_billing_interval_string', $interval ? $string[ $interval ] : $string, $interval );
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wcs_braintree_subscription_length_string() {
	$lengths = array();

	$lengths[0] = __( 'never expires', 'woo-payment-gateway' );
	foreach ( range( 1, 48 ) as $i ) {
		$lengths[ $i ] = sprintf( _n( '%s month', '%s months', $i, 'woo-payment-gateway' ), $i );
	}

	return apply_filters( 'wcs_braintree_subscription_length_string', $lengths );
}

/**
 * Return an array of options that can be used to represent formatted billing periods.
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_billing_periods_string( $type = 'singular', $period = null ) {
	$periods = wcs_braintree_billing_periods( $type );

	return apply_filters( 'wcs_braintree_billing_periods_string', $period == null ? 'month' : $periods[ $period ], $type, $period );
}

/**
 *
 * @param string $type
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree\Functions
 */
function wcs_braintree_billing_periods( $type = 'singular' ) {
	$i       = $type === 'plural' ? 2 : 1;
	$periods = array(
		'day'   => _n( 'day', 'days', $i, 'woo-payment-gateway' ),
		'month' => _n( 'month', 'months', $i, 'woo-payment-gateway' ),
	);

	return apply_filters( 'wcs_braintree_billing_periods', $periods );
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wcs_braintree_billing_intervals() {
	$count     = 24;
	$intervals = array();
	for ( $i = 1; $i <= $count; $i ++ ) {
		$intervals[ $i ] = _n( 'every month', 'every %1$s%2$s month', $i, 'woo-payment-gateway' );
	}

	// Allow plugins to add more intervals if necessary.
	return apply_filters( 'wcs_braintree_billing_intervals', $intervals );
}

/**
 *
 * @param string $env
 *
 * @since   3.0.0
 * @return mixed|boolean
 * @package Braintree/Functions
 */
function wcs_braintree_get_plans( $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;

	return get_option( "wc_braintree_{$env}_plans", array() );
}

/**
 *
 * @param string $env
 *
 * @since   3.0.0
 * @return string[]
 * @package Braintree/Functions
 */
function wcs_braintree_get_plan_options( $env = '' ) {
	$plans   = wcs_braintree_get_plans( $env );
	$options = array();
	foreach ( $plans as $plan ) {
		$options[ $plan['id'] ] = sprintf( '%1$s (%2$s)', $plan['id'], $plan['currencyIsoCode'] );
	}

	return $options;
}

/**
 *
 * @param WC_Product $product
 * @param number     $quantity
 * @param string     $price_html
 *
 * @since   3.0.0
 * @return string
 * @package Braintree/Functions
 */
function wcs_braintree_get_billing_schedule_string( $product, $quantity = 1, $price_html = '' ) {
	$interval   = $product->get_subscription_period_interval();
	$period     = $product->get_subscription_period();
	$type       = $interval > 1 ? 'plural' : 'singluar';
	$price_html .= sprintf( __( ' every %1$s', 'woo-payment-gateway' ), $interval > 1 ? sprintf( '%1$s %2$s', $interval, wcs_braintree_billing_periods_string( $type, $period ) ) : wcs_braintree_billing_periods_string( $type, $period ) );
	if ( $product->get_subscription_length() ) {
		$price_html .= wcs_braintree_get_length_string( $product->get_subscription_length(), $product->get_subscription_period() );
	}
	if ( $product->get_subscription_trial_length() ) {
		$price_html .= sprintf( __( ' with a %1$s-%2$s free trial', 'woo-payment-gateway' ), $product->get_subscription_trial_length(), $product->get_subscription_trial_period() );
	}
	if ( $product->get_subscription_sign_up_fee() ) {
		$price_html .= sprintf( __( ' and a %1$s sign up fee', 'woo-payment-gateway' ), wc_price( $product->get_subscription_sign_up_fee() * $quantity ) );
	}

	return $price_html;
}

/**
 *
 * @param WC_Product $product
 * @param int        $price
 * @param int        $quantity
 *          quantity of the products that should be included in the product price.
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_product_price_html( $product, $price = 0, $quantity = 1 ) {
	if ( is_numeric( $price ) ) {
		$price = $price * $quantity;
	}
	$price_html = $price . '<span class="subscription-Details"> ' . wcs_braintree_get_billing_schedule_string( $product, $quantity ) . '</span>';

	return apply_filters( 'wcs_braintree_get_product_price_html', $price_html, $price, $product, $quantity );
}

/**
 *
 * @param int    $length
 * @param string $period
 *          day, month, etc
 *
 * @since   3.0.0
 * @return string
 * @package Braintree/Functions
 */
function wcs_braintree_get_length_string( $length, $period ) {
	$type = $length > 1 ? 'plural' : 'singular';

	return apply_filters( 'wcs_braintree_get_length_string', $length > 0 ? sprintf( __( ' for %1$s %2$s', 'woo-payment-gateway' ), $length, wcs_braintree_billing_periods_string( $type, $period ) ) : '', $length, $period );
}

/**
 * Return true if the product is a Braintree Subscription.
 *
 * @param int|WC_Product $product
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wcs_braintree_product_is_subscription( $product ) {
	if ( ! is_object( $product ) && ! is_int( $product ) ) {
		return false;
	}
	// ensure the product is an object.
	if ( ! is_object( $product ) ) {
		$product = wc_get_product( $product );
	}

	return apply_filters(
		'wcs_braintree_product_is_subscription',
		$product
		&& $product->is_type(
			array(
				'braintree-subscription',
				'braintree-variable-subscription',
				'braintree-subscription-variation',
			)
		)
	);
}

/**
 * Determine if the WC_Cart contains a Braintree subscription product.
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wcs_braintree_cart_contains_subscription() {
	if ( ! empty( WC()->cart->cart_contents ) ) {
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['data'];
			if ( wcs_braintree_product_is_subscription( $product ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 *
 * @param WC_Product $product
 *
 * @since   3.0.0
 * @return mixed string|WP_Error
 * @package Braintree/Functions
 */
function wcs_braintree_get_plan_from_product( $product ) {
	if ( ! is_object( $product ) ) {
		$product = wc_get_product( $product );
	}
	$product_plans = $product->get_plans();
	$plans         = wcs_braintree_get_plans();
	$currency      = get_woocommerce_currency();
	foreach ( $product_plans as $id ) {
		if ( isset( $plans[ $id ] ) && $plans[ $id ]['currencyIsoCode'] === $currency ) {
			return $id;
		}
	}

	return '';
}

/**
 *
 * @since   3.0.0
 * @return DateTime
 * @package Braintree/Functions
 */
function wcs_braintree_calculate_start_date() {
	$date = new DateTime( null, new DateTimeZone( 'UTC' ) );

	return $date;
}

/**
 * Calculate the first payment date of the subscription.
 * The date is returned in UTC.
 *
 * @param WC_Product $product
 *
 * @since   3.0.0
 * @return DateTime
 * @package Braintree/Functions
 */
function wcs_braintree_calculate_first_payment_date( $trial_period = 'day', $trial_length = null ) {
	$date_in_utc = new DateTime( null, new DateTimeZone( 'UTC' ) );
	// has trial period so add time.
	if ( $trial_length ) {
		switch ( $trial_period ) {
			case 'day':
				$date_in_utc->add( new DateInterval( 'P' . $trial_length . 'D' ) );
				break;
			case 'month':
				$date_in_utc->add( new DateInterval( 'P' . $trial_length . 'M' ) );
				break;
		}
	}

	return $date_in_utc;
}

/**
 *
 * @param DateTime $date
 * @param string   $period
 * @param string   $interval
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wcs_braintree_calculate_next_payment_date( $date, $period, $interval ) {
	$next_payment_date = clone $date;
	switch ( $period ) {
		case 'day':
			$next_payment_date->add( new DateInterval( 'P' . $interval . 'D' ) );
			break;
		case 'month':
			$period = 'M';
			// calculate the date.
			// set the day of month to first day that way adding months, won't affect february.
			$next_payment_date->setDate( $next_payment_date->format( 'Y' ), $next_payment_date->format( 'm' ), 1 );
			$next_payment_date->add( new DateInterval( "P{$interval}{$period}" ) );
			$current_day           = $date->format( 'j' ); // day of month, no leading zeros
			$days_in_date          = $date->format( 't' );
			$days_in_current_month = $next_payment_date->format( 't' ); // days in month

			/**
			 * current date's billing day exceeds the next billing dates days in month, or this is the last day.
			 */
			if ( $current_day > $days_in_current_month || $days_in_date === $current_day ) {
				// days of previous month were greater so add as last day.
				$next_payment_date->setDate( $next_payment_date->format( 'Y' ), $next_payment_date->format( 'm' ), $days_in_current_month );
			} else {
				$next_payment_date->setDate( $next_payment_date->format( 'Y' ), $next_payment_date->format( 'm' ), $current_day );
			}
			break;
		default:
			$period = 'M';
	}

	return apply_filters( 'wcs_braintree_calculate_next_payment_date', $next_payment_date, $date, $period, $interval );
}

/**
 *
 * @param number   $subscription_length
 * @param string   $subscription_period
 * @param DateTime $first_payment
 *
 * @since   3.0.0
 * @return DateTime
 * @package Braintree/Functions
 */
function wcs_braintree_calculate_end_date( $subscription_length = 0, $subscription_period = 'month', $trial_period = 'day', $trial_length = 0 ) {
	if ( ! $subscription_length ) {
		return 0;
	}
	$end_date = clone wcs_braintree_calculate_first_payment_date( $trial_period, $trial_length );

	$day_of_month        = $end_date->format( 'j' );
	$days_in_first_month = $end_date->format( 't' );

	// set date to first day of month
	$end_date->setDate( $end_date->format( 'Y' ), $end_date->format( 'm' ), 1 );

	switch ( $subscription_period ) {
		case 'month':
			$end_date->add( new DateInterval( 'P' . $subscription_length . 'M' ) );
			break;
	}

	if ( $trial_length ) {
		if ( $day_of_month > $end_date->format( 't' ) ) {
			$end_date->setDate( $end_date->format( 'Y' ), $end_date->format( 'm' ), $end_date->format( 't' ) );
		} else {
			$end_date->setDate( $end_date->format( 'Y' ), $end_date->format( 'm' ), $day_of_month );
		}
	} else {
		/**
		 * subscription started on last day of month or start day is greater than number of days in end month,
		 * so make sure end date is on last day of month
		 */
		if ( ( $day_of_month === $days_in_first_month && $day_of_month > 28 ) || $day_of_month > $end_date->format( 't' ) ) {
			$end_date->setDate( $end_date->format( 'Y' ), $end_date->format( 'm' ), $end_date->format( 't' ) );
		} else {
			$end_date->setDate( $end_date->format( 'Y' ), $end_date->format( 'm' ), $day_of_month );
		}
	}

	return $end_date;
}

/**
 * Return the string for the subscription price.
 *
 * @param WC_Cart $cart
 *
 * @return <div> <strong>$20 every month for 14 months</strong>
 *         </div>
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_cart_subtotal_string( $cart ) {
	$interval = $cart->subscription_period_interval;
	$period   = $cart->subscription_period;
	$text     = sprintf( '<span class="subscription-Details">%s</span>', wcs_braintree_frontend_interval_string( $interval, $period ) );
	if ( $length = $cart->subscription_length ) {
		$text .= wcs_braintree_get_length_string( $length, $period );
	}

	return apply_filters( 'wcs_braintree_cart_subtotal_string', sprintf( '%s %s', $cart->get_cart_subtotal(), $text ), $cart );
}

/**
 * Get recurring coupon html
 *
 * @param WC_Coupon $coupon
 * @param WC_Cart   $recurring_cart
 *
 * @since 3.2.1
 */
function wcs_braintree_cart_totals_coupon_html( $coupon, $recurring_cart ) {
	if ( is_string( $coupon ) ) {
		$coupon = new WC_Coupon( $coupon );
	}
	$discount      = $recurring_cart->get_coupon_discount_amount( $coupon->get_code(), $recurring_cart->display_cart_ex_tax );
	$discount_html = wcs_braintree_get_recurring_total_text( - 1 * $discount, $recurring_cart );

	if ( $coupon->get_free_shipping() && empty( $discount ) ) {
		$discount_html = __( 'Free shipping coupon', 'woocommerce' );
	}

	$discount_html = $discount_html . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), Automattic\Jetpack\Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="'
	                 . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'woocommerce' ) . '</a>';

	return apply_filters( 'wcs_braintree_cart_totals_coupon_html', $discount_html, $coupon, $recurring_cart );
}

/**
 * Returns the interval string for the given interval and period.
 *
 * @param int    $interval
 * @param string $period
 *
 * @since   3.0.0
 * @return string every 3rd month for 12 months. / month for 9 months.
 * @package Braintree/Functions
 */
function wcs_braintree_get_interval_string( $interval, $period ) {
	return apply_filters( 'wcs_braintree_get_interval_string', sprintf( __( '%s', 'woo-payment-gateway' ), $interval > 1 ? wcs_braintree_billing_interval_string( $interval ) : sprintf( __( '/ %s', 'woo-payment-gateway' ), wcs_braintree_get_period_string( $period ) ) ), $interval, $period );
}

/**
 *
 * @param string $period
 *
 * @return string
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_period_string( $period = 'month' ) {
	$periods = array(
		'day'   => __( 'day', 'woo-payment-gateway' ),
		'month' => __( 'month', 'woo-payment-gateway' ),
	);

	return $periods[ $period ];
}

/**
 *
 * @param string $interval
 * @param string $period
 *
 * @return string
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_frontend_interval_string( $interval, $period ) {
	return sprintf( __( 'every %s', 'woo-payment-gateway' ), $interval > 1 ? sprintf( '%s %s', $interval, wcs_braintree_billing_periods_string( 'plural', $period ) ) : wcs_braintree_billing_periods_string( 'singular', $period ) );
}

/**
 * Generate the html for the cart shipping displayed on the cart page.
 *
 * @param WC_Cart $cart
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_cart_shipping_total( $cart ) {
	$text = $cart->get_cart_shipping_total() . ' ' . wcs_braintree_frontend_interval_string( $cart->subscription_period_interval, $cart->subscription_period );

	return sprintf( '%s %s', $text, wcs_braintree_get_length_string( $cart->subscription_length, $cart->subscription_period ) );
}

/**
 *
 * @param WC_Cart $cart
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_cart_recurring_tax_html( $cart ) {
	return apply_filters( 'wcs_braintree_cart_recurring_tax_html', wcs_braintree_get_recurring_total_text( $cart->get_taxes_total(), $cart ) );
}

/**
 *
 * @param float   $total
 * @param WC_Cart $cart
 */
function wcs_braintree_get_recurring_total_text( $total, $cart ) {
	$text = is_numeric( $total ) ? wc_price( $total ) : $total;
	$text = sprintf( '%s %s', $text, wcs_braintree_frontend_interval_string( $cart->subscription_period_interval, $cart->subscription_period ) );
	if ( $length = $cart->subscription_length ) {
		$text = sprintf( '%s %s', $text, wcs_braintree_get_length_string( $cart->subscription_length, $cart->subscription_period ) );
	}

	return $text;
}

/**
 * Return html for the recurring total portion displayed on the cart page.
 *
 * @param WC_Cart $cart
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_cart_recurring_total_html( $cart ) {
	$total           = $cart->get_total();
	$recurring_total = sprintf( '%s %s', $total, wcs_braintree_frontend_interval_string( $cart->subscription_period_interval, $cart->subscription_period ) );
	if ( $length = $cart->subscription_length ) {
		$recurring_total = sprintf( '%s %s', $recurring_total, wcs_braintree_get_length_string( $cart->subscription_length, $cart->subscription_period ) );
	}

	return apply_filters( 'wcs_braintree_cart_recurring_total_html', $recurring_total );
}

/**
 * Return a formatted date.
 * The timezone given is the timezone in which the formatted string will be converted to. All given times
 * should be in UTC to ensure proper conversion.
 *
 * @param DateTime|string $date
 * @param string          $timezone
 * @param string          $format
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_cart_formatted_date( $date, $timezone = 'UTC', $format = 'F j, Y' ) {
	$date = $date instanceof DateTime ? $date : new DateTime( $date, new DateTimeZone( 'UTC' ) );
	$date->setTimezone( new DateTimeZone( $timezone ) );

	return date_format( $date, $format );
}

/**
 * Return html for the tax total portion displayed on the cart page.
 *
 * @param WC_Tax  $tax
 * @param WC_Cart $cart
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_cart_tax_total_html( $tax, $cart ) {
	$recurring_total = sprintf( '%1$s %2$s', $tax->formatted_amount, wcs_braintree_frontend_interval_string( $cart->subscription_period_interval, $cart->subscription_period ) );
	if ( $cart->subscription_length ) {
		$recurring_total = sprintf( '%1$s %2$s', $recurring_total, wcs_braintree_get_length_string( $cart->subscription_length, $cart->subscription_period ) );
	}

	return apply_filters( 'wcs_braintree_cart_tax_total_html', $recurring_total );
}

/**
 *
 * @param int|WC_Order $order
 *
 * @return WC_Braintree_Subscription[]
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_subscriptions_for_order( $order ) {
	if ( is_object( $order ) && is_a( $order, 'WC_Braintree_Subscription' ) ) {
		return array( $order );
	}
	if ( $order instanceof WC_Order ) {
		$order_id = $order->get_id();
	} else {
		$order_id = $order;
	}
	if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$ids = wc_get_orders( array(
			'type'   => 'bfwc_subscription',
			'limit'  => - 1,
			'return' => 'ids',
			'parent' => $order_id
		) );
		$ids = ! $ids ? array() : $ids;
	} else {
		$args = array(
			'post_type'      => 'bfwc_subscription',
			'posts_per_page' => - 1,
			'post_parent'    => $order_id,
			'post_status'    => 'any',
		);

		$posts = get_posts( $args );
		if ( ! $posts ) {
			$ids = array();
		} else {
			$ids = wp_list_pluck( $posts, 'ID' );
		}
	}
	$subscriptions = array();

	foreach ( $ids as $id ) {
		$subscriptions[] = wcs_braintree_get_subscription( $id );
	}

	return apply_filters( 'wcs_braintree_get_subscriptions_for_order', $subscriptions, $order );
}

/**
 * Return a subscription object from the given id.
 *
 * @param int $id
 *
 * @return WC_Braintree_Subscription
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_subscription( $id ) {
	$subscription = WC()->order_factory->get_order( $id );

	return apply_filters( 'wcs_braintree_get_subscription', $subscription );
}

/**
 * Create the subscription within the WordPress database.
 *
 * @param array $args
 *
 * @return WC_Braintree_Subscription
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_create_subscription( $args ) {
	$post_args = array(
		'post_type'   => 'bfwc_subscription',
		'post_author' => 0,
		'post_status' => 'wc-pending',
		'post_parent' => absint( $args['order_id'] ),
	);

	//$post_id = wp_insert_post( $post_args );
	$subscription = new WC_Braintree_Subscription();
	$subscription->set_status( 'pending' );
	$subscription->set_parent_id( $post_args['post_parent'] );
	$subscription->set_currency( $args['order_currency'] );
	$subscription->set_customer_id( $args['customer_user'] );

	/*if ( is_wp_error( $post_id ) || $post_id === 0 ) {
		return new WP_Error( 'subscription-error', __( 'There was an error creating the subscription.', 'woo-payment-gateway' ) );
	}*/
	//$subscription = WC()->order_factory->get_order( $post_id );
	$subscription->update_subscription_meta_data(
		array(
			'start_date'                   => $args['start_date'],
			'next_payment_date'            => $args['next_payment_date'],
			'trial_end_date'               => $args['trial_end_date'],
			'end_date'                     => $args['end_date'],
			'subscription_trial_length'    => $args['subscription_trial_length'],
			'subscription_trial_period'    => $args['subscription_trial_period'],
			'first_payment_date'           => $args['first_payment_date'],
			'braintree_plan'               => $args['braintree_plan'],
			'merchant_account_id'          => $args['merchant_account_id'],
			'subscription_period'          => $args['subscription_period'],
			'subscription_period_interval' => $args['subscription_period_interval'],
			'subscription_length'          => $args['subscription_length'],
		)
	);
	$subscription->save();

	return $subscription;
}

/**
 *
 * @param WC_Order                  $order
 * @param WC_Braintree_Subscription $subscription
 * @param string                    $address_type
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_copy_address_from_order( $order, $subscription, $address_type = 'all' ) {
	$address_types = $address_type === 'all' ? array( 'billing', 'shipping' ) : array( $address_type );

	$address_fields = array(
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'email',
		'phone'
	);

	foreach ( $address_types as $type ) {
		foreach ( $address_fields as $field_key ) {
			$field_var = sprintf( '%1$s_%2$s', $type, $field_key );
			if ( method_exists( $order, 'get_' . $field_var ) ) {
				$address[ $field_key ] = $order->{'get_' . $field_var}();
			}
		}

		$subscription->set_address( $address, $type );
	}
	$subscription->save();

	return $subscription;
}

/**
 *
 * @param WC_Braintree_Subscription $subscription
 *
 * @return WP_Error|boolean|WC_Order|WP_Error
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_create_renewal_order( $subscription ) {
	if ( ! is_object( $subscription ) ) {
		$subscription = wcs_braintree_get_subscription( $subscription );
	}

	$order = wcs_braintree_create_order_from_subscription( $subscription );

	if ( ! $order ) {
		return new WP_Error();
	}

	return $order;
}

/**
 *
 * @param int|WC_Braintree_Subscription $subscription
 *
 * @since   3.0.0
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_create_order_from_subscription( $subscription ) {
	global $wpdb;
	try {
		if ( ! is_object( $subscription ) ) {
			$subscription = wcs_braintree_get_subscription( $subscription );
		}

		$items = $subscription->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) );

		$renewal_order = wc_create_order( array( 'customer_id' => $subscription->get_user_id() ) );

		// add all of the items from the subscription to the new order.
		foreach ( $items as $item_id => $item ) {
			/**
			 *
			 * @var WC_Order_Item $item
			 */
			$item_id  = wc_add_order_item(
				$renewal_order->get_id(),
				array(
					'order_item_name' => $item->get_name(),
					'order_item_type' => $item->get_type(),
				)
			);
			$new_item = $renewal_order->get_item( $item_id );
			foreach ( $item->get_meta_data() as $meta_data ) {
				$new_item->update_meta_data( $meta_data->key, $meta_data->value );
			}
			$order_itemmeta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d", $item->get_id() ) );
			foreach ( $order_itemmeta as $meta ) {
				wc_update_order_item_meta( $item_id, $meta->meta_key, $meta->meta_value );
			}
			$renewal_order->add_item( $new_item );
		}

		// copy all the metadata from the subscription to the order.
		wcs_braintree_copy_order_meta( $subscription, $renewal_order );

		$renewal_order->update_meta_data( '_renewal_order', true );
		$renewal_order->update_meta_data( '_subscription_id', $subscription->get_id() );
		$renewal_order->save();

		return $renewal_order;
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Copy meta data from one order to another.
 *
 * @param WC_Order $from
 * @param WC_Order $to
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_copy_order_meta( $from, $to ) {
	$exlcude = array( '_created_in_braintree', '_transaction_id', '_order_key' );
	foreach ( $from->get_meta_data() as $meta ) {
		if ( ! in_array( $meta->key, $exlcude ) ) {
			$to->update_meta_data( $meta->key, $meta->value );
		}
	}
	$to->save();
}

/**
 * Returns true if the order contains a Braintree Subscription.
 *
 * @param int|WC_Order $order
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_order_contains_subscription( $order ) {
	if ( ! is_object( $order ) ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order instanceof WC_Braintree_Subscription && count( wcs_braintree_get_subscriptions_for_order( $order ) ) > 0 ) {
		return true;
	}

	return false;
}

/**
 *
 * @param string $total_string
 * @param string $interval
 * @param string $period
 *
 * @return mixed
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_price_string( $total_string, $interval, $period ) {
	$string       = __( '%1$s every %2$s', 'woo-payment-gateway' );
	$price_string = sprintf( $string, $total_string, $interval > 1 ? sprintf( '%1$s %2$s', $interval, wcs_braintree_billing_periods_string( 'plural', $period ) ) : wcs_braintree_billing_periods_string( 'singular', $period ) );

	return apply_filters( 'wcs_braintree_get_price_string', $price_string, $total_string, $period, $interval );
}

/**
 * Return the nice name for the status provided.
 * If the status is invalid, then the status provided will
 * be returned.
 *
 * @param unknown $status
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_get_subscription_status_name( $status ) {
	$status   = strpos( $status, 'wc-' ) === false ? 'wc-' . $status : $status;
	$statuses = wp_parse_args( wc_get_order_statuses(), wcs_braintree_get_subscription_statuses() );

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 *
 * @return mixed
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_get_subscription_statuses_for_registration() {
	return apply_filters(
		'wcs_braintree_get_subscription_statuses',
		array(
			'wc-active'   => array(
				'label'                     => __( 'Active', 'woo-payment-gateway' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'woo-payment-gateway' ),
			),
			'wc-expired'  => array(
				'label'                     => __( 'Expired', 'woo-payment-gateway' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'woo-payment-gateway' ),
			),
			'wc-past-due' => array(
				'label'                     => __( 'Past Due', 'woo-payment-gateway' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Past Due <span class="count">(%s)</span>',
					'Past Due <span class="count">(%s)</span>',
					'woo-payment-gateway'
				),
			),
		)
	);
}

/**
 *
 * @param WC_Order $order
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_order_details( $order ) {
	if ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
		wc_braintree_get_template( 'order/subscription-details.php', array( 'subscriptions' => wcs_braintree_get_subscriptions_for_order( $order ) ) );
	}
}

/**
 *
 * @param int $id
 *
 * @since   3.0.0
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_view_subscription_template( $id ) {
	$subscription = wc_get_order( $id );
	if ( ! $subscription ) {
		wc_add_notice( __( 'Invalid subscription.', 'woo-payment-gateway' ), 'error' );
		wc_print_notices();

		return;
	}
	wcs_braintree_sync_subscription_with_gateway( $subscription );
	wc_braintree_get_template( 'myaccount/view-subscription.php', array( 'subscription' => $subscription ) );
}

/**
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_subscriptions_template() {
	global $wp;
	$current_page  = ! empty( $wp->query_vars['subscriptions'] ) ? absint( $wp->query_vars['subscriptions'] ) : 1;
	$subscriptions = wcs_braintree_get_subscriptions_for_user( get_current_user_id() );
	$max_pages     = ceil( count( $subscriptions ) / get_option( 'posts_per_page' ) );
	$subscriptions = array_slice( $subscriptions, ( ( $current_page - 1 ) * get_option( 'posts_per_page' ) ), get_option( 'posts_per_page' ) );

	wc_braintree_get_template(
		'myaccount/my-subscriptions.php',
		array(
			'subscriptions' => $subscriptions,
			'current_page'  => $current_page,
			'max_pages'     => $max_pages,
		)
	);
}

/**
 *
 * @param int $id
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_change_payment_method_template( $id ) {
	$subscription = wc_get_order( $id );
	if ( ! $subscription ) {
		wc_add_notice( __( 'Invalid subscription.', 'woo-payment-gateway' ), 'error' );
		wc_print_notices();

		return;
	}
	wc_add_notice( __( 'Please choose a new payment method.', 'woo-payment-gateway' ), 'notice' );
	do_action( 'wcs_braintree_pre_change_payment_method_template' );
	wc_braintree_get_template(
		'myaccount/change-payment-method.php',
		array(
			'subscription'       => $subscription,
			'available_gateways' => WC()->payment_gateways()->get_available_payment_gateways(),
		)
	);
}

/**
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_is_change_payment_method_request() {
	global $wp;

	return is_account_page() && isset( $wp->query_vars['change-payment-method'] );
}

/**
 *
 * @param WC_Braintree_Subscription $subscription
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_get_subscription_actions( $subscription ) {
	$actions = array(
		'view' => array(
			'url'   => wc_get_endpoint_url( 'view-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ),
			'label' => __( 'View', 'woo-payment-gateway' ),
		),
	);

	return apply_filters( 'wcs_braintree_get_subscription_actions', $actions );
}

/**
 * return an array of user actions that pertain to the subscription.
 *
 * @param WC_Braintree_Subscription $subscription
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_subscription_user_actions( $subscription ) {
	$actions = array();
	if ( $subscription->has_status( 'active' ) || $subscription->has_status( 'past-due' ) ) {
		$actions['cancel']                = array(
			'label' => __( 'Cancel', 'woo-payment-gateway' ),
			'url'   => wp_nonce_url( wc_get_endpoint_url( 'cancel-subscription', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ) ),
		);
		$actions['change_payment_method'] = array(
			'label' => __( 'Change Payment Method', 'woo-payment-gateway' ),
			'url'   => wc_get_endpoint_url( 'change-payment-method', $subscription->get_id(), wc_get_page_permalink( 'myaccount' ) ),
		);
	} elseif ( $subscription->has_status( 'pending' ) ) {
		$actions['pay_for_subscription'] = array(
			'label' => __( 'Pay', 'woo-payment-gateway' ),
			'url'   => $subscription->get_checkout_payment_url(),
		);
	}

	return apply_filters( 'wcs_braintree_subscription_user_actions', $actions, $subscription );
}

/**
 *
 * @param WC_Braintree_Subscription|int $subscription
 *
 * @return WC_Order[]
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_related_orders( $subscription ) {
	global $wpdb;
	if ( ! is_object( $subscription ) ) {
		$subscription = wcs_braintree_get_subscription( $subscription );
	}
	if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$ids = wc_get_orders( array(
			'type'       => 'bfwc_subscription',
			'limit'      => - 1,
			'return'     => 'ids',
			'meta_query' => array(
				array(
					'key'   => '_subscription_id',
					'value' => $subscription->get_id()
				)
			)
		) );
		$ids = ! $ids ? array() : $ids;
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts AS posts LEFT JOIN $wpdb->postmeta AS postmeta
			ON  posts.ID = postmeta.post_id WHERE posts.post_type = 'shop_order' AND postmeta.meta_key = '_subscription_id'
			AND postmeta.meta_value = %s",
				$subscription->get_id()
			)
		);
		$ids     = wp_list_pluck( $results, 'ID' );
	}
	$orders = array();
	if ( $subscription->get_parent_id() ) {
		$orders[] = $subscription->get_order( $subscription->get_parent_id() );
	}
	foreach ( $ids as $id ) {
		$orders[] = wc_get_order( $id );
	}

	return $orders;
}

/**
 * Retrieve subscriptions for the given user.
 *
 * @param number $user_id
 *
 * @return WC_Braintree_Subscription[]
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_subscriptions_for_user( $user_id = 0, $args = array() ) {
	$user_id       = empty( $user_id ) ? get_current_user_id() : $user_id;
	$posts         = get_posts(
		array_merge(
			array(
				'post_type'      => 'bfwc_subscription',
				'post_status'    => array_merge( array_keys( wcs_braintree_get_subscription_statuses() ), array_keys( wc_get_order_statuses() ) ),
				'posts_per_page' => - 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => '_customer_user',
						'value' => $user_id,
					),
				),
			),
			$args
		)
	);
	$subscriptions = array();
	foreach ( $posts as $post ) {
		$subscriptions[] = wcs_braintree_get_subscription( $post->ID );
	}

	return $subscriptions;
}

/**
 * Sync the WP database dates with the dates stored in Braintree to keep everything consistent.
 *
 * @param WC_Braintree_Subscription $subscription
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_sync_subscription_with_gateway( $subscription ) {
	try {
		$gateway = braintree()->gateway( wc_braintree_get_order_environment( $subscription ) );
		if ( $gateway ) {
			$braintree_subscription = $gateway->subscription()->find( $subscription->get_id() );
			$next_payment_date      = clone $braintree_subscription->nextBillingDate;
			$old_date               = $subscription->get_date( 'next_payment' );
			$next_payment_date->setTime( $old_date->format( 'H' ), $old_date->format( 'i' ), $old_date->format( 's' ) );
			$subscription->set_next_payment_date( $next_payment_date );
			if ( $braintree_subscription->transactions ) {
				$latest_transaction = $braintree_subscription->transactions[0];
				$previous_payment   = clone $latest_transaction->createdAt;
				$previous_payment->setTime( $old_date->format( 'H' ), $old_date->format( 'i' ), $old_date->format( 's' ) );
				$subscription->set_previous_payment_date( $previous_payment );
			}
			$subscription->save();
		}
	} catch ( \Braintree\Exception $e ) {
		wc_braintree_log_error( sprintf( __( 'Error syncing subscription %1$s. Reason: %1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
	} catch ( Exception $e ) {
		wc_braintree_log_error( sprintf( __( 'Error syncing subscription %1$s. Reason: %1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
	}

	return $subscription;
}

/**
 *
 * @param array $items
 * @param array $endpoints
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_account_menu_items( $items, $endpoints = array() ) {
	$wc_braintree_endpoints = array( 'subscriptions' => braintree()->subscription_settings->get_option( 'subscriptions_endpoint' ) );
	$labels                 = array( 'subscriptions' => __( 'Subscriptions', 'woo-payment-gateway' ) );
	foreach ( $wc_braintree_endpoints as $endpoint => $value ) {
		if ( empty( $value ) ) {
			unset( $labels[ $endpoint ] );
		}
	}
	// if possible we want subscriptions to appear right under orders in the navigation.
	if ( isset( $labels['subscriptions'], $items['orders'] ) ) {
		$position = array_search( 'orders', array_keys( $items ) );
		$items    = array_merge( array_slice( $items, 0, $position + 1 ), $labels, array_slice( $items, $position + 1 ) );
	} else {
		$items = array_merge( $items, $labels );
	}

	return $items;
}

/**
 *
 * @return mixed
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_get_subscription_statuses() {
	return apply_filters(
		'wcs_braintree_get_subscription_statuses',
		array(
			'wc-active'   => __( 'Active', 'woo-payment-gateway' ),
			'wc-expired'  => __( 'Expired', 'woo-payment-gateway' ),
			'wc-past-due' => __(
				'Past Due',
				'woo-payment-gateway'
			),
		)
	);
}

/**
 *
 * @param int                       $id
 * @param WC_Braintree_Subscription $subscription
 *
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_subscription_cancelled( $id, $subscription ) {
	/**
	 *
	 * @var WC_Braintree_Payment_Gateway $gateway
	 */
	$gateway = WC()->payment_gateways()->payment_gateways()[ $subscription->get_payment_method() ];
	if ( $gateway ) {
		try {
			$gateway->connect( wc_braintree_get_order_environment( $subscription ) );
			$response = $gateway->gateway->subscription()->cancel( $subscription->get_id() );
			if ( $response->success ) {
				$subscription->add_order_note( __( 'Subscription has been cancelled in Braintree.', 'woo-payment-gateway' ) );
			} else {
				$subscription->add_order_note( sprintf( __( 'Error cancelling subscription in Braintree. Reason: %1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			$subscription->add_order_note( sprintf( __( 'Error cancelling subscription in Braintree. Reason: %1$s.', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $response ) ) );
		}
		$gateway->connect();
	}
}

/**
 *
 * @param \Braintree\WebhookNotification $notification
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_webhook_subscription_cancelled( $notification ) {
	$id           = absint( $notification->subscription->id );
	$subscription = wcs_braintree_get_subscription( $id );
	// subscription has been cancelled in Braintree so avoid trying to cancel again.
	remove_action( 'wcs_braintree_subscription_status_cancelled', 'wcs_braintree_subscription_cancelled' );
	$subscription->update_status( 'cancelled' );
}

/**
 *
 * @param \Braintree\WebhookNotification $notification
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_webhook_subscription_past_due( $notification ) {
	$id           = absint( $notification->subscription->id );
	$subscription = wc_get_order( $id );
	$subscription->update_status( 'past-due' );
}

/**
 *
 * @param \Braintree\WebhookNotification $notification
 *
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_webhook_subscription_expired( $notification ) {
	$id           = absint( $notification->subscription->id );
	$subscription = wc_get_order( $id );
	$subscription->update_status( 'expired' );
}

/**
 * Create a renewal order associated with the subscription payment.
 *
 * @param \Braintree\WebhookNotification $notification
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_webhook_subscription_charged_successfully( $notification ) {
	$id                     = absint( $notification->subscription->id );
	$braintree_subscription = $notification->subscription;
	$subscription           = wc_get_order( $id );
	$transactions           = $braintree_subscription->transactions;
	$transaction            = count( $transactions ) > 0 ? $transactions[0] : null;
	if ( $transaction ) {
		global $wpdb;
		// look for an existing order associated with this transaction.
		if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
			$result = wc_get_orders( array(
				'type'           => 'shop_order',
				'limit'          => 1,
				'transaction_id' => $transaction->id,
				'return'         => 'ids'
			) );
		} else {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts as posts INNER JOIN $wpdb->postmeta as postmeta ON postmeta.post_id = posts.ID WHERE posts.post_type = 'shop_order' AND postmeta.meta_key = '_transaction_id' AND postmeta.meta_value = %s", $transaction->id ) );
		}
		if ( ! $result ) {
			$next_billing_date = clone $braintree_subscription->nextBillingDate;
			$last_payment      = clone $transaction->createdAt;
			$start_date        = $subscription->get_date( 'start' );
			$next_billing_date->setTime( $start_date->format( 'H' ), $start_date->format( 'i' ), $start_date->format( 's' ) );
			$last_payment->setTime( $start_date->format( 'H' ), $start_date->format( 'i' ), $start_date->format( 's' ) );

			$subscription->update_date( 'next_payment', $next_billing_date );
			$subscription->update_date( 'last_payment', $last_payment );

			// create renewal order.
			$renewal_order = wcs_braintree_create_renewal_order( $subscription );
			$renewal_order->update_meta_data( Constants::TRANSACTION_STATUS, $transaction->status );
			if ( $transaction->status === \Braintree\Transaction::AUTHORIZED ) {
				$renewal_order->update_meta_data( '_authorization_exp_at', $transaction->authorizationExpiresAt->getTimestamp() );
			}
			$renewal_order->payment_complete( $transaction->id );
			$renewal_order->save();
			$renewal_order->add_order_note( sprintf( __( 'Renewal order created via webhook for subscription %s', 'woo-payment-gateway' ), $subscription->get_id() ) );
			$subscription->add_order_note( sprintf( __( 'Renewal order %s created from Braintree webhook.', 'woo-payment-gateway' ), $renewal_order->get_id() ) );
			$subscription->update_status( 'active' );
		} else {
			$subscription->add_order_note( sprintf( __( 'Renewal order %1$s already created for transaction %2$s.', 'woo-payment-gateway' ), $result->ID, $transaction->id ) );
		}
	}
}

/**
 *
 * @param WC_Product $product
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_get_recurring_cart_key( $product ) {
	$plan_id = wcs_braintree_get_plan_from_product( $product );

	/**
	 * recurring cart key should create uniquiness based on parameters like subscription plan, billing period, trial length, trial period, etc
	 * Ex: silver_plan_2_month_interval_5_days_trial_for_10
	 */
	$key = sprintf( '%1$s_%2$s_%3$s_interval_%4$s_%5$s_trial_for_%6$s', $plan_id, $product->get_subscription_period_interval(), $product->get_subscription_period(), $product->get_subscription_trial_period(), $product->get_subscription_trial_length(), $product->get_subscription_length() );
	if ( ! braintree()->subscription_settings->is_active( 'combine' ) ) {
		$key = $key . '_' . wc_rand_hash();
	}

	return apply_filters( 'wcs_braintree_get_recurring_cart_key', md5( str_replace( ' ', '_', $key ) ), $product );
}

/**
 *
 * @param int $item_id
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wcs_braintree_before_delete_order_item( $item_id ) {
	// prevent infinite loop
	remove_action( 'woocommerce_before_delete_order_item', 'wcs_braintree_before_delete_order_item' );
	$data_store = WC_Data_Store::load( 'order-item' );
	$order_id   = $data_store->get_order_id_by_order_item_id( $item_id );
	$order      = wc_get_order( $order_id );
	/**
	 *
	 * @var WC_Order_Item $item
	 */
	$removed_item = $order->get_item( $item_id );

	if ( $order->get_type() === 'bfwc_subscription' ) {
		$order_to_remove_from = wc_get_order( $order->get_parent_id() );
		foreach ( $order_to_remove_from->get_items( 'line_item' ) as $item ) {
			if ( $item->get_product()->get_id() === $removed_item->get_product()->get_id() ) {
				wc_delete_order_item( $item->get_id() );
				$order_to_remove_from->calculate_totals();
				break;
			}
		}
	} elseif ( wcs_braintree_order_contains_subscription( $order ) ) {
		$key          = wcs_braintree_get_recurring_cart_key( $removed_item->get_product() );
		$subscription = wcs_braintree_get_subscription_from_recurring_cart_key( $key, $order->get_id() );
		if ( $subscription ) {
			foreach ( $subscription->get_items( 'line_item' ) as $item ) {
				if ( $item->get_product()->get_id() === $removed_item->get_product()->get_id() ) {
					wc_delete_order_item( $item->get_id() );
					$subscription->calculate_totals();
					break;
				}
			}
		}
	}
}

/**
 *
 * @param string $key
 * @param int    $parent_id
 *
 * @return WC_Braintree_Subscription
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_get_subscription_from_recurring_cart_key( $key, $parent_id ) {
	if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$ids = wc_get_orders( array(
			'type'       => 'bfwc_subscription',
			'limit'      => - 1,
			'return'     => 'ids',
			'parent'     => $parent_id,
			'meta_query' => array(
				array(
					'key'   => '_recurring_cart_key',
					'value' => $key
				)
			)
		) );
		$id  = ! empty( $ids ) ? $ids[0] : null;
	} else {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts as posts INNER JOIN $wpdb->postmeta AS postmeta ON posts.ID = postmeta.post_id WHERE posts.post_parent = %d AND postmeta.meta_key = '_recurring_cart_key' AND postmeta.meta_value = %s", $parent_id, $key ) );
	}

	return wcs_braintree_get_subscription( $id );
}

/**
 *
 * @param array  $data
 * @param string $name
 *
 * @return string[][]|NULL[][]
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wcs_braintree_localize_scripts( $data, $name ) {
	global $wp;
	switch ( $name ) {
		case 'view-subscription':
			$subscription_id = absint( $wp->query_vars['view-subscription'] );
			$data            = array(
				'messages' => array(
					'cancel_confirmation' => sprintf( __( 'Please click OK if you wish to cancel subscription %1$s.', 'woo-payment-gateway' ), $subscription_id ),
					'cancel'              => __( 'Cancel', 'woo-payment-gateway' ),
					'confirm'             => __( 'Confirm', 'woo-payment-gateway' ),
					'title'               => sprintf( __( 'Subscription #%1$s', 'woo-payment-gateway' ), $subscription_id ),
				),
			);
			break;
	}

	return $data;
}
