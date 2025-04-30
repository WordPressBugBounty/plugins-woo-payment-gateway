<?php

namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;

class FastlaneGateway extends AbstractGateway {

	public $name = 'braintree_fastlane';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-braintree-blocks-fastlane', 'build/wc-braintree-fastlane.js' );

		return [ 'wc-braintree-blocks-fastlane' ];
	}

	public function initialize() {
		$this->settings = get_option( "woocommerce_braintree_cc_settings", [] );
	}

	public function is_active() {
		return \wc_string_to_bool( $this->get_setting( 'enabled' ) )
		       && \wc_string_to_bool( $this->get_setting( 'fastlane_enabled', 'no' ) )
		       && $this->get_setting( 'fastlane_flow' ) === 'express_button';;
	}

	public function get_payment_method_data() {
		return [
			'icon_url'          => braintree()->assets_path() . 'img/fastlane.svg',
			'i18n'              => [
				'cancel'        => __( 'Cancel', 'woo-payment-gateway' ),
				'change'        => __( 'Change', 'woo-payment-gateway' ),
				'continue'      => __( 'Continue', 'woo-payment-gateway' ),
				'email_empty'   => __( 'Please provide an email address before using Fastlane.', 'woo-payment-gateway' ),
				'email_invalid' => __( 'Please enter a valid email address before using Fastlane.', 'woo-payment-gateway' )
			],
			'fraud'             => [
				'enabled' => braintree()->fraud_settings->is_active( 'enabled' )
			],
			'fastlane_flow'     => $this->get_setting( 'fastlane_flow', 'express_button' ),
			'fastlane_pageload' => \wc_string_to_bool( $this->get_setting( 'fastlane_pageload', 'no' ) ),
		];
	}

	public function enqueue_checkout_scripts() {
		if ( \wc_string_to_bool( $this->get_setting( 'enabled' ) ) ) {
			if ( \wc_string_to_bool( $this->get_setting( 'fastlane_enabled', 'no' ) ) ) {
				if ( $this->get_setting( 'fastlane_flow' ) === 'email_detection' ) {
					wp_enqueue_script( 'wc-braintree-blocks-fastlane-checkout' );
				}
			}
		}
	}

	public function get_schema_extended_data( $data ) {
		$data['fastlane'] = $this->get_payment_method_data();

		return $data;
	}

}