<?php


namespace PaymentPlugins\CartFlows\Braintree\Payments;


class Api {

	private $js_sdk_version;

	public function __construct( $js_sdk_version ) {
		$this->js_sdk_version = $js_sdk_version;
		add_filter( 'cartflows_offer_supported_payment_gateways', array( $this, 'add_payment_gateways' ) );
		add_filter( 'wc_braintree_order_transaction_args', array( $this, 'maybe_force_save_payment_method' ), 10, 3 );
		add_filter( 'cartflows_offer_js_localize', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * @param array $supported_gateways
	 *
	 * @return mixed
	 */
	public function add_payment_gateways( $supported_gateways ) {
		foreach ( $this->get_payment_method_ids() as $id ) {
			if ( $id === 'braintree_cc' ) {
				$supported_gateways[ $id ] = array(
					'path'  => dirname( __FILE__ ) . '/CreditCardGateway.php',
					'class' => '\PaymentPlugins\CartFlows\Braintree\Payments\CreditCardGateway'
				);
			} elseif ( $id === 'braintree_googlepay' ) {
				$supported_gateways[ $id ] = array(
					'path'  => dirname( __FILE__ ) . '/GooglePayGateway.php',
					'class' => '\PaymentPlugins\CartFlows\Braintree\Payments\GooglePayGateway'
				);
			} elseif ( $id === 'braintree_paypal' ) {
				$supported_gateways[ $id ] = array(
					'path'  => dirname( __FILE__ ) . '/PayPalGateway.php',
					'class' => '\PaymentPlugins\CartFlows\Braintree\Payments\PayPalGateway'
				);
			} else {
				$supported_gateways[ $id ] = array(
					'path'  => dirname( __FILE__ ) . '/BasePaymentGateway.php',
					'class' => '\PaymentPlugins\CartFlows\Braintree\Payments\BasePaymentGateway'
				);
			}
		}

		return $supported_gateways;
	}

	/**
	 * @param array $args
	 * @param \WC_Order $order
	 * @param string $gateway_id
	 */
	public function maybe_force_save_payment_method( array $args, \WC_Order $order, string $gateway_id ) {
		$checkout_id = wcf()->utils->get_checkout_id_from_post_data();
		$flow_id     = wcf()->utils->get_flow_id_from_post_data();
		if ( $checkout_id && $flow_id ) {
			$wcf_step_obj      = wcf_pro_get_step( $checkout_id );
			$next_step_id      = $wcf_step_obj->get_next_step_id();
			$wcf_next_step_obj = wcf_pro_get_step( $next_step_id );
			if ( $next_step_id && $wcf_next_step_obj->is_offer_page() && empty( $args['paymentMethodToken'] ) ) {
				$payment_method = WC()->payment_gateways()->payment_gateways()[ $gateway_id ];
				if ( $gateway_id !== 'braintree_cc' || ( $gateway_id === 'braintree_cc' && ! $payment_method->use_3ds_vaulted_nonce() ) ) {
					$args['options']['storeInVaultOnSuccess'] = true;
				}
			}
		}

		return $args;
	}

	public function enqueue_scripts( $localize ) {
		if ( in_array( $localize['payment_method'], $this->get_payment_method_ids() ) ) {
			$order                   = wc_get_order( $localize['order_id'] );
			$localize['wcBraintree'] = array(
				'successMessage'   => __( 'Processing Order...', 'cartflows-pro' ),
				'threeDSFailedMsg' => __( '3DS Authentication failed.', 'woo-payment-gateway' ),
				'clientToken'      => $this->generate_client_token( wc_braintree_get_order_environment( $order ), $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::MERCHANT_ACCOUNT_ID ) ),
				'timeout'          => 3000,
				'ajax_url'         => \WC_AJAX::get_endpoint( 'wc_braintree_cartflows_nonce' ),
				'security'         => array(
					'storeVaultedNonce' => wp_create_nonce( 'wc-braintree-vaulted-nonce' )
				),
				'order'            => array(
					'total'       => $order->get_total(),
					'threeDSData' => array(
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
						'additionalInformation' => array(
							'shippingGivenName' => $order->get_shipping_first_name(),
							'shippingSurname'   => $order->get_shipping_last_name(),
							'shippingAddress'   => array(
								'streetAddress'     => $order->get_shipping_address_1(),
								'extendedAddress'   => $order->get_shipping_address_2(),
								'locality'          => $order->get_shipping_city(),
								'region'            => $order->get_shipping_state(),
								'postalCode'        => $order->get_shipping_postcode(),
								'countryCodeAlpha2' => $order->get_shipping_country()
							)
						)
					)
				)
			);
			// create the vaulted token nonce
			$assets_url = plugin_dir_url( dirname( __DIR__ ) ) . 'build/';
			$params     = require_once dirname( dirname( __DIR__ ) ) . '/build/wc-braintree-cartflows.asset.php';
			wp_register_script( 'braintree-web-client', "https://js.braintreegateway.com/web/{$this->js_sdk_version}/js/client.min.js" );
			wp_register_script( 'braintree-web-three-d-secure', "https://js.braintreegateway.com/web/{$this->js_sdk_version}/js/three-d-secure.min.js" );
			wp_enqueue_script( 'wc-braintree-cartflows', $assets_url . 'wc-braintree-cartflows.js', $params['dependencies'], braintree()->version, true );

		}

		return $localize;
	}

	private function get_payment_method_ids() {
		return array( 'braintree_cc', 'braintree_paypal', 'braintree_googlepay', 'braintree_applepay', 'braintree_venmo' );
	}

	private function generate_client_token( $environment, $merchant_id = '' ) {
		$client = new \Braintree\Gateway( wc_braintree_connection_settings( $environment ) );

		return $client->clientToken()->generate( array(
			'merchantAccountId' => $merchant_id
		) );
	}
}