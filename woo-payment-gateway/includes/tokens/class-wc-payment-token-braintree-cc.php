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
class WC_Payment_Token_Braintree_CC extends WC_Payment_Token_Braintree {

	protected $type = 'Braintree_CC';

	protected $braintree_data = array(
		'last4'         => '',
		'card_type'     => '',
		'expiry_year'   => '',
		'expiry_month'  => '',
		'card_holder'   => '',
		'expiry_date'   => '',
		'masked_number' => '',
		'bin'           => '',
	);

	/**
	 *
	 * @param \Braintree\CreditCard|\Braintree\Transaction\CreditCardDetails $method
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree::init_from_payment_method()
	 */
	public function init_from_payment_method( $method ) {
		$this->set_method_type( str_replace( ' ', '', $method->cardType ) );
		$this->set_card_type( $method->cardType );
		$this->set_expiry_month( $method->expirationMonth );
		$this->set_expiry_year( $method->expirationYear );
		$this->set_expiry_date( $method->expirationDate );
		$this->set_card_holder( $method->cardholderName );
		$this->set_bin( $method->bin );
		$this->set_last4( $method->last4 );
		$this->set_masked_number( $method->maskedNumber );
		$this->set_payment_instrument_type( \Braintree\PaymentInstrumentType::CREDIT_CARD );
		$this->set_token( $method->token );
	}

	public function set_bin( $value ) {
		$this->set_prop( 'bin', $value );
	}

	public function get_card_type( $context = 'view' ) {
		return wc_get_credit_card_type_label( $this->get_prop( 'card_type', $context ) );
	}

	public function set_card_type( $type ) {
		$this->set_prop( 'card_type', $type );
	}

	public function get_bin( $context = 'view' ) {
		return $this->get_prop( 'bin', $context );
	}

	public function set_card_holder( $value ) {
		$this->set_prop( 'card_holder', $value );
	}

	public function get_card_holder( $context = 'view' ) {
		return $this->get_prop( 'card_holder', $context );
	}

	public function set_expiry_date( $value ) {
		$this->set_prop( 'expiry_date', $value );
	}

	public function get_expiry_date() {
		return $this->get_prop( 'expiry_date' );
	}

	public function set_masked_number( $value ) {
		$this->set_prop( 'masked_number', $value );
	}

	public function get_masked_number( $context = 'view' ) {
		return $this->get_prop( 'masked_number', $context );
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function set_last4( $last4 ) {
		$this->set_prop( 'last4', $last4 );
	}

	public function get_expiry_year( $context = 'view' ) {
		return $this->get_prop( 'expiry_year', $context );
	}

	public function set_expiry_year( $year ) {
		$this->set_prop( 'expiry_year', $year );
	}

	public function get_expiry_month( $context = 'view' ) {
		return $this->get_prop( 'expiry_month', $context );
	}

	public function set_expiry_month( $month ) {
		$this->set_prop( 'expiry_month', str_pad( $month, 2, '0', STR_PAD_LEFT ) );
	}

	public function init_payment_formats() {
		$this->payment_formats = array(
			'type_ending_in'          => array(
				'label'   => __( 'Type Ending In', 'woo-payment-gateway' ),
				'example' => 'Visa ending in 1111',
				'format'  => __( '{card_type} ending in {last4}', 'woo-payment-gateway' ),
			),
			'type_masked_number'      => array(
				'label'   => __( 'Type Masked Number', 'woo-payment-gateway' ),
				'example' => 'Visa 4111********1111',
				'format'  => '{card_type} {masked_number}',
			),
			'type_dash_masked_number' => array(
				'label'   => __( 'Type Dash Masked Number', 'woo-payment-gateway' ),
				'example' => 'Visa - 4111********1111',
				'format'  => '{card_type} - {masked_number}',
			),
			'type_last4'              => array(
				'label'   => __( 'Type Last 4', 'woo-payment-gateway' ),
				'example' => 'Visa 1111',
				'format'  => '{card_type} {last4}',
			),
			'type_dash_last4'         => array(
				'label'   => __( 'Type Dash & Last 4', 'woo-payment-gateway' ),
				'example' => 'Visa - 1111',
				'format'  => '{card_type} - {last4}',
			),
			'masked_number'           => array(
				'label'   => __( 'Masked Number', 'woo-payment-gateway' ),
				'example' => '4111********1111',
				'format'  => '{masked_number}',
			),
			'last4'                   => array(
				'label'   => __( 'Last Four', 'woo-payment-gateway' ),
				'example' => '1111',
				'format'  => '{last4}',
			),
			'card_type'               => array(
				'label'   => __( 'Card Type', 'woo-payment-gateway' ),
				'example' => 'Visa',
				'format'  => '{card_type}',
			),
			'basic'                   => array(
				'label'   => __( 'Basic', 'woo-payment-gateway' ),
				'example' => __( 'Credit Card', 'woo-payment-gateway' ),
				'format'  => __( 'Credit Card', 'woo-payment-gateway' )
			)
		);
	}
}
