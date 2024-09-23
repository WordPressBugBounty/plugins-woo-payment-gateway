<?php
/**
 * @version 3.2.5
 * @var WC_Braintree_Payment_Gateway[] $gateways
 */
?>
<?php foreach ( $gateways as $gateway ) : ?>
    <input type="hidden" class="wc_braintree_mini_cart_gateways"/>
	<?php $gateway->mini_cart_fields() ?>
<?php endforeach; ?>