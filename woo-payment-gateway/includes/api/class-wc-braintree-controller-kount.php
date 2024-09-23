<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Kount extends WC_Braintree_Rest_Controller {

	protected $namespace = 'kount/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'api_key',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_api_key' ),
				'permission_callback' => array( $this, 'admin_permission_check', ),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'(?P<api_key>[\w\_\-]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'process_event' ),
				'permission_callback' => '__return_true'
			)
		);
		// deprecated Kount rest url
		register_rest_route(
			$this->deprecated_rest_uri() . 'kount',
			'(?P<api_key>[\w\_\-]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'process_event' ),
				'permission_callback' => '__return_true',
			)
		);

		add_action( 'wc_braintree_api_kount_event_WORKFLOW_STATUS_EDIT', array( $this, 'status_update' ), 10, 2 );

		add_action( 'wc_braintree_api_kount_event_WORKFLOW_NOTES_ADD', array( $this, 'update_notes' ), 10, 2 );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function create_api_key( $request ) {
		$this->set_api_key( $this->generate_key() );

		return rest_ensure_response( $this->rest_url() . $this->get_api_key() );
	}

	private function generate_key() {
		return 'api_' . wc_rand_hash();
	}

	public function get_rest_url() {
		return $this->rest_url() . $this->get_api_key();
	}

	private function get_api_key() {
		if ( ( $key = get_option( 'wc_braintree_kount_api_key', false ) ) == false ) {
			$this->set_api_key( $this->generate_key() );
		}

		return get_option( 'wc_braintree_kount_api_key', false );
	}

	private function set_api_key( $key ) {
		update_option( 'wc_braintree_kount_api_key', $key, false );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_event( $request ) {
		$api_key   = $request->get_param( 'api_key' );
		$saved_key = $this->get_api_key();
		try {
			if ( $api_key == null || ! hash_equals( $api_key, $saved_key ) ) {
				throw new Exception( __( 'Invalid request.', 'woo-payment-gateway' ), 400 );
			}
			$xml = $request->get_body();
			wc_braintree_log_info( sprintf( __( 'Kount XML body: %1$s', 'woo-payment-gateway' ), $xml ) );
			$document = new DOMDocument();
			if ( @$document->loadXML( $xml ) ) {
				$element = $document->documentElement;

				$event_elements = $element->getElementsByTagName( 'event' );

				foreach ( $event_elements as $event ) {

					/**
					 *
					 * @var DOMElement $event
					 */
					$event;

					$name = $event->getElementsByTagName( 'name' )->item( 0 )->nodeValue;

					$order_id = $event->getElementsByTagName( 'key' )->item( 0 )->getAttribute( 'order_number' );

					$order = wc_get_order( $order_id );

					if ( $order ) {
						$attribs = array(
							'name'      => $name,
							'key'       => $event->getElementsByTagName( 'key' )->item( 0 )->nodeValue,
							'order_id'  => $order_id,
							'order'     => $order,
							'old_value' => $event->getElementsByTagName( 'old_value' )->item( 0 )->nodeValue,
							'new_value' => $event->getElementsByTagName( 'new_value' )->item( 0 )->nodeValue,
							'agent'     => $event->getElementsByTagName( 'agent' )->item( 0 )->nodeValue,
							'occurred'  => $event->getElementsByTagName( 'occurred' )->item( 0 )->nodeValue,
						);
						do_action( 'wc_braintree_api_kount_event_' . $name, $order, $attribs, $request );
					}
				}
			} else {
				throw new Exception( __( 'Invalid xml format.', 'woo-payment-gateway' ), 400 );
			}

			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'kount-error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param array $attribs
	 */
	public function status_update( $order, $attribs ) {
		$status   = $attribs['new_value'];
		$order_id = $attribs['order_id'];

		$current_status = $order->get_status();
		$new_status     = $current_status;
		$transaction    = null;
		// A,R,E,D
		switch ( $status ) {
			case 'A':
				$new_status = null;

				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'add_valid_order_statuses' ) );

				$order->payment_complete();

				remove_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'add_valid_order_statuses' ) );

				break;
			case 'E':
				$new_status = 'kount-escalate';
				break;
			case 'D':
				$new_status = 'cancelled';

				$transaction = $this->fetch_transaction( $order->get_transaction_id(), wc_braintree_get_order_environment( $order ) );

				$action = braintree()->fraud_settings->get_option( 'kount_decline_action' );
				/**
				 *
				 * @var WC_Braintree_Payment_Gateway $gateway
				 */
				$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];

				if ( $transaction != null ) {
					if ( $action === 'cancel_transaction' ) {
						switch ( $transaction->status ) {
							case \Braintree\Transaction::AUTHORIZED:
							case \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT:
								$gateway->void_charge( $order );
								break;
							case \Braintree\Transaction::SETTLED:
								$gateway->process_refund( $order->get_id(), $order->get_total() );
						}
					}
				} else {
					$order->add_order_note( sprintf( __( 'Transaction %1$s was not found in the Braintree %2$s system.', 'woo-payment-gateway' ), $order->get_transaction_id(), wc_braintree_environment() ) );
				}
				break;
			case 'R':
				$new_status = 'kount-review';
				break;
		}

		if ( ! is_null( $new_status ) && $new_status !== $current_status &&
		     apply_filters( 'wc_braintree_kount_api_can_change_status', true, $order, $transaction ) ) {
			$order->update_status( $new_status );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param array $attribs
	 */
	public function update_notes( $order, $attribs ) {
		if ( ! empty( $attribs['new_value'] ) ) {
			$order->add_order_note( sprintf( __( 'Kount note: %s', 'woo-payment-gateway' ), $attribs['new_value'] ) );
		}
	}

	public function add_valid_order_statuses( $statuses ) {
		$statuses[] = 'kount-review';
		$statuses[] = 'kount-escalate';

		return $statuses;
	}

	private function fetch_transaction( $id, $env ) {
		return braintree()->gateway( $env )->transaction()->find( $id );
	}
}
