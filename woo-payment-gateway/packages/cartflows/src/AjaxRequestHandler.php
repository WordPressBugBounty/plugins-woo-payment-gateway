<?php


namespace PaymentPlugins\CartFlows\Braintree;

/**
 * Class AjaxRequestHandler
 * @package PaymentPlugins\CartFlows\Braintree
 */
class AjaxRequestHandler {

	public function __construct() {
		add_action( 'wc_ajax_wc_braintree_cartflows_nonce', array( $this, 'handle_vaulted_nonce' ) );
	}

	public function handle_vaulted_nonce() {
		check_ajax_referer( 'wc-braintree-vaulted-nonce', 'security' );
		$order = wc_get_order( absint( $_POST['order_id'] ) );
		$order->update_meta_data( Constants::VAULTED_NONCE . wc_clean( $_POST['step_id'] ), wc_clean( $_POST['nonce'] ) );
		$order->save();
		wp_send_json_success();
	}

}