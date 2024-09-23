<?php
/**
 * @version 3.1.4
 * @package Braintree/Templates
 */
?>
<div class="wc-braintree-cart-gateways-container" <?php if ( $cart_total == 0 ){ ?>style="display: none"<?php } ?>>
    <form id="wc-braintree-cart-fields-form" method="post">
		<?php do_action( 'wc_braintree_cart_form_fields' ) ?>
        <ul class="wc_braintree_cart_gateways">
			<?php if ( $priority > 20 ): ?>
                <li class="wc-braintree-cart-text">&mdash;&nbsp;<?php esc_html_e( 'or', 'woo-payment-gateway' ) ?>&nbsp;&mdash;</li>
			<?php endif ?>
			<?php /* uncomment to show terms and conditions on cart page
                    <li class="terms-and-conditions"><?php wc_get_template( 'checkout/terms.php' ); ?></li>
             */ ?>
			<?php foreach ( $gateways as $gateway ) { ?>
                <li class="wc_braintree_cart_gateway wc_braintree_cart_gateway_<?php echo esc_attr( $gateway->id ) ?>">
					<?php $gateway->cart_fields(); ?>
                </li>
			<?php } ?>
			<?php if ( $priority < 20 ): ?>
                <li class="wc-braintree-cart-text">&mdash;&nbsp;<?php esc_html_e( 'or', 'woo-payment-gateway' ) ?>&nbsp;&mdash;</li>
			<?php endif ?>
        </ul>
    </form>
</div>