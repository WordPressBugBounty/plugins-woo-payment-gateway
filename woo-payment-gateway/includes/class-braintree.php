<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @author  Payment Plugins
 * @package Braintree/Classes
 *
 * @property WC_Braintree_Customer_Manager $customer_manager
 * @property int                           $WC_BRAINTREE_SPAM_COUNT
 */
class WC_Braintree_Manager {

	/**
	 *
	 * @var string
	 */
	public $version = '3.2.57';

	public $js_sdk_version = '3.106.0';

	public static $instance;

	private $props = array();

	public $partner_code = 'PaymentPlugins_BT';

	/**
	 *
	 * @var WC_Braintree_Settings_API
	 */
	public $api_settings;

	/**
	 *
	 * @var WC_Braintree_Advanced_Fraud_Settings
	 */
	public $fraud_settings;

	/**
	 *
	 * @var WC_Braintree_Settings_API
	 */
	public $merchant_settings;

	/**
	 *
	 * @var WC_Braintree_Settings_API
	 */
	public $descriptor_settings;

	/**
	 *
	 * @var WC_Braintree_Settings_API
	 */
	public $fee_settings;

	/**
	 *
	 * @var WC_Braintree_Settings_API
	 */
	public $subscription_settings;

	/**
	 *
	 * @var WC_Braintree_Frontend_Scripts
	 */
	public $frontend_scripts;

	/**
	 *
	 * @var WC_Braintree_Rest_API
	 */
	public $rest_api;

	/**
	 *
	 * @var \Braintree\Gateway
	 */
	private $gateway;

	/**
	 * Array of WC payment gateways provided by the plugin
	 *
	 * @var array
	 */
	private $payment_gateways = array();

	public function __construct() {
		$this->validate_install();
		$this->set_version();
		$this->add_hooks();
		$this->includes();
		$this->packages();
	}

	public function __set( $key, $value ) {
		$this->props[ $key ] = $value;
	}

	public function __get( $key ) {
		return $this->props[ $key ];
	}

	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woo-payment-gateway' ), '3.1.7' );
	}

	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Deserialization is forbidden', 'woo-payment-gateway' ), '3.1.7' );
	}

	public function set_version() {
	}

	private function add_hooks() {
		add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ), 10 );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
		add_action( 'plugins_loaded', array( $this, 'admin_includes' ), 20 );
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function plugin_path() {
		return WC_BRAINTREE_PATH;
	}

	public function plugin_name() {
		return WC_BRAINTREE_PLUGIN_NAME;
	}

	public function template_path() {
		return trailingslashit( 'woo-payment-gateway' );
	}

	public function assets_path() {
		return WC_BRAINTREE_ASSETS;
	}

	public function rest_url() {
		return get_rest_url( null, $this->rest_uri() );
	}

	public function rest_uri() {
		return 'wc-braintree/v1/';
	}

	/**
	 *
	 * @since 3.0.4
	 * @throws Exception
	 */
	private function validate_install() {
		if ( isset( $_GET['action'], $_GET['plugin'] ) && ( $_GET['action'] === 'activate' || $_GET['action'] === 'error_scrape' )
		     && $_GET['plugin'] === 'woo-payment-gateway/braintree-payments.php'
		) {
			// check if there is another Braintree plugin active.
			if ( class_exists( 'WC_PayPal_Braintree_Loader' ) ) {
				throw new Exception( 'Please deactivate all other Braintree Plugins before installing Braintree For WooCommerce to prevent conflicts.' );
			}
		}
	}

	/**
	 *
	 * @since 3.0.4
	 */
	public function plugins_loaded() {
		$this->plugin_validations();
		load_plugin_textdomain( 'woo-payment-gateway', false, dirname( WC_BRAINTREE_PLUGIN_NAME ) . '/i18n/languages' );

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			add_action( 'before_woocommerce_init', function () {
				try {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_path() . 'braintree-payments.php', true );
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $this->plugin_path() . 'braintree-payments.php', true );
				} catch ( \Exception $e ) {
				}
			} );
		}
	}

	/**
	 *
	 * @since 3.0.4
	 */
	public function plugin_validations() {
		$this->dependency_check();
		$this->other_braintree_plugins();
	}

	private function dependency_check() {
		if ( ! function_exists( 'WC' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-info is-dismissible">';
					echo '<p>' . __( 'Braintree for WooCommerce requires WooCommerce 3.0.0+ to be installed.', 'woo-payment-gateway' ) . '</p>';
					echo '</div>';
				}
			);
		}
	}

	/**
	 *
	 * @since 3.0.4
	 */
	private function other_braintree_plugins() {
		if ( class_exists( 'WC_PayPal_Braintree_Loader' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-info is-dismissible">';
					echo '<p style="font-size: 16px">' .
					     __( 'We noticed you have <b>WooCommerce PayPal Powered by Braintree Gateway</b> active. Please deactivate to prevent conflicts with <b>Braintree For WooCommerce</b>.',
						     'woo-payment-gateway' ) .
					     '</p>';
					echo '</div>';
				}
			);
		}
	}

	public function admin_includes() {
		if ( is_admin() && function_exists( 'WC' ) ) {
			include_once WC_BRAINTREE_PATH . 'includes/admin/class-wc-braintree-admin-menus.php';
			include_once WC_BRAINTREE_PATH . 'includes/admin/class-wc-braintree-admin-assets.php';
			include_once WC_BRAINTREE_PATH . 'includes/admin/class-wc-braintree-admin-settings.php';
			include_once WC_BRAINTREE_PATH . 'includes/admin/class-wc-braintree-admin-user-edit.php';
			include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-braintree-admin-order-metaboxes.php';
			include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-braintree-meta-box-product-data.php';
		}
	}

	public function includes() {
		/**
		 * Functions
		 */
		include_once WC_BRAINTREE_PATH . 'includes/wc-braintree-functions.php';
		include_once WC_BRAINTREE_PATH . 'includes/wc-braintree-template-functions.php';
		include_once WC_BRAINTREE_PATH . 'includes/wc-braintree-message-functions.php';
		include_once WC_BRAINTREE_PATH . 'includes/wc-braintree-hooks.php';

		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-update.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-install.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-post-types.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-rest-api.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-constants.php';
	}

	/**
	 * Functionality that is included only if WC is active.
	 */
	public function woocommerce_init() {
		/**
		 * Traits
		 */
		include_once WC_BRAINTREE_PATH . 'includes/traits/wc-braintree-settings-trait.php';
		include_once WC_BRAINTREE_PATH . 'includes/traits/wc-braintree-controller-cart-trait.php';

		/**
		 * Settings*
		 */
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-settings-api.php';
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-advanced-settings-api.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-api-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-merchant-account-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-fraud-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-descriptor-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-fee-settings.php';
		include_once WC_BRAINTREE_PATH . 'includes/admin/settings/class-wc-braintree-subscription-settings.php';

		/**
		 * Gateways*
		 */
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-braintree-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-braintree-local-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-cc-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-paypal-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-googlepay-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-applepay-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-venmo-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-ideal-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-p24-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-eps-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-giropay-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-bancontact-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-sepa-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-wechat-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-mybank-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-sofort-payment-gateway.php';
		include_once WC_BRAINTREE_PATH . 'includes/gateways/class-wc-braintree-alipay-gateway.php';

		/**
		 * Tokens
		 */
		include_once WC_BRAINTREE_PATH . 'includes/abstract/abstract-class-wc-payment-token-braintree.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-cc.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-paypal.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-googlepay.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-applepay.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-venmo.php';
		include_once WC_BRAINTREE_PATH . 'includes/tokens/class-wc-payment-token-braintree-local-payment.php';

		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-customer-manager.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-payment-method-conversion.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-frontend-scripts.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-field-manager.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-condition-evaluator.php';
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-product-gateway-option.php';

		/**
		 * Shortcodes
		 */
		include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-shortcodes.php';

		/**
		 * Deprecated
		 */
		include_once WC_BRAINTREE_PATH . 'includes/deprecated/wc-braintree-deprecated-functions.php';
		include_once WC_BRAINTREE_PATH . 'includes/deprecated/class-wc-braintree-deprecated-filter-hooks.php';

		/**
		 * subscription functionality
		 */
		if ( wc_braintree_subscriptions_active() ) {
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/wcs-braintree-functions.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/wcs-braintree-hooks.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-product-braintree-subscription.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-product-braintree-variable-subscription.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-product-braintree-subscription-variation.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-braintree-subscriptions-cart.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-braintree-subscriptions-checkout.php';
			include_once WC_BRAINTREE_PATH . 'includes/subscriptions/class-wc-braintree-subscription.php';
			include_once WC_BRAINTREE_PATH . 'includes/data-stores/class-wc-braintree-subscription-data-store-cpt.php';
			include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-query.php';
			include_once WC_BRAINTREE_PATH . 'includes/class-wc-braintree-form-handler.php';
			include_once WC_BRAINTREE_PATH . 'includes/deprecated/wc-braintree-subscriptions-deprecated-functions.php';
			if ( is_admin() ) {
				include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-meta-box-braintree-subscription-data.php';
				include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-braintree-meta-box-subscription-order-data.php';
				include_once WC_BRAINTREE_PATH . 'includes/admin/class-wc-braintree-admin-list-table-subscriptions.php';
				include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-braintree-meta-box-coupons.php';
			}
		} elseif ( wcs_braintree_active() ) {
			include_once WC_BRAINTREE_PATH . 'includes/deprecated/wcs-braintree-deprecated-functions.php';
		}

		/**
		 * Rest
		 */
		$this->rest_api = new WC_Braintree_Rest_API();

		$this->customer_manager = new WC_Braintree_Customer_Manager();

		$settings_classes = apply_filters(
			'wc_braintree_settings_classes',
			array(
				'api_settings'          => 'WC_Braintree_API_Settings',
				'merchant_settings'     => 'WC_Braintree_Merchant_Account_Settings',
				'fraud_settings'        => 'WC_Braintree_Advanced_Fraud_Settings',
				'descriptor_settings'   => 'WC_Braintree_Descriptor_Settings',
				'fee_settings'          => 'WC_Braintree_Fee_Settings',
				'subscription_settings' => 'WC_Braintree_Subscription_Settings',
			)
		);
		if ( wcs_braintree_active() ) {
			unset( $settings_classes['subscription_settings'] );
		}
		foreach ( $settings_classes as $id => $class ) {
			if ( class_exists( $class ) ) {
				$this->{$id} = new $class();
			}
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->scripts();
		}

		// gateway classes. Let other plugins add gateways or alter existing ones.
		$this->payment_gateways = apply_filters(
			'wc_braintree_payment_gateways',
			array(
				'WC_Braintree_CC_Payment_Gateway',
				'WC_Braintree_PayPal_Payment_Gateway',
				'WC_Braintree_GooglePay_Payment_Gateway',
				'WC_Braintree_ApplePay_Payment_Gateway',
				'WC_Braintree_Venmo_Payment_Gateway',
				'WC_Braintree_IDEAL_Payment_Gateway',
				'WC_Braintree_P24_Payment_Gateway',
				'WC_Braintree_EPS_Payment_Gateway',
				'WC_Braintree_Giropay_Payment_Gateway',
				'WC_Braintree_Bancontact_Payment_Gateway',
				'WC_Braintree_Sepa_Payment_Gateway',
				'WC_Braintree_WeChat_Payment_Gateway',
				'WC_Braintree_MyBank_Payment_Gateway',
				'WC_Braintree_Sofort_Payment_Gateway',
				'WC_Braintree_Alipay_Payment_Gateway',
			)
		);

		$this->WC_BRAINTREE_SPAM_COUNT = 0;
	}

	/**
	 * @since 3.2.23
	 */
	private function packages() {
		\PaymentPlugins\CartFlows\Braintree\Package::init();
		\PaymentPlugins\WooCommerce\Blocks\Braintree\Package::init();
		\PaymentPlugins\Braintree\WooFunnels\Package::init();
	}

	public function generate_client_token( $env = '' ) {
		$client_token = '';
		$args         = array();
		try {
			$merchant_account = wc_braintree_get_merchant_account();
			if ( ! empty( $merchant_account ) ) {
				$args['merchantAccountId'] = $merchant_account;
			}
			$gateway      = new \Braintree\Gateway( wc_braintree_connection_settings( $env ) );
			$client_token = $gateway->clientToken()->generate( $args );
		} catch ( \Braintree\Exception $e ) {
			wc_braintree_log_error( sprintf( __( 'Error creating client token. Exception: %1$s', 'woo-payment-gateway' ), get_class( $e ) ) );
		}

		return $client_token;
	}

	/**
	 *
	 * @param string $env
	 *
	 * @return NULL|\Braintree\Gateway
	 */
	public function gateway( $env = '' ) {
		$gateway  = null;
		$settings = wc_braintree_connection_settings( $env );
		if ( ! in_array( '', $settings ) ) {
			try {
				$gateway = new \Braintree\Gateway( $settings );
			} catch ( Exception $e ) {
			}
		}

		return $gateway;
	}

	/**
	 * Return an array of WC payment gateway classes provided by the plugin.
	 *
	 * @return array
	 */
	public function payment_gateways() {
		return $this->payment_gateways;
	}

	private function is_request( $type ) {
		switch ( $type ) {
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! WC_Braintree_Rest_API::is_wp_rest_request();
		}
	}

	/**
	 * @since 3.2.6
	 * @return WC_Braintree_Frontend_Scripts
	 */
	public function scripts() {
		if ( is_null( $this->frontend_scripts ) ) {
			$this->frontend_scripts = new WC_Braintree_Frontend_Scripts(
				$this->js_sdk_version,
				new \PaymentPlugins\Braintree\Assets\AssetsApi(
					WC_BRAINTREE_PATH,
					trailingslashit( dirname( WC_BRAINTREE_ASSETS ) ),
					$this->version
				)
			);
		}

		return $this->frontend_scripts;
	}

}

/**
 * Returns the main instance of Braintree for WooCommerce
 *
 * @since   3.0.0
 * @return WC_Braintree_Manager
 * @package Braintree/Functions
 */
function braintree() {
	return WC_Braintree_Manager::instance();
}

if ( ! function_exists( 'bt_manager' ) ) {
	/**
	 *
	 * @return WC_Braintree_Manager
	 * @deprecated
	 *
	 * @package Braintree/DeprecatedFunctions
	 */
	function bt_manager() {
		_doing_it_wrong( __FUNCTION__, __( 'Use of bt_manager has been deprecated. Please use function braintree() instead.', 'woo-payment-gateway' ), '3.0.0' );
		if ( ! class_exists( 'Braintree_Gateway_Manager' ) ) {
			include_once WC_BRAINTREE_PATH . 'includes/deprecated/class-braintree-gateway-manager.php';
		}

		return Braintree_Gateway_Manager::instance();
	}
}

/**
 * create singleton instance of WC_Braintree_Manager
 */
braintree();
