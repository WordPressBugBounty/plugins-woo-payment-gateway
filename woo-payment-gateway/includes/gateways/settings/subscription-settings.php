<?php
defined( 'ABSPATH' ) || exit();

return array(
	'wcs_title'             => array(
		'title'       => __( 'Subscription Settings', 'woo-payment-gateway' ),
		'type'        => 'title',
		'description' => __( 'Gateway settings that are specific to WooCommerce Subscriptions.', 'woo-payment-gateway' ),
	),
	'wcs_charge_type'       => array(
		'title'       => __( 'Charge Type', 'woo-payment-gateway' ),
		'type'        => 'select',
		'options'     => array(
			'capture'   => __( 'Capture', 'woo-payment-gateway' ),
			'authorize' => __( 'Authorize', 'woo-payment-gateway' ),
		),
		'default'     => 'capture',
		'desc_tip'    => true,
		'description' => __( 'This setting determines if the order amount is captured immediately or is authorized and can be captured later.', 'woo-payment-gateway' ),
	),
	'wcs_authorized_status' => array(
		'type'              => 'select',
		'title'             => __( 'Authorized Order Status', 'woo-payment-gateway' ),
		'default'           => 'wc-on-hold',
		'options'           => wc_get_order_statuses(),
		'custom_attributes' => array( 'data-show-if' => array( 'wcs_charge_type' => 'authorize' ) ),
		'desc_tip'          => false,
		'description'       => __( 'This is the order status that is assigned when payment for a renewal order is authorized and not captured immediately.', 'woo-payment-gateway' ),
	),
);
