<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes
 *
 * @property WC_Braintree_Rest_Controller $checkout
 * @property WC_Braintree_Rest_Controller $cart
 * @property WC_Braintree_Rest_Controller $applepay
 * @property WC_Braintree_Rest_Controller $merchant_account
 * @property WC_Braintree_Rest_Controller $webhook
 * @property WC_Braintree_Rest_Controller $local_payment
 * @property WC_Braintree_Rest_Controller $data_migration
 * @property WC_Braintree_Rest_Controller $googlepay
 * @property WC_Braintree_Rest_Controller $settings;
 * @property WC_Braintree_Rest_Controller $paypal
 * @property WC_Braintree_Rest_Controller $product
 */
class WC_Braintree_Rest_API {

	/**
	 *
	 * @var array
	 */
	private $controllers = array();

	/**
	 * @var bool @since 3.2.8
	 */
	private $force_routes = false;

	public function __construct() {
		$this->include_classes();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public static function init() {
		add_action( 'wc_ajax_wc_braintree_frontend_request', array( __CLASS__, 'process_frontend_ajax' ) );
	}

	/**
	 *
	 * @param WC_Braintree_Rest_Controller $key
	 */
	public function __get( $key ) {
		$controller = isset( $this->controllers[ $key ] ) ? $this->controllers[ $key ] : '';
		if ( empty( $controller ) ) {
			wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s is an invalid controller name.', 'woo-payment-gateway' ), $key ), braintree()->version );
		}

		return $controller;
	}

	public function __set( $key, $value ) {
		$this->controllers[ $key ] = $value;
	}

	private function include_classes() {
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-rest-webhook-authentication.php';
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-braintree-rest-controller.php';
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-braintree-controller-frontend.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-3ds.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-order-actions.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-client-token.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-payment-tokens.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-plan.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-kount.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-webhook.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-checkout.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-cart.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-applepay.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-googlepay.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-merchant-account.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-local-payment.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-data-migration.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-paypal.php';
		include_once WC_BRAINTREE_PATH . 'includes/api/class-wc-braintree-controller-product.php';

		foreach ( $this->get_controllers() as $key => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$key} = new $class_name();
			}
		}
	}

	public function register_routes() {
		if ( self::is_rest_api_request() || $this->force_routes ) {
			foreach ( $this->controllers as $key => $controller ) {
				if ( is_callable( array( $controller, 'register_routes' ) ) ) {
					$controller->register_routes();
				}
			}
		}
	}

	public function get_controllers() {
		$controllers = array(
			'checkout'         => 'WC_Braintree_Controller_Checkout',
			'cart'             => 'WC_Braintree_Controller_Cart',
			'_3ds'             => 'WC_Braintree_Controller_3ds',
			'order_actions'    => 'WC_Braintree_Controller_Order_Actions',
			'client_token'     => 'WC_Braintree_Controller_Client_Token',
			'tokens'           => 'WC_Braintree_Controller_Payment_Tokens',
			'plans'            => 'WC_Braintree_Controller_Plan',
			'kount'            => 'WC_Braintree_Controller_Kount',
			'applepay'         => 'WC_Braintree_Controller_ApplePay',
			'merchant_account' => 'WC_Braintree_Controller_Merchant_Accounts',
			'local_payment'    => 'WC_Braintree_Controller_Local_Payment',
			'data_migration'   => 'WC_Braintree_Controller_Data_Migration',
			'googlepay'        => 'WC_Braintree_Controller_GooglePay',
			'settings'         => 'WC_Braintree_Controller_Settings',
			'paypal'           => 'WC_Braintree_Controller_PayPal',
			'product'          => 'WC_Braintree_Controller_Product',
			'webhook'          => 'WC_Braintree_Controller_Webhook'
		);

		/**
		 * @param array $controllers
		 */
		return apply_filters( 'wc_braintree_api_controllers', $controllers );
	}

	public function rest_url() {
		return braintree()->rest_url();
	}

	public function rest_uri() {
		return braintree()->rest_uri();
	}

	/**
	 * Returns true if this is a REST request and it matches the Braintree plugin namespace.
	 * @return bool
	 */
	public static function is_rest_api_request() {
		global $wp;
		if ( ! empty( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], braintree()->rest_uri() ) !== false ) {
			return true;
		}
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], braintree()->rest_uri() ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns true if this is a WP Rest request specific to the Braintree plugin. Not all Braintree
	 * REST requests will return true, these must be specific to frontend requests like adding to the cart.
	 * @return bool
	 * @since 3.2.10
	 */
	public static function is_frontend_rest_request() {
		return ! empty( $_GET['wc-ajax'] ) && $_GET['wc-ajax'] === 'wc_braintree_frontend_request';
	}

	/**
	 * Returns true if this is any kind of REST requests.
	 * @return bool
	 * @since 3.2.10
	 */
	public static function is_wp_rest_request() {
		if ( function_exists( 'WC' ) && property_exists( WC(), 'is_rest_api_request' ) ) {
			return WC()->is_rest_api_request();
		}

		return ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) ) !== false;
	}

	public function force( $bool ) {
		$this->force_routes = $bool;
	}

	/**
	 * Converts a frontend ajax request to a WP Rest request.
	 * @since 3.2.10
	 */
	public static function process_frontend_ajax() {
		if ( isset( $_GET['path'] ) ) {
			global $wp;
			$wp->set_query_var( 'rest_route', sanitize_text_field( $_GET['path'] ) );
			rest_api_loaded();
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 * @since 3.2.10
	 */
	public static function get_endpoint( $path ) {
		return add_query_arg( 'path', '/' . trim( $path, '/' ), WC_AJAX::get_endpoint( 'wc_braintree_frontend_request' ) );
	}
}

WC_Braintree_Rest_API::init();
