<?php
/**
 * @var WC_Coupon $coupon
 */
woocommerce_wp_select(
	array(
		'id'          => 'subscription_type',
		'label'       => __( 'Subscription Coupon Type', 'woo-payment-gateway' ),
		'description' => __( 'If this coupon is applied to a subscription product, determines if the discount is for initial payment, recurring payments, or initial plus recurring.', 'woo-payment-gateway' ),
		'options'     => array(
			'recurring'      => __( 'Recurring & first payment', 'woo-payment-gateway' ),
			'single'         => __( 'Initial discount', 'woo-payment-gateway' ),
			'recurring_only' => __( 'Recurring payments only', 'woo-payment-gateway' ),
		),
		'value'       => get_post_meta(
			$coupon->get_id(),
			'_subscription_type',
			true
		),
	)
);
