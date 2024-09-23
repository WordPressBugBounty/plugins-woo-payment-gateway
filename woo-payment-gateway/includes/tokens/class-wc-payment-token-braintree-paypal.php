<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token' ) ) {
	exit();
}

use \PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/PaymentTokens
 *
 */
class WC_Payment_Token_Braintree_PayPal extends WC_Payment_Token_Braintree {

	protected $type = 'Braintree_PayPal';

	protected $braintree_data = array(
		'method_type' => 'PayPal',
		'email'       => '',
		'first_name'  => '',
		'last_name'   => '',
		'payer_id'    => '',
	);

	/**
	 *
	 * @param \Braintree\PayPalAccount|\Braintree\Transaction\PayPalDetails $method
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree::init_from_payment_method()
	 */
	public function init_from_payment_method( $method ) {
		$this->set_method_type( 'PayPal' );
		if ( $method instanceof \Braintree\Transaction\PayPalDetails ) {
			$this->set_email( $method->payerEmail );
			$this->set_first_name( $method->payerFirstName );
			$this->set_last_name( $method->payerLastName );
			$this->set_authorization_id( $method->authorizationId );
			$this->set_capture_id( $method->captureId );
			$this->set_payer_id( $method->payerId );
		} else {
			$this->set_email( $method->email );
		}
		$this->set_payment_instrument_type( \Braintree\PaymentInstrumentType::PAYPAL_ACCOUNT );
		$this->set_token( $method->token );
	}

	public function set_email( $value ) {
		$this->set_prop( 'email', $value );
	}

	public function get_email() {
		return $this->get_prop( 'email' );
	}

	public function set_authorization_id( $value ) {
		$this->set_prop( 'authorization_id', $value );
	}

	public function get_authorization_id() {
		return $this->get_prop( 'authorization_id' );
	}

	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', $value );
	}

	public function get_first_name() {
		return $this->get_prop( 'first_name' );
	}

	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', $value );
	}

	/**
	 *
	 * @param string $value
	 *
	 * @since 3.1.9
	 */
	public function set_capture_id( $value ) {
		$this->data['capture_id'] = $value;
	}

	/**
	 *
	 * @since 3.1.9
	 */
	public function get_capture_id() {
		return isset( $this->data['capture_id'] ) ? $this->data['capture_id'] : '';
	}

	public function get_last_name() {
		return $this->get_prop( 'last_name' );
	}

	/**
	 *
	 * @param string $value
	 *
	 * @since 3.1.9
	 */
	public function set_payer_id( $value ) {
		$this->set_prop( 'payer_id', $value );
	}

	/**
	 *
	 * @param string $value
	 *
	 * @since 3.1.9
	 */
	public function get_payer_id() {
		return $this->get_prop( 'payer_id' );
	}

	public function init_payment_formats() {
		$this->payment_formats = array(
			'paypal_and_email' => array(
				'label'   => __( 'PayPal & Email', 'woo-payment-gateway' ),
				'example' => 'PayPal - john@example.com',
				'format'  => 'PayPal - {email}',
			),
			'email'            => array(
				'label'   => __( 'Email', 'woo-payment-gateway' ),
				'example' => 'john@example.com',
				'format'  => '{email}',
			),
			'paypal'           => array(
				'label'   => __( 'PayPal', 'woo-payment-gateway' ),
				'example' => 'PayPal',
				'format'  => __( 'PayPal', 'woo-payment-gateway' ),
			),
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree::add_meta_data_to_order()
	 */
	public function add_meta_data_to_order( $order ) {
		$order->update_meta_data( Constants::PAYPAL_TRANSACTION, $this->get_capture_id() );
	}
}
