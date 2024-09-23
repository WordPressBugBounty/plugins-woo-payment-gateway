<?php if ( $transaction->status !== \Braintree\Transaction::VOIDED && $transaction->status !== \Braintree\Transaction::SETTLING && $transaction->status !== \Braintree\Transaction::SETTLED ) : ?>
<div class="wc-transaction-actions">
	<h2><?php esc_html_e( 'Actions', 'woo-payment-gateway' ); ?></h2>
	<?php
	if ( $transaction->status === \Braintree\Transaction::AUTHORIZED ) :
		?>
		<input type="text" id="capture_amount" name="capture_amount"
		class="wc_input_price"
		placeholder="<?php esc_html_e( 'capture amount', 'woo-payment-gateway' ); ?>"
		value="<?php echo $order->get_total(); ?>" />
	<button type="button" class="button do-api-capture"><?php esc_html_e( 'Capture Charge', 'woo-payment-gateway' ); ?></button>
	<?php endif; ?>
	<?php
	switch ( $transaction->status ) :
		case \Braintree\Transaction::AUTHORIZED:
		case \Braintree\Transaction::SUBMITTED_FOR_SETTLEMENT:
		case \Braintree\Transaction::SETTLEMENT_PENDING:
			?>
		<button type="button" class="button cancel-order do-api-void"><?php esc_html_e( 'Void Transaction', 'woo-payment-gateway' ); ?></button>
	<?php endswitch; ?>
</div>
<?php endif; ?>
<div class="wc-transaction-general-data">
	<h2><?php esc_html_e( 'General Info', 'woo-payment-gateway' ); ?></h2>
	<strong><?php esc_html_e( 'Status', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->status; ?>
	<strong><?php esc_html_e( 'Merchant Account', 'woo-payment-gateway' ); ?></strong>
	<?php echo  $transaction->merchantAccountId; ?>
	<strong><?php esc_html_e( 'Type', 'woo-payment-gateway' ); ?></strong>
	<?php echo ucfirst( $transaction->type ); ?>
	<?php
	if ( $transaction->status === \Braintree\Transaction::AUTHORIZED ) :
		?>
		<strong><?php esc_html_e( 'Authorization Expires', 'woo-payment-gateway' ); ?></strong>
		<?php echo date_format( $transaction->authorizationExpiresAt, 'M d Y, h:i A e' ); ?>
	<?php endif; ?>
</div>
<div class="wc-transaction-response-data">
	<h2><?php esc_html_e( 'Response Data', 'woo-payment-gateway' ); ?></h2>
	<?php
	if ( $transaction->processorAuthorizationCode !== null ) :
		?>
	<strong><?php esc_html_e( 'Authorization Code', 'woo-payment-gateway' ); ?></strong>
		<?php echo $transaction->processorAuthorizationCode; ?>
	<?php endif; ?>
	<strong><?php esc_html_e( 'Response Code', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->processorResponseCode; ?>
	<strong><?php esc_html_e( 'Fraud Checks', 'woo-payment-gateway' ); ?></strong>
	<div>
		<span class="response-code"><?php esc_html_e( 'CVV', 'woo-payment-gateway' ); ?></span>:&nbsp;<?php echo wc_braintree_get_response_code_message( $transaction->cvvResponseCode ); ?></div>
	<div>
		<span class="response-code"><?php esc_html_e( 'AVS', 'woo-payment-gateway' ); ?></span>:&nbsp;<?php esc_html_e( 'Postal code', 'woo-payment-gateway' ); ?>&nbsp; <?php echo wc_braintree_get_response_code_message( $transaction->avsPostalCodeResponseCode ); ?>,&nbsp;<?php esc_html_e( 'Street address', 'woo-payment-gateway' ); ?>&nbsp;<?php echo wc_braintree_get_response_code_message( $transaction->avsStreetAddressResponseCode ); ?></div>
</div>
<?php
if ( isset( $transaction->riskData ) ) :
	?>
<div class="wc-risk-data">
	<h2><?php esc_html_e( 'Risk Data', 'woo-payment-gateway' ); ?></h2>
	<strong><?php esc_html_e( 'Fraud Service Provider' ); ?></strong>
	<?php echo ucfirst( $transaction->riskData->fraudServiceProvider ); ?>
	<strong><?php esc_html_e( 'Fraud Provider Transaction ID', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->riskData->id; ?>
	<strong><?php esc_html_e( 'Decision', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->riskData->decision; ?>
	<strong><?php esc_html_e( 'Device Data Captured', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->riskData->deviceDataCaptured ? 'True' : 'False'; ?>
</div>
<?php endif; ?>
<?php

if ( isset( $transaction->paypal ) ) :
	?>
<div class="wc-transaction-paypal-data">
	<h2><?php esc_html_e( 'PayPal Data', 'woo-payment-gateway' ); ?></h2>
	<strong><?php esc_html_e( 'Payer ID', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->paypal['payerId']; ?>
	<strong><?php esc_html_e( 'Seller Protection Status', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->paypal['sellerProtectionStatus']; ?>
	
</div>
<?php endif; ?>
<div class="wc-transaction-payment-data">
	<h2><?php esc_html_e( 'Payment Data', 'woo-payment-gateway' ); ?></h2>
	<strong><?php esc_html_e( 'Type', 'woo-payment-gateway' ); ?></strong>
	<?php echo $payment_type; ?>
	<strong><?php esc_html_e( 'Method', 'woo-payment-gateway' ); ?></strong>
	<?php echo $order->get_payment_method_title(); ?>
	<strong><?php esc_html_e( 'Gateway', 'woo-payment-gateway' ); ?></strong>
	<?php echo $gateway->get_title(); ?>
</div>
<?php
if ( isset( $transaction->threeDSecureInfo ) && $transaction->threeDSecureInfo != null ) :
	?>
<div class="wc-transaction-data-row wc-transaction-3ds-data">
	<h2><?php esc_html_e( '3DS Data', 'woo-payment-gateway' ); ?></h2>
	<strong><?php esc_html_e( 'Liability Shifted', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->threeDSecureInfo->liabilityShifted ? 'true' : 'false'; ?>
	<strong><?php esc_html_e( 'Liablity Shift Possible', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->threeDSecureInfo->liabilityShiftPossible ? 'true' : 'false'; ?>
	<strong><?php esc_html_e( 'Status', 'woo-payment-gateway' ); ?></strong>
	<?php echo $transaction->threeDSecureInfo->status; ?>
</div>
<?php endif; ?>
