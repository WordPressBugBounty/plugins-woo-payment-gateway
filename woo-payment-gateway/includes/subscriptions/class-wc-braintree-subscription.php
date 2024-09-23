<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Order' ) ) {
	return;
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/Subscriptions
 *
 */
class WC_Braintree_Subscription extends WC_Order {

	protected $data_store_name = 'braintree_subscription';

	/**
	 *
	 * @var WC_Order
	 */
	public $order;

	public function __construct( $order = 0 ) {
		parent::__construct( $order );

		$this->order = wc_get_order( $this->get_parent_id() );
	}

	public function get_type() {
		return 'bfwc_subscription';
	}

	public function get_order( $id = 0 ) {
		return $this->order;
	}

	public function wc_braintree_get_post() {
		return get_post( $this->get_id() );
	}

	public function get_formatted_line_subtotal( $item, $tax_display = '' ) {
		$product  = $item->get_product();
		$subtotal = parent::get_formatted_line_subtotal( $item, $tax_display );
		if ( wcs_braintree_product_is_subscription( $product ) ) {
			$subtotal = wcs_braintree_get_price_string( $subtotal, $product->get_subscription_period_interval(), $product->get_subscription_period() );
		}

		return $subtotal;
	}

	/**
	 * Update the Subscription status.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Abstract_Order::update_status()
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		$new_status = ( strpos( $new_status, 'wc-' ) !== false ) ? substr( $new_status, 3 ) : $new_status;
		$this->set_status( $new_status );
		$this->save();
	}

	public function save() {
		if ( isset( $this->changes['status'] ) ) {
			if ( ! $this->can_be_updated_to( $this->get_status() ) ) {
				$this->add_order_note( sprintf( __( 'Status cannot be updated to %1$s from %2$s.', 'woo-payment-gateway' ), wcs_braintree_get_subscription_status_name( $this->get_status() ), wcs_braintree_get_subscription_status_name( $this->data['status'] ) ) );
				unset( $this->changes['status'] );
				$this->status_transition = false;
			}
		}
		parent::save();
	}

	public function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				do_action( 'wcs_braintree_subscription_status_' . $status_transition['to'], $this->get_id(), $this );
				if ( ! empty( $status_transition['from'] ) ) {
					do_action( 'wcs_braintree_subscription_status_' . $status_transition['from'] . '_' . $status_transition['to'], $this->get_id(), $this );
					$note = sprintf( __( 'Subscription status changed from %1$s to %2$s.', 'woo-payment-gateway' ), wcs_braintree_get_subscription_status_name( $status_transition['from'] ), wcs_braintree_get_subscription_status_name( $status_transition['to'] ) );
				} else {
					$note = sprintf( __( 'Subscription status set to %1$s.', 'woo-payment-gateway' ), wcs_braintree_get_subscription_status_name( $status_transition['to'] ) );
				}
				$this->add_order_note( $note, 0, $status_transition['manual'] );
			} catch ( Exception $e ) {
				wc_get_logger()->error(
					sprintf(
						'Status transition of order #%d errored!',
						$this->get_id(),
						array(
							'order' => $this,
							'error' => $e,
						)
					)
				);
			}
		}
	}

	public function payment_complete( $transaction_id = '' ) {
		if ( ! empty( $transaction_id ) ) {
			$this->set_transaction_id( $transaction_id );
		}
		$this->update_status( 'active' );
	}

	public function get_valid_statuses() {
		return array_keys( array_merge( wc_get_order_statuses(), wcs_braintree_get_subscription_statuses() ) );
	}

	public function get_order_item_totals( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );
		$total_rows  = array();

		$this->add_order_item_totals_subtotal_row( $total_rows, $tax_display );
		$this->add_order_item_totals_discount_row( $total_rows, $tax_display );
		$this->add_order_item_totals_shipping_row( $total_rows, $tax_display );
		$this->add_order_item_totals_fee_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_tax_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_payment_method_row( $total_rows, $tax_display );
		$this->add_order_item_totals_refund_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_total_row( $total_rows, $tax_display );

		return apply_filters( 'wcs_braintree_get_order_item_totals', $total_rows, $this, $tax_display );
	}

	/**
	 * Add total row for subtotal.
	 *
	 * @param array  $total_rows
	 * @param string $tax_display
	 */
	protected function add_order_item_totals_subtotal_row( &$total_rows, $tax_display ) {
		parent::add_order_item_totals_subtotal_row( $total_rows, $tax_display );
	}

	protected function add_order_item_totals_total_row( &$total_rows, $tax_display ) {
		$total_rows['recurring_total'] = array(
			'label' => __( 'Recurring Total:', 'woo-payment-gateway' ),
			'value' => $this->get_formatted_order_total( $tax_display ),
		);
	}

	public function get_formatted_order_total( $tax_display = '', $display_refunded = true ) {
		$total = parent::get_formatted_order_total( $tax_display, $display_refunded );
		$total = wcs_braintree_get_price_string( $total, $this->get_subscription_period_interval(), $this->get_subscription_period() );

		return apply_filters( 'wcs_braintree_subscription_get_formatted_order_total', $total, $this );
	}

	/**
	 * Does the subscription have a length or does it never expire.
	 *
	 * @return bool
	 */
	public function never_expires() {
		return 0 === (int) $this->get_subscription_length();
	}

	public function get_length() {
		return $this->subscription_length;
	}

	public function get_billing_interval() {
		return $this->subscription_period_interval;
	}

	public function get_period() {
		return 'month';
	}

	/**
	 * Return true if the subscription has a trial period.
	 *
	 * @return bool
	 */
	public function has_trial() {
		return 0 !== (int) $this->get_subscription_trial_length();
	}

	public function get_trial_period() {
		return $this->subscription_trial_period;
	}

	public function get_trial_length() {
		return $this->subscription_trial_length;
	}

	public function get_timezone() {
		wc_timezone_string();
	}

	public function get_date_key( $type ) {
		if ( substr( $type, 0, 1 ) !== '_' ) {
			$type = '_' . $type;
		}

		return strpos( $type, '_date' ) !== false ? $type : "{$type}_date";
	}

	/**
	 * Return the datetime object for the specified date.
	 * All dates are returned in UTC.
	 *
	 * @return DateTime
	 */
	public function get_date( $type ) {
		$key  = $this->get_date_key( $type );
		$date = $this->get_meta( $key );

		if ( ! $date ) {
			// Check if key without underscore exists.
			$date = $this->get_meta( substr( $key, 1 ) );
		}
		// all dates are stored as UTC in the database.
		if ( ! is_object( $date ) ) {
			$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $date, new DateTimeZone( 'UTC' ) );
		}

		return $date;
	}

	/**
	 *
	 * @param string $type
	 * @param
	 *          mixed string|DateTime $date
	 */
	public function update_date( $type, $date ) {
		if ( $date instanceof DateTime ) {
			$date->setTimezone( new DateTimeZone( 'UTC' ) );
			$date_string = $date->format( 'Y-m-d H:i:s' );
		} else {
			$date_string = $date;
		}
		$key = $this->get_date_key( $type );
		$this->update_meta_data( $key, $date_string );
		$this->save();
	}

	/**
	 * Return the descriptor for the type provided.
	 * Valid types are <strong>name</strong>, <strong>phone</strong>, <strong>url</strong>
	 *
	 * @param unknown $type
	 */
	public function get_descriptor( $type ) {
		$descriptors = $this->descriptor;
		if ( $descriptors ) {
			return isset( $descriptors[ $type ] ) ? $descriptors[ $type ] : '';
		}

		return '';
	}

	/**
	 * Return true if descriptors have been configured for the subscription.
	 */
	public function has_descriptors() {
		return (bool) $this->descriptors;
	}

	public function update_payment_method_title( $title ) {
		$this->set_payment_method_title( $title );
	}

	public function update_payment_method_token( $token = '' ) {
		$this->update_meta_data( '_payment_method_token', $token );
	}

	/**
	 *
	 * @param bool $bool
	 */
	public function set_created( $value ) {
		$this->set_created_in_braintree( $value );
	}

	/**
	 * Return true if the subscription has been created within Braintree.
	 */
	public function is_created() {
		return $this->get_created_in_braintree();
	}

	public function get_formatted_total() {
		$total = $this->get_total();
		extract(
			array(
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimals'           => wc_get_price_decimals(),
				'price_format'       => get_woocommerce_price_format(),
			)
		);
		$total        = number_format( $total, $decimals, $decimal_separator, $thousand_separator );
		$total_string = sprintf( '%1$s%2$s', get_woocommerce_currency_symbol( $this->get_currency() ), $total );
		$total_string = wcs_braintree_get_price_string( $total_string, $this->get_subscription_period_interval(), $this->get_subscription_period() );

		return apply_filters( 'wcs_braintree_subscription_formatted_order_total', $total_string, $this );
	}

	/**
	 * Return a formatted date string using the timezone that the subscription was created in.
	 *
	 * @param string $type
	 * @param string $format
	 */
	public function get_formatted_date( $type, $format = null ) {
		$format = $format ? $format : get_option( 'date_format' );
		$date   = $this->get_date( $type );
		if ( $date ) {
			$date->setTimezone( new DateTimeZone( wc_timezone_string() ) );

			return $date->format( $format );
		}
		switch ( $type ) {
			case 'end':
				return __( 'Never Expires', 'woo-payment-gateway' );
		}
	}

	/**
	 * return true if child orders have been processed for the subscription.
	 */
	public function has_child_orders() {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_subscription_id' AND meta_value = %s", $this->id ) );

		return ! empty( $result );
	}

	public function get_payment_method_to_display() {
		return apply_filters( 'wcs_braintree_get_payment_method_to_display', $this->get_payment_method_title(), $this );
	}

	public function get_view_subscription_url() {
		$url = wc_get_endpoint_url( 'view-subscription', $this->id, wc_get_page_permalink( 'myaccount' ) );

		return apply_filters( 'wcs_braintree_subscription_view_url', $url, $this );
	}

	/**
	 * Return true if the subscription starts on the last day of the month.
	 *
	 * @return bool
	 */
	public function last_day_of_month() {
		$date = $this->get_date( 'start' );
		$date->setTimezone( new DateTimeZone( $this->subscription_time_zone ) );
		if ( $date->format( 'j' ) === $date->format( 't' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Sync the subscription's dates before creating in Braintree.
	 * This will allow for accurate date calculations. For example, when a subscription is created by the
	 * admin, a customer may not pay for it for several days. The start date, etc need to be re-calculated.
	 */
	public function sync_dates() {
		if ( ! $this->is_created() ) {
			// update all of the dates.
			$this->update_date( 'start', wcs_braintree_calculate_start_date() );
			$this->update_date( 'first_payment', wcs_braintree_calculate_first_payment_date( $this->get_subscription_trial_period(), $this->get_subscription_trial_length() ) );
			$this->update_date( 'end', wcs_braintree_calculate_end_date( $this->get_subscription_length(), $this->get_subscription_period(), $this->get_subscription_trial_period(), $this->get_subscription_trial_length(), $this->get_timezone() ) );
			$this->update_date( 'next_payment', $this->has_trial() ? $this->get_date( 'first_payment' ) : wcs_braintree_calculate_next_payment_date( $this->get_date( 'first_payment' ), $this->get_subscription_period(), $this->get_subscription_period_interval() ) );
			if ( $this->has_trial() ) {
				$this->update_date( 'trial_end', $this->get_date( 'first_payment' ) );
			}
		}
	}

	/**
	 * Return the number of billing cycles a subsciption has.
	 */
	public function get_num_of_billing_cycles() {
		$length = ! $this->has_trial() ? $this->get_subscription_length() - 1 : $this->get_subscription_length();

		return ! $this->never_expires() ? floor( $length / $this->get_subscription_period_interval() ) : 0;
	}

	public function get_checkout_payment_url( $on_checkout = false ) {
		return $this->order->get_checkout_payment_url( $on_checkout );
	}

	/**
	 * Return if the subscription's status can be update to the provided status.
	 *
	 * @param string $status
	 */
	public function can_be_updated_to( $status ) {
		$current_status = $this->data['status'];
		$result         = true;
		$status         = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
		switch ( $status ) {
			case 'active':
				if ( in_array( $current_status, array( 'expired', 'cancelled' ) ) ) {
					$result = false;
				}
				break;
			case 'cancelled':
				if ( ! in_array( $current_status, array( 'active', 'on-hold', 'past-due' ) ) ) {
					$result = false;
				}
				break;
			case 'on-hold':
				if ( ! in_array( $current_status, array( 'active', 'processing', 'pending' ) ) ) {
					$result = false;
				}
				break;
			case 'expired':
				if ( ! in_array( $current_status, array( 'active', 'on-hold' ) ) ) {
					$result = false;
				}
				break;
			case 'past-due':
				if ( ! in_array( $current_status, array( 'active', 'on-hold', 'processing', 'pending' ) ) ) {
					$result = false;
				}
				break;
		}

		return apply_filters( 'wcs_braintree_subscription_can_be_updated_to', $result, $this );
	}

	private function set_date( $prop, $value ) {
		if ( $value instanceof DateTime ) {
			$value = $value->format( 'Y-m-d H:i:s' );
		}
		$this->update_meta_data( $this->get_date_key( $prop ), $value );
	}

	public function set_merchant_account_id( $value ) {
		$this->update_meta_data( '_merchant_account_id', $value );
	}

	public function set_start_date( $value ) {
		$this->set_date( '_start_date', $value );
	}

	public function set_next_payment_date( $value ) {
		$this->set_date( '_next_payment_date', $value );
	}

	public function set_previous_payment_date( $value ) {
		$this->set_date( '_previous_payment_date', $value );
	}

	public function set_trial_end_date( $value ) {
		$this->set_date( '_trial_end_date', $value );
	}

	public function set_end_date( $value ) {
		$this->set_date( '_end_date', $value );
	}

	public function set_subscription_trial_length( $value ) {
		$this->update_meta_data( '_subscription_trial_length', $value );
	}

	public function set_subscription_trial_period( $value ) {
		$this->update_meta_data( '_subscription_trial_period', $value );
	}

	public function set_first_payment_date( $value ) {
		$this->update_meta_data( '_first_payment_date', $value );
	}

	public function set_braintree_plan( $value ) {
		$this->update_meta_data( '_braintree_plan', $value );
	}

	public function set_subscription_period( $value ) {
		$this->update_meta_data( '_subscription_period', $value );
	}

	public function set_subscription_period_interval( $value ) {
		$this->update_meta_data( '_subscription_period_interval', $value );
	}

	public function set_subscription_length( $value ) {
		$this->update_meta_data( '_subscription_length', $value );
	}

	public function set_created_in_braintree( $value ) {
		$this->update_meta_data( '_created_in_braintree', $value );
	}

	public function set_recurring_cart_key( $value ) {
		$this->update_meta_data( '_recurring_cart_key', $value );
	}

	/**
	 * * Getters **
	 */
	public function get_merchant_account_id() {
		return $this->get_meta( '_merchant_account_id' );
	}

	public function get_start_date() {
		return $this->get_meta( '_start_date' );
	}

	public function get_trial_end_date() {
		return $this->get_meta( '_trial_end_date' );
	}

	public function get_end_date() {
		return $this->get_meta( '_end_date' );
	}

	public function get_subscription_trial_length() {
		return $this->get_meta( '_subscription_trial_length' );
	}

	public function get_subscription_trial_period() {
		return $this->get_meta( '_subscription_trial_period' );
	}

	public function get_first_payment_date() {
		return $this->get_meta( '_first_payment_date' );
	}

	public function get_next_payment_date() {
		return $this->get_meta( '_next_payment_date' );
	}

	public function get_previous_payment_date() {
		return $this->get_meta( '_previous_payment_date' );
	}

	public function get_braintree_plan() {
		return $this->get_meta( '_braintree_plan' );
	}

	public function get_subscription_period() {
		return $this->get_meta( '_subscription_period' );
	}

	public function get_subscription_period_interval() {
		return $this->get_meta( '_subscription_period_interval' );
	}

	public function get_subscription_length() {
		return $this->get_meta( '_subscription_length' );
	}

	public function get_created_in_braintree() {
		return $this->get_meta( '_created_in_braintree' );
	}

	public function get_recurring_cart_key() {
		return $this->get_meta( '_recurring_cart_key' );
	}

	public function get_cancel_subscription_url() {
		return wp_nonce_url( wc_get_endpoint_url( 'cancel-subscription', $this->get_id(), wc_get_page_permalink( 'myaccount' ) ) );
	}

	public function update_subscription_meta_data( $meta_data ) {
		foreach ( $meta_data as $key => $value ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $this, $function ) ) ) {
				$this->{$function}( $value );
			} else {
				$this->update_meta_data( $key, $value );
			}
		}
	}

	public function update_meta_data( $key, $value, $meta_id = 0 ) {
		if ( substr( $key, 0, 1 ) !== '_' ) {
			$key = '_' . $key;
		}

		return parent::update_meta_data( $key, $value, $meta_id );
	}

}
