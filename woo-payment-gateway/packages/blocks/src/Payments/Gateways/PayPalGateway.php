<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;


class PayPalGateway extends AbstractGateway {

	protected $name = 'braintree_paypal';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-braintree-blocks-paypal', 'build/wc-braintree-paypal.js' );

		return [ 'wc-braintree-blocks-paypal' ];
	}

	public function get_payment_method_data() {
		return parent::get_payment_method_data() + [
				'intent'                 => $this->get_setting( 'charge_type' ),
				'displayName'            => $this->get_setting( 'display_name' ),
				'partnerCode'            => braintree()->partner_code,
				'buttonStyle'            => [
					'label'  => $this->get_setting( 'smartbutton_label' ),
					'color'  => $this->get_setting( 'smartbutton_color' ),
					'shape'  => $this->get_setting( 'smartbutton_shape' ),
					'height' => intval( $this->get_setting( 'button_height' ) ),
				],
				'bnplButtonStyle'        => [
					'color' => $this->get_setting( 'bnpl_button_color' )
				],
				'bnplEnabled'            => $this->is_bnpl_active(),
				'paylaterMsgEnabled'     => $this->is_paylater_msg_active(),
				'paylaterTxtColor'       => $this->get_setting( 'pay_later_txt_color' ),
				'payLaterMsgCurrencies'  => [ 'USD', 'GBP', 'EUR' ],
				'expressCartEnabled'     => in_array( 'cart', $this->get_payment_sections() ),
				'expressCheckoutEnabled' => in_array( 'checkout_banner', $this->get_payment_sections() )
			];
	}

	/**
	 * @param string $page
	 *
	 * @since 3.2.25
	 * @return bool
	 */
	private function is_bnpl_active( $page = 'checkout' ) {
		return $this->get_setting( 'bnpl_enabled' ) === 'yes' && in_array( $page, $this->get_setting( 'bnpl_sections', [] ) );
	}

	private function is_paylater_msg_active() {
		$sections = $this->get_setting( 'pay_later_msg', [] );

		return in_array( 'checkout', $sections ) && wc_string_to_bool( $this->get_setting( 'bnpl_enabled' ) );
	}

}