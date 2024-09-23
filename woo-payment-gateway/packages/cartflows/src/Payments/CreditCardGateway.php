<?php


namespace PaymentPlugins\CartFlows\Braintree\Payments;


class CreditCardGateway extends BasePaymentGateway {

	public function can_process_3ds_payment() {
		return $this->payment_method->_3ds_enabled() && $this->payment_method->is_active( '3ds_enable_payment_token' );
	}
}