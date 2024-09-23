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
class WC_Braintree_Sofort_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->countries          = array( 'AT', 'BE', 'DE', 'ES', 'IT', 'NL' );
		$this->currencies         = array( 'EUR' );
		$this->id                 = 'braintree_sofort';
		$this->default_title      = $this->order_button_text = __( 'SOFORT', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree SOFORT Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'SOFORT', 'woo-payment-gateway' );
		$this->method_description = __( 'SOFORT gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/sofort.svg';
		parent::__construct();
	}
}
