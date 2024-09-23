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
class WC_Product_Braintree_Subscription extends WC_Product_Simple {

	public function __construct( $product ) {
		$this->extra_data['subscription_price']                      = 0;
		$this->extra_data['subscription_length']                     = 0;
		$this->extra_data['sandbox_subscription_period_interval']    = 1;
		$this->extra_data['production_subscription_period_interval'] = 1;
		$this->extra_data['subscription_period']                     = 'month';
		$this->extra_data['subscription_sign_up_fee']                = 0;
		$this->extra_data['subscription_trial_length']               = 0;
		$this->extra_data['subscription_trial_period']               = '';
		$this->extra_data['subscription_one_time_shipping']          = '';
		$this->extra_data['braintree_sandbox_plans']                 = array();
		$this->extra_data['braintree_production_plans']              = array();

		parent::__construct( $product );

		$this->subscription_period = 'month';
		$this->product_type        = 'braintree-subscription';
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

	public function get_price( $context = 'view' ) {
		return $this->get_subscription_price();
	}

	public function get_price_html( $deprecated = '' ) {
		return wcs_braintree_get_product_price_html( $this, parent::get_price_html( $deprecated ) );
	}

	public function get_plans() {
		$plans = $this->get_prop( 'braintree_' . wc_braintree_environment() . '_plans' );
		return $plans ? $plans : array();
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
	 */
	public function is_one_time_shipping() {
		return $this->subscription_one_time_shipping === 'yes';
	}

	/**
	 *
	 * @since 2.6.2
	 * {@inheritDoc}
	 *
	 * @see WC_Product::get_type()
	 */
	public function get_type() {
		return 'braintree-subscription';
	}

	public function get_subscription_length() {
		return $this->subscription_length;
	}

	public function get_subscription_price() {
		return $this->get_prop( 'subscription_price' );
	}

	public function get_subscription_period_interval() {
		$value = $this->get_prop( wc_braintree_environment() . '_subscription_period_interval' );
		// don't let the billing period be empty to prevent DateTime exceptions later on.
		$plans = wcs_braintree_get_plans( wc_braintree_environment() );
		if ( ( $id = wcs_braintree_get_plan_from_product( $this ) ) ) {
			$value = $plans[ $id ]['billingFrequency'];
		}
		return $value;
	}

	public function get_subscription_period() {
		return $this->subscription_period;
	}

	public function get_subscription_sign_up_fee() {
		return $this->subscription_sign_up_fee;
	}

	public function get_subscription_trial_length() {
		$trial_length = $this->subscription_trial_length;
		return empty( $trial_length ) ? 0 : $trial_length;
	}

	public function get_subscription_trial_period() {
		return $this->subscription_trial_period;
	}

	public function get_subscription_one_time_shipping() {
		return $this->subscription_one_time_shipping;
	}

	public function get_braintree_sandbox_plans() {
		return $this->braintree_sandbox_plans;
	}

	public function get_braintree_production_plans() {
		return $this->braintree_production_plans;
	}

	public function get_braintree_plans( $env = '' ) {
		$env = empty( $env ) ? wc_braintree_environment() : $env;
		return $this->get_prop( 'braintree_' . $env . '_plans' );
	}

	public function get_sandbox_subscription_period_interval() {
		return $this->get_prop( 'sandbox_subscription_period_interval' );
	}

	public function get_production_subscription_period_interval() {
		return $this->get_prop( 'production_subscription_period_interval' );
	}

	public function set_subscription_price( $price ) {
		$this->subscription_price = $price;
	}

	public function set_subscription_length( $length ) {
		$this->subscription_length = $length;
	}

	public function set_subscription_period_interval( $interval ) {
		$this->subscription_period_interval = $interval;
	}

	public function set_subscription_period( $period ) {
		$this->subscription_period = $period;
	}

	public function set_subscription_sign_up_fee( $fee ) {
		$this->subscription_sign_up_fee = $fee;
	}

	public function set_subscription_trial_length( $trial_length ) {
		$this->subscription_trial_length = $trial_length;
	}

	public function set_subscription_trial_period( $trial_period ) {
		$this->subscription_trial_period = $trial_period;
	}

	public function set_subscription_one_time_shipping( $one_time_shipping ) {
		$this->subscription_one_time_shipping = $one_time_shipping;
	}

	public function set_braintree_sandbox_plans( $plans ) {
		$this->braintree_sandbox_plans = $plans;
	}

	public function set_braintree_production_plans( $plans ) {
		$this->braintree_production_plans = $plans;
	}

	public function has_trial() {
		return $this->get_subscription_trial_length() != 0;
	}

	public function set_sandbox_subscription_period_interval( $value ) {
		$this->set_prop( 'sandbox_subscription_period_interval', $value );
	}

	public function set_production_subscription_period_interval( $value ) {
		$this->set_prop( 'production_subscription_period_interval', $value );
	}
}
