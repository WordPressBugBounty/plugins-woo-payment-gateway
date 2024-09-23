<?php


namespace PaymentPlugins\Braintree\WooFunnels;

use PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Api as PaymentsApi;

class Package {

	public static function init() {
		add_action( 'plugins_loaded', [ self::instance(), 'initialize' ] );
	}

	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function initialize() {
		if ( $this->is_enabled() ) {
			new PaymentsApi(
				braintree()->version,
				braintree()->js_sdk_version,
				new AssetsApi( dirname( __FILE__ ), braintree()->version )
			);
		}
	}

	private function is_enabled() {
		return function_exists( 'WFOCU_Core' );
	}

}