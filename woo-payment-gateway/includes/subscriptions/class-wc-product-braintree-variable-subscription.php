<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	return;
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/Subscriptions
 *
 */
class WC_Product_Braintree_Variable_Subscription extends WC_Product_Variable {

	/**
	 *
	 * @since 2.6.2
	 */
	public static function init() {
		add_filter( 'woocommerce_data_stores', __CLASS__ . '::add_data_store' );
	}

	public function __construct( $product ) {
		$this->extra_data['min_subscription_price']              = 0;
		$this->extra_data['max_subscription_price']              = 0;
		$this->extra_data['min_subscription_price_variation_id'] = 0;
		$this->extra_data['max_subscription_price_variation_id'] = 0;
		$this->extra_data['subscription_one_time_shipping']      = '';

		parent::__construct( $product );

		$this->subscription_period = 'month';

		$this->post = get_post( $this->id );
	}

	public static function add_data_store( $stores ) {
		$stores['product-braintree-variable-subscription'] = 'WC_Product_Variable_Data_Store_CPT';
		return $stores;
	}

	/**
	 *
	 * @since 2.6.2
	 *        Magic method for fetching properties.
	 *        This method was overridden in version 2.6.2 because WC 3.0.0+ issues warnings
	 *        when calling properties directly.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Product::__get()
	 */
	public function __get( $key ) {
		return $this->get_prop( $key );
	}

	public function __set( $key, $value ) {
		$this->set_prop( $key, $value );
	}

	public function get_price_html( $price = '' ) {
		if ( $this->sync_required() ) {
			self::sync_product( $this );
		}
		if ( $this->min_subscription_price_variation_id !== $this->max_subscription_price_variation_id ) {
			$low_variation  = $this->get_child( $this->min_subscription_price_variation_id );
			$high_variation = $this->get_child( $this->max_subscription_price_variation_id );

			$text = sprintf( __( 'From %1$s to %2$s', 'woo-payment-gateway' ), $low_variation->get_price_html(), $high_variation->get_price_html() );
		} else {
			$variation = $this->get_child( $this->min_subscription_price_variation_id );
			$text      = $variation->get_price_html();
		}
		return $text;
	}

	public function get_available_variation( $variation ) {
		$variation_data = parent::get_available_variation( $variation );
		if ( empty( $variation_data['price_html'] ) ) {
			$variation_data['price_html'] = '<span class="price">' . $variation->get_price_html() . '</span>';
		}
		return $variation_data;
	}

	/**
	 *
	 * @version 2.6.2 - WC 3.0.0 compatability check added.
	 * {@inheritDoc}
	 *
	 * @see WC_Product_Variable::get_child()
	 * @return WC_Product_Braintree_Subscription_Variation
	 */
	public function get_child( $child_id ) {
		return new WC_Product_Braintree_Subscription_Variation( $child_id );
	}

	public function get_variation_subscription_prices() {
		$min_price = $this->min_subscription_price;
		$max_price = $this->max_subscription_price;

		// sync if min or max is not saved.
		if ( ! $min_price || ! $max_price ) {
			self::sync_product( $this->id );
		}

		return array(
			'min' => $min_price,
			'max' => $max_price,
		);
	}

	public function get_plans() {
		$var = 'braintree_' . wc_braintree_environment() . '_plans';
		return $this->$var;
	}

	/**
	 * Return the subscription signup fee.
	 *
	 * @return string $fee;
	 */
	public function get_signup_fee() {
		return $this->subscription_sign_up_fee ? $this->subscription_sign_up_fee : 0;
	}

	/**
	 * Return true if shipping is only charged once for the product.
	 * If there is a trial period then shipping must be included
	 * in all charges if there is any shipping.
	 */
	public function is_one_time_shipping() {
		return $this->needs_shipping() && $this->subscription_one_time_shipping === 'yes' && ! $this->subscription_trial_length;
	}

	/**
	 *
	 * @param
	 *          mixed WC_Product|int $product
	 * @param bool $save
	 */
	public static function sync_product( $product, $save = true ) {
		$product  = new self( $product );
		$children = get_posts(
			array(
				'post_parent'    => $product->get_id(),
				'posts_per_page' => - 1,
				'post_type'      => 'product_variation',
				'post_status'    => 'publish',
			)
		);

		if ( ! $children ) {
			return;
		}

		$price_types = array( 'subscription_price' );

		foreach ( $price_types as $price_type ) {

			$min_price_type              = null;
			$max_price_type              = null;
			$min_price_type_variation_id = null;
			$max_price_type_variation_id = null;

			foreach ( $children as $child ) {

				$child_price = get_post_meta( $child->ID, '_' . $price_type, true );

				// if the min price_type is null or it's greater than the child_pricetype, change it's value to the
				// child_price.
				if ( is_null( $min_price_type ) || $min_price_type > $child_price ) {
					$min_price_type              = $child_price;
					$min_price_type_variation_id = $child->ID;
				}

				if ( is_null( $max_price_type ) || $max_price_type < $child_price ) {
					$max_price_type              = $child_price;
					$max_price_type_variation_id = $child->ID;
				}
			}

			$product->{"set_min_$price_type"}( $min_price_type );
			$product->{"set_max_$price_type"}( $max_price_type );
			$product->{"set_min_{$price_type}_variation_id"}( $min_price_type_variation_id );
			$product->{"set_max_{$price_type}_variation_id"}( $max_price_type_variation_id );
			$product->save();
		}
	}

	/**
	 * Check to see if the product needs to be synced.
	 */
	public function sync_required() {
		return ! $this->min_subscription_price || ! $this->max_subscription_price;
	}

	public function add_to_cart_handler( $product_type, $product ) {
		if ( $this->get_type() === $product_type ) {
			$product_type = 'variable';
		}
		return $product_type;
	}

	public function get_type() {
		return 'braintree-variable-subscription';
	}

	public function get_product_type() {
		return $this->product_type;
	}

	public function set_min_subscription_price( $price ) {
		$this->min_subscription_price = $price;
	}

	public function set_max_subscription_price( $price ) {
		$this->max_subscription_price = $price;
	}

	public function set_min_subscription_price_variation_id( $id ) {
		$this->min_subscription_price_variation_id = $id;
	}

	public function set_max_subscription_price_variation_id( $id ) {
		$this->max_subscription_price_variation_id = $id;
	}

	public function set_subscription_one_time_shipping( $one_time_shipping ) {
		$this->subscription_one_time_shipping = $one_time_shipping;
	}

	public function get_min_subscription_price() {
		return $this->min_subscription_price;
	}

	public function get_max_subscription_price() {
		return $this->max_subscription_price;
	}

	public function get_min_subscription_price_variation_id() {
		return $this->min_subscription_price_variation_id;
	}

	public function get_max_subscription_price_variation_id() {
		return $this->max_subscription_price_variation_id;
	}

	public function get_subscription_one_time_shipping() {
		return $this->subscription_one_time_shipping;
	}
}
WC_Product_Braintree_Variable_Subscription::init();
