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
class WC_Payment_Token_Braintree_Venmo extends WC_Payment_Token_Braintree {

	protected $type = 'Braintree_Venmo';

	protected $braintree_data = array(
		'venmo_user_id' => '',
		'username'      => '',
		'source_description',
		'method_type'   => 'Venmo',
	);

	/**
	 *
	 * @param \Braintree\VenmoAccount|\Braintree\Transaction\VenmoAccountDetails $method
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree::init_from_payment_method()
	 */
	public function init_from_payment_method( $method ) {
		$this->set_source_description( $method->sourceDescription );
		$this->set_username( $method->username );
		$this->set_venmo_user_id( $method->venmoUserId );
		$this->set_method_type( 'Venmo' );
		$this->set_payment_instrument_type( \Braintree\PaymentInstrumentType::VENMO_ACCOUNT );
		$this->set_token( $method->token );
	}

	public function set_username( $value ) {
		$this->set_prop( 'username', $value );
	}

	public function get_username( $context = 'view' ) {
		return $this->get_prop( 'username', $context );
	}

	public function set_venmo_user_id( $value ) {
		$this->set_prop( 'venmo_user_id', $value );
	}

	public function get_venmo_user_id( $context = 'view' ) {
		return $this->get_prop( 'venmo_user_id', $context );
	}

	public function set_source_description( $value ) {
		$this->set_prop( 'source_description', $value );
	}

	public function get_source_description( $context = 'view' ) {
		return $this->get_prop( 'source_description', $context );
	}

	public function init_payment_formats() {
		$this->payment_formats = array(
			'type_and_user'      => array(
				'label'   => __( 'Type and User ID', 'woo-payment-gateway' ),
				'example' => 'Venmo - john.smith1990',
				'format'  => __( 'Venmo - {username}', 'woo-payment-gateway' ),
			),
			'source_description' => array(
				'label'   => __( 'Account Description', 'woo-payment-gateway' ),
				'example' => 'Venmo Account: john.smith1990',
				'format'  => '{sourceDescription}',
			),
			'basic'              => array(
				'label'   => __( 'Basic', 'woo-payment-gateway' ),
				'example' => __( 'Venmo', 'woo-payment-gateway' ),
				'format'  => __( 'Venmo', 'woo-payment-gateway' )
			)
		);
	}
}
