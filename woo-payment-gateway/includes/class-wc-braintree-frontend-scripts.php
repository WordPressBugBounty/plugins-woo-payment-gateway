<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Frontend_Scripts {

	public $braintree_version;

	private $registered_scripts = array();

	private $registered_styles = array();

	private $enqueued_scripts = array();

	private $enqueued_styles = array();

	private $localized_scripts = array();

	private $update_payment_method_request;

	private $client_manager_enqueued = false;

	private $scripts_registered;

	public $assets_api;

	public $global_scripts = array(
		'hosted-fields-v3'   => 'https://js.braintreegateway.com/web/%1$s/js/hosted-fields.min.js',
		'dropin-v3-ext'      => 'https://js.braintreegateway.com/web/dropin/1.43.0/js/dropin.min.js',
		'client-v3'          => 'https://js.braintreegateway.com/web/%1$s/js/client.min.js',
		'data-collector-v3'  => 'https://js.braintreegateway.com/web/%1$s/js/data-collector.min.js',
		'3ds-v3'             => 'https://js.braintreegateway.com/web/%1$s/js/three-d-secure.min.js',
		'paypal-v3'          => 'https://js.braintreegateway.com/web/%1$s/js/paypal.min.js',
		'paypal-checkout-v3' => 'https://js.braintreegateway.com/web/%1$s/js/paypal-checkout.js',
		'googlepay-v3'       => 'https://js.braintreegateway.com/web//%1$s/js/google-payment.min.js',
		'googlepay-pay'      => 'https://pay.google.com/gp/p/js/pay.js',
		'applepay-v3'        => 'https://js.braintreegateway.com/web/%1$s/js/apple-pay.min.js',
		'venmo-v3'           => 'https://js.braintreegateway.com/web/%1$s/js/venmo.min.js',
		'local-payment-v3'   => 'https://js.braintreegateway.com/web/%1$s/js/local-payment.min.js',
	);

	public $prefix = 'wc-braintree-';

	/**
	 * @var object
	 * @since 3.2.4
	 */
	public $client_token;

	private $encoded_client_token;

	public function __construct( $version, \PaymentPlugins\Braintree\Assets\AssetsApi $assets_api ) {
		$this->braintree_version = $version;
		$this->assets_api        = $assets_api;
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( $this, 'print_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ), 5 );
		add_action( 'woocommerce_subscriptions_pre_update_payment_method', array( $this, 'pre_update_payment_method' ) );

		$this->initialize();
	}

	private function initialize() {
		if ( did_action( 'init' ) || doing_action( 'init' ) ) {
			$this->register_scripts();
		}
	}

	public function enqueue_scripts() {
		//$this->register_scripts();
	}

	public function register_scripts() {
		$this->braintree_version = apply_filters( 'wc_braintree_global_script_version', $this->braintree_version );

		foreach ( $this->global_scripts as $key => $src ) {
			$this->register_script( $key, sprintf( $src, $this->braintree_version ), array(), null );
		}
		$js_path = braintree()->assets_path() . 'js/';
		$min     = $this->get_min();

		$this->register_style( 'styles', $this->assets_url( 'css/braintree' . $this->get_min() . '.css' ) );

		$this->register_script( 'payment-methods', $this->assets_url( 'js/frontend/payment-methods' . $this->get_min() . '.js' ), array(
			'jquery'
		) );
		$this->register_script( 'message-handler', $this->assets_url( 'js/frontend/message-handler' . $this->get_min() . '.js' ),
			array(
				'jquery',
				'woocommerce',
			)
		);
		$this->register_script( 'form-handler', $this->assets_url( 'js/frontend/form-handler' . $this->get_min() . '.js' ), array( 'jquery' ) );
		$this->register_script( 'payment-method-icons', $this->assets_url( 'js/frontend/payment-method-icons.js' ), array( 'jquery' ) );
		$this->register_script( 'global', $this->assets_url( 'js/frontend/wc-braintree' . $this->get_min() . '.js' ), array( 'jquery' ) );

		$this->register_script(
			'client-manager',
			$js_path . 'frontend/client-manager' . $min . '.js',
			array(
				'jquery',
				$this->get_handle( 'client-v3' ),
				$this->get_handle( 'message-handler' ),
				$this->get_handle( 'payment-methods' ),
				$this->get_handle( 'form-handler' ),
				$this->get_handle( 'global' )
			)
		);

		$this->register_script( 'change-payment-method', $this->assets_url( 'js/frontend/change-payment-method' . $this->get_min() . '.js' ), array( 'jquery' ) );

		$this->assets_api->register_script( 'wc-braintree-vendors', 'assets/build/vendors.js' );

		$this->assets_api->register_script( 'wc-braintree-paypal-checkout', 'assets/build/paypal-checkout.js', array(
			'wc-braintree-vendors',
			$this->get_handle( 'client-manager' ),
			$this->get_handle( 'data-collector-v3' ),
			$this->get_handle( 'paypal-checkout-v3' )
		) );

		$this->scripts_registered = true;
	}

	public function assets_url( $uri = '' ) {
		return untrailingslashit( braintree()->assets_path() . $uri );
	}

	public function get_min() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	public function register_script( $handle, $src, $deps = array(), $version = false, $footer = true ) {
		$this->registered_scripts[] = $this->get_handle( $handle );
		$version                    = false === $version ? braintree()->version : $version;
		wp_register_script( $this->get_handle( $handle ), $src, $deps, $version, $footer );
	}

	public function register_style( $handle, $src, $deps = array() ) {
		$handle                    = $this->get_handle( $handle );
		$this->registered_styles[] = $handle;
		wp_register_style( $handle, $src, $deps, braintree()->version );
	}

	public function enqueue_style( $handle, $src = '', $deps = array() ) {
		$handle = $this->get_handle( $handle );
		if ( ! in_array( $handle, $this->registered_styles ) ) {
			$this->register_style( $handle, $src, $deps );
		}
		wp_enqueue_style( $handle, $src, $deps, braintree()->version );
	}

	/**
	 * @param string $handle
	 * @param string $src
	 * @param array  $deps
	 * @param bool   $version
	 * @param bool   $footer
	 */
	public function enqueue_script( $handle, $src = '', $deps = array(), $version = false, $footer = true ) {
		$handle = $this->get_handle( $handle );
		if ( ! in_array( $handle, $this->registered_scripts ) ) {
			$this->register_script( $handle, $src, $deps, $version, $footer );
		}
		$this->enqueued_scripts[] = $handle;
		wp_enqueue_script( $handle );
	}

	public function localize_script( $handle, $data ) {
		$handle = $this->get_handle( $handle );
		if ( wp_script_is( $handle, 'registered' ) ) {
			$name = str_replace( $this->prefix, '', $handle );
			$data = apply_filters( 'wc_braintree_localize_script_' . $name, $data, $name );
			if ( $data ) {
				$this->localized_scripts[] = $handle;
				$object_name               = str_replace( '-', '_', $handle ) . '_params';
				wp_localize_script( $handle, $object_name, $data );
			}
		}
	}

	/**
	 * Localize scripts registered by this plugin.
	 */
	public function print_scripts() {
		if ( ! $this->scripts_registered ) {
			$this->register_scripts();
		}
		if ( ( is_checkout() && ! is_order_received_page() ) || is_add_payment_method_page() || is_product() || is_cart() || is_account_page()
		     || apply_filters( 'wc_braintree_print_scripts', false )
		) {
			// check if page contains short code used by elementor
			$this->localize_frontend();
		}
		if ( is_add_payment_method_page() ) {
			$this->enqueue_script( 'payment-method-icons' );
			$this->localize_script( 'payment-method-icons', $this->localize_payment_method_icons() );
		}
		if ( ! is_checkout() && ! is_cart() ) {
			$this->enqueue_mini_cart();
		}
		if ( wc_braintree_subscriptions_active() && is_account_page() ) {
			global $wp;
			if ( ! empty( $wp->query_vars['view-subscription'] ) ) {
				$this->enqueue_script( 'view-subscription', $this->assets_url( 'js/frontend/view-subscription.js' ), array(
					'jquery',
					'jquery-ui-dialog'
				), braintree()->version, true );
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
				$this->localize_script( 'view-subscription', array() );
			}
			if ( wcs_braintree_is_change_payment_method_request() ) {
				$this->enqueue_script(
					'change-payment-methods',
					$this->assets_url( 'js/frontend/change-payment-method' . $this->get_min() . '.js' ),
					array(
						'jquery',
					)
				);
			}
		}
	}

	/**
	 * @since 3.2.8
	 */
	public function print_footer_scripts() {
		/**
		 * When things like coupons make the order total $0, then WC won't call $gateway->payment_fields()
		 * which means scripts won't be enqueued. This code checks to see if any gateways haven't been enqueued
		 * on the checkout page.
		 */
		global $wp;
		if ( is_checkout() && ! isset( $wp->query_vars['order_pay'] ) && ! is_order_received_page() ) {
			$gateways = array_filter( WC()->payment_gateways()->payment_gateways(), function ( $gateway ) {
				if ( $gateway instanceof WC_Braintree_Payment_Gateway ) {
					$is_available = function () {
						return parent::is_available();
					};
					$is_available = $is_available->bindTo( $gateway, 'WC_Braintree_Payment_Gateway' );

					return $is_available() && ! $gateway->has_enqueued_scripts( $this, 'checkout' );
				}

				return false;
			} );
			foreach ( $gateways as $gateway ) {
				/**
				 * @var WC_Braintree_Payment_Gateway $gateway
				 */
				$gateway->enqueue_checkout_scripts( $this );
				$gateway->output_display_items( 'checkout' );
			}
		}
		/**
		 * If the client manager is enqueued but it hasn't been localized then we know some
		 * functionality is including the gateways and so we need to localize here.
		 */
		if ( wp_script_is( $this->get_handle( 'client-manager' ) ) && ! in_array( $this->get_handle( 'client-manager' ), $this->localized_scripts ) ) {
			$this->localize_frontend();
		}
	}

	public function get_handle( $handle ) {
		return strpos( $handle, $this->prefix ) === false ? $this->prefix . $handle : $handle;
	}

	public function localize_payment_methods() {
		$data['cards']      = array_keys( wc_braintree_get_card_type_icons() );
		$data['no_results'] = __( 'Not matches found', 'woo-payment-gateway' );

		return $data;
	}

	public function localize_message_handler() {
		$data['messages'] = wc_braintree_get_error_messages();

		return $data;
	}

	public function localize_payment_method_icons() {
		$data   = array(
			'tokens' => array(),
			'icons'  => wc_braintree_get_card_type_icons(),
		);
		$tokens = wc_get_customer_saved_methods_list( get_current_user_id() );
		$index  = 0;
		foreach ( $tokens as $type => $methods ) {
			foreach ( $methods as $method ) {
				if ( isset( $method['wc_braintree_method'] ) ) {
					$data['tokens'][] = array(
						'index'     => $index,
						'card_type' => $method['method_type'],
					);
				}
				$index ++;
			}
		}

		return $data;
	}

	public function localize_client_manager() {
		$currency = wc_braintree_get_currency();

		return array(
			'url'              => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->client_token->rest_uri() . '/create' ),
			'_wpnonce'         => wp_create_nonce( 'wp_rest' ),
			'page_id'          => $this->get_page_id(),
			'currency'         => $currency,
			'merchant_account' => wc_braintree_get_merchant_account( $currency ),
			'version'          => braintree()->version
		);
	}

	public function pre_update_payment_method() {
		$this->update_payment_method_request = true;
	}

	private function get_page_id() {
		global $wp;
		if ( is_product() ) {
			return 'product';
		}
		if ( is_cart() ) {
			return 'cart';
		}
		if ( is_checkout() ) {
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				return 'order_pay';
			}

			return 'checkout';
		}
		if ( is_add_payment_method_page() ) {
			return 'add_payment_method';
		}
		if ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() ) {
			return 'change_payment_method';
		}

		return '';
	}

	/**
	 * @since 3.2.4
	 * @return array
	 */
	public function get_enqueued_scripts() {
		return $this->enqueued_scripts;
	}

	private function localize_frontend() {
		$this->enqueue_style( 'styles' );
		wp_style_add_data( $this->get_handle( 'styles' ), 'rtl', 'replace' );

		$this->localize_script( 'global', array( 'page' => $this->get_page_id() ) );

		wp_localize_script( $this->get_handle( 'global' ), 'wc_braintree_checkout_fields', wc_braintree_get_checkout_fields( $this->get_page_id() ) );

		$this->localize_script( 'client-manager', $this->localize_client_manager() );

		$this->localize_script( 'message-handler', $this->localize_message_handler() );

		$this->localize_script( 'payment-methods', $this->localize_payment_methods() );

		$this->generate_client_token();

		wp_localize_script( $this->get_handle( 'client-manager' ), 'wc_braintree_client_token', empty( $this->encoded_client_token ) ? array() : (array) $this->encoded_client_token );
	}

	public function generate_client_token() {
		if ( ! $this->client_token ) {
			$this->encoded_client_token = wc_braintree_generate_client_token();
			$this->client_token         = json_decode( base64_decode( $this->encoded_client_token ) );
		}
	}

	private function enqueue_mini_cart() {
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
			/**
			 * @var WC_Braintree_Payment_Gateway $gateway
			 */
			if ( $gateway instanceof WC_Braintree_Payment_Gateway && $gateway->supports( 'wc_braintree_mini_cart' ) && $gateway->mini_cart_enabled() ) {
				if ( ! wp_style_is( $this->get_handle( 'styles' ) ) ) {
					$this->localize_frontend();
				}
				$gateway->enqueue_mini_cart_scripts( $this );
			}
		}
	}

}
