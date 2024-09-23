<?php
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */

?>
<div class="panel">
    <div class="panel__content">
        <div class="textfield--float-label card--number-float">
            <label class="hosted-field--label" for="wc-braintree-card-number"> <span
                        class="icon"> <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                           height="20" viewBox="0 0 24 24">
						<path d="M0 0h24v24H0z" fill="none"/>
						<path
                                d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
			</span>
				<?php echo esc_html( esc_html( $fields['number']['label'] ) ) ?> </label>
            <div id="wc-braintree-card-number" class="hosted-field">
                <span class="wc-braintree-card-type"></span>
            </div>
        </div>

        <div class="textfield--float-label exp--date-float">
            <label class="hosted-field--label" for="wc-braintree-expiration-date"> <span
                        class="icon"> <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                           height="20" viewBox="0 0 24 24">
						<path
                                d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
			</span> <?php echo esc_html( $fields['exp_date']['label'] ) ?>
            </label>
            <div id="wc-braintree-expiration-date" class="hosted-field"></div>
        </div>
        <div class="textfield--float-label cvv--float cvv-container">
            <label class="hosted-field--label" for="wc-braintree-cvv"> <span class="icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24">
							<path
                                    d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
			</span> <?php echo esc_html( $fields['cvv']['label'] ) ?>
            </label>
            <div id="wc-braintree-cvv" class="hosted-field"></div>
        </div>
		<?php if ( $gateway->is_postal_code_enabled() ): ?>
            <div class="textfield--float-label postal--float postalCode-container">
                <label class="hosted-field--label" for="wc-braintree-postal-code"> <span
                            class="icon"> <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                               height="20" viewBox="0 0 24 24">
		    					<path
                                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
			</span> <?php echo esc_html( $fields['postal_code']['label'] ) ?>
                </label>
                <div id="wc-braintree-postal-code" class="hosted-field"></div>
            </div>
		<?php endif; ?>
		<?php if ( wc_braintree_save_cc_enabled() ): ?>
            <div class="textfield--float-label save--card-float wc-braintree-save-card-container">
                <label class="hosted-field--label"> <span
                            class="icon"> <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                               height="20" viewBox="0 0 24 24">>
						    <path
                                    d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM8.9 6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2H8.9V6zM18 20H6V10h12v10z"/>
						</svg>
			</span><?php echo esc_html( $fields['save']['label'] ) ?>
                </label>
                <div class="hosted-field">
                    <input type="checkbox" id="<?php echo $gateway->save_method_key ?>"
                           name="<?php echo $gateway->save_method_key ?>"> <label class="wc-braintree-save-label"
                                                                                  for="<?php echo $gateway->save_method_key ?>"></label>
                </div>
            </div>
		<?php endif; ?>
		<?php if ( $gateway->should_display_street() ) :
			$checkout = WC()->checkout();
			?>
            <div class="textfield--float-label">
                <label class="hosted-field--label" for=""> <span class="icon"> <svg
                                xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
						<svg xmlns="http://www.w3.org/2000/svg" fill="#000000" height="24"
                             viewBox="0 0 24 24" width="24">
    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
    <path d="M0 0h24v24H0z" fill="none"/>
</svg>
						</svg>
			</span>
					<?php echo esc_html( $fields['street']['label'] ) ?></label>
                <div class="hosted-field streetAddress">
                    <input type="text" id="billing_address_1" name="billing_address_1"
                           placeholder="<?php esc_attr_e( 'Street Address', 'woocommerce' ) ?>"
                           value="<?php echo $checkout->get_value( 'billing_address_1' ) ?>">
                </div>
            </div>
		<?php endif; ?>
    </div>
</div>