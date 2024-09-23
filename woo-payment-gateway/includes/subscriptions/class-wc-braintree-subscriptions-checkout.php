<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/Subscriptions
 *
 */
class WC_Braintree_Subscriptions_Checkout {

	public static function init() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'process_checkout' ), 1000, 2 );

		add_action( 'woocommerce_review_order_after_order_total', array( __CLASS__, 'after_order_total' ) );
		add_action( 'before_woocommerce_pay', array( __CLASS__, 'order_pay' ) );
		add_filter( 'woocommerce_order_needs_payment', array( __CLASS__, 'order_needs_payment' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_review', array( __CLASS__, 'add_checkout_filters' ), 5 );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'add_checkout_filters' ) );
		add_filter( 'woocommerce_checkout_registration_enabled', array( __CLASS__, 'require_account_creation' ) );
		add_filter( 'woocommerce_checkout_registration_required', array( __CLASS__, 'require_account_creation' ) );
	}

	/**
	 *
	 * @param int   $order_id
	 * @param array $posted_data
	 */
	public static function process_checkout( $order_id, $posted_data ) {
		if ( ! wcs_braintree_cart_contains_subscription() ) {
			return;
		}
		$order         = wc_get_order( $order_id );
		$subscriptions = wcs_braintree_get_subscriptions_for_order( $order_id );

		$recurring_carts = WC()->cart->recurring_carts;

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription ) {
				if ( $subscription->is_created() ) {
					// unset the recurring cart associated with this subscription to prevent duplicate processing.
					// check that $recurring_carts is an array to prevent Cannot unset string offsets exception
					if ( is_array( $recurring_carts ) ) {
						unset( $recurring_carts[ $subscription->get_recurring_cart_key() ] );
					}
				} else {
					// delete any existing subscriptions associated with the order that haven't been created in Braintree
					// yet.
					//wp_delete_post( $subscription->get_id() );
					$subscription->delete();
				}
			}
		}
		$new_subscriptions = array();
		foreach ( $recurring_carts as $key => $recurring_cart ) {
			$new_subscriptions[] = self::create_subscription( $order, $recurring_cart, $key );
		}

		$order->save();
	}

	/**
	 * Create the subscription object for the order.
	 *
	 * @param WC_Order $order
	 * @param WC_Cart  $recurring_cart
	 * @param string   $cart_key
	 */
	public static function create_subscription( $order, $recurring_cart, $recurring_cart_key ) {
		$format       = 'Y-m-d H:i:s';
		$subscription = wcs_braintree_create_subscription(
			array(
				'order_id'                     => $order->get_id(),
				'start_date'                   => $recurring_cart->start_date->format( $format ),
				'subscription_trial_length'    => $recurring_cart->subscription_trial_length,
				'subscription_trial_period'    => $recurring_cart->subscription_trial_period,
				'trial_end_date'               => $recurring_cart->trial_end_date->format( $format ),
				'next_payment_date'            => $recurring_cart->next_payment_date->format( $format ),
				'first_payment_date'           => $recurring_cart->first_payment_date->format( $format ),
				'end_date'                     => $recurring_cart->end_date ? $recurring_cart->end_date->format( $format ) : 0,
				'braintree_plan'               => $recurring_cart->braintree_plan,
				'subscription_period'          => $recurring_cart->subscription_period,
				'subscription_period_interval' => $recurring_cart->subscription_period_interval,
				'subscription_length'          => $recurring_cart->subscription_length,
				'merchant_account_id'          => $recurring_cart->merchant_account_id,
				'customer_user'                => $order->get_user_id(),
				'order_currency'               => $order->get_currency(),
				'order_note'                   => $order->get_customer_note(),
			)
		);

		if ( is_wp_error( $subscription ) ) {
			throw new Exception( $subscription->get_error_message() );
		}

		// save the recurring cart key to the subscription.
		$subscription->set_recurring_cart_key( $recurring_cart_key );

		// update the billing and shipping addresses.
		$subscription = wcs_braintree_copy_address_from_order( $order, $subscription );

		// add the line items
		WC()->checkout()->create_order_line_items( $subscription, $recurring_cart );
		WC()->checkout()->create_order_fee_lines( $subscription, $recurring_cart );
		WC()->checkout()->create_order_shipping_lines( $subscription, self::get_chosen_shipping_methods( $recurring_cart_key ), self::get_shipping_packages( $recurring_cart_key ) );
		WC()->checkout()->create_order_tax_lines( $subscription, $recurring_cart );
		WC()->checkout()->create_order_coupon_lines( $subscription, $recurring_cart );

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( isset( $gateways[ $order->get_payment_method() ] ) ) {
			$subscription->set_payment_method( $gateways[ $order->get_payment_method() ] );
		}

		$subscription->set_shipping_total( $recurring_cart->shipping_total );
		$subscription->set_discount_total( $recurring_cart->get_cart_discount_total() );
		$subscription->set_discount_tax( $recurring_cart->get_cart_discount_tax_total() );
		$subscription->set_cart_tax( $recurring_cart->tax_total );
		$subscription->set_shipping_tax( $recurring_cart->shipping_tax_total );
		$subscription->set_total( $recurring_cart->total );
		$subscription->save();

		do_action( 'wcs_braintree_subscription_created', $subscription, $order );

		return $subscription;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function pay_order_action( $order ) {
		self::process_checkout( $order->get_id(), array() );
	}

	public static function after_order_total() {
		if ( WC()->cart->recurring_carts ) {
			wc_braintree_get_template( 'checkout/recurring-totals.php', array( 'recurring_carts' => WC()->cart->recurring_carts ) );
		}
	}

	/**
	 *
	 * @param string $cart_key
	 *
	 * @since 2.6.2
	 */
	public static function get_chosen_shipping_methods( $cart_key ) {
		$shipping_data = WC()->session->get( 'wcs_braintree_shipping_data_' . $cart_key, array( 'chosen_shipping_methods' => array() ) );

		return isset( $shipping_data['chosen_shipping_methods'] ) ? $shipping_data['chosen_shipping_methods'] : array();
	}

	public static function get_shipping_packages( $cart_key ) {
		$shipping_data = WC()->session->get( 'wcs_braintree_shipping_data_' . $cart_key, array() );

		return isset( $shipping_data['packages'] ) ? $shipping_data['packages'] : array();
	}

	public static function order_pay() {
		global $wp;
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {
			$order_id = absint( $wp->query_vars['order-pay'] );
			$order    = wc_get_order( $order_id );
			if ( wcs_braintree_order_contains_subscription( $order ) ) {
				// output products price string which includes billing interval etc
				add_filter(
					'woocommerce_order_formatted_line_subtotal',
					function ( $subtotal, $item ) {
						if ( wcs_braintree_product_is_subscription( $item->get_product() ) ) {
							$product  = $item->get_product();
							$subtotal = wcs_braintree_get_product_price_html( $product, $item->get_subtotal(), $item->get_quantity() );
						}

						return $subtotal;
					},
					10,
					3
				);
				// replace the WC form-pay.php file with one that renders subscription data.
				add_filter( 'wc_get_template', array( __CLASS__, 'form_pay_template' ), 10, 3 );
				// remove any gateways that don't support Braintree Subscriptions
				add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_gateways_that_support_subscriptions' ) );
			}
		}
	}

	/**
	 *
	 * @param string $located
	 * @param string $template_name
	 * @param array  $args
	 */
	public static function form_pay_template( $file, $template_name, $args ) {
		if ( 'checkout/form-pay.php' === $template_name ) {
			$file = wc_locate_template( 'checkout/subscription-form-pay.php', braintree()->template_path(), braintree()->plugin_path() . 'templates/' );
		}

		return $file;
	}

	/**
	 *
	 * @param bool     $needs_payment
	 * @param WC_Order $order
	 */
	public static function order_needs_payment( $needs_payment, $order ) {
		if ( 'shop_order' === $order->get_type() ) {
			if ( wcs_braintree_order_contains_subscription( $order ) ) {
				$needs_payment = true;
			}
		}

		return $needs_payment;
	}

	public static function add_checkout_filters() {
		if ( wcs_braintree_cart_contains_subscription() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_gateways_that_support_subscriptions' ) );
		}
	}

	/**
	 * Given an array of gateways, only return those that support Braintree Subscriptions.
	 *
	 * @param WC_Payment_Gateway[] $gateways
	 */
	public static function filter_gateways_that_support_subscriptions( $gateways ) {
		foreach ( $gateways as $id => $gateway ) {
			if ( ! $gateway->supports( 'wc_braintree_subscriptions' ) ) {
				unset( $gateways[ $id ] );
			}
		}

		return $gateways;
	}

	/**
	 *
	 * @param bool $required
	 */
	public static function require_account_creation( $bool ) {
		if ( wcs_braintree_cart_contains_subscription() ) {
			$bool = true;
		}

		return $bool;
	}

}

WC_Braintree_Subscriptions_Checkout::init();
