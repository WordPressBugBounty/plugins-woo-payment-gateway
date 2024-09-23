<?php


namespace PaymentPlugins\Braintree\WooFunnels\Upsell;


class Client {

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( ! $this->client ) {
			$this->initialize();
		}

		return $this->client->{$name}( ...$arguments );
	}

	private function initialize( $environment = '' ) {
		$this->client = new \Braintree\Gateway( wc_braintree_connection_settings( $environment ) );
	}

	/**
	 * @var \Braintree\Gateway
	 */
	private $client;

	public function connect( $environment = 'production' ) {
		$this->initialize( $environment );

		return $this->client;
	}
}