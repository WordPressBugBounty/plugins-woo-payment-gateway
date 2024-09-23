<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/Subscriptions
 *
 */
class WC_Braintree_Subscriptions_Cart {

	/**
	 * @var bool
	 */
	private $recurring_total_calculation = false;

	/**
	 * @var
	 */
	private $current_recurring_cart_key;

	/**
	 * @var array
	 */
	private $recurring_discounts = array();

	/**
	 * @var array
	 */
	private $discounts = array();

	/**
	 * @since 3.2.1
	 * @var int
	 */
	private $calculation_count = 0;

	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 5, 10 );
		add_filter( 'woocommerce_add_to_cart_handler', array( $this, 'add_to_cart_handler' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_subscription_calculations' ) );
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'cart_totals_after_order_total' ) );
		add_action( 'woocommerce_cart_needs_payment', array( $this, 'cart_needs_payment' ), 10, 2 );
		add_filter( 'woocommerce_cart_product_price', array( $this, 'cart_product_price' ), 10, 2 );
		add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'cart_product_subtotal' ), 10, 3 );
	}

	/**
	 * If the item being added is a Braintree subscription, validate that there
	 * is a plan configured for the currency.
	 *
	 * @param bool $valid
	 * @param int $product_id
	 * @param int $quantity
	 * @param number $variation_id
	 * @param array $variations
	 */
	public function add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
		$product_id = ! $variation_id ? $product_id : $variation_id;
		if ( wcs_braintree_product_is_subscription( $product_id ) ) {
			$valid         = false;
			$plans         = wcs_braintree_get_plans();
			$product       = wc_get_product( $product_id );
			$product_plans = $product->get_braintree_plans();
			$currency      = get_woocommerce_currency();
			foreach ( $product_plans as $id ) {
				if ( $plans[ $id ]['currencyIsoCode'] === $currency ) {
					$valid = true;
					break;
				}
			}
			if ( ! $valid ) {
				wc_add_notice( sprintf( __( 'Error: product %1$s cannot be purchased using currency %2$s. You must assign a Braintree plan for currency %2$s.', 'woo-payment-gateway' ), $product->get_title(), $currency ), 'error' );

				return false;
			}
		}

		return $valid;
	}

	/**
	 *
	 * @param string $type
	 * @param WC_Product $product
	 */
	public function add_to_cart_handler( $type, $product ) {
		if ( $type === 'braintree-variable-subscription' || $type === 'braintree-subscription-variation' ) {
			$type = 'variable';
		}

		return $type;
	}

	public function set_subscription_calculations() {
		if ( wcs_braintree_cart_contains_subscription() && ! $this->recurring_total_calculation ) {
			add_filter( 'woocommerce_product_needs_shipping', array( $this, 'subscription_needs_shipping' ), 10, 2 );
			add_filter( 'woocommerce_product_get_subscription_price', array( $this, 'get_product_price' ), 10, 2 );
			add_filter( 'woocommerce_product_variation_get_subscription_price', array( $this, 'get_product_price' ), 10, 2 );
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'add_subscription_data' ), 20 );
			add_action( 'woocommerce_calculate_totals', array( $this, 'save_shipping_data' ), 10 );

			// commented 3.2.1 - sign-up fee added to product price
			//add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_cart_fees' ) );
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_subscription_calculations' ), 50 );
			add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_discount_amount' ), 10, 5 );
		}
	}

	public function remove_subscription_calculations() {
		remove_filter( 'woocommerce_product_needs_shipping', array( $this, 'subscription_needs_shipping' ), 10 );
		remove_filter( 'woocommerce_product_get_subscription_price', array( $this, 'get_product_price' ), 10 );
		remove_filter( 'woocommerce_product_variation_get_subscription_price', array( $this, 'get_product_price' ), 10 );
		remove_action( 'woocommerce_after_calculate_totals', array( $this, 'add_subscription_data' ), 10 );
		remove_action( 'woocommerce_calculate_totals', array( $this, 'save_shipping_data' ) );

		// commented 3.2.1 - sign-up fee added to product price
		// remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_cart_fees' ) );
		remove_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_discount_amount' ) );
	}

	/**
	 *
	 * @param WC_Cart $cart
	 */
	public function add_subscription_data( $cart ) {
		if ( $this->recurring_total_calculation ) {
			return;
		}
		if ( 0 == $this->calculation_count ) {
			WC()->session->set( 'wcs_braintree_coupons', WC()->cart->get_applied_coupons() );
		}
		$this->calculation_count ++;

		WC()->cart->set_applied_coupons( WC()->session->get( 'wcs_braintree_coupons' ) );

		$subscription_groups = array();

		WC()->cart->recurring_carts = array();
		$index                      = 0;
		foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {

			// product in cart_item is a subscription so perform logic.
			if ( wcs_braintree_product_is_subscription( $cart_item['data'] ) ) {
				$subscription_groups[ $this->get_recurring_cart_key( $cart_item['data'], $index ) ][] = $cart_key;
				$index ++;
			}
		}
		foreach ( $subscription_groups as $recurring_cart_key => $subscription_group ) {

			$recurring_cart                    = clone WC()->cart;
			$recurring_cart->is_recurring_cart = true;
			$recurring_cart->cart_key          = $recurring_cart_key;

			foreach ( $recurring_cart->get_cart() as $cart_item_key => $recurring_cart_item ) {
				// unset any keys that don't match this key. This is necessary
				// to calculate totals for each subscription group only.
				if ( ! in_array( $cart_item_key, $subscription_group ) ) {
					unset( $recurring_cart->cart_contents[ $cart_item_key ] );
				} else {
					// only the same products can be grouped together because of
					// the recurring_cart_key.
					$product = $recurring_cart_item['data'];
				}
			}
			$this->recurring_total_calculation = true;
			$this->current_recurring_cart_key  = $recurring_cart_key;

			/*
			 * recalculate the totals for the recurring cart
			 */
			$recurring_cart->calculate_totals();

			$trial_period = $product->get_subscription_trial_period();
			$trial_length = $product->get_subscription_trial_length();
			$length       = $product->get_subscription_length();
			$period       = $product->get_subscription_period();
			/**
			 * If a subscription product has a trial, then the next payment date
			 * should be the first payment date.
			 */
			$recurring_cart->merchant_account_id          = wc_braintree_get_merchant_account();
			$recurring_cart->start_date                   = wcs_braintree_calculate_start_date();
			$recurring_cart->first_payment_date           = wcs_braintree_calculate_first_payment_date( $trial_period, $trial_length );
			$recurring_cart->next_payment_date            = $product->has_trial() ? $recurring_cart->first_payment_date : wcs_braintree_calculate_next_payment_date( $recurring_cart->first_payment_date, $period, $product->get_subscription_period_interval() );
			$recurring_cart->trial_end_date               = $recurring_cart->first_payment_date;
			$recurring_cart->end_date                     = wcs_braintree_calculate_end_date( $length, $period, $trial_period, $trial_length );
			$recurring_cart->subscription_trial_length    = $trial_length;
			$recurring_cart->subscription_trial_period    = $trial_period;
			$recurring_cart->braintree_plan               = wcs_braintree_get_plan_from_product( $product );
			$recurring_cart->subscription_period          = $period;
			$recurring_cart->subscription_period_interval = $product->get_subscription_period_interval();
			$recurring_cart->subscription_length          = $length;

			WC()->cart->recurring_carts[ $recurring_cart_key ] = $recurring_cart;
		}

		$this->clear_discounts();

		$this->recurring_total_calculation = false;

		do_action( 'wcs_braintree_after_recurring_cart_calculations' );
	}

	/**
	 * @since 3.2.1
	 */
	private function clear_discounts() {
		$applied_coupons = WC()->cart->get_applied_coupons();
		foreach ( $this->recurring_discounts as $discount ) {
			foreach ( $applied_coupons as $index => $coupon ) {
				if ( $discount === $coupon ) {
					unset( $applied_coupons[ $index ] );
				}
			}
		}
		WC()->cart->set_applied_coupons( $applied_coupons );
		/**
		 * Recurring carts should have coupons worth $0 removed so they don't display
		 * on frontend.
		 * @var WC_Cart $cart
		 */
		foreach ( WC()->cart->recurring_carts as $cart ) {
			$applied_coupons = $cart->get_applied_coupons();
			foreach ( $applied_coupons as $index => $coupon ) {
				if ( in_array( $coupon, $this->discounts ) ) {
					unset( $applied_coupons[ $index ] );
				}
			}
			$cart->set_applied_coupons( $applied_coupons );
		}
	}

	/**
	 * Generate a recurring cart key using the product.
	 *
	 * @param WC_Product_Braintree_Subscription $product
	 */
	private function get_recurring_cart_key( $product, $index ) {
		return wcs_braintree_get_recurring_cart_key( $product );
	}

	/**
	 *
	 * @param bool $needs_shipping
	 * @param WC_Product $product
	 */
	public function subscription_needs_shipping( $needs_shipping, $product ) {
		if ( wcs_braintree_product_is_subscription( $product ) ) {
			if ( $this->recurring_total_calculation ) {
				if ( $product->is_one_time_shipping() ) {
					// don't want to keep charging shipping
					// on the recurring fee.
					$needs_shipping = false;
				}

				return $needs_shipping;
			} else {
				/**
				 * If a product is virtual then there is no shipping.
				 * If it's a physical product that requires shipping,
				 * check if there is a trial. Subscriptions with a trial must not
				 * include shipping in the regular
				 * $cart calculation because a trial indicates that the product will
				 * be shipped separately at a later date.
				 */
				if ( $needs_shipping ) {
					if ( $product->has_trial() ) {
						if ( ! $product->is_one_time_shipping() ) {
							$needs_shipping = false;
						}
					}
				}

				return $needs_shipping;
			}
		}

		return $needs_shipping;
	}

	/**
	 *
	 * @param float $price
	 * @param WC_Product $product
	 */
	public function get_product_price( $price, $product ) {
		if ( wcs_braintree_product_is_subscription( $product ) ) {
			/**
			 * If performing a regular cart calculation, check to see if the subscription has a trial;
			 * If it does, then it can't be charged with the order since it will start at a later date.
			 */
			if ( ! $this->recurring_total_calculation ) {
				if ( $product->has_trial() ) {
					$price = 0;
				}
				// If there is a sign-up fee add it to the product's price
				if ( $product->get_signup_fee() > 0 ) {
					$price = $price + $product->get_signup_fee();
				}
			}
		}

		return $price;
	}

	/**
	 * Calculate the discount amount for the recurring payment.
	 *
	 * @param int $discount
	 * @param int $price_to_discount
	 * @param object $item
	 * @param bool $bool
	 * @param WC_Coupon $coupon
	 */
	public function get_discount_amount( $discount, $price_to_discount, $item, $bool, $coupon ) {
		if ( wcs_braintree_product_is_subscription( $item['product_id'] ) ) {
			$type = get_post_meta( $coupon->get_id(), '_subscription_type', true );
			if ( $this->recurring_total_calculation ) {
				switch ( $type ) {
					case 'single':
						// the coupon only applies to the first payment so set to $0 since we
						// are in the recurring cart calculation
						$discount          = 0;
						$this->discounts[] = $coupon->get_code();
						break;
					case 'recurring':
					case 'recurring_only':
						break;
				}
			} else {
				switch ( $type ) {
					case 'recurring_only':
						$discount                    = 0;
						$this->recurring_discounts[] = $coupon->get_code();
						break;
				}
			}
		}

		return $discount;
	}

	/**
	 * Save the shipping data in the session so it can be retrieved later.
	 *
	 * @param WC_Cart $cart
	 */
	public function save_shipping_data( $cart ) {
		$shipping_data = array(
			'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods' ),
			'packages'                => WC()->shipping()->get_packages(),
		);
		if ( ! $this->recurring_total_calculation ) {
			WC()->session->set( 'wcs_braintree_shipping_data', $shipping_data );
		} else {
			if ( ! $cart->needs_shipping() ) {
				$shipping_data = array();
			}
			WC()->session->set( 'wcs_braintree_shipping_data_' . $cart->cart_key, $shipping_data );
		}
	}

	/**
	 * @param WC_Cart $cart
	 *
	 * @deprecated
	 */
	public function calculate_cart_fees( $cart ) {
		if ( ! $this->recurring_total_calculation ) {
			foreach ( $cart->get_cart() as $key => $item ) {
				$product = $item['data'];
				if ( wcs_braintree_product_is_subscription( $product ) ) {
					if ( $product->get_signup_fee() > 0 ) {
						$fee = $product->get_signup_fee() * $item['quantity'];
						$cart->add_fee( apply_filters( 'wcs_braintree_product_signup_fee_name', sprintf( __( '%s Signup Fee', 'woo-payment-gateway' ), $product->get_title() ), $product ), $fee, true );
					}
				}
			}
		}
	}

	public function cart_totals_after_order_total() {
		if ( WC()->cart->recurring_carts ) {
			wc_braintree_get_template( 'cart/cart-totals.php', array( 'recurring_carts' => WC()->cart->recurring_carts ) );
		}
	}

	public function cart_needs_payment( $needs_payment, $cart ) {
		if ( wcs_braintree_cart_contains_subscription() ) {
			$needs_payment = true;
		}

		return $needs_payment;
	}

	/**
	 *
	 * @param string $price
	 * @param WC_Product $product
	 */
	public function cart_product_price( $price, $product ) {
		if ( wcs_braintree_product_is_subscription( $product ) ) {
			$price = wcs_braintree_get_price_string( $price, $product->get_subscription_period_interval(), $product->get_subscription_period() );
		}

		return $price;
	}

	/**
	 *
	 * @param string $price
	 * @param WC_Product $product
	 * @param int $quantity
	 */
	public function cart_product_subtotal( $price, $product, $quantity ) {
		if ( wcs_braintree_product_is_subscription( $product ) ) {
			$price = wcs_braintree_get_product_price_html( $product, $price, $quantity );
		}

		return $price;
	}
}

new WC_Braintree_Subscriptions_Cart();
