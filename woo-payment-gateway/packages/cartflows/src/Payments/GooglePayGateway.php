<?php


namespace PaymentPlugins\CartFlows\Braintree\Payments;

/**
 * Class GooglePayGateway
 * @package PaymentPlugins\CartFlows\Braintree\Payments
 */
class GooglePayGateway extends BasePaymentGateway {

	public function can_process_3ds_payment() {
		return $this->payment_method->is_active( '3ds_enabled' );
	}
}