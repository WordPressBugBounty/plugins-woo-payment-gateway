<?php
/**
 * @version 3.0.8
 * @package Braintree/Templates
 * @var WC_Braintree_Local_Payment_Gateway $gateway
 */
printf ( '<input type="hidden" id="%s_active" data-active="%s"/>', $gateway->id, $gateway->is_local_payment_available () );
wc_braintree_hidden_field($gateway->payment_id_key);
?>

