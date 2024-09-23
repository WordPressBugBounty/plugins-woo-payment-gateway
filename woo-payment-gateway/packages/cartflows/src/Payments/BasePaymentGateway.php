<?php


namespace PaymentPlugins\CartFlows\Braintree\Payments;


use PaymentPlugins\CartFlows\Braintree\Constants;

class BasePaymentGateway {

	protected $supports_api_refund = true;

	private $client;

	private $logger;

	private $payment_method_nonce;

	/**
	 * @var \WC_Braintree_Payment_Gateway
	 */
	public $payment_method;

	/**
	 * BasePaymentGateway constructor.
	 *
	 * @param \Braintree\Gateway $client
	 * @param \Cartflows_Logger $logger
	 */
	public function __construct( \Braintree\Gateway $client, \Cartflows_Logger $logger ) {
		$this->client = $client;
		$this->logger = $logger;
	}

	public static function get_instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static( new \Braintree\Gateway( wc_braintree_connection_settings() ), wcf()->logger() );
		}

		return $instance;
	}

	public function is_api_refund() {
		return $this->supports_api_refund;
	}

	protected function can_process_3ds_payment() {
		return false;
	}

	public function process_offer_payment( \WC_Order $order, array $product ) {
		$this->payment_method = $this->get_payment_method( $order->get_payment_method() );
		// check if this payment requires 3DS for vaulted payment methods
		if ( $this->can_process_3ds_payment() ) {
			if ( ( ! $vaulted_nonce = $order->get_meta( Constants::VAULTED_NONCE . $product['step_id'] ) ) ) {
				// create the vaulted payment nonce and return result to client.
				$token = $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN );
				try {
					$response = $this->client->paymentMethodNonce()->create( $token );
					wp_send_json( array(
						'status'   => 'success',
						'redirect' => $this->get_3ds_redirect_hash( $response->paymentMethodNonce, round( $product['price'], 2 ) )
					) );
				} catch ( \Braintree\Exception $e ) {
					wp_send_json( array(
						'status'   => 'failure',
						'redirect' => $this->get_3ds_error_hash( __( 'There was an error processing your payment.', 'woo-payment-gateway' ) )
					) );
				}
			} else {
				// set the payment method nonce created from the vaulted payment method
				$this->payment_method_nonce = $vaulted_nonce;
			}
		}
		// process the payment
		$args = array(
			'options'           => array(
				'submitForSettlement'   => apply_filters( 'wc_braintree_transaction_submit_for_settlement', $this->payment_method->get_option( 'charge_type' ) === 'capture', $this->payment_method ),
				'storeInVaultOnSuccess' => false
			),
			'amount'            => $product['price'],
			'taxAmount'         => wc_round_tax_total( $product['price'] - ( $product['args']['subtotal'] + round( $product['shipping_fee'], wc_get_price_decimals(), PHP_ROUND_HALF_UP ) ) ),
			'shippingAmount'    => round( $product['shipping_fee'], wc_get_price_decimals(), PHP_ROUND_HALF_UP ),
			'customer'          => $this->payment_method->get_customer_attributes( $order ),
			'orderId'           => $order->get_order_number() . '-' . $product['step_id'],
			'merchantAccountId' => wc_braintree_get_merchant_account( $order->get_currency() ),
			'channel'           => braintree()->partner_code
		);
		if ( $this->payment_method_nonce ) {
			$args['paymentMethodNonce'] = $this->payment_method_nonce;
		} else {
			$args['paymentMethodToken'] = $order->get_meta( \PaymentPlugins\WC_Braintree_Constants::PAYMENT_METHOD_TOKEN );
		}
		$this->payment_method->add_order_customer_id( $args, $order );

		try {
			$result = $this->client->transaction()->sale( $args );

			if ( $result->success ) {
				$order->update_meta_data( 'cartflows_offer_txn_resp_' . $product['step_id'], $result->transaction->id );
				$order->save();

				return true;
			}
			$this->logger->log( sprintf( 'Payment for step %s failed. Reason: %s Args: %s', $product['step_id'], wc_braintree_errors_from_object( $result ), print_r( $args, true ) ) );
		} catch ( \Braintree\Exception $e ) {
			$this->logger->log( sprintf( 'Payment for step %s failed. Reason: %s Args: %s', $product['step_id'], wc_braintree_errors_from_object( $e ), print_r( $args, true ) ) );

		}

		return false;
	}

	public function process_offer_refund( \WC_Order $order, array $offer_data ) {
		$environment = wc_braintree_get_order_environment( $order );
		try {
			$this->client->config = new \Braintree\Configuration( wc_braintree_connection_settings( $environment ) );
			$refund_amount        = round( $offer_data['refund_amount'], wc_get_price_decimals() );
			$result               = $this->client->transaction()->refund( $offer_data['transaction_id'], $refund_amount );
			if ( $result->success ) {
				return $result->transaction->id;
			}
			$this->logger->log( sprintf( 'Error processing refund. Message: %s', wc_braintree_errors_from_object( $result ) ) );
		} catch ( \Braintree\Exception $e ) {
			$this->logger->log( sprintf( 'Error processing refund. Message: %s', $e->getMessage() ) );
		}

		return false;
	}

	/**
	 * @param $payment_method
	 *
	 * @return \WC_Braintree_Payment_Gateway
	 */
	private function get_payment_method( $payment_method ) {
		return WC()->payment_gateways()->payment_gateways()[ $payment_method ];
	}

	private function get_3ds_redirect_hash( \Braintree\PaymentMethodNonce $payment_nonce, $amount ) {
		return sprintf( '#wcBraintree3DS=%s', rawurlencode( base64_encode( wp_json_encode( array(
			'nonce'   => $payment_nonce->nonce,
			'details' => $payment_nonce->details,
			'amount'  => $amount,
			'entropy' => time()
		) ) ) ) );
	}

	private function get_3ds_error_hash( $msg ) {
		return sprintf( '#wcBraintree3DS=%s', rawurlencode( base64_encode( wp_json_encode( array(
			'error'   => true,
			'message' => $msg
		) ) ) ) );
	}

}