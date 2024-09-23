<?php
/**
 * @version 3.1.4
 * @package Braintree/Templates
 * @var WC_Braintree_Payment_Gateway $gateway
 */
?>
<div class="cart-totals">
	<h2><?php esc_html_e('Cart totals', 'woocommerce')?></h2>
	<ul>
		<?php foreach(WC()->cart->get_cart() as $cart_item):?>
		<li>
			<label><?php echo $cart_item[ 'data' ]->get_name () . ' x ' . $cart_item[ 'quantity' ]?></label>:&nbsp;
			<span><?php echo WC()->cart->get_product_subtotal( $cart_item['data'], $cart_item['quantity'] )?></span>
		</li>
		<?php endforeach;?>
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
		<li class="coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
			<label><?php echo wc_cart_totals_coupon_label( $coupon );?></label>:&nbsp;
			<span><?php wc_cart_totals_coupon_html( $coupon );?></span>
		</li>
		<?php endforeach;?>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
		<li class="shipping-cost">
			<label><?php esc_html_e('Shipping', 'woo-payment-gateway')?></label>:&nbsp;
			<?php if(wc_braintree_display_prices_including_tax()):?>
				<span><?php echo wc_price(WC()->cart->shipping_total + WC()->cart->shipping_tax_total)?><small class="tax_label"><?php echo WC()->countries->inc_tax_or_vat()?></small></span>
			<?php else:?>
				<span><?php echo wc_price(WC()->cart->shipping_total)?></span>
			<?php endif;?>
		</li>
		<?php endif;?>
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
		<li class="fee fee-<?php echo esc_html( $fee->name ); ?>">
			<label><?php echo esc_html( $fee->name ); ?></label>:&nbsp;
			<span><?php wc_cart_totals_fee_html($fee);?></span>
		</li>
		<?php endforeach;?>
		<?php if ( wc_tax_enabled() && !wc_braintree_display_prices_including_tax() ) :?>
		<li class="tax">
			<label><?php esc_html_e('Tax', 'woocommerce')?></label>:&nbsp;
			<span><?php wc_cart_totals_taxes_total_html();?></span>
		</li>
		<?php endif;?>
		<li>
			<label><?php esc_html_e( 'Total', 'woocommerce' );?></label>:&nbsp;
			<span><?php echo wc_cart_totals_order_total_html();?></span>
		</li>
	</ul>
	<?php if($gateway->cart_contains_trial_period_subscription()):?>
		<h2 class="h2-recurring-totals"><?php esc_html_e('Recurring totals', 'woocommerce')?></h2>
		<ul>
			<?php foreach(WC()->cart->recurring_carts as $recurring_cart):?>
				<?php foreach ( $recurring_cart->get_coupons() as $code => $coupon ) : ?>
				<li class="coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<label><?php echo wc_cart_totals_coupon_label( $coupon );?></label>:&nbsp;
					<span><?php wc_cart_totals_coupon_html( $coupon );?></span>
				</li>
			<?php endforeach;?>
			<?php if ( $recurring_cart->needs_shipping() && $recurring_cart->show_shipping() ) : ?>
				<li class="shipping-cost">
					<label><?php esc_html_e('Shipping', 'woo-payment-gateway')?></label>
					<?php if(wc_braintree_display_prices_including_tax()):?>
						<span><?php echo wc_price($recurring_cart->shipping_total + $recurring_cart->shipping_tax_total)?><small class="tax_label"><?php echo WC()->countries->inc_tax_or_vat()?></small></span>
					<?php else:?>
						<span><?php echo wc_price($recurring_cart->shipping_total)?></span>
					<?php endif;?>
				</li>
			<?php endif;?>
			<?php foreach ( $recurring_cart->get_fees() as $fee ) : ?>
				<li class="fee fee-<?php echo esc_html( $fee->name ); ?>">
					<label><?php echo esc_html( $fee->name ); ?></label>
					<span><?php wc_cart_totals_fee_html($fee);?></span>
				</li>
			<?php endforeach;?>
			<?php if ( wc_tax_enabled() && !wc_braintree_display_prices_including_tax() ) :?>
				<li class="tax">
					<label><?php esc_html_e('Tax', 'woocommerce')?></label>
					<span><?php wcs_cart_totals_taxes_total_html($recurring_cart);?></span>
				</li>
			<?php endif;?>
			<li>
				<label><?php esc_html_e( 'Total', 'woocommerce' );?></label>:&nbsp;
				<span><?php echo wcs_cart_totals_order_total_html($recurring_cart);?></span>
			</li>
			<?php endforeach;?>
		</ul>
		<?php endif;?>
		<?php if(wc_braintree_subscriptions_active() && wcs_braintree_cart_contains_subscription()):?>
			<h2 class="h2-recurring-totals"><?php esc_html_e('Recurring totals', 'woocommerce')?></h2>
			<?php foreach(WC()->cart->recurring_carts as $recurring_cart):?>
			<h3><?php esc_html_e('Recurring total', 'woocommerce')?></h3>
			<ul>
				<?php foreach ( $recurring_cart->get_coupons() as $code => $coupon ) : ?>
				<li class="coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<label><?php echo wc_cart_totals_coupon_label( $coupon );?></label>:&nbsp;
					<span><?php wc_cart_totals_coupon_html( $coupon );?></span>
				</li>
			<?php endforeach;?>
			<?php if ( $recurring_cart->needs_shipping() && $recurring_cart->show_shipping() ) : ?>
				<li class="shipping-cost">
					<label><?php esc_html_e('Shipping', 'woo-payment-gateway')?></label>
					<span><?php echo wcs_braintree_cart_shipping_total($recurring_cart)?></span>
				</li>
			<?php endif;?>
			<?php foreach ( $recurring_cart->get_fees() as $fee ) : ?>
				<li class="fee fee-<?php echo esc_html( $fee->name ); ?>">
					<label><?php echo esc_html( $fee->name ); ?></label>
					<span><?php wc_cart_totals_fee_html($fee);?></span>
				</li>
			<?php endforeach;?>
			<?php if ( wc_tax_enabled() && !wc_braintree_display_prices_including_tax() ) :?>
				<li class="tax">
					<label><?php esc_html_e('Tax', 'woocommerce')?></label>
					<span><?php echo wcs_braintree_cart_recurring_tax_html($recurring_cart);?></span>
				</li>
			<?php endif;?>
			<li>
				<label><?php esc_html_e( 'Total', 'woocommerce' );?></label>:&nbsp;
				<span><?php echo wcs_braintree_cart_recurring_total_html($recurring_cart);?></span>
			</li>
			</ul>
			<?php endforeach;?>
	<?php endif;?>
</div>