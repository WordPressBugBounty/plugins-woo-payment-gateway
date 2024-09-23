<div class="wcs_braintree_related_orders">
	<?php if ( empty( $orders ) ) : ?>
		<p><?php esc_html_e( 'There are no orders associated with this subscription.', 'woo-payment-gateway' ); ?></p>
	<?php else : ?>
	<table class="woocommerce_order_items">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order Number', 'woo-payment-gateway' ); ?></th>
				<th><?php esc_html_e( 'Relationship', 'woo-payment-gateway' ); ?></th>
				<th><?php esc_html_e( 'Status', 'woo-payment-gateway' ); ?></th>
				<th><?php esc_html_e( 'Total', 'woo-payment-gateway' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $orders as $order ) : ?>
			<tr>
				<td><a href="<?php echo get_edit_post_link( $order->get_id() ); ?>">#<?php echo $order->get_order_number(); ?></a></td>
				<td><?php $subscription->get_order( $subscription->get_parent_id() )->get_id() === $order->get_id() ? esc_html_e( 'Parent Order', 'woo-payment-gateway' ) : esc_html_e( 'Renewal Order', 'woo-payment-gateway' ); ?></td>
				<td><?php echo wc_get_order_status_name( $order->get_status() ); ?></td>
				<td><?php echo $order->get_formatted_order_total(); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
