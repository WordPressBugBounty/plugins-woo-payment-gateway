<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<?php if($gateway->is_active('loader_enabled')):?>
		<div class="wc-braintree-payment-loader" style="display: none">
			<?php wc_braintree_get_template('loaders/' . $gateway->get_option('loader_design'))?>
		</div>
<?php endif;?>
	
<?php wc_braintree_get_template(wc_braintree_get_custom_form($gateway->get_option('custom_form_design'))['template'], array('gateway' => $gateway, 'fields' => $gateway->get_custom_form_fields()))?>
