<?php
defined( 'ABSPATH' ) || exit();

return array(
	'enabled'          => array(
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Venmo through Braintree.', 'woo-payment-gateway' ),
	),
	'general_settings' => array(
		'type'        => 'title',
		'title'       => __( 'General Settings', 'woo-payment-gateway' ),
		'description' => __( 'General Settings for the Venmo gateway.', 'woo-payment-gateway' ),
	),
	'title_text'       => array(
		'type'        => 'text',
		'title'       => __( 'Title Text', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => __( 'Venmo', 'woo-payment-gateway' ),
		'class'       => '',
		'desc_tip'    => true,
		'description' => __( 'The title text is the text that will be displayed next to the gateway.', 'woo-payment-gateway' ),
	),
	'description'      => array(
		'type'        => 'text',
		'title'       => __( 'Description', 'woo-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
		'description' => __( 'Description that appears on your checkout page when the gateway is selected. Leave blank if you don\'t want any text to show.', 'woo-payment-gateway' ),
	),
	'method_format'    => array(
		'title'       => __( 'Payment Method Display', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_and_user',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how Venmo payment methods display for your customers on orders, subscriptions, etc.' ),
	),
	'icon'             => array(
		'title'   => __( 'Gateway Icon', 'woo-payment-gateway' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'venmo_blue',
		'options' => array(
			'venmo_blue'  => __( 'Venmo Blue', 'woo-payment-gateway' ),
			'venmo_black' => __( 'Venmo Black', 'woo-payment-gateway' ),
			'venmo_grey'  => __( 'Venmo Grey', 'woo-payment-gateway' ),
		),
	),
	'order_prefix'     => array(
		'type'        => 'text',
		'title'       => __( 'Order Prefix', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => '',
		'class'       => '',
		'desc_tip'    => true,
		'description' => __(
			'The order prefix is prepended to the WooCommerce order id and will appear within Braintree as the Order ID. This settings can be helpful if you want to distinguish
						orders that came from this particular site, plugin, or gateway.',
			'woo-payment-gateway'
		),
	),
	'order_suffix'     => array(
		'type'        => 'text',
		'title'       => __( 'Order Suffix', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => '',
		'class'       => '',
		'desc_tip'    => true,
		'description' => __(
			'The order suffix is appended to the WooCommerce order id and will appear within Braintree as the Order ID. This settings can be helpful if you want to distinguish
						orders that came from this particular site, plugin, or gateway.',
			'woo-payment-gateway'
		),
	),
	'order_status'     => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-payment-gateway' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-payment-gateway' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'woo-payment-gateway' ),
	),
	'line_items'       => array(
		'title'       => __( 'Add Line Items', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, all of the order line items will be included in the Transaction and will appear in Braintree. If you receive a validation error, disable this option.', 'woo-payment-gateway' )
	),
	'charge_type'      => array(
		'type'        => 'select',
		'title'       => __( 'Transaction Type', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select',
		'default'     => 'capture',
		'options'     => array(
			'capture'   => __( 'Capture', 'woo-payment-gateway' ),
			'authorize' => __( 'Authorize', 'woo-payment-gateway' ),
		),
		'description' => __(
			'If set to capture, funds will be captured immediately during checkout. Authorized transactions put a hold on the customer\'s funds but
						no payment is taken until the charge is captured. Authorized charges can be captured on the Admin Order page.',
			'woo-payment-gateway'
		),
	),
);
