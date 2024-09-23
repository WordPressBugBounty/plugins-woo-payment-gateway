<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Payment_Gateway' ) ) {
	return;
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes/Gateways
 *
 */
class WC_Braintree_Venmo_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'braintree_venmo';
		$this->template           = 'venmo.php';
		$this->token_type         = 'Venmo';
		$this->method_title       = __( 'Braintree Venmo Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Venmo', 'woo-payment-gateway' );
		$this->method_description = __( 'Gateway that integrates Venmo with your Braintree account.', 'woo-payment-gateway' );
		parent::__construct();
		$this->icon = braintree()->assets_path() . 'img/payment-methods/' . $this->get_option( 'icon' ) . '.svg';
	}

	public function add_hooks() {
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		parent::add_hooks();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'venmo',
			$scripts->assets_url( 'js/frontend/venmo.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'venmo-v3' ),
				$scripts->get_handle( 'data-collector-v3' ),
			)
		);
		$scripts->localize_script( 'venmo', $this->localize_venmo_params() );
	}

	/**
	 *
	 * @return array
	 */
	public function localize_venmo_params() {
		return array_merge(
			$this->get_localized_standard_params(),
			array(
				'html' => array( 'button' => wc_braintree_get_template_html( 'venmo-button.php' ) ),
			)
		);
	}

	/**
	 *
	 * @param array $item
	 * @param WC_Payment_Token_Braintree $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( 'Braintree_Venmo' !== $payment_token->get_type() ) {
			return $item;
		}
		$item['method']['brand']     = $payment_token->get_payment_method_title( $this->get_option( 'method_format' ) );
		$item['expires']             = __( 'N/A', 'woo-payment-gateway' );
		$item['method_type']         = $payment_token->get_method_type();
		$item['wc_braintree_method'] = true;

		return $item;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->venmoAccountDetails;
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'venmo' ) );
		}
	}
}
