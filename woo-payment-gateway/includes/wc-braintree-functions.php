<?php

defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 * Functions related to WC.
 *
 * @since 3.0.0
 */

/**
 *
 * @param string $template_name
 * @param array  $args
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_template( $template_name, $args = array() ) {
	return wc_get_template( $template_name, $args, braintree()->template_path(), braintree()->plugin_path() . 'templates/' );
}

/**
 *
 * @param string $template_name
 * @param array  $args
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_template_html( $template_name, $args = array() ) {
	return wc_get_template_html( $template_name, $args, braintree()->template_path(), braintree()->plugin_path() . 'templates/' );
}

/**
 *
 * @param string $env
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_private_key( $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;

	return braintree()->api_settings->get_option( "{$env}_private_key" );
}

/**
 *
 * @param string $env
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_public_key( $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;

	return braintree()->api_settings->get_option( "{$env}_public_key" );
}

/**
 *
 * @param string $env
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_merchant_id( $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;

	return braintree()->api_settings->get_option( "{$env}_merchant_id" );
}

/**
 * Return an array of connection values used to establish connection with Braintree.
 *
 * @param string $env
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_connection_settings( $env = '' ) {
	return apply_filters(
		'wc_braintree_connection_settings',
		array(
			'environment' => empty( $env ) ? wc_braintree_environment() : $env,
			'merchantId'  => wc_braintree_merchant_id( $env ),
			'publicKey'   => wc_braintree_public_key( $env ),
			'privateKey'  => wc_braintree_private_key( $env ),
		)
	);
}

/**
 * Return the currently active environment.
 * Possible values are production or sandbox.
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_environment() {
	return apply_filters( 'wc_braintree_environment', braintree()->api_settings->get_option( 'environment' ) );
}

/**
 * Return true of Production is currently active.
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_production_active() {
	return wc_braintree_environment() === 'production';
}

/**
 * Log the provided message in the WC logs directory.
 *
 * @param int    $level
 * @param string $message
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_log( $level, $message ) {
	if ( braintree()->api_settings->is_active( 'debug' ) ) {
		$log = wc_get_logger();
		$log->log( $level, $message, array( 'source' => 'wc-braintree' ) );
	}
}

/**
 *
 * @param string $message
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_log_error( $message ) {
	wc_braintree_log( WC_Log_Levels::ERROR, $message );
}

/**
 *
 * @param string $message
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_log_info( $message ) {
	wc_braintree_log( WC_Log_Levels::INFO, $message );
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_custom_form_options() {
	return apply_filters(
		'wc_braintree_custom_form_options',
		array(
			'bootstrap_form'         => array(
				'label'          => __( 'Bootstrap Form', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/bootstrap-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/bootstrap-form.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/bootstrap-form.js',
				'default_styles' => '{"input":{"font-size":"16px", "font-family":"helvetica, tahoma, calibri, sans-serif"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}',
			),
			'google_material_design' => array(
				'label'          => __( 'Material Design', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/google-material-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/google-material-design.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/google-material-design.js',
				'default_styles' => '{"input":{"font-size":"16px", "font-family":"roboto, verdana, sans-serif"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}',
			),
			'dynamic_card_form'      => array(
				'label'          => __( 'Dynamic Card Design', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/dynamic-card-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/dynamic-card-form.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/dynamic-card-form.js',
				'default_styles' => '{"input":{"font-size":"16px"}, "input.invalid":{"color":"#E53A40"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}',
			),
			'classic_form'           => array(
				'label'          => __( 'Classic Form', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/classic-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/classic-form.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/classic-form.js',
				'default_styles' => '{"input":{"font-size":"16px"}, ".invalid":{"color":"#D0021B"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}',
			),
			'simple_form'            => array(
				'label'          => __( 'Simple Form', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/simple-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/simple-form.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/simple-form.js',
				'default_styles' => '{"input":{"font-size":"16px", "font-family":"courier, monospace"}, ".valid":{"color":"rgb(94, 189, 128)"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}',
			),
			'card_shape_form'        => array(
				'label'          => __( 'Card Shape Form', 'woo-payment-gateway' ),
				'template'       => 'custom-forms/card-shape-form.php',
				'css'            => WC_BRAINTREE_ASSETS . 'css/custom-forms/card-shape-form.css',
				'external_css'   => '',
				'javascript'     => WC_BRAINTREE_ASSETS . 'js/frontend/custom-forms/card-shape-form.js',
				'default_styles' => '{"input":{"font-size":"16px", "font-family":"courier, monospace", "font-weight":"500"}}',
			),
		)
	);
}

/**
 *
 * @param string $key
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_get_custom_form( $key ) {
	return wc_braintree_custom_form_options()[ $key ];
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_card_loader_options() {
	return apply_filters(
		'wc_braintree_card_loader_options',
		array(
			'circular-loader.php'  => 'circular-loader',
			'hour-glass.php'       => 'hour-glass',
			'loading.php'          => 'loading',
			'pacman.php'           => 'pacman',
			'ping-pong.php'        => 'ping-pong',
			'processing-plain.php' => 'processing-plain',
			'rotating-gears.php'   => 'rotating-gears',
			'simple-dots.php'      => 'simple-dots',
			'stairs-loader.php'    => 'stairs-loader',
		)
	);
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_payment_method_formats() {
	return apply_filters(
		'wc_braintree_payment_method_formats',
		array(
			\Braintree\PaymentInstrumentType::CREDIT_CARD     => array(
				'type_ending_in'          => array(
					'label'   => __( 'Type Ending In', 'woo-payment-gateway' ),
					'example' => 'Visa ending in 1111',
					'format'  => __( '{card_type} ending in {last4}', 'woo-payment-gateway' ),
				),
				'type_masked_number'      => array(
					'label'   => __( 'Type Masked Number', 'woo-payment-gateway' ),
					'example' => 'Visa 4111********1111',
					'format'  => '{card_type} {masked_number}',
				),
				'type_dash_masked_number' => array(
					'label'   => __( 'Type Dash Masked Number', 'woo-payment-gateway' ),
					'example' => 'Visa - 4111********1111',
					'format'  => '{card_type} - {masked_number}',
				),
				'type_last4'              => array(
					'label'   => __( 'Type Last 4', 'woo-payment-gateway' ),
					'example' => 'Visa 1111',
					'format'  => '{card_type} {last4}',
				),
				'type_dash_last4'         => array(
					'label'   => __( 'Type Dash & Last 4', 'woo-payment-gateway' ),
					'example' => 'Visa - 1111',
					'format'  => '{card_type} - {last4}',
				),
				'masked_number'           => array(
					'label'   => __( 'Masked Number', 'woo-payment-gateway' ),
					'example' => '4111********1111',
					'format'  => '{masked_number}',
				),
				'last4'                   => array(
					'label'   => __( 'Last Four', 'woo-payment-gateway' ),
					'example' => '1111',
					'format'  => '{last4}',
				),
				'card_type'               => array(
					'label'   => __( 'Card Type', 'woo-payment-gateway' ),
					'example' => 'Visa',
					'format'  => '{card_type}',
				),
			),
			\Braintree\PaymentInstrumentType::PAYPAL_ACCOUNT  => array(
				'paypal_and_email' => array(
					'label'   => __( 'PayPal & Email', 'woo-payment-gateway' ),
					'example' => 'PayPal - john@example.com',
					'format'  => 'PayPal - {email}',
				),
				'email'            => array(
					'label'   => __( 'Email', 'woo-payment-gateway' ),
					'example' => 'john@example.com',
					'format'  => '{email}',
				),
				'paypal'           => array(
					'label'   => __( 'PayPal', 'woo-payment-gateway' ),
					'example' => 'PayPal',
					'format'  => __( 'PayPal', 'woo-payment-gateway' ),
				),
			),
			\Braintree\PaymentInstrumentType::APPLE_PAY_CARD  => array(
				'apple_type_last4' => array(
					'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
					'example' => 'Apple Pay - Discover 2928',
					'format'  => 'Apple Pay - {payment_instrument_name}',
				),
				'type_last4'       => array(
					'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
					'example' => 'Discover 2928',
					'format'  => '{payment_instrument_name}',
				),
			),
			\Braintree\PaymentInstrumentType::GOOGLE_PAY_CARD => array(
				'type_ending_in'    => array(
					'label'   => __( 'Type Ending In', 'woo-payment-gateway' ),
					'example' => 'Visa ending in 1111',
					'format'  => __( '{card_type} ending in {last4}', 'woo-payment-gateway' ),
				),
				'google_type_last4' => array(
					'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
					'example' => 'Google Pay - Visa 1111',
					'format'  => 'Google Pay - {card_type} {last4}',
				),
				'type_last4'        => array(
					'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
					'example' => 'Visa 1111',
					'format'  => '{card_type} {last4}',
				),
			),
			\Braintree\PaymentInstrumentType::VENMO_ACCOUNT   => array(
				'type_and_user'      => array(
					'label'   => __( 'Type and User ID', 'woo-payment-gateway' ),
					'example' => 'Venmo - john.smith1990',
					'format'  => __( 'Venmo - {username}', 'woo-payment-gateway' ),
				),
				'source_description' => array(
					'label'   => __( 'Account Description', 'woo-payment-gateway' ),
					'example' => 'Venmo Account: john.smith1990',
					'format'  => '{sourceDescription}',
				),
			),
			'local_payment'                                   => array(
				'source_and_payer' => array(
					'label'   => __( 'Type and Payer Id', 'woo-payment-gateway' ),
					'example' => 'iDEAL - XF-d647tg',
					'format'  => '{funding_source} - {payer_id}',
				),
			),
		)
	);
}

/**
 *
 * @param \Braintree\Base|\Braintree\Instance $payment_method
 *
 * @since   3.0.0
 * @return WC_Payment_Token
 * @package Braintree/Functions
 */
function wc_braintree_create_payment_token( $method, $gateway_id, $user_id = 0, $environment = '' ) {
	$token = null;
	if ( $method instanceof \Braintree\CreditCard || $method instanceof \Braintree\Transaction\CreditCardDetails ) {
		$token = new WC_Payment_Token_Braintree_CC();
		$token->set_method_type( str_replace( ' ', '', $method->cardType ) );
		$token->set_card_type( $method->cardType );
		$token->set_expiry_month( $method->expirationMonth );
		$token->set_expiry_year( $method->expirationYear );
		$token->set_expiry_date( $method->expirationDate );
		$token->set_card_holder( $method->cardholderName );
		$token->set_bin( $method->bin );
		$token->set_last4( $method->last4 );
		$token->set_masked_number( $method->maskedNumber );
		$token->set_payment_instrument_type( \Braintree\PaymentInstrumentType::CREDIT_CARD );
	}
	if ( $method instanceof \Braintree\PayPalAccount || $method instanceof \Braintree\Transaction\PayPalDetails ) {
		$token = new WC_Payment_Token_Braintree_PayPal();
		$token->set_method_type( 'PayPal' );
		if ( $method instanceof \Braintree\Transaction\PayPalDetails ) {
			$token->set_email( $method->payerEmail );
			$token->set_first_name( $method->payerFirstName );
			$token->set_last_name( $method->payerLastName );
		} else {
			$token->set_email( $method->email );
		}
		$token->set_payment_instrument_type( \Braintree\PaymentInstrumentType::PAYPAL_ACCOUNT );
	}
	if ( $method instanceof \Braintree\ApplePayCard || $method instanceof \Braintree\Transaction\ApplePayCardDetails ) {
		$token = new WC_Payment_Token_Braintree_ApplePay();
		$token->set_card_type( $method->cardType );
		$token->set_method_type( 'ApplePay' );
		$token->set_expiry_month( $method->expirationMonth );
		$token->set_expiry_year( $method->expirationYear );
		$token->set_card_holder( $method->cardholderName );
		$token->set_bin( $method->bin );
		$token->set_last4( $method->last4 );
		$token->set_masked_number( $token->get_bin() . '******' . $token->get_last4() );
		$token->set_payment_instrument_name( $method->paymentInstrumentName );
		$token->set_payment_instrument_type( \Braintree\PaymentInstrumentType::APPLE_PAY_CARD );
	}
	if ( $method instanceof \Braintree\GooglePayCard || $method instanceof \Braintree\Transaction\GooglePayCardDetails ) {
		$token = new WC_Payment_Token_Braintree_GooglePay();
		$token->set_card_type( $method->sourceCardType );
		$token->set_method_type( 'GooglePay' );
		$token->set_expiry_month( $method->expirationMonth );
		$token->set_expiry_year( $method->expirationYear );
		$token->set_bin( $method->bin );
		$token->set_last4( $method->sourceCardLast4 );
		$token->set_source_description( $method->sourceDescription );
		$token->set_virtual_card_type( $method->virtualCardType );
		$token->set_virtual_last4( $method->virtualCardLast4 );
		$token->set_masked_number( $token->get_bin() . '******' . $token->get_last4() );
		$token->set_payment_instrument_type( \Braintree\PaymentInstrumentType::GOOGLE_PAY_CARD );
	}
	if ( $method instanceof \Braintree\VenmoAccount || $method instanceof \Braintree\Transaction\VenmoAccountDetails ) {
		$token = new WC_Payment_Token_Braintree_Venmo();
		$token->set_source_description( $method->sourceDescription );
		$token->set_username( $method->username );
		$token->set_venmo_user_id( $method->venmoUserId );
		$token->set_method_type( 'Venmo' );
		$token->set_payment_instrument_type( \Braintree\PaymentInstrumentType::VENMO_ACCOUNT );
	}
	if ( 'braintree_ideal' === $gateway_id && is_array( $method ) ) {
		$method = (object) $method;
		$token  = new WC_Payment_Token_Braintree_Local_Payment();
		$token->set_funding_source( $method->fundingSource );
		$token->set_payer_id( $method->payerId );
		$token->set_payment_id( $method->paymentId );
		$token->set_payment_instrument_type( 'local_payment' );
	}
	$token->set_token( $method->token );
	$token->set_gateway_id( $gateway_id );
	$token->set_user_id( ! $user_id ? get_current_user_id() : $user_id );
	$token->set_format( wc_braintree_get_payment_method_format( $gateway_id ) );
	$token->set_environment( empty( $environment ) ? wc_braintree_environment() : $environment );

	return $token;
}

/**
 *
 * @param string $gateway_id
 *
 * @return string
 * @version 3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_payment_method_format( $gateway_id ) {
	$gateway = WC()->payment_gateways()->payment_gateways()[ $gateway_id ];

	return apply_filters( 'wc_braintree_get_payment_method_format', $gateway->get_option( 'method_format' ), $gateway );
}

/**
 * Return an array of payment gateways that support wc_braintree_fee
 *
 * @since   3.0.0
 * @return WC_Braintree_Payment_Gateway[]
 * @package Braintree/Functions
 */
function wc_braintree_get_fee_gateways() {
	$gateways = WC()->payment_gateways()->payment_gateways();
	foreach ( $gateways as $id => $gateway ) {
		if ( ! $gateway->supports( 'wc_braintree_fees' ) ) {
			unset( $gateways[ $id ] );
		}
	}

	return apply_filters( 'wc_braintree_get_fee_gateways', $gateways );
}

/**
 * Return true if the WooCommerce Subscriptions plugin is active.
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wcs_braintree_active() {
	return function_exists( 'wcs_is_subscription' );
}

/**
 * Return true if the Braintree plugin's subscription functionality is active.
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_subscriptions_active() {
	$options = get_option( 'woocommerce_braintree_subscription_settings', array( 'enabled' => 'no' ) );
	if ( empty( $options['enabled'] ) ) {
		$options['enabled'] = 'no';
	}

	return ! wcs_braintree_active() && $options['enabled'] === 'yes';
}

/**
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_custom_form_active() {
	return braintree()->payment_gateways()->credit_card->get_option( 'form_type' ) === 'custom_form';
}

/**
 * Echo an input field for a braintree payment_method_nonce.
 *
 * @param WC_Braintree_Payment_Gateway $gateway
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_nonce_field( $gateway, $value = '' ) {
	wc_braintree_hidden_field( $gateway->nonce_key, 'wc-braintree-payment-nonce' );
}

/**
 *
 * @param WC_Braintree_Payment_Gateway $gateway
 * @param string                       $token
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_payment_token_field( $gateway, $token = '' ) {
	wc_braintree_hidden_field( $gateway->token_key, 'wc-braintree-payment-token' );
}

/**
 *
 * @param WC_Braintree_Payment_Gateway $gateway
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_device_data_field( $gateway ) {
	wc_braintree_hidden_field( $gateway->device_data_key, 'wc-braintree-device-data' );
}

/**
 *
 * @param string $key
 * @param string $class
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_hidden_field( $key, $class = '' ) {
	printf( '<input type="hidden" id="%1$s" name="%1$s" class="%2$s"/>', $key, $class );
}

/**
 *
 * @since   3.1.5
 * @package Braintree/Functions
 */
function wc_braintree_spam_bot_field() {
	if ( braintree()->WC_BRAINTREE_SPAM_COUNT == 0 ) {
		printf( '<input type="checkbox" value="1" name="braintree_customer_email" style="display: none" autocomplete="off" tabindex="-1"/>' );
		braintree()->WC_BRAINTREE_SPAM_COUNT ++;
	}
}

/**
 *
 * @return boolean
 * @package Braintree/Functions
 * @version 3.0.0
 */
function wc_braintree_save_cc_enabled() {
	global $wp;
	if ( wcs_braintree_active() ) {
		if ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() ) {
			return false;
		}
		if ( WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
			return false;
		}
	}
	if ( wc_braintree_subscriptions_active() ) {
		if ( wcs_braintree_cart_contains_subscription() ) {
			return false;
		}
		if ( wcs_braintree_is_change_payment_method_request() ) {
			return false;
		}
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {
			return ! wcs_braintree_order_contains_subscription( absint( $wp->query_vars['order-pay'] ) );
		}
	}
	// added 3.1.10
	if ( wc_braintree_pre_orders_active() && WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
		return ! WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Cart::get_pre_order_product() );
	}

	return apply_filters( 'wc_braintree_save_cc_enabled', is_checkout() && WC()->payment_gateways()->payment_gateways()['braintree_cc']->is_active( 'save_card_enabled' ) );
}

/**
 *
 * @param WC_Payment_Token_Braintree $method
 * @param string                     $format
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_payment_method_title( $method, $format_key = '' ) {
	$format = wc_braintree_payment_method_formats()[ $method->get_payment_instrument_type() ][ $format_key ]['format'];
	$data   = $method->get_props_data();

	return apply_filters( 'wc_braintree_payment_method_title', str_replace( array_keys( $data ), $data, $format ), $method, $format_key );
}

/**
 *
 * @param array $classes
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_add_body_class( $classes ) {
	$classes[] = 'wc-braintree-body';

	return $classes;
}

/**
 * Return the merchant account based on WC currency.
 *
 * @param string $currency
 * @param string $env
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_merchant_account( $currency = '', $env = '' ) {
	$accounts = wc_braintree_get_merchant_accounts( $env );
	$currency = empty( $currency ) ? get_woocommerce_currency() : $currency;

	$account = isset( $accounts[ $currency ] ) ? $accounts[ $currency ] : '';

	/**
	 * @param string $account
	 * @param string $currency
	 *
	 * @since 3.2.1
	 */
	return apply_filters( 'wc_braintree_get_merchant_account', $account, $currency );
}

/**
 * Return all merchant accounts for the given environment.
 * If no env is provided then active
 * environment is used.
 *
 * @param string $env
 *
 * @since   3.0.0
 * @return array
 * @package Braintree/Functions
 */
function wc_braintree_get_merchant_accounts( $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;

	/**
	 * @since 3.2.1
	 */
	return apply_filters( 'wc_braintree_get_merchant_accounts', braintree()->merchant_settings->get_option( "{$env}_merchant_accounts" ), $env );
}

/**
 * @param        $user_id
 * @param string $env
 *
 * @since   3.0.0
 * @return mixed|boolean|string|unknown
 * @package Braintree/Functions
 */
function wc_braintree_get_customer_id( $user_id = '', $env = '' ) {
	// if the user id is zero, then a non logged in user was passed to the function.
	if ( $user_id === 0 ) {
		return '';
	}
	$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;
	$env     = empty( $env ) ? wc_braintree_environment() : $env;

	return apply_filters( 'wc_braintree_get_customer_id', get_user_option( "braintree_{$env}_vault_id", $user_id ), $user_id, $env );
}

/**
 *
 * @param int    $user_id
 * @param int    $vault_id
 * @param string $env
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_save_customer( $user_id, $vault_id, $env = '' ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;
	update_user_option( $user_id, "braintree_{$env}_vault_id", apply_filters( 'wc_braintree_save_customer_id', $vault_id ) );
}

function wc_braintree_delete_customer( $user_id, $env = '', $global = false ) {
	$env = empty( $env ) ? wc_braintree_environment() : $env;
	delete_user_option( $user_id, "braintree_{$env}_vault_id", $global );
}

/**
 *
 * @param \Braintree\Result $response
 *
 * @since        3.0.0
 * @noinspection PhpSingleStatementWithBracesInspection
 * @package      Braintree/Functions
 */
function wc_braintree_errors_from_object( $response ) {
	$message  = '';
	$messages = wc_braintree_get_error_messages();
	if ( $response instanceof \Braintree\Exception ) {
		if ( isset( $messages[ get_class( $response ) ] ) ) {
			$message = $messages[ get_class( $response ) ];
		}
	} elseif ( $response instanceof \Braintree\Result\Error ) {
		if ( $response->errors->count() > 0 ) {
			foreach ( $response->errors->deepAll() as $error ) {
				if ( isset( $messages[ $error->code ] ) ) {
					$message = apply_filters( 'wc_braintree_errors_from_collection', $messages[ $error->code ], $error );
				}
				if ( empty( $message ) ) {
					$message = $error->message;
				}
				if ( substr( $message, - 1 ) !== '.' ) {
					$message .= '.';
				}
				$message .= ' ';
			}
		} elseif ( $response->message ) {
			// added in 3.1.10
			$key = strtolower( str_replace( ' ', '_', $response->message ) );
			if ( isset( $messages[ $key ] ) ) {
				$message = $messages[ $key ];
			} else {
				$message = apply_filters( 'wc_braintree_result_error_message', $response->message );
			}
		}
	}
	if ( is_string( $response ) ) {
		$message = $response;
	}

	return apply_filters( 'wc_braintree_errors_from_object', $message, $response );
}

/**
 *
 * @param \Braintree\Transaction $transaction
 *
 * @return WC_Payment_Token_Braintree
 * @package Braintree/Functions
 */
function wc_braintree_create_payment_token_from_transaction( $transaction, $gateway_id, $user_id = 0 ) {
	$payment_details = null;
	switch ( $transaction->paymentInstrumentType ) {
		case \Braintree\PaymentInstrumentType::CREDIT_CARD:
			$payment_details = $transaction->creditCardDetails;
			break;
		case \Braintree\PaymentInstrumentType::PAYPAL_ACCOUNT:
			$payment_details = $transaction->paypalDetails;
			break;
		case \Braintree\PaymentInstrumentType::APPLE_PAY_CARD:
			$payment_details = $transaction->applePayCardDetails;
			break;
		case \Braintree\PaymentInstrumentType::GOOGLE_PAY_CARD:
			$payment_details = $transaction->googlePayCardDetails;
			break;
		case \Braintree\PaymentInstrumentType::VENMO_ACCOUNT:
			$payment_details = $transaction->venmoAccountDetails;
			break;
		case 'local_payment':
			$payment_details = $transaction->localPayment;
			break;
	}

	return wc_braintree_create_payment_token( $payment_details, $gateway_id, $user_id );
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_set_checkout_error() {
	add_action( 'woocommerce_after_template_part', 'wc_braintree_output_checkout_error' );
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_output_checkout_error( $template_name ) {
	if ( $template_name === 'notices/error.php' && is_ajax() ) {
		echo '<input type="hidden" id="wc_braintree_checkout_error" value="true"/>';
		remove_action( 'woocommerce_after_template_part', 'wc_braintree_output_checkout_error' );
		add_filter( 'wp_kses_allowed_html', 'wc_braintree_add_allowed_html', 10, 2 );
	}
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_add_allowed_html( $tags, $context ) {
	if ( $context === 'post' ) {
		$tags['input'] = array(
			'id'    => true,
			'type'  => true,
			'value' => true,
		);
	}

	return $tags;
}

/**
 *
 * @param int              $token_id
 * @param WC_Payment_Token $token
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_woocommerce_payment_token_deleted( $token_id, $token ) {
	if ( ! did_action( 'woocommerce_payment_gateways' ) ) {
		WC_Payment_Gateways::instance();
	}
	do_action( 'wc_braintree_payment_token_deleted_' . $token->get_gateway_id(), $token_id, $token );
}

/**
 * Filter Braintree payment tokens based on the current environment.
 * You wouldn't want to show sandbox
 * payment tokens when the current environment is production.
 *
 * @param WC_Payment_Token[] $tokens
 * @param int                $user_id
 * @param string             $gateway_id
 *
 * @since   3.0.0
 * @deprecated
 *
 * @package Braintree/Functions
 */
function wc_braintree_filter_customer_payment_tokens( $tokens, $user_id, $gateway_id ) {
	foreach ( $tokens as $i => $token ) {
		if ( strpos( $token->get_type(), 'Braintree_' ) !== false ) {
			$env = wc_braintree_environment();
			if ( $env !== $token->get_meta( 'environment' ) ) {
				unset( $tokens[ $i ] );
			}
		}
	}

	return $tokens;
}

/**
 * Filter payment tokens based on the current environment.
 *
 * @param WC_Payment_Token[] $tokens
 * @param int                $user_id
 * @param string             $gateway_id
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_filter_payment_tokens( $tokens, $user_id, $gateway_id ) {
	$env = wc_braintree_environment();
	foreach ( $tokens as $i => $token ) {
		if ( $token instanceof WC_Payment_Token_Braintree ) {
			if ( $env !== $token->get_environment() ) {
				unset( $tokens[ $i ] );
			}
		}
	}

	return $tokens;
}

/**
 * Returns a list of Payment tokens and bypasses WordPress's number of posts retriction.
 *
 * @param int    $user_id
 * @param string $env
 *
 * @since   3.0.2
 * @return WC_Payment_Token_Braintree[]
 * @package Braintree/Functions
 */
function wc_braintree_get_payment_tokens( $user_id, $env = '' ) {
	global $wpdb;
	$tokens = array();
	if ( ! $user_id ) {
		return $tokens;
	}
	$results = $wpdb->get_results( $wpdb->prepare( "SELECT tokens.token_id FROM {$wpdb->prefix}woocommerce_payment_tokens as tokens WHERE tokens.user_id=%d AND tokens.gateway_id LIKE %s",
		$user_id,
		'braintree_%' ) );
	if ( $results ) {
		foreach ( $results as $result ) {
			$token                       = WC_Payment_Tokens::get( $result->token_id );
			$tokens[ $result->token_id ] = $token;
			if ( ! $token instanceof WC_Payment_Token_Braintree || ( $env && $env !== $token->get_environment() ) ) {
				unset( $tokens[ $result->token_id ] );
			}
		}
	}

	return $tokens;
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_get_card_type_icons() {
	$path       = braintree()->assets_path() . 'img/';
	$gateway    = WC()->payment_gateways()->payment_gateways()['braintree_cc'];
	$icon_style = $gateway ? $gateway->get_option( 'icon_style' ) : 'closed';

	return apply_filters(
		'wc_braintree_get_card_type_icons',
		array(
			'Visa'            => sprintf( '<img class="wc-braintree-method-icon %s visa" src="%s"/>',
				$icon_style,
				$path . "payment-methods/{$icon_style}/visa.svg" ),
			'MasterCard'      => sprintf(
				'<img class="wc-braintree-method-icon %s mastercard" src="%s"/>',
				$icon_style,
				$path .
				"payment-methods/{$icon_style}/master_card.svg"
			),
			'AmericanExpress' => sprintf( '<img class="wc-braintree-method-icon %s amex" src="%s"/>',
				$icon_style,
				$path . "payment-methods/{$icon_style}/amex.svg" ),
			'Discover'        => sprintf(
				'<img class="wc-braintree-method-icon %s discover" src="%s"/>',
				$icon_style,
				$path .
				"payment-methods/{$icon_style}/discover.svg"
			),
			'DinersClub'      => sprintf(
				'<img class="wc-braintree-method-icon %s diners" src="%s"/>',
				$icon_style,
				$path .
				"payment-methods/{$icon_style}/diners_club_international.svg"
			),
			'JCB'             => sprintf( '<img class="wc-braintree-method-icon %s jcb" src="%s"/>',
				$icon_style,
				$path . "payment-methods/{$icon_style}/jcb.svg" ),
			'Maestro'         => sprintf( '<img class="wc-braintree-method-icon %s maestro" src="%s"/>',
				$icon_style,
				$path . "payment-methods/{$icon_style}/maestro.svg" ),
			'PayPal'          => sprintf( '<img class="wc-braintree-method-icon paypal" src="%s"/>', $path . 'payment-methods/paypal.svg' ),
			'GooglePay'       => sprintf( '<img class="wc-braintree-method-icon googlepay" src="%s"/>', $path . 'googlepay/google_pay_standard.svg' ),
			'UnionPay'        => sprintf(
				'<img class="wc-braintree-method-icon %s unionpay" src="%s"/>',
				$icon_style,
				$path .
				"payment-methods/{$icon_style}/china_union_pay.svg"
			),
			'ApplePay'        => sprintf( '<img class="wc-braintree-method-icon applepay" src="%s"/>', $path . 'applepay/apple_pay_mark.svg' ),
			'Venmo'           => sprintf(
				'<img class="wc-braintree-method-icon venmo" src="%s"/>',
				$path . 'payment-methods/venmo.svg'
			),
		)
	);
}

/**
 *
 * @param string $condition
 *
 * @since   3.0.0
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_evaluate_condition( $condition ) {
	if ( ! $condition ) {
		return true;
	}
	$evaluator = new WC_Braintree_Condition_Evaluator();

	return $evaluator->evaluate( $condition, wc_braintree_get_conditional_values() );
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_get_conditional_values() {
	if ( ! WC()->cart ) {
		return array();
	}
	$values = array(
		'{amount}'    => WC()->cart->total,
		'{qty}'       => WC()->cart->get_cart_contents_count(),
		'{currency}'  => get_woocommerce_currency(),
		'{b_country}' => WC()->customer->get_billing_country(),
		'{s_country}' => WC()->customer->get_shipping_country(),
		'{products}'  => wc_braintree_get_products_for_conditions(),
	);

	return apply_filters( 'wc_braintree_get_conditional_values', $values );
}

/**
 *
 * @since   3.0.0
 * @return string
 * @package Braintree/Functions
 */
function wc_braintree_get_products_for_conditions() {
	global $wpdb;
	$products = array();
	$query    = $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_type = %s AND post_status = %s", 'product', 'publish' );
	$results  = $wpdb->get_results( $query );
	foreach ( $results as $result ) {
		$products[] = $result->post_name;
	}

	return implode( ',', $products );
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_get_3ds_actions() {
	return apply_filters(
		'wc_braintree_get_3ds_actions',
		array(
			'no_action'      => __( 'No Action (Braintree will handle)', 'woo-payment-gateway' ),
			'authorize_only' => __( 'Authorize Amount', 'woo-payment-gateway' ),
			'reject'         => __( 'Reject Transaction', 'woo-payment-gateway' ),
			'accept'         => __(
				'Accept Transaction',
				'woo-payment-gateway'
			),
		)
	);
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_order_statuses_for_registration() {
	return apply_filters(
		'wc_braintree_order_statuses_for_registration',
		array(
			'wc-kount-review'   => array(
				'label'                     => _x( 'Kount review required', 'Order status', 'woo-payment-gateway' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Kount review required <span class="count">(%s)</span>',
					'Kount review required <span class="count">(%s)</span>',
					'woo-payment-gateway' ),
			),
			'wc-kount-escalate' => array(
				'label'                     => _x( 'Kount escalate', 'Order status', 'woo-payment-gateway' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Kount escalate <span class="count">(%s)</span>',
					'Kount escalate <span class="count">(%s)</span>',
					'woo-payment-gateway'
				),
			),
		)
	);
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_order_statuses() {
	return apply_filters(
		'wc_braintree_order_statuses',
		array(
			'wc-kount-review'   => __( 'Kount review required', 'woo-payment-gateway' ),
			'wc-kount-escalate' => __(
				'Kount escalate',
				'woo-payment-gateway'
			),
		)
	);
}

/**
 *
 * @param array $statuses
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_merge_order_statuses( $statuses ) {
	if ( braintree()->fraud_settings ) {
		if ( braintree()->fraud_settings->is_active( 'kount_enabled' ) ) {
			$statuses = array_merge( $statuses, wc_braintree_order_statuses() );
		}
	}

	return $statuses;
}

/**
 *
 * @param string $xml
 * @param string $start
 * @param string $end
 * @param bool   $include_start_end
 *
 * @since   3.0.0
 * @return string
 * @package Braintree/Functions
 */
function wc_braintree_parse_xml_contents( $xml, $start, $end, $include_start_end = true ) {
	$strpos   = strpos( $xml, $start ) + strlen( $start ); // $start = $subscription
	$strpos2  = strrpos( $xml, $end ); // last occurance of $end
	$length   = absint( $strpos2 - $strpos );
	$contents = substr( $xml, $strpos, $length );

	return $include_start_end ? trim( sprintf( '%s%s%s', $start, $contents, $end ) ) : trim( $contents );
}

/**
 * Return the Braintree environment that this order was created for.
 * <ul>
 * <li>sandbox</li>
 * <li>production</li>
 * </ul>
 *
 * @param WC_Order $order
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_order_environment( $order ) {
	return $order->get_meta( Constants::ENVIRONMENT, true );
}

/**
 * Hooks in to woocommerce_payment_complete_order_status and updates the order status based
 * on gateway settings.
 *
 * @param string   $status
 * @param int      $id
 * @param WC_Order $order
 *
 * @since   3.0.0
 * @return mixed
 * @package Braintree/Functions
 */
function wc_braintree_payment_complete_order_status( $status, $id, $order = null ) {
	if ( did_action( 'woocommerce_pre_payment_complete' ) ) {
		if ( null === $order ) {
			$order = wc_get_order( $id );
		}
		$gateway_id = $order->get_payment_method();
		$gateways   = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways[ $gateway_id ] ) ) {
			$gateway = $gateways[ $gateway_id ];
			if ( $gateway instanceof WC_Braintree_Payment_Gateway ) {
				if ( $gateway->get_option( 'order_status' ) !== 'default' ) {
					$status = $gateway->get_option( 'order_status' );
				}
			}
		}
	}

	return $status;
}

/**
 * Hooked in to the webhook notification test
 *
 * @param \Braintree\WebhookNotification $notification
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_webhook_check( $notification ) {
	wc_braintree_log_info( sprintf( __( 'Webhook test notification received.', 'woo-payment-gateway' ) ) );
}

/**
 *
 * @param array $gateways
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_payment_gateways( $gateways ) {
	return array_merge( $gateways, braintree()->payment_gateways() );
}

/**
 * Given a Braintree transaction object, map the cvvResponseCode etc to human readable messages.
 *
 * @param \Braintree\Transaction $transaction
 *
 * @since   3.0.0
 * @package Braintree/Functions
 */
function wc_braintree_get_response_code_message( $code ) {
	$codes = array(
		'I' => 'Not provided (I)',
		'M' => 'Matches (M)',
		'N' => 'Does not match (N)',
		'U' => 'Not verified (U)',
		'S' => 'Issuer does not participate (S)',
		'A' => 'Not applicable (A)',
		'B' => 'Bypass (B)',
	);

	return $codes[ $code ];
}

/**
 * Returns an array of localized local payment gateway params used on the
 * checkout page.
 *
 * @param array  $data
 * @param string $handle
 *
 * @since   3.0.0
 * @return string[]|string[][]|NULL[]
 * @package Braintree\Functions
 */
function wc_braintree_local_payment_params( $data, $handle ) {
	global $wp;
	$data     = array();
	$gateways = wc_braintree_get_local_gateways();
	$data     = array(
		'gateways' => array_map(
			function ( $gateway ) {
				return $gateway->localize_script_params();
			},
			$gateways
		),
	);
	if ( isset( $wp->query_vars['order-pay'] ) ) {
		$data['order_id'] = absint( $wp->query_vars['order-pay'] );
		$order            = wc_get_order( absint( $data['order_id'] ) );
		if ( $order ) {
			$data['order_key'] = $order->get_order_key();
		}
	}

	return $data;
}

/**
 *
 * @since   3.0.0
 * @return WC_Braintree_Local_Payment_Gateway[]
 * @package Braintree\Functions
 */
function wc_braintree_get_local_gateways() {
	return array_values(
		array_filter(
			WC()->payment_gateways()->get_available_payment_gateways(),
			function ( $gateway ) {
				return $gateway instanceof WC_Braintree_Local_Payment_Gateway;
			}
		)
	);
}

/**
 * Update the WC customer's address information using the provided address array.
 *
 * @param array $address
 *
 * @since   3.0.0
 * @throws Exception
 * @package Braintree\Functions
 */
function wc_braintree_update_customer_location( $address ) {
	// address validation for countries other than US is problematic when using responses from payment sources like
	// Apple Pay.
	if ( $address['postcode'] && $address['country'] === 'US' && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
		throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
	} elseif ( $address['postcode'] ) {
		$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
	}

	if ( $address['country'] ) {
		WC()->customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
		WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
		// set the customer's address if it's in the $address array
		if ( ! empty( $address['address_1'] ) ) {
			WC()->customer->set_shipping_address_1( wc_clean( $address['address_1'] ) );
		}
		if ( ! empty( $address['address_2'] ) ) {
			WC()->customer->set_shipping_address_2( wc_clean( $address['address_2'] ) );
		}
		if ( ! empty( $address['first_name'] ) ) {
			WC()->customer->set_shipping_first_name( $address['first_name'] );
		}
		if ( ! empty( $address['last_name'] ) ) {
			WC()->customer->set_shipping_last_name( $address['last_name'] );
		}
	} else {
		WC()->customer->set_billing_address_to_base();
		WC()->customer->set_shipping_address_to_base();
	}

	WC()->customer->set_calculated_shipping( true );
	WC()->customer->save();

	do_action( 'woocommerce_calculated_shipping' );
}

/**
 *
 * @since   3.0.2
 * @deprecated
 * @package Braintree/Functions
 */
function wc_braintree_enqueue_local_payments() {
	foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
		if ( $gateway instanceof WC_Braintree_Local_Payment_Gateway ) {
			$scripts = braintree()->scripts();
			if ( ! wp_script_is( $scripts->get_handle( 'local-payment' ) ) ) {
				$scripts->enqueue_script(
					'local-payment',
					$scripts->assets_url( 'js/frontend/local-payment.js' ),
					array(
						$scripts->get_handle( 'client-manager' ),
						$scripts->get_handle( 'local-payment-v3' ),
					),
					braintree()->version
				);
				$scripts->localize_script( 'local-payment', array() );

				return;
			}
		}
	}
}

/**
 *
 * @param \Braintree\WebhookNotification $notification
 * @param WP_REST_Request                $request
 *
 * @since   3.0.2
 * @package Braintree/Functions
 */
function wc_braintree_local_payment_completed( $notification, $request ) {
	$payment_id = $notification->localPaymentCompleted->paymentId;

	$order = wc_braintree_get_order_for_payment_id( $payment_id );
	if ( ! $order ) {
		wc_braintree_log_error( sprintf( 'Webhook error: no order found in your shop for paymentId %s', $payment_id ) );

		return;
	}
	$args = array( $payment_id, $notification->localPaymentCompleted->paymentMethodNonce, $order->get_id() );

	// schedule the payment action to run in 5 minutes time. This will give the client time to process
	// a payment. If the customer leaves the page, then payment will be processed via the scheduled action.
	if ( method_exists( WC(), 'queue' ) ) {
		WC()->queue()->schedule_single( time() + MINUTE_IN_SECONDS * 5, 'wc_braintree_process_scheduled_local_payment', $args );
	}
}

/**
 * @param string $payment_id
 * @param string $nonce
 * @param int    $order_id
 *
 * @since   3.2.1
 * @package Braintree/Functions
 */
function wc_braintree_process_scheduled_local_payment( $payment_id, $nonce, $order_id ) {
	$order = wc_get_order( $order_id );
	/**
	 *
	 * @var WC_Braintree_Local_Payment_Gateway $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
	if ( ! $payment_method->has_order_lock( $order ) && $order->needs_payment() ) {
		// hack to load WC functions required in process_payment method
		if ( function_exists( 'wc_load_cart' ) ) {
			WC()->frontend_includes();
			wc_load_cart();
		} else {
			WC()->frontend_includes();
			include_once( WC_ABSPATH . 'includes/class-wc-session-handler.php' );
			WC()->cart    = new WC_Cart();
			WC()->session = new WC_Session_Handler();
		}
		$payment_method->set_payment_method_nonce( $nonce );
		$payment_method->process_payment( $order_id );
		wc_braintree_log_info( sprintf( 'Order %s processed via scheduled action.', $order_id ) );
	}
}

/**
 * Returns true of the WC Pre Order plugin is active.
 *
 * @since   3.0.6
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_pre_orders_active() {
	return class_exists( 'WC_Pre_Orders' );
}

/**
 *
 * @param string $payment_id
 *
 * @since   3.0.8
 * @return WC_Order|null
 * @package Braintree/Functions
 */
function wc_braintree_get_order_for_payment_id( $payment_id ) {
	if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$ids = wc_get_orders( array(
			'type'       => 'shop_order',
			'limit'      => 1,
			'return'     => 'ids',
			'meta_query' => array(
				array(
					'key'   => '_wc_braintree_payment_id',
					'value' => $payment_id
				)
			)
		) );
		$id  = ! empty( $ids ) ? $ids[0] : null;
	} else {
		global $wpdb;
		$id
			= $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts as posts INNER JOIN $wpdb->postmeta as postmeta ON postmeta.post_id = posts.ID WHERE posts.post_type = 'shop_order' AND postmeta.meta_key = '_wc_braintree_payment_id' AND postmeta.meta_value = %s",
			$payment_id ) );
	}
	if ( $id ) {
		$order_id = $id;

		return wc_get_order( $order_id );
	}

	return null;
}

/**
 *
 * @since   3.1.8
 * @return boolean
 * @package Braintree/Functions
 */
function wc_braintree_display_prices_including_tax() {
	$cart = WC()->cart;
	if ( method_exists( $cart, 'display_prices_including_tax' ) ) {
		return $cart->display_prices_including_tax();
	}
	if ( is_callable( array( $cart, 'get_tax_price_display_mode' ) ) ) {
		return 'incl' == $cart->get_tax_price_display_mode() && ( WC()->customer && ! WC()->customer->is_vat_exempt() );
	}

	return 'incl' == $cart->tax_display_cart && ( WC()->customer && ! WC()->customer->is_vat_exempt() );
}

/**
 * Generates a client token used for setting up the Braintree SDK. The merchant account is passed
 * as an argument, so this method reliably determines which merchant account to use based on the
 * store's currency, the order's currency etc.
 *
 * @param string $env
 *
 * @since   3.2.4
 * @return string
 * @return string
 * @package Braintree/Functions
 */
function wc_braintree_generate_client_token( $env = '' ) {
	$client_token = '';
	try {
		$args = array();
		if ( ( $merchant_account = wc_braintree_get_merchant_account( wc_braintree_get_currency() ) ) ) {
			$args['merchantAccountId'] = $merchant_account;
		}
		$gateway      = new \Braintree\Gateway( wc_braintree_connection_settings( $env ) );
		$client_token = $gateway->clientToken()->generate( $args );
	} catch ( \Braintree\Exception $e ) {
		wc_braintree_log_error( sprintf( __( 'Error creating client token. Exception: %1$s', 'woo-payment-gateway' ), get_class( $e ) ) );
	}

	return $client_token;
}

/**
 * Returns the store's currency based on factors like if it's the checkout page, order pay page, etc.
 *
 * @since   3.2.4
 * @return string
 * @package Braintree/Functions
 */
function wc_braintree_get_currency() {
	global $wp;

	if ( is_checkout() ) {
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {
			$order    = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
			$currency = $order ? $order->get_currency() : get_woocommerce_currency();
		} else {
			$currency = get_woocommerce_currency();
		}
	} elseif ( is_cart() || is_product() || is_add_payment_method_page() ) {
		$currency = get_woocommerce_currency();
	} elseif ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() ) {
		$order    = wc_get_order( absint( $wp->query_vars['change-payment-method'] ) );
		$currency = $order ? $order->get_currency() : get_woocommerce_currency();
	} else {
		$currency = get_woocommerce_currency();
	}

	/**
	 * @param string $currency
	 */
	return apply_filters( 'wc_braintree_get_currency', $currency );
}

/**
 * @param string $page
 *
 * @since   3.2.5
 * @return array
 * @package Braintree/Functions
 */
function wc_braintree_get_checkout_fields( $page = '' ) {
	global $wp;
	$fields = array();
	$order  = null;
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		if ( ( $field_set = WC()->checkout()->get_checkout_fields( $type ) ) ) {
			$fields = array_merge( $fields, $field_set );
		}
	}
	if ( 'order_pay' === $page ) {
		$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
	}
	array_walk( $fields, function ( &$field, $key ) use ( $page, $order ) {
		if ( 'order_pay' === $page && null !== $order ) {
			if ( is_callable( array( $order, "get_{$key}" ) ) ) {
				$field['value'] = $order->{"get_{$key}"}();
			} else {
				$field['value'] = WC()->checkout()->get_value( $key );
			}
		} else {
			$field['value'] = WC()->checkout()->get_value( $key );
		}
		if ( isset( $field['required'] ) && ! is_bool( $field['required'] ) ) {
			$field['required'] = (bool) $field['required'];
		}
	} );

	/**
	 * @param array  $fields
	 * @param string $page
	 */
	return apply_filters( 'wc_braintree_get_checkout_fields', $fields, $page );
}