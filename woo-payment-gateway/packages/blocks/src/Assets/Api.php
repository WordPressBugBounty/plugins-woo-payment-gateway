<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Assets;

use PaymentPlugins\WooCommerce\Blocks\Braintree\Config;

/**
 * Class Api
 *
 * @package PaymentPlugins\WooCommerce\Blocks\Braintree\Assets
 */
class Api {

	private $config;

	private $registered_dependencies = false;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function version() {
		return $this->config->get_version();
	}

	public function register_dependencies() {
		$this->registered_dependencies = true;
		foreach ( $this->get_default_scripts() as $handle => $src ) {
			$src = sprintf( $src, $this->config->get_sdk_version() );
			wp_register_script( $handle, $src, [], $this->config->get_version(), null, true );
		}
		$this->register_script( 'wc-braintree-blocks-commons', 'build/commons.js', [], $this->config->get_version() );
		wp_enqueue_style( 'wc-braintree-blocks-style', $this->assets_url( 'build/style.css' ), [], $this->config->get_version() );
		wp_style_add_data( 'wc-braintree-blocks-style', 'rtl', 'replace' );
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
		];
	}

	public function assets_url( $relative_path = '' ) {
		$url = $this->config->get_url();
		preg_match( '/^(\.{2}\/)+/', $relative_path, $matches );
		if ( $matches ) {
			foreach ( range( 0, substr_count( $matches[0], '../' ) - 1 ) as $idx ) {
				$url = dirname( $url );
			}
			$relative_path = '/' . substr( $relative_path, strlen( $matches[0] ) );
		}

		return $url . $relative_path;
	}

	public function register_script( $handle, $relative_path, $dependencies = [], $version = false, $footer = true ) {
		$file         = str_replace( 'js', 'asset.php', $relative_path );
		$file         = $this->config->get_path() . $file;
		$dependencies = array_merge( [ 'wc-braintree-blocks-commons' ], $dependencies );
		if ( file_exists( $file ) ) {
			$assets_php   = require $file;
			$dependencies = array_merge( $assets_php['dependencies'], $dependencies );
			$version      = $version === false ? $assets_php['version'] : $version;
		}

		// remove dependency duplicates where handle matches entry in $dependencies
		$dependencies = array_diff( $dependencies, [ $handle ] );

		do_action( 'wc_braintree_blocks_register_script_dependencies', $handle );

		if ( ! $this->registered_dependencies ) {
			$this->register_dependencies();
		}

		wp_register_script( $handle, $this->assets_url( $relative_path ), $dependencies, $version, $footer );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'woo-payment-gateway' );
		}
	}

	public function register_external_script( $handle, $src, $version = false ) {
		$version = ! $version ? $this->config->get_sdk_version() : $version;
	}

}