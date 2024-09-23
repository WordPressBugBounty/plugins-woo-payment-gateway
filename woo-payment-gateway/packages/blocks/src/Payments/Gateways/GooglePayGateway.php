<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;


class GooglePayGateway extends AbstractGateway {

	protected $name = 'braintree_googlepay';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-braintree-blocks-googlepay', 'build/wc-braintree-googlepay.js' );

		return [ 'wc-braintree-blocks-googlepay' ];
	}

	public function get_payment_method_data() {
		return parent::get_payment_method_data() + [
				'expressCartEnabled'     => in_array( 'cart', $this->get_payment_sections() ),
				'expressCheckoutEnabled' => in_array( 'checkout_banner', $this->get_payment_sections() ),
				'googleMerchantName'     => $this->get_setting( 'merchant_name' ),
				'googleMerchantId'       => wc_braintree_production_active() ? $this->get_setting( 'merchant_id' ) : '',
				'googleEnvironment'      => wc_braintree_production_active() ? 'PRODUCTION' : 'TEST',
				'buttonOptions'          => $this->get_payment_button_options(),
				'totalPriceLabel'        => __( 'Total', 'woocommerce' ),
				'buttonShape'            => $this->get_setting( 'button_shape', 'rect' )
			];
	}

	public function get_payment_method_icon() {
		return [
			'id'  => 'gpay',
			'src' => $this->assets_api->assets_url( '../../assets/img/googlepay/' . $this->get_setting( 'icon' ) . '.svg' ),
			'alt' => 'GPay'
		];
	}

	private function get_payment_button_options() {
		return [
			'buttonColor'    => $this->get_setting( 'button_color' ),
			'buttonType'     => $this->get_setting( 'button_type' ),
			'buttonSizeMode' => 'fill'
		];
	}

}