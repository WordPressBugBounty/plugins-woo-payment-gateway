<?php
return array(
	'title'                      => array(
		'type'        => 'title',
		'description' => __( 'Your API keys are used to authenticate requests sent to Braintree. Before you can process transactions in Production or Sandbox, you must enter your API keys.', 'woo-payment-gateway' ),
	),
	'environment'                => array(
		'title'       => __( 'Active Environment', 'woo-payment-gateway' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'default'     => 'sandbox',
		'options'     => array(
			'sandbox'    => __( 'Sandbox', 'woo-payment-gateway' ),
			'production' => __( 'Production', 'woo-payment-gateway' ),
		),
		'description' => __(
			'The environment determines if you are accepting live transactions or test transactions. Sandbox is useful for testing your integration before you go live. 
						Click <a href="https://www.braintreepayments.com/sandbox" target="_blank">Sandbox Sign up</a> to create a sandbox account. If you do not have a production account yet, click <a href="https://www.braintreepayments.com/sales-apply" target="_blank">Production Sign up.</a>',
			'woo-payment-gateway'
		),
	),
	'production_public_key'      => array(
		'type'              => 'text',
		'title'             => __( 'Production Public Key', 'woo-payment-gateway' ),
		'default'           => '',
		'value'             => '',
		'class'             => '',
		'desc_tip'          => true,
		'description'       => __( 'Your public key is used like a username when connecting to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'production' ) ),
	),
	'production_private_key'     => array(
		'type'              => 'password',
		'title'             => __( 'Production Private Key', 'woo-payment-gateway' ),
		'default'           => '',
		'value'             => '',
		'class'             => '',
		'desc_tip'          => true,
		'description'       => __( 'Your private key is used like a password when connecting to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'production' ) ),
	),
	'production_merchant_id'     => array(
		'type'              => 'text',
		'title'             => __( 'Production Merchant ID', 'woo-payment-gateway' ),
		'default'           => '',
		'value'             => '',
		'class'             => '',
		'desc_tip'          => true,
		'description'       => __( 'Your production merchant ID is used to identify your account when connection to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'production' ) ),
	),
	'sandbox_public_key'         => array(
		'type'              => 'text',
		'title'             => __( 'Sandbox Public Key', 'woo-payment-gateway' ),
		'value'             => '',
		'default'           => '',
		'class'             => '',
		'desc_tip'          => true,
		'description'       => __( 'Your public key is used like a username when connecting to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'sandbox' ) ),
	),
	'sandbox_private_key'        => array(
		'type'              => 'password',
		'title'             => __( 'Sandbox Private Key', 'woo-payment-gateway' ),
		'value'             => '',
		'default'           => '',
		'class'             => '',
		'desc_tip'          => true,
		'description'       => __( 'Your private key is used like a password when connecting to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'sandbox' ) ),
	),
	'sandbox_merchant_id'        => array(
		'type'              => 'text',
		'title'             => __( 'Sandbox Merchant ID', 'woo-payment-gateway' ),
		'value'             => '',
		'default'           => '',
		'desc_tip'          => true,
		'description'       => __( 'Your sandbox merchant ID is used to identify your account when connection to Braintree.', 'woo-payment-gateway' ),
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'sandbox' ) ),
	),
	'webhook_url'                => array(
		'type'        => 'paragraph',
		'title'       => __( 'Webhook URL', 'woo-payment-gateway' ),
		'text'        => get_rest_url( null, 'wc-braintree/v1/webhook/notification' ),
		'class'       => 'wc-braintree-webhook-url',
		'description' => sprintf( __( 'This is the URL that Braintree uses to send notifications such as when a local payment is processed. You must add this URL to your Braintree control panel when using local payment methods. %1$sWebhook guide%2$s', 'woo-payment-gateway' ), '<a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/webhooks"/>', '</a>' ),
	),
	'locate_keys'                => array(
		'type'        => 'title',
		'title'       => '',
		'description' => include braintree()->plugin_path() . 'includes/admin/views/api-key-instructions.php',
	),
	'sandbox_connection_test'    => array(
		'type'              => 'button',
		'title'             => __( 'Sandbox Connection Test', 'woo-payment-gateway' ),
		'class'             => 'button button-secondary wc-braintree-connection-test sandbox',
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'sandbox' ) ),
	),
	'production_connection_test' => array(
		'type'              => 'button',
		'title'             => __( 'Production Connection Test', 'woo-payment-gateway' ),
		'class'             => 'button button-secondary wc-braintree-connection-test production',
		'custom_attributes' => array( 'data-show-if' => array( 'environment' => 'production' ) ),
	),
	'debug'                      => array(
		'type'        => 'checkbox',
		'title'       => __( 'Debug Enabled', 'woo-payment-gateway' ),
		'default'     => 'yes',
		'description' => __(
			'If enabled, the plugin will capture debug information related to payments. This is useful for troubleshooting errors.<a href="' .
			admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank"><p>Debug Log</p></a>',
			'woo-payment-gateway'
		),
	),
	'register'                   => array(
		'type'        => 'checkbox',
		'title'       => __( 'Register Plugin', 'woo-payment-gateway' ),
		'class'       => 'wc-braintree-required',
		'default'     => 'yes',
		'description' => sprintf( '<strong>%s</strong> %s', __( 'Recommended!', 'woo-payment-gateway' ), __( 'By registering the plugin we receive your merchant ID. There is no security risk as merchant ID\'s are public. This helps us find your account quickly for support requests.', 'woo-payment-gateway' ) ),
	),
);
