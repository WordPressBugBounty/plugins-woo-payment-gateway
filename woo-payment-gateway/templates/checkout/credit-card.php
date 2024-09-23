<?php
/**
 * @version 3.2.5
 * @package Braintree/Templates
 * @var WC_Braintree_CC_Payment_Gateway $gateway
 */
wc_braintree_hidden_field( $gateway->_3ds_nonce_key, 'wc-braintree-3ds-vaulted-nonce' );
wc_braintree_hidden_field( $gateway->config_key );
?>
<div class="wc-braintree-cc-container <?php echo $gateway->get_option( 'icon_style' ) ?>-icons">
	<?php
	if ( $gateway->is_custom_form_active() ) {
		wc_braintree_get_template( 'custom-form.php', array(
			'gateway' => $gateway
		) );
	} else {
		wc_braintree_get_template( 'dropin-form.php', array(
			'gateway' => $gateway
		) );
	}
	?>
</div>