<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="classic-form-container">
    <div class="card-number-wrapper field-container">
        <span class="field-label"><?php echo esc_html( $fields['number']['label'] ) ?></span>
        <div id="wc-braintree-card-number" class="hosted-field">
            <span class="wc-braintree-card-type"></span>
        </div>
        <span class="wc-braintree-error"></span>
    </div>
    <div class="form-group-wrapper multi-fields">
        <div class="exp-date-field field-container">
            <span class="field-label"><?php echo esc_html( $fields['exp_date']['label'] ) ?></span>
            <div id="wc-braintree-expiration-date" class="hosted-field"
                 data-placeholder="<?php esc_attr_e( 'MM / YY', 'woo-payment-gateway' ) ?>"></div>
            <span class="wc-braintree-error"></span>
        </div>
        <div class="cvv-field field-container cvv-container">
            <span class="field-label"><?php echo esc_html( $fields['cvv']['label'] ) ?></span>
            <div id="wc-braintree-cvv" class="hosted-field"></div>
            <span class="cvv-image"><img src="<?php echo esc_url( braintree()->assets_path() . 'img/cvv.svg' ) ?>"/></span> <span
                    class="wc-braintree-error"></span>
        </div>
    </div>
	<?php if ( $gateway->is_postal_code_enabled() || wc_braintree_save_cc_enabled() ): ?>
        <div class="form-group-wrapper multi-fields">
			<?php if ( $gateway->is_postal_code_enabled() ): ?>
                <div class="postal-field field-container postalCode-container">
                    <span class="field-label"><?php echo esc_html( $fields['postal_code']['label'] ) ?></span>
                    <div id="wc-braintree-postal-code" class="hosted-field"
                         data-placeholder="<?php esc_attr_e( 'Postal Code', 'woo-payment-gateway' ) ?>"></div>
                    <span class="wc-braintree-error"></span>
                </div>
			<?php endif; ?>
			<?php if ( wc_braintree_save_cc_enabled() ): ?>
                <div class="save-card-field field-container wc-braintree-save-card-container">
                    <span class="field-label active"><?php echo esc_html( $fields['save']['label'] ) ?></span>
                    <input type="checkbox" id="<?php echo $gateway->save_method_key ?>"
                           name="<?php echo $gateway->save_method_key ?>"> <label class="wc-braintree-save-label"
                                                                                  for="<?php echo $gateway->save_method_key ?>"></label>
                </div>
			<?php endif; ?>
        </div>
	<?php endif; ?>
	<?php

	if ( $gateway->should_display_street() ) :
		$checkout = WC()->checkout();
		?>
        <div class="streetAddress-wrapper field-container">
            <span class="field-label"><?php echo esc_html( $fields['street']['label'] ) ?></span>
            <div class="hosted-field streetAddress">
                <input type="text" id="billing_address_1" name="billing_address_1"
                       placeholder="<?php esc_attr_e( 'Street Address', 'woocommerce' ) ?>"
                       value="<?php echo $checkout->get_value( 'billing_address_1' ) ?>"/>
            </div>
        </div>
	<?php endif; ?>
</div>