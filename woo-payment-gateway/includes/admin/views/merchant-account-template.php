<?php defined( 'ABSPATH' ) || exit(); ?>
<tr valign="top">
    <th scope="row" class="titledesc"><label
                for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <div id="wc-braintree-<?php echo $data['environment']; ?>-merchant-container"
                 class="wc-braintree-<?php echo $data['environment']; ?>-merchant-container"
				<?php echo $this->get_custom_attribute_html( $data ); ?>>
                <p>
                    <button class="button wc-braintree-add-merchant"><?php echo $data['button_text']; ?></button>
					<?php
					if ( ! in_array( '', wc_braintree_connection_settings( $data['environment'] ) ) ) :
						?>
                        <button class="button wc-braintree-import-accounts"><?php esc_html_e( 'Import Accounts', 'woo-payment-gateway' ); ?></button>
					<?php endif; ?>
                </p>
                <table id="wc-braintree-<?php echo $data['environment']; ?>-merchant-table"
                       class="wc-braintree-merchant-table wc-braintree-<?php echo $data['environment']; ?>-merchant-table">
                    <thead>
                    <tr>
                        <th><?php printf( '%1$s Merchant Account ID', $data['environment_text'] ); ?></th>
                        <th class="wc-braintree-merchant-currency"><?php esc_html_e( 'Currency', 'woo-payment-gateway' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'woo-payment-gateway' ); ?></th>
                    </tr>
                    </thead>
                    <tbody id="wc-braintree-<?php echo $data['environment']; ?>-merchant-tbody"></tbody>
                </table>
            </div>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
        </fieldset>
    </td>
</tr>
<script type="text/template"
        id="wc-braintree-<?php echo $data['environment']; ?>merchant-account-template">
    <td>
        <input type="text" data-field-key="<?php echo $field_key; ?>" class="wc-braintree-merchant-account"
               name="<?php echo $field_key; ?>[<%=currency%>]" value="<%=merchant_account%>"/>
    </td>
    <td class="wc-braintree-merchant-currency">
        <select class="wc-enhanced-select wc-braintree-currency">
			<?php
			foreach ( get_woocommerce_currencies() as $code => $currency ) :
				?>
                <option value="<?php echo $code; ?>"><?php echo $code; ?></option>
			<?php endforeach; ?>
        </select>
    </td>
    <td><span class="dashicons dashicons-trash wc-braintree-delete-row"></span></td>
</script>

