<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="custom-form-checkout-loader">
	<h1><?php esc_html_e('Processing...', 'woo-payment-gateway')?></h1>
</div>

<style>
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader {
	background: rgba(255, 255, 255, 0.76);
	display: flex;
	display: -webkit-flex;
	align-items: center;
	justify-content: center;
}
</style>