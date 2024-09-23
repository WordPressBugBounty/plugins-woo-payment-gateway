<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<?php if($current_page > $max_pages):?>
	<?php printf(__('You have reached the end of your subscriptions. %sFirst page%s','woo-payment-gateway'), '<a href="' . wc_get_endpoint_url('subscriptions') . '">', '</a>')?>
<?php elseif(empty($subscriptions)):?>
	<?php printf ( __ ( 'You do not currently have any subscriptions.', 'woo-payment-gateway' ) );?>
<?php else:?>
<table class="woocommerce_subscriptions_table">
	<thead>
		<tr>
			<th><?php esc_html_e('Subscription', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Status', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Next Payment', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Total', 'woo-payment-gateway' )?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($subscriptions as $subscription):?>
		<tr>
			<td><a href="<?php echo $subscription->get_view_subscription_url()?>">#<?php echo $subscription->get_order_number()?></a></td>
			<td><?php echo get_post_status_object(get_post_status($subscription->get_id()))->label?></td>
			<td><?php echo $subscription->get_formatted_date('next_payment')?></td>
			<td><?php echo $subscription->get_formatted_total()?></td>
			<td>
				<?php foreach(wcs_braintree_get_subscription_actions($subscription) as $key => $action):?>
					<a class="button <?php echo $key?>" href="<?php echo $action['url']?>"><?php echo $action['label']?></a>
				<?php endforeach;?>
			</td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<p>
<?php if($max_pages > 1):?>
	<?php if($current_page == 1):?>
		<a class="wcs-braintree-next" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions', $current_page + 1))?>"><?php esc_html_e('Next', 'woo-payment-gateway')?></a>
	<?php elseif ($current_page == $max_pages):?>
		<a class="wcs-braintree-previous" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions', $current_page - 1))?>"><?php esc_html_e('Previous', 'woo-payment-gateway')?></a>
		<a class="wcs-braintree-first-page" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions', 1))?>"><?php esc_html_e('First page', 'woo-payment-gateway')?></a>
	<?php else:?>
		<a class="wcs-braintree-previous" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions', $current_page - 1))?>"><?php esc_html_e('Previous', 'woo-payment-gateway')?></a>
		<a class="wcs-braintree-next" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions', $current_page + 1))?>"><?php esc_html_e('Next', 'woo-payment-gateway')?></a>
	<?php endif;?>
<?php endif;?>
</p>
<?php endif;?>