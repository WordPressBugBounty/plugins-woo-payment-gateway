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
class WC_Braintree_WeChat_Payment_Gateway extends WC_Braintree_Local_Payment_Gateway {

	public function __construct() {
		$this->countries          = array( 'CN' );
		$this->currencies         = array( 'AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY', 'SGD', 'USD' );
		$this->id                 = 'braintree_wechatpay';
		$this->default_title      = $this->order_button_text = __( 'WeChat Pay', 'woo-payment-gateway' );
		$this->method_title       = __( 'Braintree WeChat Pay Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'WeChat', 'woo-payment-gateway' );
		$this->method_description = __( 'WeChat Pay gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/payment-methods/wechat.svg';
		parent::__construct();
	}

	protected function get_default_available_countries() {
		return array( 'CN' );
	}
}
