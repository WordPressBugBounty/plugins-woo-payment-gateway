<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree;


class BraintreeClient {

	private $environment;

	public function __construct( $environment = null ) {
		$this->environment = $environment;
	}

	/**
	 * @param string $environment
	 *
	 * @return \Braintree\Gateway
	 */
	public function gateway( $environment = '' ) {
		if ( ! $environment && $this->environment ) {
			$environment = $this->environment;
		} else {
			$environment = wc_braintree_environment();
		}

		return new \Braintree\Gateway( $this->get_settings( $environment ) );
	}

	private function get_settings( $environment ) {
		return wc_braintree_connection_settings( $environment );
	}
}