<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<h2><?php esc_html_e('Subscriptions', 'woo-payment-gateway' )?></h2>
<table class="shop_table order_details">
	<thead>
		<tr>
			<th><?php esc_html_e('Subscription', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Status', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Next Payment', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Total', 'woo-payment-gateway' )?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($subscriptions as $subscription):?>
		<tr>
			<td>
				<a href="<?php echo $subscription->get_view_subscription_url()?>">#<?php echo $subscription->get_order_number()?></a>
			</td>
			<td><?php echo get_post_status_object(get_post_status($subscription->get_id()))->label?></td>
			<td><?php echo $subscription->get_formatted_date('next_payment')?></td>
			<td><?php echo $subscription->get_formatted_total()?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>