<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Assets\Api as AssetsApi;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Api as PaymentsApi;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\ApplePayGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\CreditCardGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\GooglePayGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\PayPalGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\VenmoGateway;

/**
 * Class Config
 * @package PaymentPlugins\WooCommerce\Blocks\Braintree
 */
class Config {

	private $container;

	private $version;

	private $base_path;

	private $base_url;

	private $sdk_version;

	/**
	 * Config constructor.
	 *
	 * @param \Automattic\WooCommerce\Blocks\Registry\Container $container
	 * @param string $js_sdk_version
	 * @param string $version
	 * @param string $path
	 */
	public function __construct( \Automattic\WooCommerce\Blocks\Registry\Container $container, string $js_sdk_version, string $version, string $path ) {
		$this->container   = $container;
		$this->sdk_version = $js_sdk_version;
		$this->version     = $version;
		$this->base_path   = dirname( $path );
		$this->base_url    = plugin_dir_url( $path );
		$this->register_dependencies();
		$this->initialize();
	}

	private function initialize() {
		$this->container->get( PaymentsApi::class );
	}

	public function get_version() {
		return $this->version;
	}

	public function get_path() {
		return $this->base_path . DIRECTORY_SEPARATOR;
	}

	public function get_url() {
		return $this->base_url;
	}

	/**
	 * Returns the current version of the Braintree JS SDK.
	 * @return string
	 */
	public function get_sdk_version() {
		return $this->sdk_version;
	}

	private function register_dependencies() {
		$this->register_payment_gateways();
		$this->container->register( BraintreeClient::class, function ( $container ) {
			return new BraintreeClient();
		} );
		$this->container->register( AssetsApi::class, function ( $container ) {
			return new AssetsApi( $this );
		} );
		$this->container->register( PaymentsApi::class, function ( $container ) {
			return new PaymentsApi(
				$container->get( AssetsApi::class ),
				$container->get( AssetDataRegistry::class ),
				$container->get( BraintreeClient::class ) );
		} );
	}

	private function register_payment_gateways() {
		$this->container->register( CreditCardGateway::class, function ( $container ) {
			return new CreditCardGateway( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( GooglePayGateway::class, function ( $container ) {
			return new GooglePayGateway( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( PayPalGateway::class, function ( $container ) {
			return new PayPalGateway( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( ApplePayGateway::class, function ( $container ) {
			return new ApplePayGateway( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( VenmoGateway::class, function ( $container ) {
			return new VenmoGateway( $container->get( AssetsApi::class ) );
		} );
	}
}