<?php
/**
 * @var WC_Braintree_Payment_Gateway $gateway
 * @package Braintree/Templates
 * @version 3.0.0
 */

?>
<div class="wc-braintree-payment-gateway <?php if ( $has_methods ) { ?>has_methods<?php } ?>">
	<?php
	wc_braintree_nonce_field( $gateway );
	wc_braintree_device_data_field( $gateway );
	?>
	<?php
	$description = $gateway->get_description();
	if ( $description ) {
		echo wp_kses_post( wpautop( wptexturize( $description ) ) );
	}
	?>
	<?php if ( $has_methods ): ?>
        <input type="radio" class="wc-braintree-payment-type" id="<?php echo $gateway->id ?>_use_nonce"
               name="<?php echo $gateway->payment_type_key ?>" value="nonce"/>
        <label class="wc-braintree-label-payment-type"
               for="<?php echo $gateway->id ?>_use_nonce"><?php echo $gateway->get_new_method_label() ?></label>
	<?php endif; ?>
    <div class="wc-braintree-new-payment-method-container" style="<?php $has_methods ? printf( 'display: none' ) : printf( '' ) ?>">
		<?php wc_braintree_get_template( 'checkout/' . $gateway->template, array( 'gateway' => $gateway ) ) ?>
    </div>
	<?php
	if ( $methods ) {
		wc_braintree_get_template( 'payment-methods.php', array(
			'gateway' => $gateway,
			'methods' => $methods,
			'label'   => $gateway->get_saved_method_label()
		) );
	}
	?>
</div>