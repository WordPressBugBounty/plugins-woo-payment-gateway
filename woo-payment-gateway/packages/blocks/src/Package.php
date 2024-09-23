<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree;


class Package {

	public static function init() {
		add_action( 'woocommerce_blocks_loaded', [ __CLASS__, 'initialize' ] );
	}

	public static function initialize() {
		if ( self::enabled() ) {
			self::container()->get( Config::class );
		}
	}

	/**
	 * Return true of WooCommerce Blocks is enabled as a feature plugin.
	 *
	 * @return bool
	 */
	private static function enabled() {
		if ( \class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
			if ( self::is_core_plugin_build() ) {
				return true;
			}
			if ( \method_exists( '\Automattic\WooCommerce\Blocks\Package', 'feature' ) ) {
				$feature = \Automattic\WooCommerce\Blocks\Package::feature();
				if ( \method_exists( $feature, 'is_feature_plugin_build' ) ) {
					return $feature->is_feature_plugin_build();
				}
			}
		}

		return false;
	}

	private static function is_core_plugin_build() {
		return \function_exists( 'WC' ) && \version_compare( '6.9.0', WC()->version, '<=' );
	}

	public static function container() {
		static $container;
		if ( ! $container ) {
			$container = \Automattic\WooCommerce\Blocks\Package::container();
			$container->register( Config::class, function ( $container ) {
				return new Config( $container, braintree()->js_sdk_version, braintree()->version, dirname( __FILE__ ) );
			} );
		}

		return $container;
	}

}