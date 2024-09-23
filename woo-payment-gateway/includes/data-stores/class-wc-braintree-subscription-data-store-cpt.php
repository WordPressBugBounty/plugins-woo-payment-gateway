<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/DataStore
 *
 */
class WC_Braintree_Subscription_Data_Store_CPT extends WC_Order_Data_Store_CPT {

	const data_type = 'braintree_subscription';

	private $subscription_internal_meta_keys = array(
		'_merchant_account_id',
		'_trial_end_date',
		'_subscription_trial_length',
		'_subscription_trial_period',
		'_start_date',
		'_first_payment_date',
		'_next_payment_date',
		'_end_date',
		'_previous_payment_date',
		'_braintree_plan',
		'_subscription_period',
		'_subscription_period_interval',
		'_subscription_length',
		'_created_in_braintree',
		'_recurring_cart_key',
	);

	public static function init() {
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'add_data_store' ) );
	}

	/**
	 * @param $data_stores
	 *
	 * @return mixed
	 */
	public static function add_data_store( $data_stores ) {
		if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
			$data_stores[ self::data_type ] = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::class );
		} else {
			$data_stores[ self::data_type ] = __CLASS__;
		}

		return $data_stores;
	}

}

WC_Braintree_Subscription_Data_Store_CPT::init();
