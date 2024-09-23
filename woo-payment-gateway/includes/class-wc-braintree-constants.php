<?php

namespace PaymentPlugins;

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.7
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Constants {

	const PAYMENT_METHOD_TOKEN = '_payment_method_token';

	const VERSION = '_wc_braintree_version';

	const MERCHANT_ACCOUNT_ID = '_merchant_account_id';

	const ENVIRONMENT = '_wc_braintree_environment';

	const TRANSACTION_STATUS = '_transaction_status';

	const AUTH_EXP = '_authorization_exp_at';

	const BRAINTREE_CC = 'braintree_cc';

	const BRAINTREE_CREDIT_CARD = 'braintree_credit_card';

	const BRAINTREE_PAYPAL = 'braintree_paypal';

	const BRAINTREE_GOOGLEPAY = 'braintree_googlepay';

	const BRAINTREE_APPLEPAY = 'braintree_applepay';

	const BRAINTREE_VENMO = 'braintree_venmo';

	const TOKEN_CHECK = 'wc_braintree_token_check';

	/**
	 *
	 * @since 3.1.9
	 * @var string
	 */
	const PAYPAL_TRANSACTION = '_paypal_transaction_id';

	/**
	 *
	 * @since 3.1.10
	 * @var string
	 */
	const PAYMENT_ID = '_wc_braintree_payment_id';

	const SESSION_ID = '_session_id';

	const PRODUCT_GATEWAY_ORDER = '_braintree_gateway_order';

	const BUTTON_POSITION = '_braintree_button_position';

	const PAYPAL_CHECKOUT = 'checkout';

	const PAYPAL_VAULT = 'vault';

	const CUSTOMER_ID = '_wc_braintree_customer_id';
}
