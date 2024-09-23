<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Payment_Gateway' ) ) {
	return;
}

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 * Abstract class that should be extended by local payment gateways.
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 *
 */
abstract class WC_Braintree_Local_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	/**
	 *
	 * @var array currencies that this payment method accepts
	 */
	protected $currencies = array();

	public $countries = array();

	public $default_title = '';

	public $payment_id_key;

	public function __construct() {
		$this->template   = 'local-payment.php';
		$this->token_type = 'Local_Payment';
		parent::__construct();
		$this->settings['method_format'] = 'source_and_payer';
		// must always be capture per Braintree's documentation
		$this->settings['charge_type'] = 'capture';

		$this->payment_id_key    = $this->id . '_payment_id';
		$this->order_button_text = $this->get_option( 'order_button_text' );
	}

	public function add_hooks() {
		parent::add_hooks();
		remove_filter( 'wc_braintree_admin_settings_tabs', array( $this, 'admin_settings_tabs' ) );
		add_filter( 'wc_braintree_local_gateways_tab', array( $this, 'admin_settings_tabs' ) );
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function set_supports() {
		$this->supports = array( 'products', 'refunds', 'wc_braintree_fees' );
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_' . $this->id . '_form_fields', include WC_BRAINTREE_PATH . 'includes/gateways/settings/local-payment-settings.php', $this );
	}

	/**
	 * Return true only if the gateway requirements for being displayed are met.
	 *
	 * @return bool
	 */
	public function is_local_payment_available() {
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$order    = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
			$currency = $order->get_currency();
			$country  = $order->get_billing_country();
		} else {
			$currency = get_woocommerce_currency();
			$country  = WC()->customer ? WC()->customer->get_billing_country() : null;
		}
		$_available = false;
		if ( in_array( $currency, $this->currencies ) ) {
			$type = $this->get_option( 'allowed_countries' );
			if ( 'all_except' === $type ) {
				$_available = ! in_array( $country, $this->get_option( 'except_countries', array() ) );
			} elseif ( 'specific' === $type ) {
				$_available = in_array( $country, $this->get_option( 'specific_countries', array() ) );
			} else {
				$_available = $this->countries ? in_array( $country, $this->countries ) : true;
			}
		}

		/**
		 * @since 3.2.26
		 */
		return apply_filters( 'wc_braintree_is_local_payment_available', $_available, $this, $currency, $country );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		if ( ! in_array( $scripts->get_handle( 'local-payment' ), $scripts->get_enqueued_scripts() ) ) {
			$scripts->enqueue_script(
				'local-payment',
				$scripts->assets_url( 'js/frontend/local-payment.js' ),
				array(
					$scripts->get_handle( 'client-manager' ),
					$scripts->get_handle( 'local-payment-v3' ),
				)
			);
			$scripts->localize_script( 'local-payment', array() );
		}
	}

	public function localize_script_params() {
		return array_merge(
			$this->get_localized_standard_params(),
			array(
				'payment_key'  => $this->payment_id_key,
				'payment_type' => str_replace( 'braintree_', '', $this->id ),
				'button_text'  => $this->order_button_text,
				'return_url'   => wc_get_checkout_url(),
				'routes'       => array(
					'payment_data'     => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->local_payment->rest_uri() . '/payment-data' ),
					'complete_payment' => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->local_payment->rest_uri() . '/payment/complete' )
				),
			)
		);
	}

	public function output_settings_nav() {
		global $current_section;
		parent::output_settings_nav();
		include braintree()->plugin_path() . 'includes/admin/views/html-local-gateways-nav.php';
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->localPayment;
	}

	public function get_gateway_supports_description() {
		$text = '';
		if ( $this->currencies ) {
			$text = sprintf(
				__( '%1$s is available when store currency is %2$s', 'woo-payment-gateway' ),
				$this->get_method_title(),
				'<b>' .
				implode( ', ', $this->currencies ) . '</b>'
			);
		}
		if ( $this->countries ) {
			$text .= sprintf( __( ' and billing country is %s', 'woo-payment-gateway' ), '<b>' . implode( ', ', $this->countries ) . '</b>' );
		}

		return $text;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::process_payment()
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $this->has_order_lock( $order ) ) {
			// there is an order lock so this order is being processed by a webhook
			return $this->order_success_result( $order );
		} else {
			// zero order total so pass to parent for processing
			if ( $order->get_total() == 0 ) {
				return parent::process_payment( $order_id );
			}
			if ( ( $payment_id = $order->get_meta( Constants::PAYMENT_ID ) ) ) {
				if ( ! $this->get_payment_method_nonce() ) {
					return array(
						'result'   => 'success',
						'redirect' => $this->get_order_created_url( $order ),
					);
				}
				$this->set_order_lock( $order );

				return parent::process_payment( $order_id );
			} else {
				return array(
					'result'   => 'success',
					'redirect' => $this->get_order_created_url( $order ),
				);
			}
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	private function get_order_created_url( $order ) {
		// timestamp added so url is always unique
		return sprintf( '#local_payment=%s', base64_encode( wp_json_encode(
			array(
				'payment_method' => $order->get_payment_method(),
				'order_id'       => $order->get_id(),
				'order_key'      => $order->get_order_key(),
				'timestamp'      => time()
			)
		) ) );
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.8
	 */
	public function set_order_lock( $order ) {
		$order_id = ( is_object( $order ) ? $order->get_id() : $order );
		set_transient( 'braintree_lock_order_' . $order_id, $order_id, apply_filters( 'wc_braintree_set_order_lock', 2 * MINUTE_IN_SECONDS ) );
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.8
	 */
	public function remove_order_lock( $order ) {
		delete_transient( 'braintree_lock_order_' . ( is_object( $order ) ? $order->get_id() : $order ) );
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.0.8
	 */
	public function has_order_lock( $order ) {
		$lock = get_transient( 'braintree_lock_order_' . ( is_object( $order ) ? $order->get_id() : $order ) );

		return $lock !== false;
	}

	/**
	 * @param array $args
	 * @param WC_Order $order
	 * @param array $items
	 *
	 * @return $this|WC_Braintree_Local_Payment_Gateway
	 */
	public function add_order_line_items( &$args, $order, &$items = array() ) {
		// Local payment methods don't support line items.
		return $this;
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'local-payment' ) );
		}
	}

	public function get_braintree_documentation_url() {
		return 'https://docs.paymentplugins.com/wc-braintree/config/#/braintree_local_gateways';
	}

	protected function get_default_available_countries() {
		return array();
	}
}
