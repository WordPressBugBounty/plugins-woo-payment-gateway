<?php
/**
 * @var WC_Braintree_Payment_Gateway $gateway
 * @package Braintree/Templates
 * @version 3.2.27
 */

?>
<div class="wc-braintree-applepay-product-checkout-container">
	<?php
	wc_braintree_get_template( 'applepay-button.php', array(
		'gateway'      => $gateway,
		'button'       => $gateway->product_gateway_option->get_option( 'button' ),
		'type'         => $gateway->product_gateway_option->get_option( 'button_type_product' ),
		'style'        => $gateway->get_applepay_button_style(),
		'button_style' => $gateway->get_option( 'button_style', 'standard' )
	) ) ?>
</div>