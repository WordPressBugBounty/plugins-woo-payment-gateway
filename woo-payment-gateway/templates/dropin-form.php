<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div id="wc-braintree-dropin-container">
    <div id="wc-braintree-dropin-form"></div>
	<?php if ( $gateway->should_display_street() ) :
		$checkout = WC()->checkout(); ?>
        <div class="row dropin-row">
            <div class="form-group col-xs-12 wc-braintree-dropin-column">
                <label class="control-label braintree-form__label"><?php esc_html_e( 'Street Address', 'woocommerce' ) ?></label>
                <div class="form-control streetAddress">
                    <input type="text" id="billing_address_1" name="billing_address_1"
                           placeholder="<?php esc_attr_e( 'Street Address', 'woocommerce' ) ?>"
                           value="<?php echo $checkout->get_value( 'billing_address_1' ) ?>">
                </div>
            </div>
        </div>
	<?php endif; ?>
	<?php if ( wc_braintree_save_cc_enabled() ): ?>
        <div class="wc-braintree-dropin-row">
            <div class="wc-braintree-dropin-column wc-braintree-dropin-card-label wc-braintree-save">
                <label class="wc-braintree-save-method"><?php esc_html_e( 'Save Card', 'woo-payment-gateway' ) ?></label>
                <input type="checkbox" id="<?php echo $gateway->save_method_key ?>"
                       name="<?php echo $gateway->save_method_key ?>"/>
                <label class="wc-braintree-dropin-save-label" for="<?php echo $gateway->save_method_key ?>"></label>
            </div>
        </div>
	<?php endif; ?>
</div>