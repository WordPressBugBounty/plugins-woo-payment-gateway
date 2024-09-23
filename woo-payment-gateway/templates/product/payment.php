<?php
/**
 * @version 3.1.10
 * @package Braintree/Templates
 */
?>
<div class="wc-braintree-clear"></div>
<div class="wc-braintree-product-gateways-container">
	<ul class="wc-braintree-product-gateways wc_braintree_product_gateways" style="display:none">
		<?php foreach($gateways as $gateway):?>
		<li class="wc-braintree-product-gateway wc_braintree_product_gateway_<?php echo $gateway->id?>">
			<?php $gateway->product_fields()?>
		</li>
		<?php endforeach;?>
	</ul>
</div>
<style type="text/css">
	ul.wc-braintree-product-gateways{
		list-style: none;
	}
</style>