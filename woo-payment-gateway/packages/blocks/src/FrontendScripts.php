<?php

namespace PaymentPlugins\WooCommerce\Blocks\Braintree;

use PaymentPlugins\WooCommerce\Blocks\Braintree\Assets\Api as AssetsApi;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Api as PaymentsApi;

class FrontendScripts {

	private $assets;

	private $config;

	public function __construct( AssetsApi $assets, Config $config ) {
		$this->assets = $assets;
		$this->config = $config;
	}

	public function initialize() {
		add_action( 'init', [ $this, 'register_scripts' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'enqueue_checkout_scripts' ] );
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before', [ $this, 'enqueue_cart_scripts' ] );
		//add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_admin_scripts' ] );
	}

	public function register_scripts() {
		foreach ( $this->get_default_scripts() as $handle => $src ) {
			$src = sprintf( $src, $this->config->get_sdk_version() );
			wp_register_script( $handle, $src, [], $this->config->get_version(), null, true );
		}

		$this->assets->register_script( 'wc-braintree-blocks-commons', 'build/commons.js', [], $this->config->get_version() );
		$this->assets->register_script( 'wc-braintree-block-data', 'build/block-data.js' );
		$this->assets->register_script( 'wc-braintree-blocks-fastlane-checkout', 'build/wc-braintree-fastlane-block.js', [ 'wc-braintree-blocks-commons' ] );

		wp_register_style( 'wc-braintree-blocks-style', $this->assets->assets_url( 'build/style.css' ), [], $this->config->get_version() );
		wp_style_add_data( 'wc-braintree-blocks-style', 'rtl', 'replace' );
	}

	public function enqueue_cart_scripts() {
		wp_enqueue_style( 'wc-braintree-blocks-style' );
	}

	public function enqueue_checkout_scripts() {
		wp_enqueue_style( 'wc-braintree-blocks-style' );

		$payments_api = Package::container()->get( PaymentsApi::class );

		foreach ( $payments_api->get_payment_methods() as $payment_method ) {
			if ( method_exists( $payment_method, 'enqueue_checkout_scripts' ) ) {
				$payment_method->enqueue_checkout_scripts();
			}
		}
	}

	private function get_default_scripts() {
		return [
			'braintree-web-hosted-fields'   => 'https://js.braintreegateway.com/web/%1$s/js/hosted-fields.min.js',
			'braintree-web-dropin'          => 'https://js.braintreegateway.com/web/dropin/1.38.1/js/dropin.min.js',
			'braintree-web-client'          => 'https://js.braintreegateway.com/web/%1$s/js/client.min.js',
			'braintree-web-data-collector'  => 'https://js.braintreegateway.com/web/%1$s/js/data-collector.min.js',
			'braintree-web-three-d-secure'  => 'https://js.braintreegateway.com/web/%1$s/js/three-d-secure.min.js',
			'braintree-web-paypal-checkout' => 'https://js.braintreegateway.com/web/%1$s/js/paypal-checkout.js',
			'braintree-web-google-payment'  => 'https://js.braintreegateway.com/web//%1$s/js/google-payment.min.js',
			'braintree-web-gpay'            => 'https://pay.google.com/gp/p/js/pay.js',
			'braintree-web-apple-pay'       => 'https://js.braintreegateway.com/web/%1$s/js/apple-pay.min.js',
			'braintree-web-venmo'           => 'https://js.braintreegateway.com/web/%1$s/js/venmo.min.js',
			'braintree-web-local-payment'   => 'https://js.braintreegateway.com/web/%1$s/js/local-payment.min.js',
			'braintree-web-fastlane'        => 'https://js.braintreegateway.com/web/%1$s/js/fastlane.min.js'
		];
	}

	public function enqueue_admin_scripts() {
		if ( ! is_admin() ) {
			return;
		}
		$id = get_queried_object_id();
		if ( \is_int( $id ) && class_exists( '\WC_Blocks_Utils' ) ) {
			if ( \WC_Blocks_Utils::has_block_in_page( $id, 'woocommerce/checkout' ) ) {
				$payments_api = Package::container()->get( PaymentsApi::class );
				foreach ( $payments_api->get_payment_methods() as $payment_method ) {
					if ( method_exists( $payment_method, 'enqueue_checkout_scripts' ) ) {
						$payment_method->enqueue_checkout_scripts();
					}
				}
				wp_enqueue_style( 'wc-braintree-blocks-style' );
			}
		}
	}

}