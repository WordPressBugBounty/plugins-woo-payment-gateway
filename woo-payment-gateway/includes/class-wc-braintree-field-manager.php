<?php

defined( 'ABSPATH' ) || exit();

use PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since   3.0.0
 * @author  Payment Plugins
 * @package Braintree/Classes
 */
class WC_Braintree_Field_Manager {

	/**
	 *
	 * @var string
	 * @since 3.1.1
	 */
	private static $_output_checkout_fields = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'action_init' ) );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'output_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'checkout_update_order_review' ) );
		add_action( 'before_woocommerce_pay', array( __CLASS__, 'change_payment_request' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'before_add_to_cart' ) );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( __CLASS__, 'mini_cart_buttons' ), 5 );
	}

	public static function action_init() {
		add_action( 'woocommerce_proceed_to_checkout', 'wc_braintree_cart_checkout_template', apply_filters( 'wc_braintree_cart_buttons_priority', 30 ) );
	}

	/**
	 * Added because some themes aren't 100% compatible with WC and they skip template hooks.
	 * This was added to ensure necessary fields are output to pages when required actions
	 * aren't triggered.
	 *
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @since 3.1.1
	 */
	public static function after_template_part( $template_name, $template_path ) {
		if ( empty( $template_path ) && $template_name == 'checkout/review-order.php' && is_checkout() && ! self::$_output_checkout_fields ) {
			self::output_checkout_fields();
		}
	}

	public static function output_checkout_fields() {
		do_action( 'wc_braintree_output_checkout_fields' );
		if ( ! WC()->cart->needs_payment() ) {
			wp_add_inline_script( braintree()->scripts()->get_handle( 'client-manager' ),
				'var wc_braintree_cart_needs_payment = false', 'before' );
		}
		self::$_output_checkout_fields = true;
	}

	/**
	 * @deprecated 3.2.5
	 */
	public static function output_cart_fields() {
	}

	public static function checkout_update_order_review() {
		if ( is_ajax() ) {
			// initialze payment gateways.
			WC()->payment_gateways();
		}
	}

	public static function change_payment_request() {
		if ( wcs_braintree_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
			$subscription = wcs_get_subscription( absint( $_GET['change_payment_method'] ) );
			do_action( 'wc_braintree_output_change_payment_method_fields' );
		}
	}

	/**
	 *
	 * @param WC_Braintree_Subscription $subscription
	 *
	 * @deprecated 3.2.5
	 */
	public static function change_braintree_payment_method( $subscription ) {
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function output_subscription_pay_fields( $order ) {
	}

	public static function before_add_to_cart() {
		global $product;
		$position = $product ? $product->get_meta( Constants::BUTTON_POSITION ) : null;
		if ( empty( $position ) ) {
			$position = 'bottom';
		}

		if ( 'bottom' == $position ) {
			$action = 'woocommerce_after_add_to_cart_button';
		} else {
			$action = 'woocommerce_before_add_to_cart_button';
		}
		add_action( $action, array( __CLASS__, 'output_product_checkout_fields' ) );
	}

	public static function output_product_checkout_fields() {
		global $product;
		$gateways = array();
		$ordering = $product->get_meta( Constants::PRODUCT_GATEWAY_ORDER );
		$ordering = ! $ordering ? array() : $ordering;

		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			if ( $gateway->supports( 'wc_braintree_product_checkout' ) && ! $product->is_type( 'external' ) ) {
				$option = new WC_Braintree_Product_Gateway_Option( $product, $gateway );
				if ( $option->enabled() ) {
					$gateway->set_product_gateway_option( $option );
					if ( isset( $ordering[ $gateway->id ] ) ) {
						$gateways[ $ordering[ $gateway->id ] ] = $gateway;
					} else {
						$gateways[] = $gateway;
					}
				}
			}
		}
		if ( count( apply_filters( 'wc_braintree_product_payment_gateways', $gateways ) ) > 0 ) {
			ksort( $gateways );
			wc_braintree_get_template( 'product/payment.php', array( 'gateways' => $gateways ) );

			do_action( 'wc_braintree_output_product_checkout_fields' );
		}
	}

	public static function mini_cart_buttons() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			if ( $gateway instanceof WC_Braintree_Payment_Gateway && $gateway->supports( 'wc_braintree_mini_cart' ) && $gateway->mini_cart_enabled() ) {
				$gateways[ $id ] = $gateway;
			}
		}
		if ( $gateways ) {
			wc_braintree_get_template( 'mini-cart/payment-methods.php', array( 'gateways' => $gateways ) );
		}
	}

	/**
	 * @deprecated 3.2.5
	 */
	public static function pay_order_fields() {
	}

	/**
	 * @param $needs_shipping
	 *
	 * @deprecated 3.2.5
	 */
	public static function output_needs_shipping( $needs_shipping ) {
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public static function get_cart_total() {
		$total = WC()->cart->total;

		return $total;
	}

	/**
	 *
	 * @param WC_Checkout $checkout
	 *
	 * @deprecated 3.2.5
	 */
	public static function required_checkout_fields( $checkout ) {
	}

	/**
	 * 3DS doesn't like $0.00 totals which can happen if only product in cart is subscription product with trial.
	 *
	 * @param float $total
	 *
	 * @since      3.0.6
	 * @deprecated 3.2.5
	 */
	public static function recurring_cart_total( $total ) {
	}

}

if ( ! is_admin() ) {
	WC_Braintree_Field_Manager::init();
}
