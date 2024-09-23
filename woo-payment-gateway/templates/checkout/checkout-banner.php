<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="wc-braintree-checkout-banner">
	<ul class="wc_braintree_banner_gateways">
		<?php foreach($gateways as $gateway):?>
			<li class="wc-braintree-banner-gateway wc_braintree_banner_gateway_<?php echo esc_attr($gateway->id)?>">
				<?php $gateway->banner_fields()?>
			</li>
		<?php endforeach;?>
	</ul>
</div>