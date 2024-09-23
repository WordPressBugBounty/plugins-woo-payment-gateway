<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
wc_braintree_payment_token_field ( $gateway );
?>
<input type="radio" class="wc-braintree-payment-type" checked="checked" id="<?php echo $gateway->id?>_use_token" name="<?php echo $gateway->payment_type_key?>" value="token"/>
<label for="<?php echo $gateway->id?>_use_token" class="wc-braintree-label-payment-type"><?php echo $label?></label>
<div class="wc-braintree-payment-methods-container">
	<select class="wc-braintree-payment-method wc-braintree-select2">
	<?php foreach($methods as $method):?>
		<option data-method-type="<?php echo $method->get_method_type()?>" value="<?php echo $method->get_token()?>"><?php echo $method->get_payment_method_title($gateway->get_option('method_format'))?></option>
	<?php endforeach;?>
	</select>
</div>