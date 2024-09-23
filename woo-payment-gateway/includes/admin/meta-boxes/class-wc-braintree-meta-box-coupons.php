<?php

class WC_Braintree_Meta_Box_Coupons {

	public static function init() {
		add_action( 'woocommerce_coupon_options', array( __CLASS__, 'output_options' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 *
	 * @param int $id
	 * @param WC_Coupon $coupon
	 */
	public static function output_options( $id, $coupon ) {
		include 'views/html-coupon-data.php';
	}

	/**
	 *
	 * @param int $post_id
	 * @param WC_Coupon $coupon
	 */
	public static function save( $post_id, $coupon ) {
		$props = array( wc_clean( $_POST['subscription_type'] ) );
		// WC doesn't allow props to be added using $coupon->set_prop so using post meta instead.
		foreach ( $props as $key => $value ) {
			update_post_meta( $post_id, '_subscription_type', $value );
		}
	}
}
WC_Braintree_Meta_Box_Coupons::init();
