<?php

defined( 'ABSPATH' ) || exit();

/**
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_3ds_Validation {

	/**
	 *
	 * @var \Braintree\PaymentMethodNonce
	 */
	private $nonce = null;

	/**
	 *
	 * @var \Braintree\ThreeDSecureInfo
	 */
	private $threeds_info = '';

	public $_3ds_card_ineligible;

	public $_3ds_liability_not_shifted;

	/**
	 *
	 * @var WC_Braintree_Payment_Gateway
	 */
	private $gateway;

	/**
	 *
	 * @param \Braintree\PaymentMethodNonce $payment_method_nonce
	 * @param WC_Braintree_Payment_Gateway  $gateway
	 */
	public function __construct( $payment_method_nonce, $gateway ) {
		$this->set_nonce( $payment_method_nonce );
		$this->threeds_info               = $payment_method_nonce->threeDSecureInfo;
		$this->gateway                    = $gateway;
		$this->_3ds_card_ineligible       = $gateway->get_option( '3ds_card_ineligible' );
		$this->_3ds_liability_not_shifted = $gateway->get_option( '3ds_liability_not_shifted' );
		$this->init();
	}

	public function init() {
		$action = '';
		if ( $this->is_card_ineligible() ) {
			$action = $this->_3ds_card_ineligible;
		} elseif ( ! $this->is_liability_shifted() ) {
			$action = $this->_3ds_liability_not_shifted;
		}
		if ( $action ) {
			switch ( $action ) {
				case 'authorize_only':
					add_action( 'wc_braintree_order_transaction_args', array( $this->gateway, '_3ds_authorize_order' ), 10, 2 );
					break;
				case 'reject':
					add_action( 'wc_braintree_before_process_order_braintree_cc', array( $this->gateway, '_3ds_reject_order' ) );
					break;
				case 'accept':
					if ( $this->is_card_ineligible() ) {
						add_action( 'wc_braintree_order_transaction_args', array( $this->gateway, '_3ds_accept_order' ), 10, 2 );
					}
					break;
			}
		}
	}

	/**
	 */
	public function get_nonce() {
		return $this->nonce;
	}

	/**
	 *
	 * @param \Braintree\PaymentMethodNonce $nonce
	 */
	public function set_nonce( $nonce ) {
		$this->nonce = $nonce;
	}

	public function is_liability_shifted() {
		return $this->threeds_info->liabilityShifted;
	}

	public function is_liability_shift_possible() {
		return $this->threeds_info->liabilityShiftPossible;
	}

	public function is_card_ineligible() {
		return ! $this->is_liability_shifted() && ! $this->is_liability_shift_possible();
	}

}
