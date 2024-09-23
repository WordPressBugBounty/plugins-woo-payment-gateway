<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="card-container">
	<div id="dynamic-card-form" class="scale-down">
		<div class="cardinfo-card-number">
			<label class="cardinfo-label" for="wc-braintree-card-number"><?php echo esc_html($fields['number']['label'])?></label>
			<div class='input-wrapper' id="wc-braintree-card-number">
				<span class="wc-braintree-card-type"></span>
			</div>
		</div>
		<div class="cardinfo-wrapper">
			<div class="cardinfo-exp-date">
				<label class="cardinfo-label" for="wc-braintree-expiration-date"><?php echo esc_html( $fields['exp_date']['label'] )?></label>
				<div class='input-wrapper' id="wc-braintree-expiration-date"></div>
			</div>
			<div class="cardinfo-cvv cvv-container">
				<label class="cardinfo-label" for="wc-braintree-cvv"><?php echo esc_html( $fields['cvv']['label'] )?></label>
				<div class='input-wrapper' id="wc-braintree-cvv"></div>
			</div>
		</div>
		<?php if($gateway->is_postal_code_enabled() || wc_braintree_save_cc_enabled()):?>
			<div class="cardinfo-wrapper">
				<?php if($gateway->is_postal_code_enabled()):?>
				<div class="cardinfo-postal-code postalCode-container">
				<label class="cardinfo-label" for="wc-braintree-postal-code"><?php echo esc_html( $fields['postal_code']['label'] )?></label>
				<div class="input-wrapper" id="wc-braintree-postal-code"></div>
				</div>
				<?php endif;?>
				<?php if(wc_braintree_save_cc_enabled()):?>
				<div class="cardinfo-save-card wc-braintree-save-card-container">
					<label class="cardinfo-label"><?php echo esc_html( $fields['save']['label'] )?></label>
					<input type="checkbox" id="<?php echo $gateway->save_method_key?>"
						name="<?php echo $gateway->save_method_key?>"> <label class="wc-braintree-save-label"
						for="<?php echo $gateway->save_method_key?>"></label>
				</div>
				<?php endif;?>
			</div>
		<?php endif;?>
		<?php
		
		if ($gateway->should_display_street()) :
			$checkout = WC()->checkout();
			?>
		<div class="cardinfo-wrapper">
			<div class="cardinfo-streetAddress">
				<label class="cardinfo-label"><?php esc_html( $fields['street']['label'] )?></label>
				<div class="input-wrapper streetAddress">
					<input type="text" id="billing_address_1" name="billing_address_1"
						placeholder="<?php esc_attr_e('Street Address', 'woocommerce')?>"
						value="<?php echo $checkout->get_value('billing_address_1')?>" />
				</div>
			</div>
		</div>
		<?php endif;?>
	</div>
</div>
