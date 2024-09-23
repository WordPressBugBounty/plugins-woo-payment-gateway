<?php


namespace PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways;


use PaymentPlugins\Braintree\WooFunnels\Upsell\Client;

class BasePaymentGateway extends \WFOCU_Gateway {

	private $logger;

	private $client;

	public $refund_supported = true;

	private $payment_method_nonce;

	public function __construct( Client $client, \WFOCU_Logger $logger ) {
		$this->client = $client;
		$this->logger = $logger;
	}

	public static function get_instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static( new Client(), WFOCU_Core()->log );
		}

		return $instance;
	}

	public function has_token( $order ) {
		$payment_token = $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN );

		return ! empty( $payment_token );
	}

	public function is_vaulted_threed_secure_enabled() {
		return false;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return false|true|void
	 */
	public function process_charge( $order ) {
		// record any client side errors.
		$this->handle_client_error();
		/**
		 * @var \WC_Braintree_Payment_Gateway $payment_method
		 */
		$args = $this->get_transaction_args_from_order( $order );
		try {
			$environment = wc_braintree_get_order_environment( $order );
			$result      = $this->client->connect( $environment )->transaction()->sale( $args );
			if ( $result->success ) {
				WFOCU_Core()->data->set( '_transaction_id', $result->transaction->id );
				$this->save_order_meta_data( $order, $result->transaction );

				return $this->handle_result( true );
			} else {
				$this->logger->log( sprintf( 'Error processing payment for order %s: Reason: %s', $order->get_id(), wc_braintree_errors_from_object( $result ) ) );
				throw new \WFOCU_Payment_Gateway_Exception( wc_braintree_errors_from_object( $result ), 400 );
			}
		} catch ( \Braintree\Exception $e ) {
			throw new \WFOCU_Payment_Gateway_Exception( wc_braintree_errors_from_object( $e ), 400 );
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return bool|void
	 */
	public function process_refund_offer( $order ) {
		$transaction_id = isset( $_POST['txn_id'] ) ? $_POST['txn_id'] : false;
		$amount         = isset( $_POST['amt'] ) ? round( $_POST['amt'], 2 ) : false;
		$environment    = wc_braintree_get_order_environment( $order );
		$error_msg      = 'Error processing refund for order %s. Reason: %s';
		if ( ! $transaction_id || ! $amount ) {
			return false;
		}
		try {
			// fetch the transaction object and check status. May need to void rather than refund.
			$transaction = $this->client->connect( $environment )->transaction()->find( $transaction_id );

			if ( in_array( $transaction->status, [ \Braintree\Transaction::SETTLED, \Braintree\Transaction::SETTLING ] ) ) {
				$result = $this->client->transaction()->refund( $transaction_id, $amount );
			} else {
				$result = $this->client->transaction()->void( $transaction_id );
			}

			if ( $result->success ) {
				$this->logger->log( sprintf( 'Transaction %s refunded: Amount: %s', $order->get_id(), $amount ) );

				return true;
			} else {
				$this->logger->log( sprintf( $error_msg, $order->get_id(), wc_braintree_errors_from_object( $result ) ) );

				return false;
			}
		} catch ( \Braintree\Exception $e ) {
			$this->logger->log( sprintf( $error_msg, $order->get_id(), wc_braintree_errors_from_object( $result ) ) );

			return false;
		}
	}

	protected function create_transaction( $order ) {
	}

	public function get_transaction_link( $transaction_id, $order_id ) {
		$order = wc_get_order( $order_id );

		return $this->get_wc_gateway()->get_transaction_url( $order );
	}

	/**
	 * Returns true of the payment method nonce can be added to the vault. Default behavior is
	 * to return true;
	 *
	 * @return bool
	 */
	public function should_store_in_vault() {
		return true;
	}

	/**
	 * @param \WC_order $order
	 */
	protected function get_transaction_args_from_order( \WC_order $order ) {
		/**
		 * @var \WC_Braintree_Payment_Gateway $payment_method
		 */
		$payment_method = $this->get_wc_gateway();
		$package        = WFOCU_Core()->data->get( '_upsell_package' );
		$args           = [
			'options'            => [
				'submitForSettlement'   => apply_filters( 'wc_braintree_transaction_submit_for_settlement', $payment_method->get_option( 'charge_type' ) === 'capture', $payment_method ),
				'storeInVaultOnSuccess' => false
			],
			'amount'             => round( $package['total'], 2 ),
			'taxAmount'          => round( $order->get_total_tax(), 2 ),
			'shippingAmount'     => round( $order->get_shipping_total(), 2 ),
			'customer'           => $payment_method->get_customer_attributes( $order ),
			'orderId'            => $order->get_order_number(),
			'paymentMethodToken' => $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN ),
			'merchantAccountId'  => wc_braintree_get_merchant_account( $order->get_currency() ),
			'channel'            => braintree()->partner_code
		];
		if ( isset( $_POST['_wc_braintree_woofunnels_3ds_nonce'] ) ) {
			$args['paymentMethodNonce'] = $_POST['_wc_braintree_woofunnels_3ds_nonce'];
			unset( $args['paymentMethodToken'] );
		}
		if ( ( $device_data = $payment_method->get_device_data() ) ) {
			$args['deviceData'] = $device_data;
		}
		$payment_method->add_order_billing_address( $args, $order );
		if ( $order->get_shipping_address_1() ) {
			$payment_method->add_order_shipping_address( $args, $order );
		}
		$current_offer = WFOCU_Core()->data->get( 'current_offer' );
		if ( $current_offer ) {
			$args['orderId'] = apply_filters( 'wfocu_payments_get_order_number', sprintf( '%s-%s', $order->get_order_number(), $current_offer ), $this );
		}

		return apply_filters( 'wc_braintree_woofunnels_transaction_args_from_order', $args, $this, $order );
	}

	/**
	 * Create a payment method nonce from the vaulted token.
	 *
	 * @param        $token
	 * @param string $environment
	 */
	public function create_payment_method_nonce( $token, $environment = '' ) {
		try {
			$result = $this->client->connect( $environment )->paymentMethodNonce()->create( $token );

			return $result->paymentMethodNonce;
		} catch ( \Braintree\Exception $e ) {
			return new \WP_Error( 'vaulted-nonce', wc_braintree_errors_from_object( $e ) );
		}
	}

	/**
	 * @param \WC_Order $order
	 */
	public function create_client_token( \WC_Order $order ) {
		$environment      = wc_braintree_get_order_environment( $order );
		$merchant_account = wc_braintree_get_merchant_account( $order->get_currency() );
		$args             = [];
		if ( $merchant_account ) {
			$args['merchantAccountId'] = $merchant_account;
		}
		try {
			return $this->client->connect( $environment )->clientToken()->generate( $args );
		} catch ( \Braintree\Exception $e ) {
			return new \WP_Error( 'client-token', wc_braintree_errors_from_object( $e ) );
		}
	}

	/**
	 * @param \WC_Order              $order
	 * @param \Braintree\Transaction $transaction
	 */
	protected function save_order_meta_data( \WC_Order $order, \Braintree\Transaction $transaction ) {
		$order->update_meta_data( \PaymentPlugins\WC_Braintree_Constants::CUSTOMER_ID, $transaction->customerDetails->id );
		$order->save();
	}

	public function handle_client_error() {
		$package = WFOCU_Core()->data->get( '_upsell_package' );
		if ( $package && isset( $package['_client_error'] ) ) {
			$this->logger->log( sprintf( 'Braintree client error: %s', sanitize_text_field( $package['_client_error'] ) ) );
		}
	}

}