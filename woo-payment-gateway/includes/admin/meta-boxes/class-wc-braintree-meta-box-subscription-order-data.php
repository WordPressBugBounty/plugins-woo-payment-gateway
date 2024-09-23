<?php

/**
 *
 * @since   3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Meta_Box_Subscription_Order_Data {

	private static $processing_update = false;

	private static $calculating_totals = false;

	private static $_new_order_item = false;

	/**
	 *
	 * @var WC_Order
	 */
	private static $current_order = null;

	public static function init() {
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save' ), 60, 2 );
		add_action( 'woocommerce_order_before_calculate_taxes', array( __CLASS__, 'before_calculate_totals' ), 10, 2 );
		add_action( 'woocommerce_order_after_calculate_totals', array( __CLASS__, 'after_calculate_totals' ) );
		add_action( 'woocommerce_order_before_calculate_totals', array( __CLASS__, 'before_calculate_totals' ), 10, 2 );
		/*
		 * add_action ( 'woocommerce_order_after_calculate_totals', array(
		 * __CLASS__, 'after_calculate_totals'
		 * ) );
		 */
		add_action( 'woocommerce_ajax_add_order_item_meta', array( __CLASS__, 'validate_order_item' ), 10, 3 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 40, 2 );
		add_action( 'woocommerce_new_order_item', array( __CLASS__, 'new_order_item' ), 10, 3 );
		add_action( 'woocommerce_update_order_item', array( __CLASS__, 'update_order_item' ), 10, 3 );
		add_action( 'woocommerce_before_delete_order_item', array( __CLASS__, 'delete_order_item' ) );
	}

	public static function add_meta_boxes( $post_type, $post ) {
		if ( $post_type === 'bfwc_subscription' ) {
			add_meta_box( 'wcs-braintree-related-orders', __( 'Related Orders', 'woo-payment-gateway' ), array( __CLASS__, 'subscription_related_orders_view' ), 'bfwc_subscription', 'normal', 'default' );
		}
		if ( $post_type === 'shop_order' ) {
			add_meta_box( 'wcs-braintree-related-orders', __( 'Related Subscriptions', 'woo-payment-gateway' ), array( __CLASS__, 'related_orders_view' ), 'shop_order', 'normal', 'default' );
		}
		add_filter( 'wc_order_statuses', array( __CLASS__, 'order_statuses' ) );
	}

	/**
	 *
	 * @param WP_Post $post
	 */
	public static function subscription_related_orders_view( $post ) {
		$subscription = wcs_braintree_get_subscription( $post->ID );
		$orders       = wcs_braintree_get_related_orders( $subscription );
		include 'views/html-subscription-related-orders.php';
	}

	public static function related_orders_view( $post ) {
		$order           = wc_get_order( $post->ID );
		$subscription_id = $order->get_meta( '_subscription_id', true );
		if ( wcs_braintree_order_contains_subscription( $order ) || $subscription_id ) {
			$subscriptions = wcs_braintree_get_subscriptions_for_order( $order );
			if ( empty( $subscriptions ) ) {
				$subscriptions[] = wcs_braintree_get_subscription( $subscription_id );
			}
			include 'views/html-related-orders.php';
		}
	}

	public static function order_statuses( $statuses ) {
		if ( self::is_subscription_type() ) {
			$statuses = array_merge( $statuses, wcs_braintree_get_subscription_statuses() );
		}

		return $statuses;
	}

	private static function is_subscription_type() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		return $screen_id === 'bfwc_subscription';
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	private static function order_is_subscription( $order ) {
		return $order && 'bfwc_subscription' === $order->get_type();
	}

	/**
	 *
	 * @param bool     $and_taxes
	 * @param WC_order $order
	 */
	public static function before_calculate_totals( $and_taxes, $order ) {
		self::$calculating_totals = true;
		self::$current_order      = $order;
		add_filter( 'woocommerce_order_item_get_total', array( __CLASS__, 'get_item_total' ), 10, 2 );
		add_filter( 'woocommerce_order_item_get_subtotal', array( __CLASS__, 'get_item_total' ), 10, 2 );
	}

	public static function after_calculate_totals() {
		self::$calculating_totals = false;
		remove_filter( 'woocommerce_order_item_get_total', array( __CLASS__, 'get_item_total' ) );
		remove_filter( 'woocommerce_order_item_get_subtotal', array( __CLASS__, 'get_item_total' ) );
	}

	/**
	 *
	 * @param float         $total
	 * @param WC_Order_Item $item
	 */
	public static function get_item_total( $total, $item ) {
		if ( 'line_item' === $item->get_type() ) {
			if ( ! self::order_is_subscription( $item->get_order() ) ) {
				$product = $item->get_product();
				if ( wcs_braintree_product_is_subscription( $product ) ) {
					if ( $product->has_trial() ) {
						$total = 0;
					}
				}
			}
		}

		return $total;
	}

	public static function save( $post_id, $post ) {
		$shop_order = wc_get_order( $post_id );
		if ( self::order_is_subscription( $shop_order ) ) {
			$subscription = wcs_braintree_get_subscription( $post_id );
			if ( ! $subscription->is_created() ) {
				// A subscription needs an order associated with it.
				$order_id = $subscription->get_parent_id();
				if ( ! $order_id ) {
					$order = wc_create_order(
						array(
							'created_via'   => 'admin',
							'customer_user' => $subscription->get_customer_id(),
						)
					);
					$order->set_status( wc_clean( $_POST['order_status'] ) );
					$subscription->set_parent_id( $order->get_id() );
					$subscription->save();
				} else {
					$order = wc_get_order( $order_id );
				}
				$order->set_address( $subscription->get_address( 'billing' ), 'billing' );
				$order->set_address( $subscription->get_address( 'shipping' ), 'shipping' );
				$order->set_customer_id( $subscription->get_customer_id() );
				$order->save();
			}
		} else {
			if ( wcs_braintree_order_contains_subscription( $shop_order ) ) {
				foreach ( wcs_braintree_get_subscriptions_for_order( $shop_order ) as $subscription ) {
					if ( $subscription->has_status( array( 'pending' ) ) ) {
						WC_Meta_Box_Order_Data::save( $subscription->get_id() );
					}
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Braintree_Subscription $subscription
	 * @param WC_Order_Item_Product     $item
	 */
	private static function add_subscription_props( $subscription, $item ) {
		$product            = $item->get_product();
		$start_date         = wcs_braintree_calculate_start_date();
		$first_payment_date = wcs_braintree_calculate_first_payment_date( $product->get_subscription_trial_period(), $product->get_subscription_trial_length() );
		$next_payment_date  = $product->has_trial() ? $first_payment_date : wcs_braintree_calculate_next_payment_date( $first_payment_date, $product->get_subscription_period(), $product->get_subscription_period_interval() );
		$end_date           = wcs_braintree_calculate_end_date( $product->get_subscription_length(), $product->get_subscription_period(), $product->get_subscription_trial_period(), $product->get_subscription_trial_length() );
		$subscription->set_props(
			array(
				'recurring_cart_key'           => wcs_braintree_get_recurring_cart_key( $product ),
				'start_date'                   => $start_date,
				'next_payment_date'            => $next_payment_date,
				'first_payment_date'           => $first_payment_date,
				'end_date'                     => $end_date,
				'trial_end_date'               => $first_payment_date,
				'subscription_trial_length'    => $product->get_subscription_trial_length(),
				'subscription_trial_period'    => $product->get_subscription_trial_period(),
				'braintree_plan'               => wcs_braintree_get_plan_from_product( $product ),
				'subscription_period'          => $product->get_subscription_period(),
				'subscription_period_interval' => $product->get_subscription_period_interval(),
				'subscription_length'          => $product->get_subscription_length(),
				'merchant_account_id'          => wc_braintree_get_merchant_account(),
				'created_in_braintree'         => false,
			)
		);
	}

	/**
	 * Method that validates all order items in a subscription.
	 * If un-like products are added to the subscription, the item is deleted from the
	 * subscription and an error is triggered.
	 *
	 * @param WC_Order_Item[] $added_items
	 * @param WC_Order        $order
	 */
	public static function validate_order_item( $item_id, $item, $order ) {
		try {
			if ( self::order_is_subscription( $order ) ) {
				foreach ( $order->get_items( 'line_item' ) as $line_item ) {
					$keys = array(
						wcs_braintree_get_recurring_cart_key( $item->get_product() ),
						wcs_braintree_get_recurring_cart_key( $line_item->get_product() ),
					);
					if ( count( array_unique( $keys ) ) > 1 ) {
						throw new Exception( sprintf( __( 'Product %1$s cannot be assigned to this subscription. Reason: the products do not have the same billing schedule.', 'woo-payment-gateway' ), $item->get_product()->get_name() ) );
					}
				}
			}
		} catch ( Exception $e ) {
			wc_delete_order_item( $item_id );
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 *
	 * @param int           $item_id
	 * @param WC_Order_Item $item
	 * @param int           $order_id
	 */
	public static function new_order_item( $item_id, $item, $order_id ) {
		if ( ! self::$_new_order_item && 'line_item' === $item->get_type() ) {
			/*
			 * remove_action ( 'woocommerce_new_order_item', array(
			 * __CLASS__, 'new_order_item'
			 * ) );
			 */
			self::$_new_order_item = true;
			$shop_order            = wc_get_order( $order_id );
			$item_order_id         = 0;
			if ( self::order_is_subscription( $shop_order ) ) {
				if ( ( $order = wc_get_order( $shop_order->get_parent_id() ) ) == false ) {
					$order = wc_create_order( array( 'customer_id' => $shop_order->get_customer_id() ) );
					$shop_order->set_parent_id( $order->get_id() );
					$shop_order->save();
				}
				self::add_subscription_props( $shop_order, $item );
				$shop_order->save();
				$item_order_id = $order->get_id();
			} elseif ( is_a( $shop_order, 'WC_Order' ) ) {
				if ( wcs_braintree_product_is_subscription( $item->get_product() ) ) {
					$cart_key     = wcs_braintree_get_recurring_cart_key( $item->get_product() );
					$subscription = wcs_braintree_get_subscription_from_recurring_cart_key( $cart_key, $shop_order->get_id() );
					if ( ! $subscription ) {
						$subscription = new WC_Braintree_Subscription();
						$subscription->set_customer_id( $shop_order->get_customer_id() );
						$subscription->set_parent_id( $shop_order->get_id() );
						$subscription->set_recurring_cart_key( $cart_key );
						self::add_subscription_props( $subscription, $item );
						$subscription->save();
					}
					$item_order_id = $subscription->get_id();
				}
			}
			if ( $item_order_id ) {
				// this is a subscription item
				/**
				 *
				 * @var WC_Order_Item_Data_Store $data_store
				 */
				$data_store = WC_Data_Store::load( 'order-item' );
				$new_item   = clone $item;
				$new_item->set_id( 0 );
				$new_item->set_order_id( $item_order_id );
				$new_item->add_meta_data( '_reference_item', $item->get_id() );
				$new_item->save();
				// save using data store so it's silent and update item hook isn't called.
				$data_store->update_metadata( $item_id, '_reference_item', $new_item->get_id() );
				$shop_order->calculate_totals();
				wc_get_order( $item_order_id )->calculate_totals();
			}
			self::$_new_order_item = false;
		}
	}

	/**
	 *
	 * @param int           $item_id
	 * @param WC_Order_Item $item
	 * @param int           $order_id
	 */
	public static function update_order_item( $item_id, $item, $order_id ) {
		if ( self::$processing_update ) {
			return;
		}
		self::$processing_update = true;
		$shop_order              = wc_get_order( $order_id );
		$reference_item          = WC_Order_Factory::get_order_item( $item->get_meta( '_reference_item' ), true );
		if ( 'line_item' === $item->get_type() && $reference_item ) {
			$reference_item->set_props(
				array(
					'quantity' => $item->get_quantity(),
					'subtotal' => $item->get_subtotal(),
					'total'    => $item->get_total(),
				)
			);
			$reference_item->save();
			$reference_item->get_order()->calculate_totals();
		}
		self::$processing_update = false;
	}

	/**
	 *
	 * @param int $item_id
	 */
	public static function delete_order_item( $item_id ) {
		/**
		 *
		 * @var WC_Order_Item_Data_Store $data_store
		 */
		$data_store = WC_Data_Store::load( 'order-item' );
		$item       = WC_Order_Factory::get_order_item( $item_id );
		if ( false === $item ) {
			return;
		}
		$shop_order        = $item->get_order();
		$reference_item_id = $item->get_meta( '_reference_item', true );
		if ( $reference_item_id ) {
			$reference_item = WC_Order_Factory::get_order_item( $reference_item_id );
			$order          = $reference_item->get_order();
			if ( self::order_is_subscription( $order ) ) {
				$order->set_recurring_cart_key( '' );
				$order->save();
			}
			$data_store->delete_order_item( $reference_item_id );
		}
		if ( self::order_is_subscription( $shop_order ) ) {
			$shop_order->set_recurring_cart_key( '' );
			$shop_order->save();
		}
	}

}

WC_Braintree_Meta_Box_Subscription_Order_Data::init();
