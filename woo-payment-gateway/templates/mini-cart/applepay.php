<?php
/**
 * @version 3.2.5
 * @package Braintree/Templates
 */

?>
<a class="wc-braintree-applepay-mini-cart-button button" style="display: none">
	<?php
	wc_braintree_get_template( 'applepay-button.php', array(
		'gateway'      => $gateway,
		'button'       => $gateway->get_option( 'button' ),
		'type'         => $gateway->get_option( 'button_type_cart' ),
		'button_style' => $gateway->get_option( 'button_style', 'standard' )
	) ) ?>
</a>

