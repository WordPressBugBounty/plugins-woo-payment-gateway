<?php


namespace PaymentPlugins\Braintree\WooFunnels\Upsell\Payments;


use PaymentPlugins\Braintree\WooFunnels\AssetsApi;
use PaymentPlugins\WC_Braintree_Constants;

class Api {

	private $plugin_version;

	private $js_sdk_version;

	private $assets;

	public function __construct( $plugin_version, $js_sdk_version, AssetsApi $assets ) {
		$this->js_sdk_version = $js_sdk_version;
		$this->assets         = $assets;
		add_action( 'init', [ $this, 'load_gateways' ] );
		add_filter( 'wfocu_wc_get_supported_gateways', [ $this, 'get_supported_gateways' ] );
		add_filter( 'wfocu_subscriptions_get_supported_gateways', [ $this, 'add_supported_subscription_gateways' ] );
		add_filter( 'wc_braintree_order_transaction_args', [ $this, 'maybe_force_tokenization' ], 10, 3 );
		add_action( 'wfocu_footer_before_print_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'wfocu_localized_data', [ $this, 'localize_data' ] );
		add_action( 'wfocu_subscription_created_for_upsell', [ $this, 'update_subscription_meta' ], 10, 3 );
		add_filter( 'wc_braintree_get_paypal_flow', [ $this, 'get_paypal_flow' ] );
	}

	public function load_gateways() {
		foreach ( $this->get_payment_gateways() as $clazz ) {
			call_user_func( [ $clazz, 'get_instance' ] );
		}
	}

	public function get_supported_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_payment_gateways() );
	}

	public function add_supported_subscription_gateways( $gateways ) {
		return array_merge( $gateways, array_keys( $this->get_payment_gateways() ) );
	}

	private function get_payment_gateways() {
		return [
			'braintree_cc'        => 'PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\CreditCardGateway',
			'braintree_paypal'    => 'PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\PayPalGateway',
			'braintree_googlepay' => 'PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\GooglePayGateway',
			'braintree_applepay'  => 'PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\ApplePayGateway',
			'braintree_venmo'     => 'PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\VenmoGateway'
		];
	}

	private function is_braintree_payment_method( $payment_method ) {
		return in_array( $payment_method, array_keys( $this->get_payment_gateways() ) );
	}

	/**
	 * @param array     $args
	 * @param \WC_Order $order
	 * @param string    $gateway_id
	 */
	public function maybe_force_tokenization( array $args, \WC_Order $order, string $gateway_id ) {
		/**
		 * @var \PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\BasePaymentGateway $payment_gateway
		 */
		$payment_gateway = $this->get_payment_gateway( $order->get_payment_method() );

		if ( empty( $args['paymentMethodToken'] ) && $payment_gateway->should_tokenize() && $payment_gateway->should_store_in_vault() ) {
			$args['options']['storeInVaultOnSuccess'] = true;
		}

		return $args;
	}

	private function get_payment_gateway( $id ) {
		return WFOCU_Core()->gateways->get_integration( $id );
	}

	public function enqueue_scripts() {
		if ( ! \WFOCU_Core()->public->if_is_offer() || WFOCU_Core()->public->if_is_preview() ) {
			return true;
		}
		$order = WFOCU_Core()->data->get_current_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$payment_method = $order->get_payment_method();

		if ( $this->is_braintree_payment_method( $payment_method ) ) {
			$payment_gateway = $this->get_payment_gateway( $payment_method );
			/**
			 * @var \PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\BasePaymentGateway $payment_gateway
			 */
			if ( $payment_method === 'braintree_cc' && $payment_gateway->is_vaulted_threed_secure_enabled() ) {
				$this->assets->register_script( 'wc-braintree-woofunnels-upsell', 'build/wc-braintree-woofunnels-upsell.js' );
				wp_register_script( 'braintree-web-client', "https://js.braintreegateway.com/web/{$this->js_sdk_version}/js/client.min.js" );
				wp_register_script( 'braintree-web-three-d-secure', "https://js.braintreegateway.com/web/{$this->js_sdk_version}/js/three-d-secure.min.js" );
				wp_register_script( 'braintree-web-data-collector', "https://js.braintreegateway.com/web/{$this->js_sdk_version}/js/data-collector.min.js" );
				$this->assets->do_script_items( [ 'wc-braintree-woofunnels-upsell' ] );
			}
		}
	}

	/**
	 * @param array $data
	 */
	public function localize_data( $data ) {
		if ( ! is_array( $data ) || isset( $data['is_preview'] ) && $data['is_preview'] == true ) {
			return $data;
		}
		$order = WFOCU_Core()->data->get_current_order();
		if ( ! $order || ! $order->get_id() ) {
			return $data;
		}
		if ( $this->is_braintree_payment_method( $order->get_payment_method() ) ) {
			/**
			 * @var \PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways\BasePaymentGateway $payment_gateway
			 */
			$payment_gateway      = $this->get_payment_gateway( $order->get_payment_method() );
			$payment_method_token = $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN );
			if ( $payment_method_token && $payment_gateway->is_vaulted_threed_secure_enabled() ) {
				// create a nonce from the token
				/**
				 * @var \Braintree\PaymentMethodNonce $nonce
				 */
				$nonce = $payment_gateway->create_payment_method_nonce( $payment_method_token );
				if ( ! is_wp_error( $nonce ) ) {
					$data['wcBraintree']                = [
						'vaultedNonce'       => $nonce,
						'paymentMethod'      => $payment_gateway->get_key(),
						'paymentMethodToken' => $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN ),
						'threeDSecureData'   => [
							'email'                 => $order->get_billing_email(),
							'billingAddress'        => array(
								'givenName'         => $order->get_billing_first_name(),
								'surname'           => $order->get_billing_last_name(),
								'phoneNumber'       => $order->get_billing_phone(),
								'streetAddress'     => $order->get_billing_address_1(),
								'extendedAddress'   => $order->get_billing_address_2(),
								'locality'          => $order->get_billing_city(),
								'region'            => $order->get_billing_state(),
								'postalCode'        => $order->get_billing_postcode(),
								'countryCodeAlpha2' => $order->get_billing_country()
							),
							'additionalInformation' => $order->get_shipping_address_1() ? [
								'shippingGivenName' => $order->get_shipping_first_name(),
								'shippingSurname'   => $order->get_shipping_last_name(),
								'shippingAddress'   => [
									'streetAddress'     => $order->get_shipping_address_1(),
									'extendedAddress'   => $order->get_shipping_address_2(),
									'locality'          => $order->get_shipping_city(),
									'region'            => $order->get_shipping_state(),
									'postalCode'        => $order->get_shipping_postcode(),
									'countryCodeAlpha2' => $order->get_shipping_country()
								]
							] : []
						]
					];
					$data['wcBraintree']['clientToken'] = $payment_gateway->create_client_token( $order );
				}
			}
		}

		return $data;
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param string           $product_hash
	 * @param \WC_Order        $order
	 *
	 * @return void
	 */
	public function update_subscription_meta( $subscription, $product_hash, $order ) {
		if ( $this->is_braintree_payment_method( $subscription->get_payment_method() ) ) {
			$subscription->update_meta_data( WC_Braintree_Constants::PAYMENT_METHOD_TOKEN, $order->get_meta( WC_Braintree_Constants::PAYMENT_METHOD_TOKEN ) );
			$subscription->save();
		}
	}

	public function get_paypal_flow( $type ) {
		if ( $type !== \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT ) {
			if ( $this->get_payment_gateway( 'braintree_paypal' )->should_tokenize() ) {
				$type = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
			}
		}

		return $type;
	}

}