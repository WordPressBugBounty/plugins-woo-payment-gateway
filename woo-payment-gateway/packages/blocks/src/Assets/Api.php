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

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function version() {
		return $this->config->get_version();
	}

	public function register_dependencies() {
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

		wp_register_script( $handle, $this->assets_url( $relative_path ), $dependencies, $version, $footer );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'woo-payment-gateway' );
		}
	}

	public function register_external_script( $handle, $src, $version = false ) {
		$version = ! $version ? $this->config->get_sdk_version() : $version;
	}

}