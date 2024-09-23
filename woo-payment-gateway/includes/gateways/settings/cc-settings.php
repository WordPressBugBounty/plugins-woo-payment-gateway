<?php

defined( 'ABSPATH' ) || exit();

return array(
	'title'                     => array(
		'type'        => 'description',
		'class'       => 'wc-braintree-cc-desc',
		'description' => __( 'If you have a sandbox environment and want to test credit card transactions, use <a target="_blank" href="https://developers.braintreepayments.com/reference/general/testing/php#credit-card-numbers">Braintree\'s test data</a>.', 'woo-payment-gateway' ),
	),
	'enabled'                   => array(
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'yes',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept credit card payments through Braintree.', 'woo-payment-gateway' ),
	),
	'general_settings'          => array(
		'type'        => 'title',
		'title'       => __( 'General Settings', 'woo-payment-gateway' ),
		'description' => __( 'General Settings for the credit card gateway.', 'woo-payment-gateway' ),
	),
	'title_text'                => array(
		'type'        => 'text',
		'title'       => __( 'Title Text', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => __( 'Credit Cards', 'woo-payment-gateway' ),
		'class'       => '',
		'desc_tip'    => true,
		'description' => __( 'The title text is the text that will be displayed next to the gateway.', 'woo-payment-gateway' ),
	),
	'description'               => array(
		'type'        => 'text',
		'title'       => __( 'Description', 'woo-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
		'description' => __( 'Description that appears on your checkout page when the gateway is selected. Leave blank if you don\'t want any text to show.', 'woo-payment-gateway' ),
	),
	'method_format'             => array(
		'title'       => __( 'Credit Card Display', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'icon_style'                => array(
		'type'        => 'select',
		'title'       => __( 'Icon Style', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select',
		'default'     => 'closed',
		'options'     => array(
			'closed' => __( 'Enclosed Icons', 'woo-payment-gateway' ),
			'open'   => __( 'Open Icons', 'woo-payment-gateway' ),
		),
		'description' => sprintf(
			__( 'This option determines the type of credit card icons that appear on your frontend. Open Example: %1$s vs  Enclosed Example: %2$s', 'woo-payment-gateway' ),
			'<img class="wc-braintree-card-icon" src="' .
			braintree()->assets_path() . 'img/payment-methods/open/visa.svg' . '"/>',
			'<img  class="wc-braintree-card-icon" src="' .
			braintree()->assets_path() . 'img/payment-methods/closed/visa.svg' . '"/>'
		),
	),
	'order_prefix'              => array(
		'type'        => 'text',
		'title'       => __( 'Order Prefix', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => '',
		'class'       => '',
		'desc_tip'    => true,
		'description' => __(
			'The order prefix is prepended to the WooCommerce order id and will appear within Braintree as the Order ID. This setting can be helpful if you want to distinguish
						orders that came from this particular site, plugin, or gateway.',
			'woo-payment-gateway'
		),
	),
	'order_suffix'              => array(
		'type'        => 'text',
		'title'       => __( 'Order Suffix', 'woo-payment-gateway' ),
		'value'       => '',
		'default'     => '',
		'class'       => '',
		'desc_tip'    => true,
		'description' => __(
			'The order suffix is appended to the WooCommerce order id and will appear within Braintree as the Order ID. This setting can be helpful if you want to distinguish
						orders that came from this particular site, plugin, or gateway.',
			'woo-payment-gateway'
		),
	),
	'order_status'              => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-payment-gateway' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-payment-gateway' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.',
			'woo-payment-gateway' ),
	),
	'charge_type'               => array(
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
	'save_card_enabled'         => array(
		'type'        => 'checkbox',
		'value'       => 'yes',
		'default'     => 'yes',
		'title'       => __( 'Allow Credit Card Save', 'woo-payment-gateway' ),
		'desc_tip'    => false,
		'description' => __( 'If enabled, a checkbox will be available on the checkout page allowing your customer\'s to save their credit card. The payment methods are stored securely in Braintree\'s vault and never touch your server. Note: if the cart contains a subscription or pre-order, there will be no checkbox because the payment method will be saved automatically.',
			'woo-payment-gateway' ),
	),
	'fail_on_duplicate'         => array(
		'title'       => __( 'Fail on Duplicate', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'If enabled, a payment method cannot be saved to the Braintree vault twice.', 'woo-payment-gateway' ),
	),
	'payment_methods'           => array(
		'type'        => 'multiselect',
		'title'       => __( 'Accepted Payment Methods', 'woo-payment-gateway' ),
		'class'       => 'wc-enhanced-select braintree-accepted-cards',
		'default'     => array( 'amex', 'discover', 'visa', 'master_card' ),
		'options'     => array(
			'visa'                      => __( 'Visa', 'woo-payment-gateway' ),
			'amex'                      => __( 'Amex', 'woo-payment-gateway' ),
			'discover'                  => __( 'Discover', 'woo-payment-gateway' ),
			'master_card'               => __( 'MasterCard', 'woo-payment-gateway' ),
			'jcb'                       => __( 'JCB', 'woo-payment-gateway' ),
			'maestro'                   => __( 'Maestro', 'woo-payment-gateway' ),
			'diners_club_international' => __( 'Diners Club', 'woo-payment-gateway' ),
			'china_union_pay'           => __( 'Union Pay', 'woo-payment-gateway' ),
		),
		'desc_tip'    => true,
		'description' => __( 'The selected icons will show customers which credit card brands you accept.', 'woo-payment-gateway' ),
	),
	'line_items'                => array(
		'title'       => __( 'Add Line Items', 'woo-payment-gateway' ),
		'type'        => 'checkbox',
		'default'     => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, all of the order line items will be included in the Transaction and will appear in Braintree. If you receive a validation error, disable this option.', 'woo-payment-gateway' )
	),
	'form_settings'             => array(
		'type'        => 'title',
		'title'       => __( 'Form Settings', 'woo-payment-gateway' ),
		'description' => __(
			'This plugin provides several options for your credit card form design. You can use the Braintree dropin form or one of the custom forms. 
						With custom forms you can change the look and feel of the designs. All forms are <b>SAQ A</b> compliant. For settings such as CVV and AVS that are maintained in Braintree, allow up to 5 minutes for changes to take affect.
						<p style="font-size:16px" class="show_if_form_type_dropin_form hide_if_form_type_custom_form"><b>Dropin Form:</b> Postal code and CVV are rendered dynamically based on your AVS and CVV settings in your Braintree control panel.</p>
						<p style="font-size:16px" class="show_if_form_type_custom_form hide_if_form_type_dropin_form"><b>Custom Form:</b> Postal code and CVV are rendered dynamically based on your AVS and CVV settings in your Braintree control panel.</p>',
			'woo-payment-gateway'
		),
	),
	'form_type'                 => array(
		'type'        => 'select',
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'title'       => __( 'Card Form', 'woo-payment-gateway' ),
		'default'     => 'custom_form',
		'value'       => '',
		'options'     => array(
			'custom_form' => __( 'Custom Form', 'woo-payment-gateway' ),
			'dropin_form' => __( 'Dropin Form', 'woo-payment-gateway' ),
		),
		'desc_tip'    => true,
		'description' => __( 'You have the option of selecting to use a custom form or the Braintree dropin form. If the custom form is selected, you can customize all of the options associated with custom forms.', 'woo-payment-gateway' ),
	),
	'dropin_postal_enabled'     => array(
		'type'              => 'checkbox',
		'title'             => __( 'Is Postal Enabled', 'woo-payment-gateway' ),
		'default'           => 'no',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'Checkout page only: If enabled, the postal code from the dropin form is used for validations and not the postal billing field from WooCommerce.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'dropin_form' ) ),
	),
	'custom_form_design'        => array(
		'type'              => 'select',
		'title'             => __( 'Custom Form', 'woo-payment-gateway' ),
		'default'           => 'bootstrap_form',
		'options'           => wp_list_pluck( wc_braintree_custom_form_options(), 'label' ),
		'value'             => '',
		'class'             => 'wc-enhanced-select',
		'desc_tip'          => true,
		'description'       => __( 'This option allows you to select the card form which will be displayed on the payment pages.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'custom_form' ) ),
	),
	'custom_form_styles'        => array(
		'title'             => __( 'Form Styles', 'woo-payment-gateway' ),
		'type'              => 'button',
		'class'             => 'button button-secondary wc-braintree-settings-modal',
		'sanitize_callback' => function ( $value ) {
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			return $value;
		},
		'default'           => array(
			'input' => array(
				'font-size'   => '16px',
				'font-family' => 'helvetica, tahoma, calibri, sans-serif',
				'color'       => '#3a3a3a'
			)
		),
		'desc_tip'          => true,
		'description'       => __( 'This option allows you to customize the custom form\'s styles.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'custom_form' ) ),

	),
	'dynamic_card_display'      => array(
		'type'              => 'checkbox',
		'title'             => __( 'Card Detection Enabled' ),
		'default'           => 'yes',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'If enabled, the payment form will display the card type dynamically as the customer enters their payment information.' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'custom_form' ) ),
	),
	'postal_field_enabled'      => array(
		'type'              => 'checkbox',
		'title'             => __( 'Postal Field Enabled', 'woo-payment-gateway' ),
		'default'           => 'no',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'Checkout page only; If enabled, a postal code will be visible in the credit card form otherwise the billing postal coded will be used. All other pages, the postal code is rendered dynamically based on AVS settings.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'custom_form' ) ),
	),
	'street_enabled'            => array(
		'type'        => 'checkbox',
		'title'       => __( 'Street Address Enabled', 'woo-payment-gateway' ),
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'If enabled, a street field will be presented within the credit card form on all pages except checkout page; Checkout page already has billing fields. This setting is for when you have AVS street checks enabled.', 'woo-payment-gateway' ),
	),
	'loader_enabled'            => array(
		'type'              => 'checkbox',
		'title'             => __( 'Card Loader Enabled', 'woo-payment-gateway' ),
		'default'           => 'yes',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'If enabled, a loader will appear around the card form to let the customer know that the payment is processing.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'form_type' => 'custom_form' ) ),
	),
	'loader_design'             => array(
		'type'              => 'select',
		'title'             => __( 'Processing Design', 'woo-payment-gateway' ),
		'default'           => 'circular-loader.php',
		'class'             => 'wc-enhanced-select',
		'value'             => '',
		'desc_tip'          => true,
		'description'       => __( 'This is the design of the payment processing loader that appears over the card form.', 'woo-payment-gateway' ),
		'options'           => wc_braintree_card_loader_options(),
		'custom_attributes' => array(
			'data-show-if' => array(
				'form_type'      => 'custom_form',
				'loader_enabled' => true,
			),
		),
	),
	'3ds_settings'              => array(
		'type'        => 'title',
		'title'       => __( '3D Secure Settings', 'woo-payment-gateway' ),
		'description' => __(
			'<a href="https://articles.braintreepayments.com/guides/fraud-tools/3d-secure#processing" target="_blank">Learn more about 3D Secure processing in 
						our support article.</a><p><a target="_blank" href="https://developers.braintreepayments.com/guides/3d-secure/testing-go-live/php#testing">3DS testing guide</a></p>',
			'woo-payment-gateway'
		),
	),
	'3ds_enabled'               => array(
		'type'        => 'checkbox',
		'title'       => __( 'Enabled', 'woo-payment-gateway' ),
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, 3DS will be required when transactions are processed, such as on the checkout page.', 'woo-payment-gateway' ),
	),
	'3ds_enable_payment_token'  => array(
		'title'             => __( '3DS For Vaulted Cards', 'woo-payment-gateway' ),
		'type'              => 'checkbox',
		'default'           => 'no',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'For additional security you can request 3DS when a customer is using a saved credit card.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( '3ds_enabled' => true ) ),
	),
	'3ds_challenge_requested'   => array(
		'title'             => __( 'Force Authentication Challenge', 'woo-payment-gateway' ),
		'type'              => 'checkbox',
		'default'           => 'no',
		'value'             => 'yes',
		'desc_tip'          => true,
		'description'       => __( 'This option will set the "challengeRequested" property to true in the verifyCard 3DS API call. This will force the 3DS authentication challenge.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( '3ds_enabled' => true ) ),
	),
	'3ds_liability_not_shifted' => array(
		'title'             => __( 'Liability Not Shifted', 'woo-payment-gateway' ),
		'type'              => 'select',
		'class'             => 'wc-enhanced-select',
		'default'           => 'no_action',
		'options'           => array(
			'no_action'      => __( 'No Action (Braintree will handle)', 'woo-payment-gateway' ),
			'authorize_only' => __( 'Authorize Amount', 'woo-payment-gateway' ),
			'reject'         => __( 'Reject Transaction', 'woo-payment-gateway' ),
			'accept'         => __( 'Accept Transaction', 'woo-payment-gateway' ),
		),
		'desc_tip'          => true,
		'description'       => __( 'When 3DS is enabled, you can configure how to respond to a liability not shifted scenario. Liability not shifted means that you (the merchant) are still liable for fraud etc.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( '3ds_enabled' => true ) ),
	),
	'3ds_card_ineligible'       => array(
		'title'             => __( 'Card Ineligible for 3DS', 'woo-payment-gateway' ),
		'type'              => 'select',
		'class'             => 'wc-enhanced-select',
		'default'           => 'no_action',
		'options'           => wc_braintree_get_3ds_actions(),
		'desc_tip'          => true,
		'description'       => __( 'When a card is ineligible for 3DS these are the actions you can take.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( '3ds_enabled' => true ) ),
	),
	'3ds_conditions'            => array(
		'title'             => __( 'Conditional Statements', 'woo-payment-gateway' ),
		'type'              => 'text',
		'default'           => '',
		'value'             => '',
		'desc_tip'          => false,
		'class'             => 'wc-braintree-conditional-statement',
		'description'       => __( 'Conditional statements allow you to control when 3D Secure is available on the checkout page. <a href="https://docs.paymentplugins.com/wc-braintree/config/#/conditional_stmnts" target="_blank"> Conditional statements explained</a>', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( '3ds_enabled' => true ) ),
	),
);
