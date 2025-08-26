<?php


namespace PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways;

/**
 * Class CreditCardGateway
 *
 * @package PaymentPlugins\WooCommerce\Blocks\Braintree\Payments\Gateways
 */
class CreditCardGateway extends AbstractGateway {

	protected $name = 'braintree_cc';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-braintree-blocks-credit-card', 'build/wc-braintree-credit-card.js' );

		return [ 'wc-braintree-blocks-credit-card' ];
	}

	public function get_payment_method_data() {
		$token = new \WC_Payment_Token_Braintree_CC();
		$token->set_format( $this->get_setting( 'method_format', 'type_ending_in' ) );
		$base_url        = \plugins_url( 'assets/images/payment-methods/', WC_PLUGIN_FILE );
		$email_detection = $this->get_setting( 'fastlane_flow' ) === 'email_detection';
		$show_signup     = $email_detection && \wc_string_to_bool( $this->get_setting( 'fastlane_enabled', 'yes' ) )
		                   && \wc_string_to_bool( $this->get_setting( 'fastlane_signup', 'yes' ) );

		return parent::get_payment_method_data() + [
				'dropinEnabled'       => ! $this->is_custom_form_enabled(),
				'vaultedThreeDSecure' => $this->is_vaulted_three_d_secure_active(),
				'customForm'          => $this->get_setting( 'custom_form_design', 'bootstrap_form' ),
				'hostedFieldsOptions' => $this->get_hosted_fields_options(),
				'hostedFieldsStyles'  => $this->get_setting( 'custom_form_styles' ),
				'icons'               => $this->get_payment_method_icons(),
				'editor'              => [
					'icon' => $this->assets_api->assets_url( 'assets/img/dropin_preview.png' )
				],
				'payment_format'      => $token->get_formats()[ $token->get_format() ]['format'],
				'i18n'                => [
					'email_empty'     => __( 'Please provide an email address before using Fastlane.', 'woo-payment-gateway' ),
					'email_invalid'   => __( 'Please enter a valid email address before using Fastlane.', 'woo-payment-gateway' ),
					'continue'        => __( 'Continue', 'woo-payment-gateway' ),
					'cancel'          => __( 'Cancel', 'woo-payment-gateway' ),
					'change'          => __( 'Change', 'woo-payment-gateway' ),
					'fastlane_signup' => __( 'Sign up for', 'woo-payment-gateway' )
				],
				'fastlane_icons'      => [
					'amex'       => $base_url . 'amex.svg',
					'diners'     => $base_url . 'diners.svg',
					'discover'   => $base_url . 'discover.svg',
					'jcb'        => $base_url . 'jcb.svg',
					'maestro'    => $base_url . 'maestro.svg',
					'mastercard' => $base_url . 'mastercard.svg',
					'visa'       => $base_url . 'visa.svg'
				],
				'showSignup'          => $show_signup,
				'fastLaneLogo'        => braintree()->assets_path() . 'img/fastlane.svg'
			];
	}

	protected function get_payment_method_icon() {
		$icons = [];
		$url   = $this->assets_api->assets_url( '../../assets/img/payment-methods/closed/' );
		foreach ( $this->get_setting( 'payment_methods', [] ) as $method ) {
			$icons[] = [
				'id'  => $method,
				'src' => $url . $method . '.svg',
				'alt' => $method
			];
		}

		return $icons;
	}

	private function get_payment_method_icons() {
		$url = $this->assets_api->assets_url( '../../assets/img/payment-methods/closed/' );

		return array_map( function ( $type ) use ( $url ) {
			switch ( $type ) {
				case 'american-express':
					$src = $url . 'amex.svg';
					break;
				case 'master-card':
					$src = $url . 'master_card.svg';
					break;
				case 'diners-club':
					$src = $url . 'diners_club_international.svg';
					break;
				default:
					$src = $url . $type . '.svg';
			}

			return [
				'type' => $type,
				'src'  => $src,
			];
		}, [
			'visa',
			'american-express',
			'discover',
			'master-card',
			'jcb',
			'maestro',
			'diners-club',
			'china_union_pay'
		] );
	}

	private function is_custom_form_enabled() {
		return $this->get_setting( 'form_type' ) === 'custom_form';
	}

	private function get_custom_form_name() {
		return str_replace( '_form', '', $this->get_setting( 'custom_form_design', 'bootstrap_form' ) );
	}

	protected function is_three_d_secure_enabled() {
		return $this->is_three_d_secure_active();
	}

	private function is_three_d_secure_active() {
		return parent::is_three_d_secure_enabled() && wc_braintree_evaluate_condition( $this->get_setting( '3ds_conditions' ) );
	}

	private function is_vaulted_three_d_secure_active() {
		return $this->is_three_d_secure_active() && $this->get_setting( '3ds_enable_payment_token', 'no' ) === 'yes';
	}

	private function get_hosted_fields_options() {
		return [
			'number'          => [
				'placeholder' => __( 'Card Number', 'woo-payment-gateway' ),
			],
			'expirationDate'  => [
				'placeholder' => __( 'MM / YY', 'woo-payment-gateway' ),
			],
			'expirationMonth' => [
				'placeholder' => __( 'MM', 'woo-payment-gateway' ),
			],
			'expirationYear'  => [
				'placeholder' => __( 'YY', 'woo-payment-gateway' ),
			],
			'cvv'             => [
				'placeholder' => __( 'CVV', 'woo-payment-gateway' ),
			],
			'postalCode'      => [
				'placeholder' => __( 'Postal Code', 'woo-payment-gateway' )
			]
		];
	}

	public function enqueue_checkout_scripts() {
		if ( $this->is_custom_form_enabled() ) {
			$form = $this->get_custom_form_name();
			if ( \in_array( $form, [ 'bootstrap', 'simple' ] ) ) {
				wp_register_style( 'wc-braintree-blocks-credit-card-styles', $this->assets_api->assets_url( "build/credit-card/{$form}.css" ), [], $this->assets_api->version() );
				wp_style_add_data( 'wc-braintree-blocks-credit-card-styles', 'rtl', 'replace' );

				wp_enqueue_style( 'wc-braintree-blocks-credit-card-styles' );
			}
		}
	}

}