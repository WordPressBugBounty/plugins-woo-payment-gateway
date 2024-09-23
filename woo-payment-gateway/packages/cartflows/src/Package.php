<?php


namespace PaymentPlugins\CartFlows\Braintree;

/**
 * Class Package
 * @package PaymentPlugins\CartFlows\Braintree
 */
class Package {

	public static function init() {
		add_action( 'plugins_loaded', array( self::instance(), 'initialize' ) );
	}

	private static function instance() {
		static $instance;
		if ( $instance ) {
			return $instance;
		}

		return new self();
	}

	private function is_enabled() {
		return defined( 'CARTFLOWS_PRO_FILE' );
	}

	public function initialize() {
		if ( $this->is_enabled() ) {
			new Payments\Api( braintree()->js_sdk_version );
			new AjaxRequestHandler();
		}
	}

}