<?php
/**
 * @version 3.2.25
 * @package Braintree/Templates
 */

?>
<button class="apple-pay-button apple-pay-button-<?php echo esc_attr( $button_style ) ?> <?php echo esc_attr( $button ) ?>"
        style="<?php echo '-apple-pay-button-style: ' . esc_attr( $style ) . '; -apple-pay-button-type:' . esc_attr( apply_filters( 'wc_braintree_applepay_button_type', $type ) ) ?>"></button>