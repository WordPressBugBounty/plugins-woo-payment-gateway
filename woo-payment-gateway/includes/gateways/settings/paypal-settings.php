<?php
defined( 'ABSPATH' ) || exit();

return array(
	'title'               => array(
		'type'        => 'description',
		'description' => '<div class="wc-braintree-paypal-instructions"><p><a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/braintree_paypal?id=setup">' .
		                 __( 'How To Setup & Test PayPal', 'woo-payment-gateway' ) . '</a>' .
		                 '<p><a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/braintree_paypal?id=require-billing-phone">' .
		                 __( 'Require Billing Phone', 'woo-payment-gateway' ) . '</a>' .
		                 '<p><a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/braintree_paypal?id=enable-billing-address">' .
		                 __( 'Enable PayPal Billing Address', 'woo-payment-gateway' ) . '</a></div>',
	),
	'enabled'             => array(
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept PayPal through Braintree.', 'woo-payment-gateway' ),
	),
	'general_settings'    => array(
		'type'        => 'title',
		'title'       => __( 'General Settings', 'woo-payment-gateway' ),
		'description' => __( 'General Settings for the credit card gateway.', 'woo-payment-gateway' ),
	),
	'title_text'          => array(
		'type'        => 'text',
		'title'       => __( 'Title Text', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => __( 'PayPal', 'woo-payment-gateway' ),
		'class'       => '',
		'desc_tip'    => true,
		'description' => __( 'The title text is the text that will be displayed next to the gateway.', 'woo-payment-gateway' ),
	),
	'description'         => array(
		'type'        => 'text',
		'title'       => __( 'Description', 'woo-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
		'description' => __( 'Description that appears on your checkout page when the gateway is selected. Leave blank if you don\'t want any text to show.', 'woo-payment-gateway' ),
	),
	'sections'            => array(
		'type'        => 'multiselect',
		'title'       => __( 'Sections', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select',
		'default'     => array( 'cart' ),
		'options'     => array(
			'cart'            => __( 'Cart page', 'woo-payment-gateway' ),
			'product'         => __( 'Product Page', 'woo-payment-gateway' ),
			'checkout_banner' => __( 'Express Checkout', 'woo-payment-gateway' ),
			'mini_cart'       => __( 'Mini-cart', 'woo-payment-gateway' )
		),
		'description' => $this->get_payment_section_description(),
	),
	'method_format'       => array(
		'title'       => __( 'PayPal Display', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'email',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how PayPal accounts display for your customers on orders, subscriptions, etc.' ),
	),
	'order_prefix'        => array(
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
	'order_suffix'        => array(
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
	'order_status'        => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-payment-gateway' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-payment-gateway' ) ), wc_get_order_statuses() ),
		'desc_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'woo-payment-gateway' ),
	),
	'charge_type'         => array(
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'title'       => __( 'Transaction Type', 'woo-payment-gateway' ),
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
	'billing_agreement'   => array(
		'type'        => 'textarea',
		'title'       => __( 'Billing Agreement Description', 'woo-payment-gateway' ),
		'default'     => sprintf( __( 'Purchase agreement from %s.', 'woo-payment-gateway' ), get_bloginfo( 'name' ) ),
		'value'       => '',
		'description' => __(
			'The billing agreement description appears on your customer\'s PayPal account and gives information about the company they have granted authorization to. This is a good way to prevent
								customers from cancelling recurring billing authorizations because they are unsure who they granted access to.',
			'woo-payment-gateway'
		),
		'desc_tip'    => true,
	),
	'display_name'        => array(
		'title'       => __( 'Display Name', 'woo-payment-gateway' ),
		'type'        => 'text',
		'default'     => get_option( 'blogname' ),
		'desc_tip'    => true,
		'description' => __( 'This is the business name that is displayed on the PayPal popup.', 'woo-payment-gateway' ),
	),
	/*'line_items'          => array(
		'title'       => __( 'Add Line Items', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'If enabled, all of the order line items will be included in the Transaction and will appear in Braintree. If you receive a validation error, disable this option.', 'woo-payment-gateway' )
	),*/
	'smartbutton'         => array(
		'type'        => 'title',
		'title'       => __( 'Smartbutton Checkout Page Design', 'woo-payment-gateway' ),
		'description' => __(
			'Smartbuttons are designed to improve customer conversion on eCommerce platforms. You can control the look and feel of the Checkout page buttons using these settings. Pages like the Cart have a specific design for conversion purposes
						and can only be changed using filters.',
			'woo-payment-gateway'
		),
	),
	'smartbutton_color'   => array(
		'type'    => 'select',
		'title'   => __( 'Button Color', 'woo-payment-gateway' ),
		'class'   => 'wc-braintree-smartbutton-color wc-enhanced-select',
		'default' => 'gold',
		'options' => array(
			'gold'   => __( 'Gold', 'woo-payment-gateway' ),
			'blue'   => __( 'Blue', 'woo-payment-gateway' ),
			'silver' => __( 'Silver', 'woo-payment-gateway' ),
			'black'  => __( 'Black', 'woo-payment-gateway' ),
			'white'  => __( 'White', 'woo-payment-gateway' )
		),
	),
	'smartbutton_shape'   => array(
		'type'    => 'select',
		'title'   => __( 'Button Shape', 'woo-payment-gateway' ),
		'class'   => 'wc-braintree-smartbutton-shape wc-enhanced-select',
		'default' => 'rect',
		'options' => array(
			'pill' => __( 'Pill', 'woo-payment-gateway' ),
			'rect' => __( 'Rectangle', 'woo-payment-gateway' ),
		),
	),
	'smartbutton_label'   => array(
		'type'    => 'select',
		'title'   => __( 'Button Label', 'woo-payment-gateway' ),
		'class'   => 'wc-braintree-smartbutton-label wc-enhanced-select',
		'default' => 'paypal',
		'options' => array(
			'paypal'   => __( 'Standard', 'woo-payment-gateway' ),
			'checkout' => __( 'Checkout', 'woo-payment-gateway' ),
			'buynow'   => __( 'Buy Now', 'woo-payment-gateway' ),
			'pay'      => __( 'Pay', 'woo-payment-gateway' )
		),
	),
	'smartbutton_cards'   => array(
		'title'   => __( 'Card Enabled', 'woo-payment-gateway' ),
		'class'   => 'wc-braintree-smartbutton ',
		'type'    => 'checkbox',
		'default' => 'yes',
	),
	'card_button_color'   => array(
		'title'             => __( 'Card Button Color', 'woo-payment-gateway' ),
		'type'              => 'select',
		'class'             => 'wc-braintree-smartbutton-card-color wc-enhanced-select',
		'default'           => 'black',
		'options'           => array(
			'black' => __( 'Black', 'woo-payment-gateway' ),
			'white' => __( 'White', 'woo-payment-gateway' )
		),
		'custom_attributes' => array(
			'data-show-if' => array(
				'smartbutton_cards' => true
			)
		)
	),
	'button_height'       => array(
		'type'              => 'slider',
		'title'             => __( 'Button Height', 'woo-payment-gateway' ),
		'default'           => 40,
		'custom_attributes' => array(
			'data-options' => array(
				'min'  => 25,
				'max'  => 55,
				'step' => 1
			)
		)
	),
	'smart_button_demo'   => array(
		'type'  => 'button_demo',
		'title' => __( 'Demo', 'woo-payment-gateway' ),
		'id'    => 'wc-braintree-button-demo',
	),
	'pay_later'           => array(
		'type'  => 'title',
		'title' => __( 'PayPal Pay Later (Formerly Credit)', 'woo-payment-gateway' )
	),
	'bnpl_enabled'        => array(
		'type'        => 'checkbox',
		'class'       => 'wc-braintree-smartbutton',
		'value'       => 'yes',
		'default'     => 'no',
		'title'       => __( 'Enable', 'woo-payment-gateway' ),
		'desc_tip'    => true,
		'description' => __( 'Pay Later allows your customers to pay for their order over time. You receive all the funds up front and the customer makes payments on their end.', 'woo-payment-gateway' ),
	),
	'bnpl_sections'       => array(
		'type'              => 'multiselect',
		'title'             => __( 'Sections', 'woo-payment-gateway' ),
		'class'             => 'wc-enhanced-select',
		'default'           => array( 'checkout' ),
		'options'           => array(
			'cart'     => __( 'Cart page', 'woo-payment-gateway' ),
			'product'  => __( 'Product Page', 'woo-payment-gateway' ),
			'checkout' => __( 'Checkout Page', 'woo-payment-gateway' )
		),
		'description'       => __( 'These are the pages Pay Later will be available on. You can control if Pay Later displays on certain products by going to the edit product page.' ),
		'custom_attributes' => array(
			'data-show-if' => array(
				'bnpl_enabled' => true
			)
		),
		'sanitize_callback' => function ( $value ) {
			return ! is_array( $value ) ? array() : $value;
		}
	),
	'bnpl_button_color'   => array(
		'title'             => __( 'Pay Later Button Color', 'woo-payment-gateway' ),
		'type'              => 'select',
		'default'           => 'gold',
		'class'             => 'wc-braintree-smartbutton-credit-color wc-enhanced-select',
		'options'           => array(
			'gold'   => __( 'Gold', 'woo-payment-gateway' ),
			'blue'   => __( 'Blue', 'woo-payment-gateway' ),
			'silver' => __( 'Silver', 'woo-payment-gateway' ),
			'black'  => __( 'Black', 'woo-payment-gateway' ),
			'white'  => __( 'White', 'woo-payment-gateway' )
		),
		'custom_attributes' => array(
			'data-show-if' => array(
				'bnpl_enabled' => true
			)
		)
	),
	'pay_later_msg'       => array(
		'title'             => __( 'Pay Later Messaging', 'woo-payment-gateway' ),
		'type'              => 'multiselect',
		'class'             => 'wc-enhanced-select',
		'default'           => array( 'cart', 'checkout' ),
		'options'           => array(
			'product'  => __( 'Product page', 'woo-payment-gateway' ),
			'cart'     => __( 'Cart page', 'woo-payment-gateway' ),
			'checkout' => __( 'Checkout page', 'woo-payment-gateway' )
		),
		'sanitize_callback' => function ( $value ) {
			return ! is_array( $value ) ? array() : $value;
		},
		'desc_tip'          => false,
		'description'       => __( 'When enabled, messaging related to PayPal\'s Pay Later offering will be displayed. This option is recommended for conversion rates.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'bnpl_enabled' => true ) )
	),
	'pay_later_txt_color' => array(
		'title'             => __( 'Pay Later Text Color', 'woo-payment-gateway' ),
		'type'              => 'select',
		'default'           => 'black',
		'options'           => array(
			'black'      => __( 'Black', 'woo-payment-gateway' ),
			'white'      => __( 'White', 'woo-payment-gateway' ),
			'monochrome' => __( 'Monochrome', 'woo-payment-gateway' ),
			'grayscale'  => __( 'Grayscale', 'woo-payment-gateway' ),
		),
		'description'       => __( 'The color of the Pay Later messaging.', 'woo-payment-gateway' ),
		'tool_tip'          => true,
		'custom_attributes' => array( 'data-show-if' => array( 'bnpl_enabled' => true ) )
	)
);
