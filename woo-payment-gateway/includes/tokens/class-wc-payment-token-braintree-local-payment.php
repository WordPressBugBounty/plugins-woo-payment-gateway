<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token_Braintree' ) ) {
	exit();
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/PaymentTokens
 *
 */
class WC_Payment_Token_Braintree_Local_Payment extends WC_Payment_Token_Braintree {

	protected $type = 'Braintree_Local_Payment';

	protected $braintree_data = array(
		'funding_source' => '',
		'payer_id'       => '',
		'payment_id'     => '',
	);

	/**
	 *
	 * @param mixed $method
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree::init_from_payment_method()
	 */
	public function init_from_payment_method( $method ) {
		$method = (object) $method;
		$this->set_funding_source( $method->fundingSource );
		$this->set_payer_id( $method->payerId );
		$this->set_payment_id( $method->paymentId );
		$this->set_payment_instrument_type( 'local_payment' );
	}

	public function set_funding_source( $value ) {
		$this->set_prop( 'funding_source', $value );
	}

	public function get_funding_source() {
		return $this->get_prop( 'funding_source' );
	}

	public function set_payer_id( $value ) {
		$this->set_prop( 'payer_id', $value );
	}

	public function get_payer_id() {
		return $this->get_prop( 'payer_id' );
	}

	public function set_payment_id( $value ) {
		$this->set_prop( 'payment_id', $value );
	}

	public function get_payment_id() {
		return $this->get_prop( 'payment_id' );
	}

	public function init_payment_formats() {
		$this->payment_formats = array(
			'source_and_payer' => array(
				'label'   => __( 'Type and Payer Id', 'woo-payment-gateway' ),
				'example' => 'iDEAL - XF-d647tg',
				'format'  => '{funding_source} - {payer_id}',
			),
		);
	}
}
