<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;


class VenmoGateway extends AbstractGateway {

	protected $name = 'braintree_venmo';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-braintree-blocks-venmo', 'build/wc-braintree-venmo.js' );

		return [ 'wc-braintree-blocks-venmo' ];
	}

	public function get_payment_method_data() {
		return parent::get_payment_method_data() + [
				'buttonIcon'            => $this->assets_api->assets_url( '../../assets/img/payment-methods/venmo_white.svg' ),
				'placeOrderButtonLabel' => __( 'Pay with Venmo', 'woo-payment-gateway' )
			];
	}

	public function get_payment_method_icon() {
		return [
			'id'  => 'venmo',
			'src' => $this->assets_api->assets_url( '../../assets/img/payment-methods/' . $this->get_setting( 'icon' ) . '.svg' ),
			'alt' => 'Venmo'
		];
	}

}