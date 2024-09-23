<p>
	<b><?php esc_html_e( 'Transaction Status', 'woo-payment-gateway' ); ?>:</b>&nbsp;<?php echo ucfirst( str_replace( '_', ' ', $status ) ); ?></p>
<?php
if ( $status === \Braintree\Transaction::AUTHORIZED && $order->get_meta( '_authorization_exp_at' ) ) :
	$date = new DateTime( '@' . $order->get_meta( '_authorization_exp_at' ) );
	?>
<p>
	<b><?php esc_html_e( 'Authorization Expires', 'woo-payment-gateway' ); ?>:</b>&nbsp;<?php echo date_format( $date, 'M d Y, h:i A e' ); ?>
<?php endif; ?>
<?php

switch ( $status ) {
	case \Braintree\Transaction::VOIDED:
	case \Braintree\Transaction::SETTLED:
		?>
		

<p><?php esc_html_e( 'There are no actions available at this time.', 'woo-payment-gateway' ); ?></p>
		<?php
		return;
	case \Braintree\Transaction::AUTHORIZED:
	case \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT:
	case \Braintree\Transaction::SETTLEMENT_PENDING:
		$can_void = true;
		break;
	default:
		$can_void = false;
}
$can_settle = $status === \Braintree\Transaction::AUTHORIZED;
?>
<div id="wc-braintree-actions">
	<div class="wc-braintree-buttons-container">
		<?php if ( $can_settle ) : ?>
		<button type="button" class="button capture-charge"><?php esc_html_e( 'Capture Charge', 'woo-payment-gateway' ); ?></button>
		<?php endif; ?>
		<?php if ( $can_void ) : ?>
		<button type="button" class="button cancel-order do-api-void"><?php esc_html_e( 'Void Transaction', 'woo-payment-gateway' ); ?></button>
		<?php endif; ?>
	</div>
	<div class="wc-order-data-row wc-order-capture-charge"
		style="display: none;">
		<div class="wc-order-capture-charge-container">
			<table class="wc-order-capture-charge">
				<tr>
					<td class="label"><?php esc_html_e( 'Total available to capture', 'woo-payment-gateway' ); ?>:</td>
					<td class="total"><?php echo wc_price( $order->get_total() ); ?></td>
				</tr>
				<tr>
					<td class="label"><?php esc_html_e( 'Amount To Capture', 'woo-payment-gateway' ); ?>:</td>
					<td class="total"><input type="text" id="capture_amount"
						name="capture_amount" class="wc_input_price" />
						<div class="clear"></div></td>
				</tr>
			</table>
		</div>
		<div class="clear"></div>
		<div class="capture-actions">
			<button type="button" class="button button-primary do-api-capture"><?php esc_html_e( 'Capture', 'woo-payment-gateway' ); ?></button>
			<button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', 'woo-payment-gateway' ); ?></button>
		</div>
		<div class="clear"></div>
	</div>
</div>
