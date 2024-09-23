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
class WC_Braintree_EPS_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->currencies         = array( 'EUR' );
		$this->id                 = 'braintree_eps';
		$this->default_title      = $this->order_button_text = __( 'EPS', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree EPS Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'EPS', 'woo-payment-gateway' );
		$this->method_description = __( 'EPS gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/eps.svg';
		parent::__construct();
	}

	protected function get_default_available_countries() {
		return array( 'AT' );
	}
}
