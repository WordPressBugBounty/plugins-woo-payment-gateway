<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<table>
	<tbody>
		<tr>
			<th><?php esc_html_e('Status', 'woo-payment-gateway' )?></th>
			<td><?php echo get_post_status_object(get_post_status($subscription->get_id()))->label?></td>
		</tr>
		<tr>
			<th><?php esc_html_e('Start Date', 'woo-payment-gateway')?>
			<td><?php echo $subscription->get_formatted_date('start')?></td>
		</tr>
		<?php if($subscription->get_previous_payment_date()):?>
		<tr>
			<th><?php esc_html_e('Previous Payment', 'woo-payment-gateway' )?></th>
			<td><?php echo $subscription->get_formatted_date('previous_payment')?></td>
		</tr>
		<?php endif;?>
		<tr>
			<th><?php esc_html_e('Next Payment', 'woo-payment-gateway' )?></th>
			<td><?php echo $subscription->get_formatted_date('next_payment')?></td>
		</tr>
		<tr>
			<th><?php esc_html_e('End Date', 'woo-payment-gateway' )?></th>
			<td><?php echo $subscription->get_formatted_date('end')?></td>
		</tr>
		<tr>
			<th><?php esc_html_e('Actions', 'woo-payment-gateway' )?></th>
			<td>
				<?php foreach(wcs_braintree_subscription_user_actions($subscription) as $k => $action):?>
					<a href="<?php echo $action['url']?>"
				class="button action-<?php echo $k?>"><?php echo $action['label']?></a>
				<?php endforeach;?>
			</td>
		</tr>
	</tbody>
</table>
<h2><?php esc_html_e('Subscription Totals', 'woo-payment-gateway' )?></h2>
<table>
	<tbody>
		<?php foreach($subscription->get_items('line_item') as $item_id => $item):?>
			<?php wc_get_template('order/order-details-item.php', array(
					'order' => $subscription,
					'item_id'		     => $item_id,
					'item'			     => $item,
					'show_purchase_note' => false,
					'purchase_note'	     => '',
					'product'	         => $item->get_product()
			))?>
		<?php endforeach;?>
		<?php foreach($subscription->get_order_item_totals() as $key => $total):?>
			<tr>
				<th scope="row"><?php echo $total['label']; ?></th>
				<td><?php echo $total['value']?></td>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>
<h2><?php esc_html_e('Related Orders', 'woo-payment-gateway' )?></h2>
<?php
$related_orders = wcs_braintree_get_related_orders( $subscription );
$has_orders = ( bool ) $related_orders;

if ( ! $has_orders ) :
	printf( __( 'There are no orders associated with this subscription.', 'woo-payment-gateway' ) );
 else :
	?>
<table>
	<thead>
		<tr>
			<th><?php esc_html_e('Order', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Date', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Status', 'woo-payment-gateway' )?></th>
			<th><?php esc_html_e('Total', 'woo-payment-gateway' )?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($related_orders as $order):?>
		<tr>
			<td><a href="<?php echo $order->get_view_order_url()?>"><?php printf('#%s', $order->get_order_number())?></a></td>
			<td><?php echo date_i18n(get_option('date_format'), strtotime($order->get_date_created()))?></td>
			<td><?php echo wc_get_order_status_name($order->get_status())?></td>
			<td><?php echo $order->get_formatted_order_total()?></td>
		</tr>
	<?php endforeach;?>
	</tbody>
</table>
<?php endif;?>
<div id="wc-braintree-subscription-dialog" style="display:none">
	<p><?php printf(__('Please click Confirm if you wish to cancel this subscription.', 'woo-payment-gateway'), $subscription->get_id())?></p>
</div>