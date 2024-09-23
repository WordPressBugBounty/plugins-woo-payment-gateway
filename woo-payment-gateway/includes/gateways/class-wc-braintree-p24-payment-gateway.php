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
class WC_Braintree_P24_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->currencies         = array( 'EUR', 'PLN' );
		$this->id                 = 'braintree_p24';
		$this->default_title      = $this->order_button_text = __( 'Przelewy24', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree P24 Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Przelewy24', 'woo-payment-gateway' );
		$this->method_description = __( 'P24 gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/p24.svg';
		parent::__construct();
	}

	protected function get_default_available_countries() {
		return array( 'PL' );
	}
}
