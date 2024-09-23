<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use \PaymentPlugins\WooCommerce\Blocks\Braintree\Assets\Api as AssetsApi;
use PaymentPlugins\WooCommerce\Blocks\Braintree\BraintreeClient;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Package;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\ApplePayGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\CreditCardGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\GooglePayGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\PayPalGateway;
use PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways\VenmoGateway;

/**
 * Class Api
 *
 * @package PaymentPlugins\WooCommerce\Blocks\Braintree\Payments
 */
class Api {

	private $assets_api;

	private $data_registry;

	private $client;

	private $payment_gateways = [];

	public function __construct( AssetsApi $assets_api, AssetDataRegistry $data_registry, BraintreeClient $client ) {
		$this->assets_api    = $assets_api;
		$this->data_registry = $data_registry;
		$this->client        = $client;
		$this->initialize();
	}

	public function initialize() {
		add_action( 'woocommerce_blocks_payment_method_type_registration', [ $this, 'register_payment_methods' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'unregister_scripts' ] );
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before', [ $this, 'unregister_scripts' ] );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', [ $this, 'add_checkout_data' ] );
		add_action( 'woocommerce_blocks_cart_enqueue_data', [ $this, 'add_cart_data' ] );
		add_filter( 'woocommerce_saved_payment_methods_list', [ $this, 'transform_payment_method_type' ], 100 );
	}

	public function unregister_scripts() {
		wp_deregister_script( 'wc-braintree-global' );
	}

	public function register_payment_methods( PaymentMethodRegistry $registry ) {
		$container = Package::container();
		$this->register( $registry, $container->get( CreditCardGateway::class ) );
		$this->register( $registry, $container->get( GooglePayGateway::class ) );
		$this->register( $registry, $container->get( PayPalGateway::class ) );
		$this->register( $registry, $container->get( ApplePayGateway::class ) );
		$this->register( $registry, $container->get( VenmoGateway::class ) );
	}

	private function register( $registry, $instance ) {
		$this->payment_gateways[] = $instance;
		$registry->register( $instance );
	}

	public function add_checkout_data() {
		$this->add_payment_data( 'checkout' );
	}

	public function add_cart_data() {
		$this->add_payment_data( 'cart' );
	}

	protected function add_payment_data( $context ) {
		if ( ! $this->data_registry->exists( 'wcBraintreeData' ) ) {
			$data = [
				'page'             => $context,
				'merchantAccounts' => wc_braintree_get_merchant_accounts(),
				'hasSubscription'  => wcs_braintree_active() && \WC_Subscriptions_Cart::cart_contains_subscription(),
				'blocksVersion'    => \Automattic\WooCommerce\Blocks\Package::get_version()
			];
			try {
				$args = [];
				if ( ( $merchant_account_id = wc_braintree_get_merchant_account() ) ) {
					$args['merchantAccountId'] = $merchant_account_id;
				}
				$client_token        = $this->client->gateway()->clientToken()->generate( $args );
				$data['clientToken'] = $client_token;
				$this->data_registry->add( 'wcBraintreeData', $data );
			} catch ( \Braintree\Exception $e ) {
			}
		}
		if ( ! $this->data_registry->exists( 'wcBraintreeMessages' ) ) {
			$this->data_registry->add( 'wcBraintreeMessages', wc_braintree_get_error_messages() );
		}
	}

	public function transform_payment_method_type( $list ) {
		foreach ( $this->payment_gateways as $payment_method ) {
			if ( isset( $list[ $payment_method->get_name() ] ) ) {
				if ( isset( $list['cc'] ) ) {
					foreach ( $list[ $payment_method->get_name() ] as $entry ) {
						$list['cc'][] = $entry;
					}
				} else {
					$list['cc'] = $list[ $payment_method->get_name() ];
				}
				unset( $list[ $payment_method->get_name() ] );
			}
		}

		return $list;
	}

}