<?php

namespace PaymentPlugins\Braintree\Fastlane;

class FastlaneController {

	public function initialize() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'woocommerce_checkout_fields', [ $this, 'update_checkout_fields_priority' ] );
		add_action( 'wc_braintree_before_card_container', [ $this, 'render_before_card_container' ] );
	}

	/**
	 * @return \WC_Braintree_CC_Payment_Gateway|null
	 */
	private function get_card_gateway() {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = $gateways['braintree_cc'] ?? null;

		return $gateway;
	}

	private function is_fastlane_enabled() {
		$gateway = $this->get_card_gateway();

		return $gateway && \wc_string_to_bool( $gateway->get_option( 'enabled', 'no' ) )
		       && \wc_string_to_bool( $gateway->get_option( 'fastlane_enabled', 'no' ) );
	}

	private function email_has_priority() {
		$gateway = $this->get_card_gateway();

		return \wc_string_to_bool( $gateway->get_option( 'fastlane_email_top', 'yes' ) );
	}

	public function enqueue_scripts() {
		if ( $this->is_fastlane_enabled() && ( is_checkout() && ! is_order_received_page() ) ) {
			wp_enqueue_script( 'wc-braintree-fastlane-checkout' );
			wp_enqueue_style( 'wc-braintree-fastlane-checkout' );

			$gateway = $this->get_card_gateway();
			$token   = new \WC_Payment_Token_Braintree_CC();
			$token->set_format( $gateway->get_option( 'method_format' ) );

			$base_url = \plugins_url( 'assets/images/payment-methods/', WC_PLUGIN_FILE );

			$data = [
				'html'              => [
					'modal'          => wc_braintree_get_template_html( 'fastlane/modal.php' ),
					'tokenized_card' => wc_braintree_get_template_html( 'fastlane/tokenized-card.php' ),
				],
				'i18n'              => [
					'email_empty'   => __( 'Please provide an email address before using Fastlane.', 'woo-payment-gateway' ),
					'email_invalid' => __( 'Please enter a valid email address before using Fastlane.', 'woo-payment-gateway' )
				],
				'icons'             => [
					'amex'       => $base_url . 'amex.svg',
					'diners'     => $base_url . 'diners.svg',
					'discover'   => $base_url . 'discover.svg',
					'jcb'        => $base_url . 'jcb.svg',
					'maestro'    => $base_url . 'maestro.svg',
					'mastercard' => $base_url . 'mastercard.svg',
					'visa'       => $base_url . 'visa.svg'
				],
				'needs_shipping'    => WC()->cart && WC()->cart->needs_shipping(),
				'payment_format'    => $token->get_formats()[ $token->get_format() ]['format'],
				'fastlane_flow'     => $gateway->get_option( 'fastlane_flow', 'express_button' ),
				'fastlane_pageload' => \wc_string_to_bool( $gateway->get_option( 'fastlane_pageload', 'no' ) )
			];

			wp_localize_script( 'wc-braintree-fastlane-checkout', 'wc_braintree_fastlane_params', $data );
		}
	}

	public function update_checkout_fields_priority( $fields ) {
		if ( $this->is_fastlane_enabled() && $this->email_has_priority() ) {
			if ( isset( $fields['billing']['billing_email'] ) ) {
				$fields['billing']['billing_email']['priority'] = 1;
			}
		}

		return $fields;
	}

	/**
	 * @param \WC_Braintree_CC_Payment_Gateway $gateway
	 *
	 * @return void
	 */
	public function render_before_card_container( $gateway ) {
		if ( $gateway->is_fastlane_enabled() && $gateway->get_option( 'fastlane_flow' ) === 'email_detection' ) {
			$show_signup = \wc_string_to_bool( $gateway->get_option( 'fastlane_signup', 'yes' ) );
			if ( $show_signup && ! is_add_payment_method_page() && ! is_checkout_pay_page() ) {
				wc_braintree_get_template( 'fastlane/signup-link.php' );
			}
		}
	}

}