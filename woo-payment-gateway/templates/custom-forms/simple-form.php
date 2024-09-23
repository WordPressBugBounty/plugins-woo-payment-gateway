<?php
/**
 * @version 3.1.4
 * @package Braintree/Templates
 */
?>
<div class="simple-form">
	<div class="form-group">
		<label><?php echo esc_html($fields['number']['label'])?></label>
		<div id="wc-braintree-card-number" 
			class="hosted-field">
			<span class="wc-braintree-card-type"></span>
		</div>
	</div>
	<div class="form-group">
		<label><?php echo esc_html( $fields['exp_date']['label'] )?></label>
		<div id="wc-braintree-expiration-date" class="hosted-field"></div>
	</div>
	<div class="form-group cvv-container">
		<label><?php echo esc_html( $fields['cvv']['label'] )?></label>
		<div id="wc-braintree-cvv" class="hosted-field"></div>
	</div>
	<?php if($gateway->is_postal_code_enabled()):?>
	<div class="form-group postalCode-container">
		<label><?php echo esc_html( $fields['postal_code']['label'] )?></label>
		<div id="wc-braintree-postal-code"
			data-placeholder="<?php esc_attr_e('Postal Code', 'woo-payment-gateway')?>"
			class="hosted-field"></div>
	</div>
	<?php endif?>
	<?php if(wc_braintree_save_cc_enabled()):?>
	<div class="form-group wc-braintree-save-card-container">
		<label><?php echo esc_html( $fields['save']['label'] )?></label>
		<div class="hosted-field save-card-field">
			<input type="checkbox" id="<?php echo $gateway->save_method_key?>"
				name="<?php echo $gateway->save_method_key?>"> <label class="wc-braintree-save-label"
				for="<?php echo $gateway->save_method_key?>"></label>
		</div>
	</div>
	<?php endif;?>
	<?php
	
if ($gateway->should_display_street()) :
		$checkout = WC()->checkout();
		?>
	<div class="form-group streetAddress">
		<label><?php echo esc_html( $fields['street']['label'] )?></label>
		<div class="hosted-field">
			<input type="text" id="billing_address_1" name="billing_address_1"
				placeholder="<?php esc_attr_e('Street Address', 'woocommerce')?>"
				value="<?php echo $checkout->get_value('billing_address_1')?>" />
		</div>
	</div>
	<?php endif;?>
</div>