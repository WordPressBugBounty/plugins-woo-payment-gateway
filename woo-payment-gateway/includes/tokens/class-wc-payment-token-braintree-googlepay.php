<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token_Braintree_CC' ) ) {
	exit();
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/PaymentTokens
 *
 */
class WC_Payment_Token_Braintree_GooglePay extends WC_Payment_Token_Braintree_CC {

	protected $type = 'Braintree_GooglePay';

	protected $braintree_data = array(
		'source_description' => '',
		'virtual_card_last4' => '',
		'virtual_card_type'  => '',
	);

	/**
	 *
	 * @param \Braintree\GooglePayCard|\Braintree\Transaction\GooglePayCardDetails $method
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Braintree_CC::init_from_payment_method()
	 */
	public function init_from_payment_method( $method ) {
		$this->set_card_type( $method->sourceCardType );
		$this->set_method_type( 'GooglePay' );
		$this->set_expiry_month( $method->expirationMonth );
		$this->set_expiry_year( $method->expirationYear );
		$this->set_bin( $method->bin );
		$this->set_last4( $method->sourceCardLast4 );
		$this->set_source_description( $method->sourceDescription );
		$this->set_virtual_card_type( $method->virtualCardType );
		$this->set_virtual_last4( $method->virtualCardLast4 );
		$this->set_masked_number( $this->get_bin() . '******' . $this->get_last4() );
		$this->set_payment_instrument_type( \Braintree\PaymentInstrumentType::GOOGLE_PAY_CARD );
		$this->set_token( $method->token );
	}

	public function set_source_description( $value ) {
		$this->set_prop( 'source_description', $value );
	}

	public function get_source_description() {
		return $this->get_prop( 'source_description' );
	}

	public function set_virtual_last4( $value ) {
		$this->set_prop( 'virtual_card_last4', $value );
	}

	public function get_virtual_card_last4() {
		$this->get_prop( 'virtual_card_last4' );
	}

	public function set_virtual_card_type( $value ) {
		$this->set_prop( 'virtual_card_type', $value );
	}

	public function get_virtual_card_type() {
		return $this->get_prop( 'virtual_card_type' );
	}

	public function init_payment_formats() {
		$this->payment_formats = array(
			'type_ending_in'    => array(
				'label'   => __( 'Type Ending In', 'woo-payment-gateway' ),
				'example' => 'Visa ending in 1111',
				'format'  => __( '{card_type} ending in {last4}', 'woo-payment-gateway' ),
			),
			'google_type_last4' => array(
				'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
				'example' => 'Google Pay - Visa 1111',
				'format'  => 'Google Pay - {card_type} {last4}',
			),
			'type_last4'        => array(
				'label'   => __( 'Type and Last Four', 'woo-payment-gateway' ),
				'example' => 'Visa 1111',
				'format'  => '{card_type} {last4}',
			),
			'basic'             => array(
				'label'   => __( 'Basic', 'woo-payment-gateway' ),
				'example' => __( 'Google Pay', 'woo-payment-gateway' ),
				'format'  => __( 'Google Pay', 'woo-payment-gateway' )
			)
		);
	}

}
