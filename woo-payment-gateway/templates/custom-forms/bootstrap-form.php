<?php
/**
 * @version 3.2.8
 * @package Braintree/Templates
 */
?>
    <div class="row">
        <div class="form-group col-xs-8">
            <label class="control-label"><?php echo esc_html( $fields['number']['label'] ) ?></label>
            <!--  Hosted Fields div container -->
            <div class="form-control" id="wc-braintree-card-number">
                <span class="wc-braintree-card-type"></span>
            </div>
        </div>
        <div class="form-group col-xs-4">
            <label class="control-label"><?php echo esc_html( $fields['exp_date']['label'] ) ?></label>
            <div class="form-control" id="wc-braintree-expiration-date"></div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-6 cvv-container">
            <label class="control-label"><?php echo esc_html( $fields['cvv']['label'] ) ?></label>
            <div class="form-control" id="wc-braintree-cvv"></div>
        </div>
		<?php if ( $gateway->is_postal_code_enabled() ): ?>
            <div class="form-group col-xs-6 postalCode-container">
                <label class="control-label"><?php echo esc_html( $fields['postal_code']['label'] ) ?></label>
                <div class="form-control" id="wc-braintree-postal-code"></div>
            </div>
		<?php endif; ?>
    </div>
<?php if ( wc_braintree_save_cc_enabled() ): ?>
    <div class="row">
        <div class="form-group col-xs-6 wc-braintree-save wc-braintree-save-card-container">
            <label class="save-card-label"><?php echo esc_html( $fields['save']['label'] ) ?></label>
            <input type="checkbox" id="<?php echo $gateway->save_method_key ?>"
                   name="<?php echo $gateway->save_method_key ?>"> <label
                    class="wc-braintree-save-label" for="<?php echo $gateway->save_method_key ?>"></label>
        </div>
    </div>
<?php endif; ?>
<?php

if ( $gateway->should_display_street() ) :
	$checkout = WC()->checkout();
	?>
    <div class="row">
        <div class="form-group col-xs-12">
            <label class="control-label"><?php echo esc_html( $fields['street']['label'] ) ?></label>
            <div class="form-control streetAddress">
                <input type="text" id="billing_address_1" name="billing_address_1"
                       placeholder="<?php esc_attr_e( 'Street Address', 'woocommerce' ) ?>"
                       value="<?php echo $checkout->get_value( 'billing_address_1' ) ?>">
            </div>
        </div>
    </div>
<?php endif; ?>