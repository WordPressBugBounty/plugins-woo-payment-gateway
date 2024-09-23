<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 * Abstract class that is meant to be extended by Braintree payment methods.
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 */
abstract class WC_Braintree_Payment_Gateway extends WC_Payment_Gateway {

	use WC_Braintree_Settings_Trait;

	public $nonce_key;

	public $token_key;

	public $device_data_key;

	public $save_method_key;

	public $payment_type_key;

	public $config_key = '';

	/**
	 * The token type used to create WC_Payment_Token objects.
	 *
	 * @var string
	 */
	protected $token_type;

	protected $deprecated_id = '';

	/**
	 *
	 * @var array
	 */
	protected $client_token = '';

	/**
	 * Token that represents a vaulted payment method in Braintree.
	 * Example: cby23j
	 *
	 * @var string
	 */
	protected $payment_method_token = null;

	/**
	 * Nonce that represents a once time use payment method.
	 *
	 * @var string
	 */
	protected $payment_method_nonce = null;

	protected $update_payment_method_request = false;

	protected $tab_title = '';

	/**
	 * Template that points to where the gateway's html is rendered.
	 *
	 * @var string
	 */
	public $template = '';

	/**
	 *
	 * @var bool Indicator for when payment is being processed.
	 */
	public $processing_payment;

	/**
	 * @var array
	 * @since 3.2.3
	 */
	protected $order_totals
		= array(
			'item'     => array(
				'subtotal'  => 0,
				'tax_total' => 0
			),
			'discount' => array(
				'subtotal'  => 0,
				'tax_total' => 0
			),
			'fee'      => array(
				'subtotal'  => 0,
				'tax_total' => 0
			),
			'shipping' => array(
				'subtotal'  => 0,
				'tax_total' => 0
			),
			'tax'      => array(
				'subtotal'  => 0,
				'tax_total' => 0
			)
		);

	/**
	 * @var int[]
	 * @since 3.2.4
	 */
	protected $line_item_validations
		= array(
			'commodityCode'  => 12,
			'description'    => 127,
			'discountAmount' => 2,
			'name'           => 35,
			'productCode'    => 12,
			'taxAmount'      => 2,
			'totalAmount'    => 2,
			'unitAmount'     => 4,
			'unitOfMeasure'  => 12,
			'unitTaxAmount'  => 2
		);

	/**
	 * @var bool
	 * @since 3.2.5
	 */
	protected $has_digital_wallet = false;

	/**
	 * @var WC_Braintree_Product_Gateway_Option
	 * @since 3.2.7
	 */
	public $product_gateway_option;

	protected $saved_method_label;

	/**
	 * Initialize static functions
	 */
	public static function init() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'capture_transaction_from_status' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'void_transaction_from_status' ), 10, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'available_payment_gateways' ) );
		add_action( 'woocommerce_process_shop_subscription_meta', array( __CLASS__, 'process_shop_subscription_meta' ), 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment', array( __CLASS__, 'deprecated_subscription_check' ), - 1000 );
	}

	/**
	 *
	 * @var \Braintree\Gateway
	 */
	public $gateway;

	/**
	 * WC_Braintree_Payment_Gateway constructor.
	 */
	public function __construct() {
		$this->set_supports();
		$this->init_form_fields();
		$this->init_settings();
		$this->add_hooks();
		$this->connect();
		$this->has_fields         = true;
		$this->nonce_key          = $this->id . '_nonce_key';
		$this->token_key          = $this->id . '_token_key';
		$this->device_data_key    = $this->id . '_device_data';
		$this->save_method_key    = $this->id . '_save_method';
		$this->payment_type_key   = $this->id . '_payment_type';
		$this->config_key         = $this->id . '_config_data';
		$this->title              = $this->get_option( 'title_text' );
		$this->description        = $this->get_option( 'description' );
		$this->new_method_label   = __( 'New Card', 'woo-payment-gateway' );
		$this->saved_method_label = __( 'Saved Cards', 'woo-payment-gateway' );
	}

	/**
	 * Add all standard filters
	 */
	public function add_hooks() {
		add_action( 'woocommerce_settings_checkout_' . $this->id, array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_subscriptions_pre_update_payment_method', array( $this, 'pre_update_payment_method' ), 10, 2 );
		add_action( 'wc_braintree_payment_token_deleted_' . $this->id, array( $this, 'delete_payment_method' ), 10, 2 );
		add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'subscription_payment_meta' ), 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );
		add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );
		add_filter( 'wc_braintree_admin_settings_tabs', array( $this, 'admin_settings_tabs' ) );
		add_action( 'wc_braintree_before_process_order_' . $this->id, array( $this, 'pre_order_before_process_order' ) );
		add_action( 'wc_braintree_before_process_order_' . $this->id, array( $this, 'wcs_before_process_order' ) );
		add_action( 'wc_braintree_before_process_order_' . $this->id, array( $this, 'wcs_braintree_before_process_order' ) );
		/**
		 *
		 * @since 3.0.6
		 */
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_payment' ) );
	}

	public function set_supports() {
		$this->supports = array(
			'tokenization',
			'subscriptions',
			'products',
			'add_payment_method',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer',
			'wc_braintree_fees',
			'wc_braintree_subscriptions',
			'wc_braintree_subscriptions_change_payment_method',
		);
	}

	/**
	 * Wrapper for the WC_Payment_Gateway::is_available function. A filter is added so merchants
	 * can add custom logic for determining when the gateway should be available.
	 *
	 * @return bool|mixed|void
	 */
	public function is_available() {
		/**
		 * @param bool
		 * @param WC_Braintree_Payment_Gateway
		 *
		 * @since 3.2.19
		 */
		return apply_filters( 'wc_braintree_gateway_is_available', parent::is_available(), $this );
	}

	/**
	 *
	 * @param string $env
	 */
	public function connect( $env = '' ) {
		try {
			$this->gateway = new \Braintree\Gateway( wc_braintree_connection_settings( $env ) );
		} catch ( \Braintree\Exception $e ) {
			wc_braintree_log_info( sprintf( __( 'Error setting up %1$s. Reason: %2$s', 'woo-payment-gateway' ), $this->method_title,
				$e->getMessage() ) );
		}
	}

	public function payment_fields() {
		$methods = is_add_payment_method_page() ? array() : $this->get_tokens();
		$this->output_display_items( 'checkout' );
		$this->enqueue_frontend_scripts( braintree()->scripts() );
		wc_braintree_get_template(
			'checkout/braintree-payment-method.php',
			array(
				'methods'     => $methods,
				'has_methods' => (bool) $methods,
				'gateway'     => $this,
			)
		);
	}

	/**
	 * Delete a payment method in the Braintree gateway.
	 *
	 * @param int              $token_id
	 * @param WC_Payment_Token $token
	 */
	public function delete_payment_method( $token_id, $token ) {
		try {
			$this->connect( $token->get_meta( 'environment' ) );
			$response = $this->gateway->paymentMethod()->delete( $token->get_token() );
			if ( ! $response->success ) {
				wc_braintree_log_error( sprintf( __( 'Braintree payment token %1$s not deleted in Braintree gateway. Reason: %2$s',
					'woo-payment-gateway' ), $token->get_token(), wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			wc_braintree_log_error( sprintf( __( 'Braintree payment token %1$s not deleted in Braintree gateway. Reason: %2$s',
				'woo-payment-gateway' ), $token->get_token(), wc_braintree_errors_from_object( $e ) ) );
		}
		$this->connect();
	}

	/**
	 *
	 * @param WC_Subscription $subscription
	 * @param string          $new_payment_method
	 */
	public function pre_update_payment_method( $subscription, $new_payment_method ) {
		if ( $new_payment_method === $this->id ) {
			$this->update_payment_method_request = true;
		}
	}

	/**
	 *
	 * @param array           $payment_meta
	 * @param WC_Subscription $subscription
	 */
	public function subscription_payment_meta( $payment_meta, $subscription ) {
		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				Constants::PAYMENT_METHOD_TOKEN => array(
					'value' => $this->get_order_meta_data( Constants::PAYMENT_METHOD_TOKEN, $subscription ),
					'label' => __( 'Payment Method Token', 'woo-payment-gateway' ),
				),
			),
		);

		return $payment_meta;
	}

	public function generate_button_demo_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args(
			$data,
			array(
				'title'       => '',
				'class'       => '',
				'style'       => '',
				'description' => '',
				'desc_tip'    => false,
				'id'          => 'wc-braintree-button-demo',
			)
		);
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/button-demo.php';

		return ob_get_clean();
	}

	public function admin_options() {
		$this->output_settings_nav();
		do_action( 'wc_braintree_admin_options', $this );
		printf( '<input type="hidden" id="wc_braintree_prefix" name="wc_braintree_prefix" value="%1$s"/>', $this->get_prefix() );
		echo '<div class="wc-braintree-settings-container ' . $this->id . '">';
		parent::admin_options();
		echo '</div>';
	}

	public function enqueue_admin_scripts() {
	}

	/**
	 *
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function enqueue_frontend_scripts( $scripts ) {
		global $wp;
		if ( is_checkout() && ! is_order_received_page() ) {
			$this->enqueue_checkout_scripts( $scripts );
		}
		if ( is_add_payment_method_page() && ! isset( $wp->query_vars['payment-methods'] ) ) {
			$this->enqueue_add_payment_method_scripts( $scripts );
		}
		if ( is_cart() ) {
			$this->enqueue_cart_scripts( $scripts );
		}
		if ( is_product() ) {
			$this->enqueue_product_scripts( $scripts );
		}
		if ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() ) {
			$this->enqueue_checkout_scripts( $scripts );
		}
	}

	/**
	 *
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function enqueue_checkout_scripts( $scripts ) {
	}

	/**
	 *
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function enqueue_add_payment_method_scripts( $scripts ) {
		$this->enqueue_checkout_scripts( $scripts );
	}

	/**
	 *
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function enqueue_cart_scripts( $scripts ) {
	}

	/**
	 *
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function enqueue_product_scripts( $scripts ) {
	}

	/**
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 *
	 * @since 3.2.5
	 */
	public function enqueue_mini_cart_scripts( $scripts ) {
		if ( ! wp_script_is( $scripts->get_handle( 'mini-cart' ) ) ) {
			$scripts->enqueue_script( 'mini-cart', $scripts->assets_url( 'js/frontend/mini-cart.js' ),
				apply_filters( 'wc_braintree_mini_cart_deps', array(
					$scripts->get_handle( 'client-manager' ),
					$scripts->get_handle( 'data-collector-v3' )
				), $scripts ) );
		}
		wp_localize_script( $scripts->get_handle( 'mini-cart' ), "wc_{$this->id}_mini_cart_params", $this->get_localized_standard_params() );
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_' . $this->id . '_form_fields',
			include WC_BRAINTREE_PATH . 'includes/gateways/settings/' .
			        str_replace( 'braintree_', '', $this->id ) . '-settings.php',
			$this
		);
		if ( wcs_braintree_active() && $this->supports( 'subscriptions' ) ) {
			$this->form_fields = array_merge(
				$this->form_fields,
				apply_filters(
					'wcs_braintree_subscription_form_fields',
					include WC_BRAINTREE_PATH .
					        '/includes/gateways/settings/subscription-settings.php',
					$this
				)
			);
		}
	}

	/**
	 * Generate a client token
	 *
	 * @return string
	 */
	public function generate_client_token() {
		$client_token = '';
		$args         = array();
		try {
			$merchant_account = wc_braintree_get_merchant_account();
			if ( ! empty( $merchant_account ) ) {
				$args['merchantAccountId'] = $merchant_account;
			}
			$client_token = $this->gateway->clientToken()->generate( $args );
		} catch ( \Braintree\Exception $e ) {
			wc_braintree_log_error( sprintf( __( 'Error creating client token. Exception: %1$s', 'woo-payment-gateway', get_class( $e ) ) ) );
		}

		return $client_token;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $this->update_payment_method_request && wcs_braintree_active() && wcs_is_subscription( $order ) ) {
			return $this->process_change_payment_request( $order );
		}
		do_action( 'wc_braintree_before_process_order_' . $this->id, $order );

		if ( wc_notice_count( 'error' ) > 0 ) {
			return array( 'result' => 'failure' );
		}

		$this->processing_payment = true;

		if ( $order->get_total() == 0 || $this->pre_order_with_payment_later( $order ) ) {
			$this->save_zero_total_order_meta( $order );
			if ( $this->pre_order_with_payment_later( $order ) ) {
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
			} else {
				$order->payment_complete();
			}
			WC()->cart->empty_cart();
			$this->remove_session_checkout_vars();
			$this->do_post_payment_processing( $order );

			return $this->order_success_result( $order );
		}

		$this->add_order_general_args( $args, $order )
		     ->add_order_options( $args, $order )
		     ->add_order_descriptors( $args, $order )
		     ->add_order_device_data( $args, $order )
		     ->add_order_customer_id( $args, $order );

		if ( $this->use_saved_method() ) {
			$args['paymentMethodToken'] = $this->get_payment_method_token();
		} else {
			$args['paymentMethodNonce'] = $this->get_payment_method_nonce();
		}

		$args = apply_filters( 'wc_braintree_order_transaction_args', $args, $order, $this->id );

		$result = array();
		wc_braintree_log_info( sprintf( __( 'Processing payment for order %1$s. Transaction args: %2$s', 'woo-payment-gateway' ), $order_id,
			print_r( $args, true ) ) );
		try {
			$response = $this->gateway->transaction()->sale( $args );
			if ( $response->success ) {
				$this->save_order_meta( $response->transaction, $order );
				$this->record_transaction();
				if ( isset( $args['options']['storeInVaultOnSuccess'] ) && $args['options']['storeInVaultOnSuccess'] == true ) {
					$token = $this->get_payment_token( $this->get_payment_method_from_transaction( $response->transaction ) );
					$token->set_user_id( $order->get_customer_id() );
					// set token in case downstream processes need access to it.
					$this->payment_method_token = $token->get_token();
					try {
						$token->save();
						WC_Payment_Tokens::set_users_default( $order->get_customer_id(), $token->get_id() );
					} catch ( Exception $e ) {
						wc_braintree_log_error( sprintf( 'Error saving payment method. Reason: %s', $e->getMessage() ) );
					}
				}
				if ( braintree()->fraud_settings->is_active( 'kount_enabled' ) && isset( $response->transaction->riskData ) ) {
					$this->perform_kount_actions( $order, $response->transaction, $args );
				} else {
					$this->payment_complete_actions( $order, $response->transaction, $args );
				}
				$this->remove_session_checkout_vars();
				WC()->cart->empty_cart();
				$this->do_post_payment_processing( $order );
				$result = $this->order_success_result( $order );
			} else {
				throw new Exception( wc_braintree_errors_from_object( $response ) );
			}
		} catch ( Exception $e ) {
			if ( $e instanceof \Braintree\Exception ) {
				wc_braintree_log_error( sprintf( __( 'Error processing payment for order %1$s. Exception: %2$s. Transaction args: %3$s',
					'woo-payment-gateway' ), $order->get_id(), get_class( $e ), print_r( $args, true ) ) );
				$msg = wc_braintree_errors_from_object( $e );
			} else {
				$msg = $e->getMessage();
			}
			$notice = sprintf( __( 'There was an error processing your payment. Reason: %1$s', 'woo-payment-gateway' ), $msg );
			$order->add_order_note( sprintf( __( 'Error processing payment. Reason: %1$s', 'woo-payment-gateway' ), $msg ) );
			$order->update_status( 'failed' );
			wc_braintree_set_checkout_error();
			wc_add_notice( $notice, 'error' );
			$result = $this->order_error_result( $notice );
		}

		return $result;
	}

	/**
	 * Handles order status once payment has been captured or authorized.
	 *
	 * @param WC_Order               $order
	 * @param \Braintree\Transaction $transaction
	 * @param array                  $args
	 *
	 * @since 3.0.8
	 */
	public function payment_complete_actions( $order, $transaction, $args ) {
		if ( $args['options']['submitForSettlement'] ) {
			// payment was captured, so call payment complete.
			$order->payment_complete( $transaction->id );
		} else {
			// payment is authorized only. Must be captured at a later time.
			$order_status = $this->get_option( 'order_status' );
			$order->update_status( apply_filters( 'wc_braintree_authorized_order_status', 'default' === $order_status ? 'on-hold' : $order_status,
				$order, $this ) );
		}
		if ( apply_filters( 'wc_braintree_add_order_success_note', true, $order, $transaction, $this ) ) {
			$order->add_order_note( sprintf( __( 'Order %1$s in Braintree: Transaction ID: %2$s. Payment method: %3$s', 'woo-payment-gateway' ),
				$args['options']['submitForSettlement'] ? __( 'captured', 'woo-payment-gateway' ) : __( 'authorized', 'woo-payment-gateway' ),
				$order->get_transaction_id(), $order->get_payment_method_title() ) );
		}
	}

	/**
	 * If the payment_method_token has been set then return it.
	 * If not look for the token in the $_POST.
	 */
	public function get_payment_method_token() {
		if ( $this->payment_method_token ) {
			return $this->payment_method_token;
		}
		if ( ! empty( $_POST[ $this->token_key ] ) ) {
			return wc_clean( $_POST[ $this->token_key ] );
		}
	}

	/**
	 * If the payment_method_nonce has been set, then return it.
	 * If not look for the nonce in the $_POST.
	 */
	public function get_payment_method_nonce() {
		if ( $this->payment_method_nonce ) {
			return $this->payment_method_nonce;
		}

		return ! empty( $_POST[ $this->nonce_key ] ) ? wc_clean( $_POST[ $this->nonce_key ] ) : '';
	}

	/**
	 *
	 * @param string $value
	 */
	public function set_payment_method_nonce( $value ) {
		$this->payment_method_nonce = $value;
	}

	/**
	 *
	 * @param string $value
	 */
	public function set_payment_method_token( $value ) {
		$this->payment_method_token = $value;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_customer_attributes( $order ) {
		return array(
			'firstName' => $order->get_billing_first_name(),
			'lastName'  => $order->get_billing_last_name(),
			'phone'     => $order->get_billing_phone(),
			'email'     => $order->get_billing_email(),
			'company'   => $order->get_billing_company(),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_order_id( $order ) {
		return sprintf( '%1$s%2$s%3$s', $this->get_option( 'order_prefix' ), $order->get_order_number(), $this->get_option( 'order_suffix' ) );
	}

	/**
	 *
	 * @deprecated
	 */
	public function get_descriptors() {
		$args = braintree()->descriptor_settings->is_active( 'enabled' ) ? array(
			'name'  => braintree()->descriptor_settings->get_option( 'descriptor_name' ),
			'phone' => braintree()->descriptor_settings->get_option( 'descriptor_phone' ),
			'url'   => braintree()->descriptor_settings->get_option( 'descriptor_url' ),
		) : array();
		foreach ( $args as $k => $v ) {
			if ( empty( $v ) ) {
				unset( $args[ $k ] );
			}
		}

		return $args;
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @since 3.2.1
	 */
	public function add_order_descriptors( &$args, $order ) {
		$descriptors = braintree()->descriptor_settings->is_active( 'enabled' ) ? array(
			'name'  => braintree()->descriptor_settings->get_option( 'descriptor_name' ),
			'phone' => braintree()->descriptor_settings->get_option( 'descriptor_phone' ),
			'url'   => braintree()->descriptor_settings->get_option( 'descriptor_url' ),
		) : array();
		foreach ( $descriptors as $k => $v ) {
			if ( empty( $v ) ) {
				unset( $args[ $k ] );
			}
		}
		$args['descriptor'] = $descriptors;

		return $this;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_order_options( $order ) {
		$options = array(
			'submitForSettlement'   => apply_filters( 'wc_braintree_transaction_submit_for_settlement',
				$this->get_option( 'charge_type' ) === 'capture', $this ),
			'storeInVaultOnSuccess' => $this->should_save_payment_method(),
		);

		return $options;
	}

	public function add_order_options( &$args, $order ) {
		$args['options'] = array(
			'submitForSettlement'   => apply_filters( 'wc_braintree_transaction_submit_for_settlement',
				$this->get_option( 'charge_type' ) === 'capture', $this ),
			'storeInVaultOnSuccess' => $this->should_save_payment_method(),
		);

		return $this;
	}

	public function get_device_data() {
		if ( ! empty( $_POST[ $this->device_data_key ] ) ) {
			return stripslashes( $_POST[ $this->device_data_key ] );
		}
	}

	/**
	 * @param $args
	 * @param $order
	 *
	 * @since 3.2.1
	 *
	 * @return $this
	 */
	public function add_order_device_data( &$args, $order ) {
		if ( ( $data = $this->get_device_data() ) ) {
			$args['deviceData'] = $data;
		}

		return $this;
	}

	/**
	 * Return true if the payment method used on the checkout page should be saved.
	 * Payment methods
	 * should only be saved if the customer's wants to save the method or a new method is being used and
	 * there are subscriptions associated with the order.
	 */
	public function should_save_payment_method() {
		if ( ! empty( $_POST["wc-{$this->id}-new-payment-method"] ) ) {
			return true;
		}

		return ( ! empty( $_POST[ $this->save_method_key ] ) && ! $this->use_saved_method() )
		       || ( wcs_braintree_active() && ! $this->use_saved_method()
		            && ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() )
		            && $this->supports( 'subscriptions' ) );
	}

	/**
	 * Return true if a saved payment method should be used.
	 */
	public function use_saved_method() {
		if ( ! empty( $_POST["wc-{$this->id}-payment-token"] ) ) {
			$token = WC_Payment_Tokens::get( wc_clean( $_POST["wc-{$this->id}-payment-token"] ) );

			if ( $token ) {
				$this->payment_method_token = $token->get_token();
			}
		}

		return ( ! empty( $_POST[ $this->payment_type_key ] ) && $_POST[ $this->payment_type_key ] === 'token' ) || $this->payment_method_token;
	}

	public function order_error_result( $msg = '' ) {
		return array(
			'result'                => 'failure',
			'redirect'              => '',
			'braintreeErrorMessage' => $msg
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function order_success_result( $order ) {
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Save transaction data as meta data of the order.
	 *
	 * @param \Braintree\Transaction $transaction
	 * @param WC_Order               $order
	 */
	public function save_order_meta( $transaction, $order ) {
		$token = $this->get_payment_token( $this->get_payment_method_from_transaction( $transaction ) );
		$token->add_meta_data_to_order( $order );
		$order->set_payment_method_title( $token->get_payment_method_title() );
		$order->set_transaction_id( $transaction->id );
		$order->add_meta_data( Constants::VERSION, braintree()->version, true );
		$order->add_meta_data( Constants::MERCHANT_ACCOUNT_ID, $transaction->merchantAccountId, true );
		$order->add_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token(), true );
		$order->add_meta_data( Constants::ENVIRONMENT, wc_braintree_environment(), true );
		$order->add_meta_data( Constants::TRANSACTION_STATUS, $transaction->status, true );
		if ( $transaction->status === \Braintree\Transaction::AUTHORIZED ) {
			$order->add_meta_data( Constants::AUTH_EXP, $transaction->authorizationExpiresAt->getTimestamp(), true );
		}

		if ( wcs_braintree_active() && wcs_order_contains_subscription( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order );
			foreach ( $subscriptions as $subscription ) {
				$this->save_order_meta( $transaction, $subscription );
			}
		}
		if ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
			$subscriptions = wcs_braintree_get_subscriptions_for_order( $order );
			foreach ( $subscriptions as $subscription ) {
				$this->save_order_meta( $transaction, $subscription );
			}
		}

		/**
		 * @param WC_Order                     $order
		 * @param \Braintree\Transaction       $transaction
		 * @param WC_Braintree_Payment_Gateway $this
		 *
		 * @since 3.2.11
		 */
		do_action( 'wc_braintree_save_order_meta', $order, $transaction, $this );

		$order->save();
	}

	/**
	 * Return the payment method details from the tranaction.
	 *
	 * @param \Braintree\Transaction $transaction
	 */
	abstract public function get_payment_method_from_transaction( $transaction );

	/**
	 *
	 * @param mixed $method
	 *
	 * @return WC_Payment_Token_Braintree
	 */
	public function get_payment_token( $method ) {
		$class_name = 'WC_Payment_Token_Braintree_' . $this->get_token_type();
		if ( class_exists( $class_name ) ) {
			/**
			 *
			 * @var WC_Payment_Token_Braintree $token
			 */
			$token = new $class_name();
			$token->init_from_payment_method( (object) $method );
			$token->set_gateway_id( $this->id );
			$token->set_user_id( get_current_user_id() );
			$token->set_format( $this->get_option( 'method_format' ) );
			$token->set_environment( wc_braintree_environment() );

			return $token;
		}
	}

	public function get_token_type() {
		return apply_filters( 'wc_braintree_gateway_token_type', $this->token_type );
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function save_zero_total_order_meta( $order ) {
		$token = $this->get_token( $this->get_payment_method_token(), $order->get_customer_id() );
		$order->add_meta_data( Constants::VERSION, braintree()->version, true );
		$order->add_meta_data( Constants::MERCHANT_ACCOUNT_ID, wc_braintree_get_merchant_account( $order->get_currency() ), true );
		$order->add_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token(), true );
		$order->add_meta_data( '_wc_braintree_environment', wc_braintree_environment(), true );
		$order->set_payment_method_title( $token->get_payment_method_title() );

		if ( wcs_braintree_active() ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order );
			foreach ( $subscriptions as $subscription ) {
				$this->save_zero_total_order_meta( $subscription );
			}
		}
		if ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
			$subscriptions = wcs_braintree_get_subscriptions_for_order( $order );
			foreach ( $subscriptions as $subscription ) {
				$this->save_zero_total_order_meta( $subscription );
			}
		}
		/**
		 * @param WC_Order                     $order
		 * @param \Braintree\Transaction       $transaction
		 * @param WC_Braintree_Payment_Gateway $this
		 *
		 * @since 3.2.11
		 */
		do_action( 'wc_braintree_save_zero_total_order_meta', $order, $this );

		$order->save();
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order   = wc_get_order( $order_id );
		$id      = $order->get_transaction_id();
		$message = __( 'Error processing refund. Reason: %1$s', 'woo-payment-gateway' );
		try {
			if ( empty( $id ) ) {
				throw new InvalidArgumentException( sprintf( __( 'The transaction ID for order %1$s is blank. A refund cannot be processed unless there is a valid transaction associated with the order.',
					'woo-payment-gateway' ), $order_id ) );
			}
			$this->connect( wc_braintree_get_order_environment( $order ) );
			$response = $this->gateway->transaction()->refund( $id, $amount );
			if ( $response->success ) {
				$transaction = $response->transaction;
				$order->update_meta_data( Constants::TRANSACTION_STATUS, $transaction->status );
				$order->add_order_note(
					sprintf(
						__( 'Refund successful in Braintree. Amount: %1$s. Refund ID: %2$s', 'woo-payment-gateway' ),
						wc_price( $amount, array( 'currency' => $order->get_currency() ) ), $response->transaction->id
					)
				);
				$order->save();

				return true;
			} else {
				$errors = $response->errors->forKey( 'transaction' );
				if ( $errors ) {
					foreach ( $errors->shallowAll() as $error ) {
						if ( $error->code === '91506' ) {
							$message .= ' ' . __( 'If you wish to cancel the transaction you can set the order\'s status to cancelled or use the void option.',
									'woo-payment-gateway' );
						}
					}
				}

				return new WP_Error( 'refund-error', sprintf( $message, wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'refund-error', sprintf( $message, wc_braintree_errors_from_object( $e ) ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'refund-error',
				sprintf( __( 'Exception thrown while issuing refund. Reason: %1$s Exception class: %2$s', 'woo-payment-gateway' ), $e->getMessage(),
					get_class( $e ) ) );
		}
	}

	/**
	 * Capture the provided amount.
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 *
	 * @return bool|WP_Error
	 */
	public function capture_charge( $amount, $order ) {
		$id = $order->get_transaction_id();
		try {
			$response = $this->gateway->transaction()->submitForSettlement( $id, $amount );
			if ( $response->success ) {
				$this->save_order_meta( $response->transaction, $order );

				// we don't want order_status option to be called here since capture is happening via admin.'
				remove_filter( 'woocommerce_payment_complete_order_status', 'wc_braintree_payment_complete_order_status', 99 );

				$order->payment_complete( $id );
				$order->add_order_note(
					sprintf(
						__( 'Transaction submitted for settlement in Braintree. Amount %1$s', 'woo-payment-gateway' ),
						wc_price(
							$amount,
							array(
								'currency' => $order->get_currency(),
							)
						)
					)
				);

				return true;
			} else {
				return new WP_Error( 'capture-error', sprintf( __( 'There was an error capturing the charge. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'capture-error', sprintf( __( 'There was an error capturing the charge. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ) );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @return bool|WP_Error
	 */
	public function void_charge( $order ) {
		$id = $order->get_transaction_id();
		try {
			$response = $this->gateway->transaction()->void( $id );
			if ( $response->success ) {
				$this->save_order_meta( $response->transaction, $order );
				$order->update_status( 'cancelled' );
				$order->add_order_note( sprintf( __( 'Transaction %1$s has been voided in Braintree.', 'woo-payment-gateway' ), $id ) );

				return true;
			} else {
				return new WP_Error( 'capture-error',
					sprintf( __( 'There was an error voiding the transaction. Reason: %1$s', 'woo-payment-gateway' ),
						wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'capture-error', sprintf( __( 'There was an error voiding the transaction. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ) );
		}
	}

	/**
	 * @param array         $args
	 * @param WC_Order|flse $order
	 *
	 * @return array|string[]
	 */
	public function add_payment_method( $args = array(), $order = false ) {
		$nonce    = isset( $_POST[ $this->nonce_key ] ) ? wc_clean( $_POST[ $this->nonce_key ] ) : $this->payment_method_nonce;
		$user_id  = $order ? $order->get_customer_id() : get_current_user_id();
		$customer = new WC_Customer( $user_id );
		if ( ! $nonce ) {
			return array( 'result' => 'error' );
		}
		try {
			/**
			 * Action added so other plugins can throw errors before add payment method starts.
			 *
			 * Example: plugin validates captcha and throws exception if invalid.
			 *
			 * @since 3.0.6
			 */
			do_action( 'wc_braintree_before_add_payment_method', $this );

			// first check that the customer has a braintree vault ID.
			if ( ! ( $customer_id = wc_braintree_get_customer_id( $user_id ) ) ) {
				$customer_id = $this->create_customer( $customer );
				if ( is_wp_error( $customer_id ) ) {
					throw new Exception( $customer_id->get_error_message() );
				}
			}
			$args     = array_merge(
				array(
					'customerId'         => $customer_id,
					'paymentMethodNonce' => $nonce,
					'deviceData'         => $this->get_device_data(),
					'options'            => array(
						'failOnDuplicatePaymentMethod'  => $this->is_active( 'fail_on_duplicate' ),
						'verificationMerchantAccountId' => wc_braintree_get_merchant_account( $order ? $order->get_currency() : '' ),
						'makeDefault'                   => true,
						'verifyCard'                    => true,
					),
					'cardholderName'     => sprintf( '%1$s %2$s', $customer->get_first_name(), $customer->get_last_name() ),
					'billingAddress'     => isset( $_POST['billing_address_1'] ) ? array( 'streetAddress' => wc_clean( $_POST['billing_address_1'] ) ) : array(),
				),
				$args
			);
			$args     = apply_filters( 'wc_braintree_add_payment_method_args', $args, $this );
			$response = $this->gateway->paymentMethod()->create( $args );
			if ( $response->success ) {
				$token = $this->get_payment_token( $response->paymentMethod );
				$token->save();
				WC_Payment_Tokens::set_users_default( $user_id, $token->get_id() );
				$this->payment_method_token = $token->get_token();
				/**
				 * Perform address cleanup in Braintree.
				 * Address can build up over time and if they exceed gateway limit, can cause errors.
				 */
				if ( @isset( $response->paymentMethod->billingAddress ) && is_object( $response->paymentMethod->billingAddress ) ) {
					if ( ! is_null( ( $response->paymentMethod->billingAddress->id ) ) ) {
						try {
							$this->gateway->address()->delete( $customer_id, $response->paymentMethod->billingAddress->id );
						} catch ( \Braintree\Exception $e ) {
							wc_braintree_log_error(
								sprintf(
									__(
										'Error deleting payment method address. This error was caught gracefully
								and not shown to the customer. You can delete address %1$s in your Braintree gateway.',
										'woo-payment-gateway'
									),
									$response->paymentMethod->billingAddress->id
								)
							);
						}
					}
				}

				return array(
					'result'   => 'success',
					'redirect' => wc_get_account_endpoint_url( 'payment-methods' ),
				);
			} else {
				wc_add_notice( sprintf( __( 'There was an error saving your payment method. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ), 'error' );

				return array( 'result' => 'error' );
			}
		} catch ( \Braintree\Exception $e ) {
			wc_add_notice( sprintf( __( 'There was an error saving your payment method. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ), 'error' );

			return array( 'result' => 'error' );
		} catch ( Exception $e ) {
			wc_add_notice( sprintf( __( 'There was an error saving your payment method. Reason: %1$s', 'woo-payment-gateway' ), $e->getMessage() ),
				'error' );

			return array( 'result' => 'error' );
		}
	}

	/**
	 *
	 * @param string $id
	 *
	 * @return WP_Error|\Braintree\Transaction
	 */
	public function fetch_transaction( $id, $env = '' ) {
		try {
			$this->connect( $env );
			$transaction = $this->gateway->transaction()->find( $id );

			return $transaction;
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'transaction-error',
				sprintf( 'Error fetching transaction. Exception: %s. Message: %s', get_class( $e ), $e->getMessage() ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'transaction-error',
				sprintf( 'Error fetching transaction. Exception: %s. Message: %s', get_class( $e ), $e->getMessage() ) );
		}
		$this->connect();
	}

	/**
	 *
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public static function capture_transaction_from_status( $order_id, $order ) {
		if ( $order->get_meta( Constants::TRANSACTION_STATUS ) === \Braintree\Transaction::AUTHORIZED ) {
			$payment_method   = $order->get_payment_method();
			$payment_gateways = WC()->payment_gateways()->payment_gateways();
			$gateway          = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ] : null;
			if ( $gateway && ( $gateway instanceof WC_Braintree_Payment_Gateway ) && ! $gateway->processing_payment ) {
				$gateway->capture_charge( $order->get_total(), $order );
			}
		}
	}

	/**
	 *
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public static function void_transaction_from_status( $order_id, $order ) {
		$statuses = array(
			\Braintree\Transaction::AUTHORIZED,
			\Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT,
			\Braintree\Transaction::SETTLEMENT_PENDING,
		);
		if ( in_array( $order->get_meta( Constants::TRANSACTION_STATUS ), $statuses ) ) {
			$payment_method   = $order->get_payment_method();
			$payment_gateways = WC()->payment_gateways()->payment_gateways();
			$gateway          = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ] : null;
			if ( $gateway && ( $gateway instanceof WC_Braintree_Payment_Gateway ) ) {
				$gateway->void_charge( $order );
			}
		}
	}

	public function get_localized_standard_params() {
		return array(
			'gateway'                     => $this->id,
			'environment'                 => wc_braintree_environment(),
			'advanced_fraud'              => array( 'enabled' => braintree()->fraud_settings->is_active( 'enabled' ) ),
			'token_selector'              => $this->token_key,
			'nonce_selector'              => $this->nonce_key,
			'device_data_selector'        => $this->device_data_key,
			'payment_type_selector'       => $this->payment_type_key,
			'tokenized_response_selector' => $this->id . '_tokenized_response',
			'ipAddress'                   => WC_Geolocation::get_ip_address(),
			'roles'                       => array( 'admin' => current_user_can( 'administrator' ) ),
			'_wp_rest_nonce'              => wp_create_nonce( 'wp_rest' ),
			'user_id'                     => get_current_user_id(),
			'messages'                    => array(
				'terms'          => __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ),
				'required_field' => __( 'Please fill out all required fields.', 'woo-payment-gateway' ),
			),
			'locale'                      => $this->get_locale(),
			'routes'                      => array(
				'checkout'         => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->checkout->rest_uri() . '/checkout' ),
				'add_to_cart'      => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart' ),
				'shipping'         => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping' ),
				'shipping_address' => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping-address' ),
				'shipping_method'  => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->cart->rest_uri() . '/cart/shipping-method' ),
			),
		);
	}

	public static function available_payment_gateways( $gateways ) {
		global $wp;
		if ( is_add_payment_method_page() && isset( $wp->query_vars['add-payment-method'] ) ) {
			unset( $gateways['braintree_paypal'] );
			unset( $gateways['braintree_applepay'] );
			unset( $gateways['braintree_googlepay'] );
			unset( $gateways['braintree_venmo'] );
		}

		return $gateways;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function wcs_before_process_order( $order ) {
		if ( wcs_braintree_active() && wcs_order_contains_subscription( $order ) ) {
			if ( $order->get_total() == 0 && ! self::use_saved_method() && $this->supports( 'subscriptions' ) ) {
				// save the payment method so it can be used for the subscription.
				$args = array( 'billingAddress' => $order->get_billing_address_1() ? array( 'streetAddress' => $order->get_billing_address_1() ) : array() );
				$this->add_payment_method( apply_filters( 'wcs_braintree_add_payment_method_args', $args, $this->id, $this ), $order );
			}
		}
	}

	/**
	 * If the plugin's subscription functionality is being used and there is a subscription in the cart and a
	 * new payment method is being used save the payment method.
	 *
	 * Create the Braintree Subscription.
	 *
	 * @param WC_Order $order
	 */
	public function wcs_braintree_before_process_order( $order ) {
		if ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
			if ( ! self::use_saved_method() ) {
				if ( $order->get_total() == 0 ) {
					$args = array( 'billingAddress' => array( 'streetAddress' => $order->get_billing_address_1() ) );
					$this->add_payment_method( apply_filters( 'wcs_braintree_add_payment_method_args', $args, $this->id, $this ), $order );
				} else {
					// by setting the $_POST variable, method should_save_payment_method will return true;
					$_POST[ $this->save_method_key ] = true;
				}
			}
		}
	}

	/**
	 * Creates a subscription in the Braintree Gateway using data from the WC_Braintree_Subscription
	 *
	 * @param WC_Braintree_Subscription $subscription
	 * @param WC_Order                  $order
	 */
	public function create_braintree_subscription( $subscription, $order ) {
		if ( $subscription->is_created() ) {
			return;
		}
		$args = array(
			'id'                 => $subscription->get_id(),
			'planId'             => $subscription->get_braintree_plan(),
			'price'              => $subscription->get_total(),
			'merchantAccountId'  => $subscription->get_merchant_account_id(),
			'paymentMethodToken' => $this->get_payment_method_token(),
		);
		if ( $subscription->never_expires() ) {
			$args['neverExpires'] = true;
		} else {
			$args['numberOfBillingCycles'] = $subscription->get_num_of_billing_cycles();
		}
		if ( $subscription->has_trial() ) {
			$args['trialDuration']     = $subscription->get_subscription_trial_length();
			$args['trialDurationUnit'] = $subscription->get_subscription_trial_period();
			$args['trialPeriod']       = true;
		} else {
			$args['firstBillingDate'] = $subscription->get_date( 'next_payment' );
		}
		$args = apply_filters( 'wcs_braintree_subscription_args', $args );
		try {
			$response = $this->gateway->subscription()->create( $args );
			if ( $response->success ) {
				$subscription->set_payment_method( $this->id );
				$subscription->set_created( true );
				$subscription->update_status( 'active' );
			} else {
				$subscription->update_status( 'failed' );
				$order->update_status( 'failed' );
				$order->add_order_note( sprintf( __( 'Error processing subscription. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ) );
				wc_add_notice( sprintf( __( 'Error processing subscription. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ), 'error' );

				return;
			}
		} catch ( \Braintree\Exception $e ) {
			$order->add_order_note( sprintf( __( 'Error processing subscription. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ) );
			wc_add_notice( sprintf( __( 'Error processing subscription. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ), 'error' );

			return;
		}
	}

	/**
	 *
	 * @param string $token
	 *
	 * @return WC_Payment_Token_Braintree
	 */
	public function get_token( $token, $user_id = 0 ) {
		global $wpdb;
		$payment_token = null;
		$query         = $wpdb->prepare( "SELECT token_id FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s AND gateway_id = %s AND user_id = %d",
			$token, $this->id, ! $user_id ? get_current_user_id() : $user_id );
		$result        = $wpdb->get_row( $query );
		if ( $result ) {
			/**
			 *
			 * @var WC_Payment_Token_Braintree $payment_token
			 */
			$payment_token = WC_Payment_Tokens::get( $result->token_id );
			$payment_token->set_format( $this->get_option( 'method_format' ) );
		} else {
			// added in 3.0.7. Don't allow the $payment_token to be null to prevent exceptions
			// when data is bad.
			$class_name    = 'WC_Payment_Token_Braintree_' . $this->get_token_type();
			$payment_token = new $class_name();
			$payment_token->set_token( $token );
		}
		$payment_token->set_format( $this->get_option( 'method_format' ) );

		return $payment_token;
	}

	/**
	 * Return true if the token exists in the woocommerce_payment_tokens table.
	 *
	 * @param string $token
	 * @param int    $user_id
	 *
	 * @since 3.0.7
	 */
	public function token_exists( $token, $user_id = 0 ) {
		global $wpdb;
		$payment_token = null;
		$count         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s AND gateway_id = %s AND user_id = %d",
			$token, $this->id, ! $user_id ? get_current_user_id() : $user_id ) );

		return $count > 0;
	}

	/**
	 *
	 * @param WC_Subscription $subscription
	 */
	public function subscription_payment_method_updated( $subscription ) {
		// 3.0.7 this isn't a frontend request so nothing needs to happen.
		if ( defined( 'DOING_CRON' ) || is_admin() ) {
			return;
		}
		if ( $this->use_saved_method() ) {
			$this->payment_method_token = $this->get_payment_method_token();
		} else {
			$result = $this->add_payment_method(
				array(
					'billingAddress' => array( 'streetAddress' => isset( $_POST['billing_address_1'] ) ? wc_clean( $_POST['billing_address_1'] ) : '' ),
				), $subscription
			);
			if ( $result['result'] === 'error' ) {
				/**
				 * WCS seems to have a bug where notices added at this stage are deleted and not captured.
				 * This code resolves that by not allowing notices to be cleared until they are added by WCS.
				 */
				remove_action( 'before_woocommerce_pay', 'woocommerce_output_all_notices' );
				add_action( 'before_woocommerce_pay', 'wc_clear_notices', 200 );

				return;
			}
		}
		if ( $this->payment_method_token ) {
			$token = $this->get_token( $this->payment_method_token );
			// save meta data.
			$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$subscription->set_payment_method_title( $token->get_payment_method_title() );
			$subscription->save();
		}
	}

	/**
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 */
	public function process_subscription_payment( $amount, $order ) {
		// sync payment methods if needed.
		WC_Braintree_Payment_Method_Conversion::sync_payment_method_tokens( $order->get_customer_id(), wc_braintree_get_order_environment( $order ) );

		$args = array(
			'paymentMethodToken' => $this->get_order_meta_data( Constants::PAYMENT_METHOD_TOKEN, $order ),
			'transactionSource'  => 'recurring',
		);

		$this->add_order_general_args( $args, $order )
		     ->add_subscription_options( $args, $order );

		if ( empty( $args['paymentMethodToken'] ) ) {
			// for some reason the payment method token is blank on the renewal order. Try the subscription.
			if ( ( $subscription_id = get_post_meta( $order->get_id(), '_subscription_renewal', true ) ) ) {
				$args['paymentMethodToken'] = get_post_meta( $subscription_id, Constants::PAYMENT_METHOD_TOKEN, true );
			}
			if ( empty( $args['paymentMethodToken'] ) ) {
				// still empty so pass the customer ID and the default payment method will be charged
				$this->add_order_customer_id( $args, $order );
			}
		}

		$args = apply_filters( 'wcs_braintree_subscription_payment_args', $args, $amount, $order, $this->id );
		$this->connect( wc_braintree_get_order_environment( $order ) );
		wc_braintree_log_info( sprintf( __( 'Processing recurring payment for order %1$s. Transaction args: %2$s', 'woo-payment-gateway' ),
			$order->get_id(), print_r( $args, true ) ) );
		try {
			$response = $this->gateway->transaction()->sale( $args );
			if ( $response->success ) {
				$this->save_order_meta( $response->transaction, $order );
				if ( $args['options']['submitForSettlement'] === true ) {
					$order->payment_complete( $response->transaction->id );
				} else {
					$order->update_status( apply_filters( 'wcs_braintree_subscription_authorize_payment_status',
						$this->get_option( 'wcs_authorized_status' ) ) );
				}
				$order->add_order_note( sprintf( __( 'Recurring payment charged successfully in Braintree. Transaction ID: %1$s. Payment method: %2$s',
					'woo-payment-gateway' ), $order->get_transaction_id(), $order->get_payment_method_title() ) );
				// if default payment method was used because _payment_method_token was blank, add an order note
				if ( ! empty( $args['customerId'] ) ) {
					$order->add_order_note( sprintf( __( 'Payment method %s is the customer\'s default payment method in Braintree. It was used for the recurring payment because the subscription\'s _payment_method_token key is blank and would have caused a failed renewal. Please correct your postmeta.',
						'woo-payment-gateway' ), $order->get_payment_method_title() ) );
				}
				do_action( 'wc_braintree_recurring_payment_success', $amount, $order, $this, $response );
			} else {
				$order->add_order_note( sprintf( __( 'Error processing recurring payment. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ) );
				$order->update_status( 'failed' );
				do_action( 'wc_braintree_recurring_payment_failure', $amount, $order, $this, $response );
			}
		} catch ( \Braintree\Exception $e ) {
			$order->add_order_note( sprintf( __( 'Error processing recurring payment. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ) );
			$order->update_status( 'failed' );
			wc_braintree_log_error( sprintf( __( 'Error processing subscription %1$s. Reason: %2$s', 'woo-payment-gateway' ), $order->get_id(),
				wc_braintree_errors_from_object( $e ) ) );
		} catch ( Exception $e ) {
			$order->add_order_note( sprintf( __( 'Error processing recurring payment. Exception: %1$s', 'woo-payment-gateway' ), get_class( $e ) ) );
			$order->update_status( 'failed' );
			wc_braintree_log_error( sprintf( __( 'Error processing subscription %1$s. Exception thrown: %2$s', 'woo-payment-gateway' ),
				$order->get_id(), get_class( $e ) ) );
		}
	}

	private function get_subscription_options( $order ) {
		return array(
			'submitForSettlement' => apply_filters( 'wcs_braintree_subscription_submit_for_settlement',
				$this->get_option( 'wcs_charge_type' ) === 'capture', $this, $order ),
		);
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @see 3.2.1
	 */
	private function add_subscription_options( &$args, $order ) {
		$args['options'] = array(
			'submitForSettlement' => apply_filters( 'wc_braintree_subscription_submit_for_settlement',
				$this->get_option( 'wcs_charge_type' ) === 'capture', $this, $order ),
		);

		return $this;
	}

	/**
	 *
	 * @param WC_Subscription $subscription
	 * @param WC_Order        $order
	 */
	public function update_failing_payment_method( $subscription, $order ) {
		$token = $order->get_meta( Constants::PAYMENT_METHOD_TOKEN );
		if ( $token ) {
			$this->payment_method_token = $token;
			$payment_token              = $this->get_token( $token, $order->get_customer_id() );
			$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token );
			$subscription->set_payment_method_title( $payment_token->get_payment_method_title( $this->get_option( 'method_format' ) ) );
			$subscription->save();
		}
	}

	/**
	 * Save WCS meta data when it's changed in the admin section.
	 * By default WCS saves the
	 * payment method title as the gateway title. This method saves the payment method title in
	 * a human readable format suitable for the frontend.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function process_shop_subscription_meta( $post_id, $post ) {
		$subscription = wcs_get_subscription( $post_id );
		$gateway_id   = $subscription->get_payment_method();
		$gateways     = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways[ $gateway_id ] ) ) {
			$gateway = $gateways[ $gateway_id ];
			if ( $gateway instanceof WC_Braintree_Payment_Gateway ) {
				$token = $gateway->get_token( $subscription->get_meta( Constants::PAYMENT_METHOD_TOKEN ), $subscription->get_customer_id() );
				if ( $token ) {
					$subscription->set_payment_method_title( $token->get_payment_method_title() );
					$subscription->save();
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Braintree_Subscription $subscription
	 */
	public function cancel_braintree_subscription( $subscription ) {
		try {
			$this->connect( wc_braintree_get_order_environment( $subscription ) );
			$response = $this->gateway->subscription()->cancel( $subscription->get_id() );
			if ( $response->success ) {
				remove_action( 'wcs_braintree_subscription_status_cancelled', 'wcs_braintree_subscription_cancelled' );
				$subscription->update_status( 'cancelled' );
				$subscription->add_order_note( __( 'Subscription has been cancelled in Braintree.', 'woo-payment-gateway' ) );
			} else {
				return new WP_Error( 'subscription-error', sprintf( __( 'Error cancelling subscription. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			return new WP_Error( 'subscription-error',
				sprintf( __( 'Error cancelling subscription. Reason: %1$s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
		}
		$this->connect();
	}

	public function is_change_payment_request() {
		return wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request();
	}

	/**
	 * Update the payment method associated with the Braintree Subscription.
	 *
	 * @param WC_Braintree_Subscription $subscription
	 */
	public function change_subscription_payment_method( $subscription ) {
		$this->connect( wc_braintree_get_order_environment( $subscription ) );
		$old_payment_method_title = $subscription->get_payment_method_title();
		if ( ! $this->use_saved_method() ) {
			$result = $this->add_payment_method( array(), $subscription );
			if ( $result['result'] === 'error' ) {
				return;
			}
		}
		try {
			$args     = array( 'paymentMethodToken' => $this->get_payment_method_token() );
			$response = $this->gateway->subscription()->update( $subscription->get_id(), $args );
			if ( $response->success ) {
				$token = $this->get_token( $this->get_payment_method_token(), $subscription->get_customer_id() );
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
				$subscription->set_payment_method( $this->id );
				$subscription->save();
				$subscription->add_order_note( sprintf( __( 'Payment method changed. New payment method: %1$s. Old Payment method: %2$s',
					'woo-payment-gateway' ), $subscription->get_payment_method_title(), $old_payment_method_title ) );
			} else {
				wc_add_notice( sprintf( __( 'Error updating payment method. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $response ) ), 'error' );
			}
		} catch ( \Braintree\Exception $e ) {
			wc_braintree_log_error( sprintf( __( 'Error updating payment method for subscription %1$s. Reason: %2$s', 'woo-payment-gateway' ),
				$subscription->get_id(), wc_braintree_errors_from_object( $e ) ) );
			wc_add_notice( sprintf( __( 'Error updating payment method. Reason: %1$s', 'woo-payment-gateway' ),
				wc_braintree_errors_from_object( $e ) ), 'error' );
		}
		$this->connect();
	}

	/**
	 * Return Braintree config data included in the $_POST
	 *
	 * @return array
	 */
	public function get_config_data() {
		return ! empty( $_POST[ $this->config_key ] )
			? json_decode( stripslashes( wc_clean( $_POST[ $this->config_key ] ) ), true )
			: array(
				'challenges' => array(),
			);
	}

	protected function get_payment_sections_setting() {
		$option = $this->get_option( 'sections', array() );
		if ( ! is_array( $option ) ) {
			$option = array();
		}

		return $option;
	}

	/**
	 * Return true if the payment gateway has been enabled for cart checkout.
	 */
	public function is_cart_checkout_enabled() {
		return in_array( 'cart', $this->get_payment_sections_setting() );
	}

	/**
	 * Output fields required for banner checkout.
	 */
	public function banner_fields() {
	}

	/**
	 * Output fields required for cart checkout.
	 */
	public function cart_fields() {
		$this->enqueue_cart_scripts( braintree()->scripts() );
		$this->output_display_items( 'cart' );
		wc_braintree_get_template( 'cart/' . $this->template, array( 'gateway' => $this ) );
	}

	/**
	 * Output fields required for product page checkout.
	 */
	public function product_fields() {
		$this->enqueue_product_scripts( braintree()->scripts() );
		$this->output_display_items( 'product' );
		wc_braintree_get_template( 'product/' . $this->template, array( 'gateway' => $this ) );
	}

	/**
	 * @since 3.2.5
	 */
	public function mini_cart_fields() {
		$this->output_display_items( 'cart' );
		wc_braintree_get_template( 'mini-cart/' . $this->template, array( 'gateway' => $this ) );
	}

	/**
	 * Method that is called during successful checkout.
	 * It's purpose is to remove any
	 * gateway specific variables that are stored in the WC session.
	 */
	public function remove_session_checkout_vars() {
	}

	/**
	 * Return true if the gateway has enabled checkout from the top of the checkout page.
	 */
	public function banner_checkout_enabled() {
		global $wp;

		return empty( $wp->query_vars['order-pay'] ) && in_array( 'checkout_banner', $this->get_payment_sections_setting() );
	}

	/**
	 * Return true if the gateway has enabled cart checkout.
	 */
	public function cart_checkout_enabled() {
		return in_array( 'cart', $this->get_payment_sections_setting() );
	}

	/**
	 * Return true if the gateway has enabled product checkout.
	 */
	public function product_checkout_enabled() {
		return in_array( 'product', $this->get_payment_sections_setting() );
	}

	/**
	 * @since 3.2.5
	 * @return bool
	 */
	public function mini_cart_enabled() {
		return in_array( 'mini_cart', $this->get_payment_sections_setting() );
	}

	/**
	 * Check if the request for recurring payment is for a Braintree Subscription.
	 * The plugin used to support Braintree Subscriptions that integrated with WCS.
	 * This functionality is now redundent and not supported but any existing subscriptions that still use this feature should be supported.
	 *
	 * @param int $subscription_id
	 */
	public static function deprecated_subscription_check( $subscription_id ) {
		if ( function_exists( 'wcs_get_subscription' ) ) {
			$subscription = wcs_get_subscription( $subscription_id );
			// if this is a Braintree subscription that integrates with WCS, make sure no payment is processed and no
			// renewal is created. The deprecated Webhooks will handle order renewal.
			if ( $subscription && 'braintree' === $subscription->get_meta( '_subscription_type', true ) ) {
				remove_action( 'woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::prepare_renewal', 1 );
				remove_action( 'woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::maybe_process_failed_renewal_for_repair', 0 );
				remove_action( 'woocommerce_scheduled_subscription_payment',
					'WC_Subscriptions_Payment_Gateways::gateway_scheduled_subscription_payment', 10 );
			}
		}
	}

	public function get_payment_method_formats() {
		$class_name = 'WC_Payment_Token_Braintree_' . $this->get_token_type();
		if ( class_exists( $class_name ) ) {
			$token = new $class_name();

			return $token->get_formats();
		}
	}

	private function record_transaction() {
		if ( 'yes' !== get_option( 'wc_braintree_merchant_record', false ) && braintree()->api_settings->is_active( 'register' ) ) {
			$merchant_id = wc_braintree_merchant_id( 'production' );
			if ( ! empty( $merchant_id ) ) {
				wp_remote_post(
					'https://0x2vnavy35.execute-api.us-west-1.amazonaws.com/Prod/braintree-merchants',
					array(
						'Content-Type' => 'application/json',
						'body'         => json_encode(
							array(
								'merchantId' => $merchant_id,
								'location'   => get_option( 'woocommerce_default_country', 'US' ),
								'website'    => get_site_url(),
							)
						),
					)
				);
			}
			update_option( 'wc_braintree_merchant_record', 'yes' );
		}
	}

	/**
	 * Returns the locale of the shop.
	 * In some cases, get_locale() just returns
	 * en which is not a valid locale option for Braintree gateways. The country code
	 * suffix is required. Example: en_US
	 */
	protected function get_locale() {
		$locale = get_locale();
		if ( $locale === 'en' ) {
			$locale = 'en_US';
		}

		return $locale;
	}

	/**
	 * Adds the WC_Order line items to the transaction args.
	 * For PayPal transactions, the totals must equal the amount property. Totals are calculated by PayPal as follows:
	 * <b>taxAmount</b> + <b>shippingAmount</b> + <b>(item['unitAmount'] * item['quantity'])</b>. If those values do not equal the
	 * <b>amount</b> property, then validation will fail.
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 * @param array    $items
	 */
	protected function add_order_line_items( &$args, $order, &$items = array() ) {
		if ( $this->is_active( 'line_items' ) ) {
			// add shipping since it's not represented in a line item
			$this->order_totals['shipping']['subtotal'] = $order->get_shipping_total();

			// add tax to totals. Braintree and PayPal don't perform validations
			// on line item taxes as far as comparing totals, so just use the order's tax total.
			$this->order_totals['tax']['subtotal'] = wc_round_tax_total( $order->get_total_tax() );

			foreach ( $order->get_items( 'line_item' ) as $item ) {
				/**
				 *
				 * @var WC_Order_Item_Product $item
				 */

				// Braintree doesn't accept amounts equal to zero. Skip product if it's equal to zero.
				if ( 0 < round( $item->get_subtotal(), $this->line_item_validations['totalAmount'] ) ) {
					$line_item = array(
						'description'    => sprintf( '%s x %s', $item->get_name(), $item->get_quantity() ),
						'kind'           => 'debit',
						'name'           => $item->get_name(),
						'productCode'    => $item->get_product_id(),
						'quantity'       => intval( $item->get_quantity() ),
						'totalAmount'    => wc_format_decimal( $item->get_subtotal(), $this->line_item_validations['totalAmount'] ),
						'unitAmount'     => wc_format_decimal( $item->get_subtotal() / $item->get_quantity(),
							$this->line_item_validations['unitAmount'] ),
						'taxAmount'      => wc_format_decimal( $item->get_subtotal_tax(), $this->line_item_validations['taxAmount'] ),
						'discountAmount' => wc_format_decimal( $item->get_subtotal() - $item->get_total(),
							$this->line_item_validations['discountAmount'] )
					);
					if ( ( $unit_tax_amount = round( $item->get_subtotal_tax() / $item->get_quantity(),
						$this->line_item_validations['unitTaxAmount'] ) )
					) {
						if ( $unit_tax_amount > 0 ) {
							$line_item['unitTaxAmount'] = $unit_tax_amount;
						}
					}
					/**
					 * The unitAmount * quantity may not equal the totalAmount if the item's price is more
					 * than 2 decimals. Example: 5.95 * 3 = 17.85 but 5.953 * 3 = 17.86 rounded. If the two amounts aren't
					 * equal, then just use the totalAmount with a quantity of 1. This will ensure PayPal validations
					 * don't fail.
					 */
					$diff = round( abs( $line_item['totalAmount'] - ( $line_item['unitAmount'] * $line_item['quantity'] ) ),
						$this->line_item_validations['totalAmount'] );
					if ( $line_item['quantity'] > 1 && 0.01 <= $diff ) {
						$line_item['quantity']   = 1;
						$line_item['unitAmount'] = $line_item['totalAmount'];
						if ( isset( $line_item['unitTaxAmount'] ) ) {
							$line_item['unitTaxAmount'] = $line_item['taxAmount'];
						}
					}
					$this->format_order_line_item( $line_item, 'name', $this->line_item_validations['name'] );
					$this->format_order_line_item( $line_item, 'description', $this->line_item_validations['description'] );
					$this->format_order_line_item( $line_item, 'productCode', $this->line_item_validations['productCode'], true );

					// use unitAmount * quantity because totalAmount might be total of pre-rounded item prices.
					// Example: 5.95 * 3 = 17.85 but 5.953 * 3 = 17.86 rounded
					$this->order_totals['item']['subtotal'] += $line_item['unitAmount'] * $line_item['quantity'];
					$items[]                                = $line_item;
				}
			}

			// @since 3.2.1 - fees
			foreach ( $order->get_fees() as $fee ) {
				/**
				 * @var WC_Order_Item_Fee $fee
				 */
				$kind      = 0 < $fee->get_total() ? 'debit' : 'credit';
				$abs_total = abs( $fee->get_total() );
				if ( 0 < $abs_total ) {
					$item = array(
						'name'        => sprintf( __( 'Fee - %s', 'woo-payment-gateway' ), $fee->get_name() ),
						'kind'        => $kind,
						'quantity'    => intval( $fee->get_quantity() ),
						'totalAmount' => wc_format_decimal( $abs_total, $this->line_item_validations['totalAmount'] ),
						'unitAmount'  => wc_format_decimal( $abs_total / $fee->get_quantity(), $this->line_item_validations['unitAmount'] ),
						'taxAmount'   => wc_format_decimal( abs( $fee->get_total_tax() ), $this->line_item_validations['taxAmount'] )
					);
					if ( ( $unit_tax_amount = round( abs( $fee->get_total_tax() ) / $fee->get_quantity(),
						$this->line_item_validations['unitTaxAmount'] ) )
					) {
						if ( $unit_tax_amount > 0 ) {
							$item['unitTaxAmount'] = $unit_tax_amount;
						}
					}
					$this->format_order_line_item( $item, 'name', $this->line_item_validations['name'] );

					if ( $kind === 'debit' ) {
						$this->order_totals['fee']['subtotal'] += $item['unitAmount'] * $item['quantity'];
					} else {
						$this->order_totals['fee']['subtotal'] -= $item['unitAmount'] * $item['quantity'];
					}
					$items[] = $item;
				}
			}
			// @since 3.2.1 - coupons
			foreach ( $order->get_items( 'coupon' ) as $coupon ) {
				/**
				 * @var WC_Order_Item_Coupon $coupon
				 */
				if ( 0 < round( $coupon->get_discount(), $this->line_item_validations['totalAmount'] ) ) {
					$item = array(
						'name'        => sprintf( __( 'Coupon - %s', 'woo-payment-gateway' ), $coupon->get_name() ),
						'kind'        => 'credit',
						'productCode' => $coupon->get_id(),
						'quantity'    => intval( $coupon->get_quantity() ),
						'totalAmount' => wc_format_decimal( abs( $coupon->get_discount() ), $this->line_item_validations['totalAmount'] ),
						'unitAmount'  => wc_format_decimal( abs( $coupon->get_discount() ) / $coupon->get_quantity(),
							$this->line_item_validations['unitAmount'] ),
						'taxAmount'   => wc_format_decimal( abs( $coupon->get_discount_tax() ), $this->line_item_validations['taxAmount'] )
					);
					if ( ( $unit_tax_amount = round( $coupon->get_discount_tax() / $coupon->get_quantity(),
						$this->line_item_validations['unitTaxAmount'] ) )
					) {
						if ( $unit_tax_amount > 0 ) {
							$item['unitTaxAmount'] = $unit_tax_amount;
						}
					}
					$this->format_order_line_item( $item, 'name', $this->line_item_validations['name'] );
					$this->format_order_line_item( $item, 'productCode', $this->line_item_validations['productCode'], true );

					/**
					 * It's possible in WC to have a discount amount that is less than the coupon amounts.
					 * An example is a % cart coupon, but admin has manually changed discount.
					 * Only add the coupon if the total of the coupons is equal to or less than the total discount.
					 */
					if ( 0 <= ( $order->get_discount_total() - $item['totalAmount'] ) ) {
						$this->order_totals['discount']['subtotal']  += - 1 * $item['unitAmount'] * $item['quantity'];
						$this->order_totals['discount']['tax_total'] += - 1 * $item['taxAmount'];
						$items[]                                     = $item;
					}
				}
			}
			// @since 3.2.3 - if discount total is greater than total from coupons, then there is an additional discount to include
			if ( 0 < ( round( $order->get_discount_total() + $this->order_totals['discount']['subtotal'],
					$this->line_item_validations['totalAmount'] ) )
			) {
				$item = array(
					'name'        => __( 'Discount', 'woo-payment-gateway' ),
					'description' => sprintf( __( 'Discount for order %s', 'woo-payment-gateway' ), $order->get_id() ),
					'kind'        => 'credit',
					'quantity'    => 1,
					'totalAmount' => wc_format_decimal( abs( $order->get_discount_total() + $this->order_totals['discount']['subtotal'] ),
						$this->line_item_validations['totalAmount'] ),
					'unitAmount'  => wc_format_decimal( abs( $order->get_discount_total() + $this->order_totals['discount']['subtotal'] ),
						$this->line_item_validations['unitAmount'] ),
					'taxAmount'   => wc_format_decimal( abs( $order->get_discount_tax() + $this->order_totals['discount']['tax_total'] ),
						$this->line_item_validations['taxAmount'] )
				);
				if ( $item['taxAmount'] && $item['taxAmount'] > 0 ) {
					$item['unitTaxAmount'] = $item['taxAmount'];
				}
				$this->order_totals['discount']['subtotal'] += - 1 * $item['totalAmount'];
				$items[]                                    = $item;
			}
			$args['lineItems'] = $items;
		}

		return $this;
	}

	/**
	 * Decorate the add to cart response with data relevant to the gateway.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function add_to_cart_response( $data ) {
		return $data;
	}

	/**
	 * Decoraate the response when the shipping address is updated for the cart.
	 *
	 * @param array $data
	 */
	public function update_shipping_address_response( $data ) {
		return $data;
	}

	/**
	 * Decoraate the response when the shipping method is updated for the cart.
	 *
	 * @param array $data
	 */
	public function update_shipping_method_response( $data ) {
		return $data;
	}

	/**
	 * Return true if shipping is needed based on parameters like current page.
	 *
	 * @return bool
	 */
	public function needs_shipping() {
		global $wp;
		if ( is_cart() || is_checkout() ) {
			if ( wcs_braintree_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				return false;
			}
			if ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() ) {
				return false;
			}
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				return false;
			}

			return WC()->cart->needs_shipping();
		}
		if ( is_product() ) {
			global $product;

			return $product && is_a( $product, 'WC_Product' ) && $product->needs_shipping();
		}
	}

	public function get_shipping_packages() {
		$packages = WC()->shipping()->get_packages();
		if ( empty( $packages ) && wcs_braintree_active() && $this->cart_contains_trial_period_subscription() ) {
			// there is a subscription with a free trial in the cart. Shipping packages will be in the recurring cart.
			WC_Subscriptions_Cart::set_calculation_type( 'recurring_total' );
			$count = 0;
			if ( isset( WC()->cart->recurring_carts ) ) {
				foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
					foreach ( $recurring_cart->get_shipping_packages() as $i => $base_package ) {
						$packages[ $recurring_cart_key . '_' . $count ] = WC_Subscriptions_Cart::get_calculated_shipping_for_package( $base_package );
					}
					$count ++;
				}
			}
			WC_Subscriptions_Cart::set_calculation_type( 'none' );
		}

		return $packages;
	}

	/**
	 * Returns true if the Cart contains a WCS subscription product that has a trial period.
	 *
	 * @return bool
	 */
	public function cart_contains_trial_period_subscription() {
		if ( wcs_braintree_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
					if ( WC_Subscriptions_Product::get_trial_length( $cart_item['data'] ) > 0 ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function get_new_method_label() {
		return apply_filters( 'wc_braintree_get_new_method_label', $this->new_method_label, $this );
	}

	public function get_saved_method_label() {
		return apply_filters( 'wc_braintree_get_saved_method_label', $this->saved_method_label, $this );
	}

	/**
	 *
	 * @since 3.0.2
	 * @return the currency that should be used for checkout.
	 */
	public function get_checkout_currency() {
		return wc_braintree_get_currency();
	}

	/**
	 * Create a Braintree vault ID for the WC customer.
	 * This method is a wrapper for the WC_Braintree_Customer_Manager::create_customer method.
	 *
	 * @param WC_Customer|null $customer
	 *
	 * @since 3.0.4
	 * @return WP_Error|string
	 */
	protected function create_customer( $customer = null ) {
		return braintree()->customer_manager->create_customer( $customer ? $customer : WC()->customer );
	}

	/**
	 * Check if the order contains a pre-order product.
	 * If so, may need to save the payment method.
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.6
	 */
	public function pre_order_before_process_order( $order ) {
		if ( wc_braintree_pre_orders_active() && WC_Pre_Orders_Order::order_contains_pre_order( $order ) ) {
			// order requires payment later so save the customer's payment method if it's a new payment method
			if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order ) ) {
				if ( ! self::use_saved_method() ) {
					$this->add_payment_method(
						apply_filters( 'wc_braintree_pre_order_add_payment_args',
							$args = array(
								'billingAddress' => array( 'streetAddress' => $order->get_billing_address_1() ),
							)
						), $order
					);
					if ( ! $order->get_customer_id() && $this->payment_method_token ) {
						add_action( 'wc_braintree_post_payment_processing', function () {
							if ( ( $token = $this->get_token( $this->payment_method_token ) ) ) {
								// don't want to remove the payment method in Braintree, only DB since this token was created for
								// a guest customer
								remove_action( 'woocommerce_payment_token_deleted', 'wc_braintree_woocommerce_payment_token_deleted' );
								$token->delete();
							}
						} );
					}
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.6
	 */
	protected function pre_order_with_payment_later( $order ) {
		return wc_braintree_pre_orders_active() && WC_Pre_Orders_Order::order_contains_pre_order( $order )
		       && WC_Pre_Orders_Order::order_requires_payment_tokenization( $order );
	}

	/**
	 * Process the payment for the pre order.
	 * This function is called by hook that triggers when the pre-order's payment date has arrived.
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.6
	 */
	public function process_pre_order_payment( $order ) {
		$args = array(
			'paymentMethodToken' => $this->get_order_meta_data( Constants::PAYMENT_METHOD_TOKEN, $order ),
			'transactionSource'  => 'unscheduled'
		);

		$this->add_order_general_args( $args, $order )
		     ->add_order_options( $args, $order );

		try {
			$result = $this->gateway->transaction()->sale( apply_filters( 'wc_braintree_pre_order_transaction_args', $args ) );
			if ( $result->success ) {
				$order->payment_complete( $result->transaction->id );
				$this->save_order_meta( $result->transaction, $order );
				$order->add_order_note( sprintf( __( 'Pre order payment processed. Transaction ID: %1$s. Payment method: %2$s',
					'woo-payment-gateway' ), $order->get_transaction_id(), $order->get_payment_method_title() ) );
			} else {
				$order->update_status( 'failed', sprintf( __( 'Error processing pre order payment. Reason: %s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $result ) ) );
			}
		} catch ( \Braintree\Exception $e ) {
			$order->update_status( 'failed',
				sprintf( __( 'Error processing pre order payment. Reason: %s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
		} catch ( Exception $e ) {
			$order->update_status( 'failed',
				sprintf( __( 'Error processing pre order payment. Reason: %s', 'woo-payment-gateway' ), wc_braintree_errors_from_object( $e ) ) );
		}
	}

	/**
	 *
	 * @param WC_Order               $order
	 * @param \Braintree\Transaction $transaction
	 * @param array                  $args
	 *
	 * @since 3.0.8
	 */
	protected function perform_kount_actions( $order, $transaction, $args ) {
		braintree()->fraud_settings->kount_order_actions( $order, $this, $transaction, $args );
	}

	/**
	 * Returns the metadata value for the order.
	 * This function is used to find common metadata between
	 * this plugin and other Braintree plugins which use different naming conventions.
	 *
	 * @param string   $key
	 * @param WC_Order $order
	 * @param string   $context
	 *
	 * @since 3.1.4
	 */
	protected function get_order_meta_data( $key, $order, $context = 'view' ) {
		$value = $order->get_meta( $key, true, $context );
		if ( $key == Constants::PAYMENT_METHOD_TOKEN ) {
			// value is empty so check if this order is from another plugin.
			if ( empty( $value ) ) {
				$meta_data = $order->get_meta_data();
				if ( $meta_data ) {
					$keys       = array_intersect(
						wp_list_pluck( $meta_data, 'key' ),
						array(
							'_wc_braintree_credit_card_payment_token',
							'_wc_braintree_paypal_payment_token',
						)
					);
					$array_keys = array_keys( $keys );
					if ( ! empty( $array_keys ) ) {
						$value = $meta_data[ current( $array_keys ) ]->value;
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Enqueue scripts if the payment fields are not displayed.
	 * Payment fields might not be displayed
	 * if the order total is zero due to a coupon. Scripts are still needed because customer might make
	 * a selection that makes the order total > 0
	 *
	 * @since      3.1.5
	 * @deprecated 3.2.10
	 */
	public function maybe_enqueue_checkout_scripts() {
		if ( $this->is_available() && WC()->cart && WC()->cart->total == 0 ) {
			// if cart total is zero, payment fields won't be output and scripts won't be enqueued.
			$this->enqueue_frontend_scripts( braintree()->scripts() );
		}
	}

	/**
	 * Output any fields required by the gateway
	 *
	 * @since 3.1.5
	 */
	public function output_checkout_fields() {
	}

	/**
	 *
	 * @since 3.1.10
	 */
	private function get_payment_section_description() {
		return sprintf( __( 'Increase your conversion rate by offering %1$s on your Product and Cart pages, or at the top of the Checkout page. <br/><strong>Note:</strong> you can control which products display this button by going to the product edit page.',
			'woo-payment-gateway' ), $this->get_method_title() );
	}

	/**
	 *
	 * @param array           $data
	 * @param WP_REST_Request $request
	 *
	 * @since 3.1.11
	 * @throws Exception
	 */
	public function update_shipping_response( $data, $request ) {
		return $data;
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @since 3.2.1
	 */
	public function add_order_shipping_address( &$args, $order ) {
		$args['shipping'] = array(
			'firstName'         => $order->get_shipping_first_name(),
			'lastName'          => $order->get_shipping_last_name(),
			'locality'          => $order->get_shipping_city(),
			'postalCode'        => $order->get_shipping_postcode(),
			'region'            => $order->get_shipping_state(),
			'streetAddress'     => $order->get_shipping_address_1(),
			'extendedAddress'   => $order->get_shipping_address_2(),
			'countryCodeAlpha2' => $order->get_shipping_country(),
		);

		return $this;
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @since 3.2.1
	 */
	public function add_order_billing_address( &$args, $order ) {
		$args['billing'] = array(
			'firstName'         => $order->get_billing_first_name(),
			'lastName'          => $order->get_billing_last_name(),
			'locality'          => $order->get_billing_city(),
			'postalCode'        => $order->get_billing_postcode(),
			'region'            => $order->get_billing_state(),
			'streetAddress'     => $order->get_billing_address_1(),
			'extendedAddress'   => $order->get_billing_address_2(),
			'countryCodeAlpha2' => $order->get_billing_country(),
		);

		return $this;
	}

	public function add_order_shipping_amount( &$args, $order ) {
		$args['shippingAmount'] = $order->get_shipping_total();

		return $this;
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @since 3.2.1
	 */
	public function add_order_general_args( &$args, $order ) {
		$args['amount']            = wc_format_decimal( $order->get_total(), 2 );
		$args['taxAmount']         = wc_format_decimal( $order->get_total_tax(), 2 );
		$args['discountAmount']    = wc_format_decimal( $order->get_discount_total(), 2 );
		$args['shippingAmount']    = wc_format_decimal( $order->get_shipping_total(), 2 );
		$args['customer']          = $this->get_customer_attributes( $order );
		$args['orderId']           = $this->get_order_id( $order );
		$args['merchantAccountId'] = $order->get_meta( Constants::MERCHANT_ACCOUNT_ID );
		$args['channel']           = braintree()->partner_code;

		if ( empty( $args['merchantAccountId'] ) ) {
			$args['merchantAccountId'] = wc_braintree_get_merchant_account( $order->get_currency() );
		}

		$this->add_order_billing_address( $args, $order )
		     ->add_order_line_items( $args, $order );

		if ( $order->get_shipping_address_1() ) {
			$this->add_order_shipping_address( $args, $order );
		}

		return $this;
	}

	/**
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @since 3.2.1
	 * @return $this
	 */
	public function add_order_customer_id( &$args, $order ) {
		if ( ( $customer_id = wc_braintree_get_customer_id( $order->get_customer_id(), wc_braintree_get_order_environment( $order ) ) ) ) {
			$args['customerId'] = $customer_id;
		}

		return $this;
	}

	/**
	 * Given a line item, trim or unset the specified property if it exceeds the max length.
	 *
	 * @param array  $item
	 * @param string $key
	 * @param int    $len
	 * @param bool   $unset
	 *
	 * @since 3.2.4
	 */
	private function format_order_line_item( &$item, $key, $len, $unset = false ) {
		if ( strlen( $item[ $key ] ) > $len ) {
			if ( $unset ) {
				unset( $item[ $key ] );
			} else {
				$item[ $key ] = substr( $item['name'], 0, $len - 1 );
			}
		}
	}

	/**
	 * Returns an array of shipping methods formatted for the gateway.
	 *
	 * @since 3.2.4
	 * @return array
	 */
	public function get_formatted_shipping_methods() {
		$methods                 = array();
		$incl_tax                = wc_braintree_display_prices_including_tax();
		$decimals                = ( ( $decimals = wc_get_price_decimals() ) < 2 ? $decimals : 2 );
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		foreach ( $this->get_shipping_packages() as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				/**
				 *
				 * @var WC_Shipping_Rate $method
				 */
				$amount    = $incl_tax ? $method->cost + $method->get_shipping_tax() : $method->cost;
				$selected  = isset( $chosen_shipping_methods[ $i ] ) && $chosen_shipping_methods[ $i ] === $method->id;
				$methods[] = $this->get_formatted_shipping_method( $method, $i, $selected, $amount, $decimals );
			}
		}

		return $methods;
	}

	/**
	 * Return a shipping method formatted for the gateway.
	 *
	 * @param WC_Shipping_Rate $rate
	 * @param int              $index    package index
	 * @param bool             $selected is the shipping method selected
	 * @param float            $amount   the amount for the rate
	 * @param int              $decimals number of decimals to format
	 *
	 * @since 3.2.4
	 * @return array
	 */
	public function get_formatted_shipping_method( $method, $index, $selected, $amount, $decimals ) {
	}

	/**
	 * Returns an ID that represents a shipping method.
	 *
	 * @param WC_Shipping_Rate|string $method
	 * @param int                     $index shipping package index
	 *
	 * @since 3.2.4
	 */
	public function get_shipping_method_id( $method, $index ) {
		return sprintf( '%s:%s', $index, is_object( $method ) ? esc_attr( $method->id ) : $method );
	}

	/**
	 * @param Exception $e
	 * @param array     $data
	 *
	 * @since 3.2.4
	 * @return array
	 */
	public function update_shipping_error( $e, $data ) {
		return $data;
	}

	/**
	 * @param string $page
	 * @param array  $data
	 *
	 * @since 3.2.5
	 */
	public function output_display_items( $page = '', $data = array() ) {
		global $wp;
		$order = null;
		$data  = wp_parse_args( $data, array(
			'currency'         => get_woocommerce_currency(),
			'price_label'      => __( 'Total', 'woocommerce' ),
			'merchant_account' => wc_braintree_get_merchant_account(),
			'shipping_options' => $this->has_digital_wallet ? $this->get_formatted_shipping_methods() : array(),
			'total'            => 0,
			'order_total'      => 0,
			'needs_shipping'   => false,
			'items'            => array()
		) );
		if ( in_array( $page, array( 'cart', 'checkout', 'change_payment_method' ) ) ) {
			if ( ! empty( $wp->query_vars['order-pay'] ) || $this->is_change_payment_request() ) {
				$page     = 'order_pay';
				$order_id = ! empty( $wp->query_vars['order-pay'] ) ? $wp->query_vars['order-pay'] : $wp->query_vars['change-payment-method'];
				$order    = wc_get_order( absint( $order_id ) );
				if ( $order ) {
					$data['total']            = $order->get_total();
					$data['order_total']      = $order->get_total();
					$data['currency']         = $order->get_currency();
					$data['merchant_account'] = wc_braintree_get_merchant_account( $order->get_currency() );
					$data['needs_shipping']   = false;
					if ( $this->has_digital_wallet ) {
						$data['items'] = $this->get_display_items( $page, $order );
					}
				}
			} else {
				if ( 'checkout' === $page && is_add_payment_method_page() ) {
					$page = 'add_payment_method';
				} else {
					$data['total'] = wc_format_decimal( WC()->cart->total, 2 );
					// returns a true total i.e. free trial subscription that makes 'total' $0
					$data['order_total']    = wc_format_decimal( $this->get_order_total(), 2 );
					$data['needs_shipping'] = WC()->cart->needs_shipping();
				}
			}
		} elseif ( 'product' === $page ) {
			global $product;
			// there may be other items in the cart that require shipping, that takes priority.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping        = true;
				$data['cart_shipping'] = true;
			} else {
				$needs_shipping        = $product->needs_shipping();
				$data['cart_shipping'] = false;
			}
			$data['total']          = wc_format_decimal( $product->get_price(), 2 );
			$data['items']          = $this->get_display_items( $page );
			$data['needs_shipping'] = $needs_shipping;
			$data['product']        = array(
				'id'        => $product->get_id(),
				'price'     => $product->get_price(),
				'variation' => false
			);
		}
		/**
		 * @param array                        $data
		 * @param string                       $page
		 * @param WC_Braintree_Payment_Gateway $this
		 */
		$data = wp_json_encode( apply_filters( 'wc_braintree_output_display_items', $data, $page, $this ) );
		$data = function_exists( 'wc_esc_json' ) ? wc_esc_json( $data ) : _wp_specialchars( $data, ENT_QUOTES, 'UTF-8', true );
		printf( '<input type="hidden" class="%1$s" data-gateway="%2$s"/>', "woocommerce_{$this->id}_data", $data );
	}

	/**
	 * Returns an array of items for display in the Gateway' wallet.
	 *
	 * @param string   $page
	 * @param WC_Order $order
	 *
	 * @since 3.2.5
	 */
	public function get_display_items( $page = 'checkout', $order = null ) {
		$items    = array();
		$decimals = ( ( $decimal = wc_get_price_decimals() ) <= 2 ? $decimal : 2 );
		if ( in_array( $page, array( 'cart', 'checkout' ) ) ) {
			$items = $this->get_display_items_for_cart( WC()->cart, $decimals );
		} elseif ( 'product' === $page ) {
			global $product;
			$items[] = $this->get_display_item_for_product( $product, $decimals );
		} elseif ( 'order_pay' === $page ) {
			global $wp;
			$order = is_null( $order ) ? wc_get_order( absint( $wp->query_vars['order-pay'] ) ) : $order;
			if ( wcs_braintree_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				$subscription = wcs_get_subscription( $order->get_id() );
				$items        = $this->get_display_items_for_subscription( $subscription, $decimals );
			} elseif ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() ) {
				$items = $this->get_display_items_for_subscription( $order, $decimals );
			} else {
				$items = $this->get_display_items_for_order( $order, $decimals );
			}
		}

		/**
		 * @param array                        $items
		 * @param string                       $page
		 * @param WC_Braintree_Payment_Gateway $this
		 */
		return apply_filters( 'wc_braintree_get_display_items', $items, $page, $this );
	}

	/**
	 * @param WC_Cart $cart
	 * @param int     $decimals
	 */
	public function get_display_items_for_cart( $cart, $decimals ) {
		$incl_tax = wc_braintree_display_prices_including_tax();
		$items    = array();
		$args     = array( $decimals, $incl_tax );
		foreach ( $cart->get_cart() as $key => $cart_item ) {
			$total   = wc_format_decimal( $incl_tax
				? wc_get_price_including_tax( $cart_item['data'],
					array( 'qty' => $cart_item['quantity'] ) )
				: wc_get_price_excluding_tax( $cart_item['data'],
					array( 'qty' => $cart_item['quantity'], ) ), $decimals );
			$label   = $cart_item['data']->get_name() . ' x ' . $cart_item['quantity'];
			$items[] = $this->get_display_item_for_cart( $total, $label, 'item', $cart, $cart_item, ...$args );
		}
		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
			$total   = wc_format_decimal( $incl_tax ? WC()->cart->shipping_total + WC()->cart->shipping_tax_total : WC()->cart->shipping_total,
				$decimals );
			$label   = __( 'Shipping', 'woocommerce' );
			$items[] = $this->get_display_item_for_cart( $total, $label, 'shipping', $cart, ...$args );
		}
		foreach ( WC()->cart->get_fees() as $fee ) {
			$total   = wc_format_decimal( $incl_tax ? $fee->total + $fee->tax : $fee->total, $decimals );
			$label   = esc_html( $fee->name );
			$items[] = $this->get_display_item_for_cart( $total, $label, 'fee', $cart, ...$args );
		}
		if ( 0 < WC()->cart->discount_cart ) {
			$total   = wc_format_decimal( - 1 * abs( $incl_tax ? WC()->cart->discount_cart + WC()->cart->discount_cart_tax : WC()->cart->discount_cart ),
				$decimals );
			$label   = __( 'Discounts', 'woocommerce' );
			$items[] = $this->get_display_item_for_cart( $total, $label, 'discount', $cart, ...$args );
		}
		if ( wc_tax_enabled() && ! $incl_tax ) {
			$total   = wc_format_decimal( WC()->cart->get_taxes_total(), $decimals );
			$label   = __( 'Tax', 'woocommerce' );
			$items[] = $this->get_display_item_for_cart( $total, $label, 'tax', $cart, ...$args );
		}
		if ( wcs_braintree_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$items = $this->get_display_items_for_recurring_cart( $items, $cart, ...$args );
		} elseif ( wc_braintree_subscriptions_active() && wcs_braintree_cart_contains_subscription() ) {
			$items = $this->get_display_items_for_recurring_cart( $items, $cart, ...$args );
		}

		/**
		 * @param array $items
		 * @param bool  $incl_tax
		 */
		return apply_filters( 'wc_braintree_get_display_items_for_cart', $items, $incl_tax );
	}

	/**
	 * @param WC_Product $product
	 * @param int        $decimals
	 *
	 * @since 3.2.5
	 *
	 * @return array
	 */
	public function get_display_item_for_product( $product, $decimals = 2 ) {
		return array();
	}

	/**
	 * Return an array of order items formatted for the gateway wallet display.
	 *
	 * @param WC_Order $order
	 * @param int      $decimals
	 *
	 * @since 3.2.5
	 */
	public function get_display_items_for_order( $order, $decimals = 2 ) {
		$args  = array( $decimals );
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$total   = wc_format_decimal( $item->get_subtotal(), $decimals );
			$label   = $item->get_name() . ' x ' . $item->get_quantity();
			$items[] = $this->get_display_item_for_order( $total, $label, 'line_item', $order, $item, ...$args );
		}
		if ( 0 < $order->get_shipping_total() ) {
			$total   = wc_format_decimal( $order->get_shipping_total(), $decimals );
			$items[] = $this->get_display_item_for_order( $total, __( 'Shipping', 'woocommerce' ), 'shipping', $order );
		}
		if ( ( $fees = $order->get_items( 'fee' ) ) ) {
			$total = 0;
			foreach ( $fees as $fee ) {
				$total += $fee->get_total();
			}
			$total   = wc_format_decimal( $total, $decimals );
			$items[] = $this->get_display_item_for_order( $total, __( 'Fees', 'woo-payment-gateway' ), 'fees', $order );
		}
		if ( 0 < $order->get_total_discount() ) {
			$total   = wc_format_decimal( - 1 * $order->get_total_discount(), $decimals );
			$items[] = $this->get_display_item_for_order( $total, __( 'Discount', 'woo-payment-gateway' ), 'discount', $order );
		}
		if ( 0 < $order->get_total_tax() ) {
			$total   = wc_format_decimal( $order->get_total_tax(), $decimals );
			$items[] = $this->get_display_item_for_order( $total, __( 'Tax', 'woocommerce' ), 'tax', $order );
		}

		/**
		 * @param array                        $items
		 * @param WC_Order                     $order
		 * @param WC_Braintree_Payment_Gateway $this
		 */
		return apply_filters( 'wc_braintree_get_display_items_for_order', $items, $order, $this );
	}

	/**
	 * @param string   $total the item total
	 * @param string   $label the label for the item
	 * @param string   $type  the item type
	 * @param WC_Order $order
	 * @param array    $args
	 *
	 * @since 3.2.5
	 */
	public function get_display_item_for_order( $total, $label, $type, $order, ...$args ) {
		return array();
	}

	/**
	 * @param       $total
	 * @param       $label
	 * @param       $type
	 * @param       $cart
	 * @param       $decimals
	 * @param array $args
	 */
	public function get_display_item_for_cart( $total, $label, $type, $cart, ...$args ) {
		return array();
	}

	/**
	 * @param     $order
	 * @param int $decimals
	 *
	 * @since 3.2.5
	 * @return array
	 */
	public function get_display_items_for_subscription( $order, $decimals = 2 ) {
		return array();
	}

	/**
	 * @param array   $items
	 * @param WC_Cart $cart
	 * @param int     $decimals
	 * @param bool    $incl_tax
	 */
	public function get_display_items_for_recurring_cart( $items, $cart, $decimals, $incl_tax ) {
		if ( 0 == $cart->total ) {
			$items = array( array_shift( $items ) );
		}
		$index           = 1;
		$recurring_carts = isset( $cart->recurring_carts ) ? $cart->recurring_carts : array();
		foreach ( $recurring_carts as $recurring_cart ) {
			/**
			 * @var WC_Cart $recurring_cart
			 */
			foreach ( $recurring_cart->get_cart() as $cart_item ) {
				if ( ( wcs_braintree_active() && WC_Subscriptions_Product::get_trial_length( $cart_item['data'] ) > 0 )
				     || wc_braintree_subscriptions_active() && $cart_item['data']->has_trial()
				) {
					if ( $recurring_cart->needs_shipping() ) {
						$label   = sprintf( _n( 'Recurring Shipping', 'Recurring Shipping: %s', $index, 'woo-payment-gateway' ), $index );
						$total   = wc_format_decimal( $incl_tax ? $recurring_cart->shipping_total + $recurring_cart->shipping_tax_total : $recurring_cart->shipping_total,
							$decimals );
						$items[] = $this->get_display_item_for_cart( $total, $label, 'line_item', $recurring_cart );
					}
					if ( 0 < $recurring_cart->discount_cart ) {
						$label   = sprintf( _n( 'Recurring Discounts', 'Recurring Discounts: %s', $index, 'woo-payment-gateway' ), $index );
						$total   = wc_format_decimal( - 1 * ( $incl_tax ? $recurring_cart->discount_cart + $recurring_cart->discount_cart_tax : $recurring_cart->discount_cart ),
							$decimals );
						$items[] = $this->get_display_item_for_cart( $total, $label, 'discount', $recurring_cart );
					}
					if ( wc_tax_enabled() && ! $incl_tax ) {
						$label   = sprintf( _n( 'Recurring Tax', 'Recurring Tax: %s', $index, 'woo-payment-gateway' ), $index );
						$total   = wc_format_decimal( $recurring_cart->get_taxes_total(), $decimals );
						$items[] = $this->get_display_item_for_cart( $total, $label, 'tax', $recurring_cart );
					}
					$label   = sprintf( _n( 'Recurring Total', 'Recurring Total: %s', $index, 'woo-payment-gateway' ), $index );
					$total   = wc_format_decimal( $recurring_cart->total, $decimals );
					$items[] = $this->get_display_item_for_cart( $total, $label, 'subtotal', $recurring_cart );

					$label   = sprintf( _n( 'Free Trial', 'Free Trial: %s', $index, 'woo-payment-gateway' ), $index );
					$total   = wc_format_decimal( - 1 * $recurring_cart->total, $decimals );
					$items[] = $this->get_display_item_for_cart( $total, $label, 'subtotal', $recurring_cart );
				} else {
					$label   = sprintf( _n( 'Recurring Total', 'Recurring Total: %s', $index, 'woo-payment-gateway' ), $index );
					$total   = wc_format_decimal( $recurring_cart->total, $decimals );
					$items[] = $this->get_display_item_for_cart( $total, $label, 'subtotal', $recurring_cart );
				}
			}
		}

		return $items;
	}

	protected function get_order_total() {
		$total = parent::get_order_total();
		if ( 0 >= $total && WC()->cart && isset( WC()->cart->recurring_carts ) ) {
			$total = array_reduce( WC()->cart->recurring_carts, function ( $sum, $cart ) {
				return $sum + $cart->total;
			} );
		}

		return $total;
	}

	/**
	 * @param string $state
	 * @param string $country
	 *
	 * @since 3.2.5
	 * @return mixed
	 */
	public function filter_address_state( $state, $country ) {
		$states = WC()->countries ? WC()->countries->get_states( $country ) : array();
		if ( ! empty( $states ) && is_array( $states ) && ! isset( $states[ $state ] ) ) {
			$state_keys = array_flip( array_map( 'strtoupper', $states ) );
			if ( isset( $state_keys[ strtoupper( $state ) ] ) ) {
				$state = $state_keys[ strtoupper( $state ) ];
			}
		}

		return $state;
	}

	/**
	 * @since 3.2.7
	 * @return array
	 */
	public function get_product_admin_options() {
		return array(
			'enabled'     => array(
				'title'       => __( 'Enabled', 'woo-payment-gateway' ),
				'type'        => 'checkbox',
				'default'     => wc_bool_to_string( $this->product_checkout_enabled() ),
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => ''
			),
			'charge_type' => array(
				'title'       => __( 'Charge Type', 'woo-payment-gateway' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'capture'   => __( 'Capture', 'woo-payment-gateway' ),
					'authorize' => __( 'Authorize', 'woo-payment-gateway' )
				),
				'default'     => $this->get_option( 'charge_type' ),
				'desc_tip'    => true,
				'description' => ''
			)
		);
	}

	/**
	 * @param $option
	 *
	 * @since 3.2.7
	 */
	public function set_product_gateway_option( $option ) {
		$this->product_gateway_option = $option;
	}

	/**
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 * @param string                        $context
	 *
	 * @since 3.2.10
	 */
	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		return false;
	}

	public function get_transaction_url( $order ) {
		$environment = wc_braintree_get_order_environment( $order );
		$url         = sprintf( 'merchants/%1$s/transactions/', wc_braintree_merchant_id( $environment ) );
		if ( 'sandbox' === $environment ) {
			$this->view_transaction_url = sprintf( 'https://sandbox.braintreegateway.com/%s', $url );
		} else {
			$this->view_transaction_url = sprintf( 'https://braintreegateway.com/%s', $url );
		}
		$this->view_transaction_url = $this->view_transaction_url . '%s';

		return parent::get_transaction_url( $order );
	}

	/**
	 * Perform processes that are dependent on a successful payment.
	 *
	 * @param WC_Order    $order
	 * @param string|null $type
	 *
	 * @since 3.2.17
	 */
	protected function do_post_payment_processing( $order, $type = null ) {
		if ( wc_braintree_subscriptions_active() ) {
			foreach ( wcs_braintree_get_subscriptions_for_order( $order ) as $subscription ) {
				$this->create_braintree_subscription( $subscription, $order );
			}
		}

		/**
		 * @param WC_Order                     $order
		 * @param WC_Braintree_Payment_Gateway $this
		 */
		do_action( 'wc_braintree_post_payment_processing', $order, $this );
	}

	/**
	 * @param WC_Subscription $subscription
	 */
	public function process_change_payment_request( $subscription ) {
		if ( ! $this->use_saved_method() ) {
			$result = $this->add_payment_method(
				array(
					'billingAddress' => array( 'streetAddress' => isset( $_POST['billing_address_1'] ) ? wc_clean( $_POST['billing_address_1'] ) : '' ),
				), $subscription
			);
			if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
				$result['redirect'] = wc_get_page_permalink( 'myaccount' );
			}
		} else {
			$this->payment_method_token = $this->get_payment_method_token();
			$result                     = array( 'result' => 'success', 'redirect' => wc_get_page_permalink( 'myaccount' ) );
		}
		if ( $this->payment_method_token ) {
			$token = $this->get_token( $this->payment_method_token );
			// save meta data.
			$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$subscription->set_payment_method_title( $token->get_payment_method_title() );
			$subscription->save();
		}

		return $result;
	}

	public function validate_sections_field( $key, $value ) {
		if ( empty( $value ) ) {
			$value = array();
		}

		return $value;
	}

}

WC_Braintree_Payment_Gateway::init();
