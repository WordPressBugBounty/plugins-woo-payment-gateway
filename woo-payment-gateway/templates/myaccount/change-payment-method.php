<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
wc_print_notices();
?>
<form id="order_review" method="post" class="wc-braintree-change-payment-method-form">
	<?php wp_nonce_field('change-payment-method', 'wc_braintree_change_method_nonce')?>
	<input type="hidden" name="wc_braintree_subscription" value="<?php echo $subscription->get_id()?>">
	<table>
		<tbody>
			<tr>
				<th><?php esc_html_e('Subscription', 'woo-payment-gateway' )?></th>
				<td><a href="<?php echo $subscription->get_view_subscription_url()?>"><?php printf(__('#%s', 'woo-payment-gateway' ), $subscription->get_order_number())?></a></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Payment Method', 'woo-payment-gateway' )?></th>
				<td><?php echo $subscription->get_payment_method_to_display()?></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Order Total', 'woo-payment-gateway' )?></th>
				<td><?php echo $subscription->get_formatted_total()?></td>
			</tr>
		</tbody>
	</table>
	<?php do_action('wcs_braintree_before_change_payment_method_gateways', $subscription)?>
	<?php if ( ! empty( $available_gateways ) ) :?>
	<div id="payment">
		<ul class="wc_payment_methods payment_methods methods">
			<?php
				foreach ( $available_gateways as $gateway ) {
					if($gateway->supports('wc_braintree_subscriptions_change_payment_method')){
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				}
			?>
		</ul>
	</div>
	<div class="form-row">
		<input type="submit" id="place_order" class="button alt" value="<?php esc_html_e('Change Payment Method', 'woo-payment-gateway' )?>" data-value="<?php  esc_html_e('Change Payment Method', 'woo-payment-gateway' )?>">
	</div>
	<?php else: 
		echo '<li>' . apply_filters( 'woocommerce_no_available_payment_methods_message', __( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ) . '</li>';
	?>
	<?php endif;?>
</form>