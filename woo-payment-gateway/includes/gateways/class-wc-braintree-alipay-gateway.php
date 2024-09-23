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
class WC_Braintree_Alipay_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->currencies         = array( 'EUR', 'GBP', 'USD', 'AUD', 'HKD', 'SGD' );
		$this->id                 = 'braintree_alipay';
		$this->default_title      = $this->order_button_text = __( 'Alipay', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree Alipay Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Alipay', 'woo-payment-gateway' );
		$this->method_description = __( 'Alipay gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/alipay.svg';
		parent::__construct();
	}

	protected function get_default_available_countries() {
		return array( 'CN' );
	}
}
