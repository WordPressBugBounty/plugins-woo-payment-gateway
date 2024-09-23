<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	/**
	 * The purpose of this update is to migrate the payment_sections settings for Apple Pay, GPay, and PaymentRequest
	 * so they align with the new Cart & Checkout Blocks. Previously, these payment methods would show up on the cart and checkout
	 * block if they were just enabled. They ignored the payment sections setting where as going forward, they will use the payment_sections
	 * settings
	 */
	$args = [
		[ 'checkout', '<\!--(\s+)?+wp:woocommerce\/checkout', 'checkout_banner' ],
		[ 'cart', '<\!--(\s+)?+wp:woocommerce\/cart', 'cart' ]
	];
	if ( version_compare( WC()->version, '8.3.0', '>=' ) ) {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		foreach ( $args as $list ) {
			list( $page, $regex, $type ) = $list;
			$page_id = wc_get_page_id( $page );
			$ids     = [ 'braintree_applepay', 'braintree_googlepay', 'braintree_paypal' ];
			if ( $page_id ) {
				$post = get_post( $page_id );
				if ( $post && $post instanceof WP_Post ) {
					$content = $post->post_content;
					// see if the checkout page is using blocks.
					if ( preg_match( "/${regex}/", $content ) ) {
						// checkout page is using Block, so update Apple Pay, GPay, and Payment Request Settings.
						foreach ( $ids as $id ) {
							/**
							 * @var \WC_Braintree_Payment_Gateway $payment_gateway
							 */
							$payment_gateway = isset( $payment_gateways[ $id ] ) ? $payment_gateways[ $id ] : null;
							if ( $payment_gateway ) {
								$payment_sections = $payment_gateway->get_option( 'sections', array() );
								/**
								 * If the payment method is enabled, but Express Checkout isn't enabled, that means they were using
								 * Express Checkout for Blocks
								 */
								if ( $payment_gateway->enabled === 'yes' && ! in_array( $type, $payment_sections, true ) ) {
									$payment_sections[] = $type;
									$payment_gateway->update_option( 'sections', $payment_sections );
								}
							}
						}
					}
				}
			}
		}
	}
}