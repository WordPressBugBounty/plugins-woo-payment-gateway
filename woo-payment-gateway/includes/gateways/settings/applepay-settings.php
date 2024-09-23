<?php

defined( 'ABSPATH' ) || exit();

return array(
	'guide'                => array(
		'type'        => 'description',
		'description' => __( 'Please review our <a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/braintree_applepay?id=instructions">Apple Pay Guide</a> for detailed instructions on how to setup Apple Pay.', 'woo-payment-gateway' ) .
		                 sprintf( '<div class=""><a href="#" class="button button-secondary domain-association">%s</a></div>', __( 'Add Domain Association file', 'woo-payment-gateway' ) ),
	),
	'enabled'              => array(
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Apple Pay through Braintree.', 'woo-payment-gateway' ),
	),
	'general_settings'     => array(
		'type'        => 'title',
		'title'       => __( 'General Settings', 'woo-payment-gateway' ),
		'description' => __( 'General Settings for the credit card gateway.', 'woo-payment-gateway' ),
	),
	'title_text'           => array(
		'type'        => 'text',
		'title'       => __( 'Title Text', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => __( 'Apple Pay', 'woo-payment-gateway' ),
		'class'       => '',
		'desc_tip'    => true,
		'description' => __( 'The title text is the text that will be displayed next to the gateway.', 'woo-payment-gateway' ),
	),
	'description'          => array(
		'type'        => 'text',
		'title'       => __( 'Description', 'woo-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
		'description' => __( 'Description that appears on your checkout page when the gateway is selected. Leave blank if you don\'t want any text to show.', 'woo-payment-gateway' ),
	),
	'sections'             => array(
		'type'        => 'multiselect',
		'title'       => __( 'Pages Enabled On' ),
		'default'     => array( 'cart', 'product' ),
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'cart'            => __( 'Cart page', 'woo-payment-gateway' ),
			'product'         => __( 'Product page', 'woo-payment-gateway' ),
			'checkout_banner' => __( 'Express Checkout', 'woo-payment-gateway' ),
			'mini_cart'       => __( 'Mini-cart', 'woo-payment-gateway' )
		),
		'description' => $this->get_payment_section_description(),
	),
	'store_name'           => array(
		'type'        => 'text',
		'title'       => __( 'Store Name', 'woo-payment-gateway' ),
		'default'     => get_bloginfo( 'name' ),
		'value'       => '',
		'desc_tip'    => true,
		'description' => __( 'The name of the store that will appear on the Apple Pay sheet.', 'woo-payment-gateway' ),
	),
	'method_format'        => array(
		'title'       => __( 'Credit Card Display', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_last4',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'order_prefix'         => array(
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
	'order_suffix'         => array(
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
	'order_status'         => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-payment-gateway' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-payment-gateway' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.',
			'woo-payment-gateway' ),
	),
	'charge_type'          => array(
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
	'line_items'           => array(
		'title'       => __( 'Add Line Items', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, all of the order line items will be included in the Transaction and will appear in Braintree. If you receive a validation error, disable this option.', 'woo-payment-gateway' )
	),
	'button'               => array(
		'type'        => 'select',
		'title'       => __( 'Button Design', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select',
		'default'     => 'apple-pay-button-black',
		'options'     => array(
			'apple-pay-button-black'           => __( 'Black Button', 'woo-payment-gateway' ),
			'apple-pay-button-white-with-line' => __( 'White With Black Line', 'woo-payment-gateway' ),
			'apple-pay-button-white'           => __( 'White Button', 'woo-payment-gateway' ),
		),
		'description' => __( 'This is the style for all Apple Pay buttons presented on your store.', 'woo-payment-gateway' ),
	),
	'button_style'         => array(
		'title'   => __( 'Button Style', 'woo-payment-gateway' ),
		'type'    => 'select',
		'default' => 'standard',
		'options' => array(
			'standard' => __( 'Standard', 'woo-payment-gateway' ),
			'rounded'  => __( 'Rounded', 'woo-payment-gateway' )
		)
	),
	'button_title'         => array(
		'type'        => 'title',
		'title'       => '',
		'description' => sprintf( '<div class="wc-braintree-applepay-button-type-desc">%s</div>', __( 'These buttons represent the two available button types for Apple Pay. You can configure a different type for each page that Apple Pay is available on.', 'woo-payment-gateway' ) ) .
		                 sprintf(
			                 '<img class="wc-braintree-applepay-button" src="%1$s%2$s"/><img class="wc-braintree-applepay-button" src="%1$s%3$s"/>',
			                 braintree()->assets_path() .
			                 'img/applepay/',
			                 'applepay_button_plain.png',
			                 'applepay_button_buy.png'
		                 ),
	),
	'button_type_checkout' => array(
		'title'   => __( 'Checkout button type', 'woo-payment-gateway' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-payment-gateway' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-payment-gateway' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-payment-gateway' )
		),
		'default' => 'plain',
	),
	'button_type_cart'     => array(
		'title'   => __( 'Cart button type', 'woo-payment-gateway' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-payment-gateway' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-payment-gateway' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-payment-gateway' )
		),
		'default' => 'plain',
	),
	'button_type_product'  => array(
		'title'   => __( 'Product button type', 'woo-payment-gateway' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-payment-gateway' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-payment-gateway' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-payment-gateway' )
		),
		'default' => 'buy',
	),
);
