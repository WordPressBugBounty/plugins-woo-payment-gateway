<?php


namespace PaymentPlugins\Braintree\WooFunnels\Upsell\Payments\Gateways;


class CreditCardGateway extends BasePaymentGateway {

	protected $key = 'braintree_cc';

	public function __construct( ...$args ) {
		parent::__construct( ...$args );
		add_filter( 'wc_braintree_save_cc_enabled', [ $this, 'show_save_cc_enabled' ] );
	}

	public function show_save_cc_enabled( $bool ) {
		if ( $bool ) {
			if ( $this->should_tokenize() ) {
				$bool = false;
			}
		}

		return $bool;
	}

	public function should_store_in_vault() {
		return ! $this->get_wc_gateway()->use_3ds_vaulted_nonce();
	}

	public function is_vaulted_threed_secure_enabled() {
		$payment_method = $this->get_wc_gateway();

		return $payment_method->is_active( '3ds_enabled' ) && $payment_method->is_active( '3ds_enable_payment_token' );
	}

}