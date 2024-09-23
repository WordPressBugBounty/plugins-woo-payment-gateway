<?php

defined( 'ABSPATH' ) || exit();

return array(
	'title'            => array(
		'type'        => 'description',
		'class'       => 'wc-braintree-googlepay-desc',
		'description' => '<p><a target="_blank" href="https://pay.google.com/business/console">' . __( 'GPay Console', 'woo-payment-gateway' ) . '</a></p> ' .
		                 '<p>' . __( 'Click the GPay Console link to login to your Google Account and signup for GPay.', 'woo-payment-gateway' ) . '</p>' .
		                 '<p>'
		                 . __( 'To have the Google API team approve your integration you can enable sandbox mode and Google Pay. When sandbox mode is enabled, Google Pay will work, allowing you to capture the necessary screenshots the Google API team needs to approve your Merchant ID request.',
				'woo-payment-gateway' ) . '</p>' .
		                 '<a href="https://groups.google.com/forum/#!forum/googlepay-test-mode-stub-data" target="_blank">' . __( 'GPay test cards', 'woo-payment-gateway' ) . '</a>'
	),
	'enabled'          => array(
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Google Pay.', 'woo-payment-gateway' ),
	),
	'general_settings' => array(
		'type'        => 'title',
		'title'       => __( 'General Settings', 'woo-payment-gateway' ),
		'description' => __( 'General Settings for the Google Pay gateway.', 'woo-payment-gateway' ),
	),
	'merchant_id'      => array(
		'title'       => __( 'Google Merchant ID', 'woo-payment-gateway' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Once you have been approved by Google to accept payments you will be issued a merchant ID. Enter that merchant ID in this field. A merchant ID is not needed when Sandbox mode is enabled. This will allow you to go through the Google Pay approval process.',
			'woo-payment-gateway' ),
	),
	'merchant_name'    => array(
		'type'        => 'text',
		'title'       => __( 'Merchant Name', 'woo-payment-gateway' ),
		'default'     => get_bloginfo( 'name' ),
		'description' => __( 'The name of your business as it appears on the Google Pay payment sheet.', 'woo-payment-gateway' ),
		'desc_tip'    => true,
	),
	'title_text'       => array(
		'type'        => 'text',
		'title'       => __( 'Title Text', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => __( 'Google Pay', 'woo-payment-gateway' ),
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
	'sections'         => array(
		'type'        => 'multiselect',
		'title'       => __( 'Pages Enabled On' ),
		'default'     => array( 'cart' ),
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'cart'            => __( 'Cart page', 'woo-payment-gateway' ),
			'product'         => __( 'Product page', 'woo-payment-gateway' ),
			'checkout_banner' => __( 'Express Checkout', 'woo-payment-gateway' ),
			'mini_cart'       => __( 'Mini-cart', 'woo-payment-gateway' )
		),
		'description' => $this->get_payment_section_description(),
	),
	'icon'             => array(
		'type'        => 'select',
		'title'       => __( 'Gateway Icon', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'google_pay_round_outline' => __( 'Black Outline Rounded Corners', 'woo-payment-gateway' ),
			'google_pay_standard'      => __( 'Standard', 'woo-payment-gateway' ),
			'google_pay_outline'       => __( 'Black Outline', 'woo-payment-gateway' ),
		),
		'default'     => 'google_pay_round_outline',
		'description' => __( 'Google Pay icon that appears on the checkout page.', 'woo-payment-gateway' ),
	),
	'method_format'    => array(
		'title'       => __( 'Credit Card Display', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
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
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.',
			'woo-payment-gateway' ),
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
		'default'     => 'capture',
		'class'       => 'wc-enhanced-select',
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
	'3ds_settings'     => array(
		'type'  => 'title',
		'title' => __( '3D Secure Settings', 'woo-payment-gateway' ),
	),
	'3ds_enabled'      => array(
		'type'        => 'checkbox',
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'default'     => 'yes',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, 3DS will be required when transactions are processed, such as on the checkout page.', 'woo-payment-gateway' ),
	),
	'button'           => array(
		'type'  => 'title',
		'title' => __( 'Button Design', 'woo-payment-gateway' ),
	),
	'button_color'     => array(
		'title'       => __( 'Button Color', 'woo-payment-gateway' ),
		'type'        => 'select',
		'default'     => 'default',
		'class'       => 'wc-braintree-button-option wc-braintree-button-color wc-enhanced-select',
		'options'     => array(
			'default' => __( 'Default', 'woo-payment-gateway' ),
			'black'   => __( 'Black', 'woo-payment-gateway' ),
			'white'   => __( 'White', 'woo-payment-gateway' ),
		),
		'desc_tip'    => true,
		'description' => __( 'The color of the Google Pay button on the checkout page.', 'woo-payment-gateway' ),
	),
	'button_type'      => array(
		'title'       => __( 'Button Type', 'woo-payment-gateway' ),
		'type'        => 'select',
		'default'     => 'long',
		'class'       => 'wc-braintree-button-option wc-braintree-button-type wc-enhanced-select',
		'options'     => array(
			'buy'       => __( 'Buy', 'woo-payment-gateway' ),
			'plain'     => __( 'Plain', 'woo-payment-gateway' ),
			'checkout'  => __( 'Checkout', 'woo-payment-gateway' ),
			'order'     => __( 'Order', 'woo-payment-gateway' ),
			'pay'       => __( 'Pay', 'woo-payment-gateway' ),
			'subscribe' => __( 'subscribe', 'woo-payment-gateway' )
		),
		'tool_tip'    => true,
		'description' => __( 'The type of Google Pay button on the checkout page.', 'woo-payment-gateway' ),
	),
	'button_shape'     => array(
		'title'       => __( 'Button Shape', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-braintree-button-option wc-braintree-button-shape wc-enhanced-select',
		'default'     => 'rect',
		'options'     => array(
			'pill' => __( 'Pill shape', 'woo-payment-gateway' ),
			'rect' => __( 'Rectangle', 'woo-payment-gateway' ),
		),
		'description' => __( 'The button shape', 'woo-payment-gateway' ),
	),
	'button_demo'      => array(
		'title'       => __( 'Button Demo', 'woo-payment-gateway' ),
		'type'        => 'button_demo',
		'description' => __( 'If no button demo appears then the device you are using does not support Google Pay. Try viewing your settings on a different device.', 'woo-payment-gateway' ),
		'id'          => 'wc-braintree-button-demo',
	),
);
