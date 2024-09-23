<?php defined( 'ABSPATH' ) || exit(); ?>
<h2><?php esc_html_e( 'Braintree Vault ID\'s', 'woo-payment-gateway' ); ?></h2>
<p>
	
			  <?php
					esc_html_e(
						'These are the Braintree Vault ID\'s associated with this user. If you want to remove the user\'s vault ID, delete it and click <b>Update Profile</b>.
			<p>If you change the vault ID, the payment methods for the new ID will be imported automatically.</p>',
						'woo-payment-gateway'
					)
					?>
</p>
<table class="form-table">
	<tbody>
		<tr>
			<th><label><?php esc_html_e( 'Production Vault ID', 'woo-payment-gateway' ); ?></label></th>
			<td><input name="wc_braintree_production_vault_id"
				value="<?php echo wc_braintree_get_customer_id( $user->ID, 'production' ); ?>" /></td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Sandbox Vault ID', 'woo-payment-gateway' ); ?></label></th>
			<td><input name="wc_braintree_sandbox_vault_id"
				value="<?php echo wc_braintree_get_customer_id( $user->ID, 'sandbox' ); ?>" /></td>
		</tr>
	</tbody>
</table>
<h2><?php esc_html_e( 'Braintree Payment Methods', 'woo-payment-gateway' ); ?></h2>
<h3 class="wc-braintre-environment-header"><?php esc_html_e( 'Production', 'woo-payment-gateway' ); ?></h3>
<table class="form-table wc-braintree-tokens-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Method', 'woo-payment-gateway' ); ?></th>
			<th><?php esc_html_e( 'Gateway', 'woo-payment-gateway' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'woo-payment-gateway' ); ?></th>
		</tr>
		<?php
		foreach ( $production_tokens as $token ) :
			?>
			<?php $gateway = WC()->payment_gateways()->payment_gateways()[ $token->get_gateway_id() ]; ?>
			<tr>
			<td><?php echo $token->get_payment_method_title( $gateway->get_option( 'method_format' ) ); ?></td>
			<td><?php echo $gateway->get_method_title(); ?></td>
			<td><span data-token="<?php echo $token->get_id(); ?>"
				class="dashicons dashicons-trash wc-braintree-delete-token"></td>
		</tr>
		<?php endforeach; ?>
	</thead>
</table>
<h3 class="wc-braintre-environment-header"><?php esc_html_e( 'Sandbox', 'woo-payment-gateway' ); ?></h3>
<table class="form-table wc-braintree-tokens-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Method', 'woo-payment-gateway' ); ?></th>
			<th><?php esc_html_e( 'Gateway', 'woo-payment-gateway' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'woo-payment-gateway' ); ?></th>
		</tr>
		<?php
		foreach ( $sandbox_tokens as $token ) :
			?>
			<?php $gateway = WC()->payment_gateways()->payment_gateways()[ $token->get_gateway_id() ]; ?>
			<tr>
			<td><?php echo $token->get_payment_method_title( $gateway->get_option( 'method_format' ) ); ?></td>
			<td><?php echo $gateway->get_method_title(); ?></td>
			<td><span data-token="<?php echo $token->get_id(); ?>"
				class="dashicons dashicons-trash wc-braintree-delete-token"></td>
		</tr>
		<?php endforeach; ?>
	</thead>
</table>

