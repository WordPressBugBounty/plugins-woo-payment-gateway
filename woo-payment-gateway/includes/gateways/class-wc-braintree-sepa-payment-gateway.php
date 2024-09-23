<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Local_Payment_Gateway' ) ) {
	return;
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/Gateways
 *
 */
class WC_Braintree_Sepa_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->currencies         = array( 'EUR' );
		$this->id                 = 'braintree_sepa';
		$this->default_title      = $this->order_button_text = __( 'SEPA', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree SEPA Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'SEPA', 'woo-payment-gateway' );
		$this->method_description = __( 'SEPA gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/sepa.svg';
		parent::__construct();
	}

	public function get_default_available_countries() {
		return array( 'AT', 'DE' );
	}
}
