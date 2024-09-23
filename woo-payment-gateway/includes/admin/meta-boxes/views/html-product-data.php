<div id="braintree_product_data"
     class="panel woocommerce_braintree_panel woocommerce_options_panel hidden">
    <span class="spinner"></span>
    <p>
		<?php esc_html_e( 'In this section you can control which gateways are displayed on the product page.', 'woo-payment-gateway' ); ?>
    </p>
    <div class="options_group">
        <input type="hidden" id="wc_braintree_update_product"
               name="wc_braintree_update_product"/>
        <table class="wc-braintree-product-table wc_gateways">
            <thead>
            <tr>
                <th></th>
                <th><?php esc_html_e( 'Method', 'woo-payment-gateway' ); ?></th>
                <th><?php esc_html_e( 'Enabled', 'woo-payment-gateway' ); ?></th>
                <th></th>
            </thead>
            <tbody class="ui-sortable">
			<?php foreach ( self::$_options as $option ) : ?>
                <tr data-gateway_id="<?php echo $option->get_payment_method()->id; ?>">
                    <td class="sort">
                        <div class="wc-item-reorder-nav">
                            <button type="button" class="wc-move-up" tabindex="0"
                                    aria-hidden="false"
                                    aria-label="<?php /* Translators: %s Payment gateway name. */
							        echo esc_attr( sprintf( __( 'Move the "%s" payment method up', 'woocommerce' ), $option->get_payment_method()->get_method_title() ) ); ?>"><?php esc_html_e( 'Move up', 'woocommerce' ); ?></button>
                            <button type="button" class="wc-move-down" tabindex="0"
                                    aria-hidden="false"
                                    aria-label="<?php /* Translators: %s Payment gateway name. */
							        echo esc_attr( sprintf( __( 'Move the "%s" payment method down', 'woocommerce' ), $option->get_payment_method()->get_method_title() ) ); ?>"><?php esc_html_e( 'Move down', 'woocommerce' ); ?></button>
                            <input type="hidden" name="braintree_gateway_order[]"
                                   value="<?php echo esc_attr( $option->get_payment_method()->id ); ?>"/>
                        </div>
                    </td>
                    <td>
						<?php echo $option->get_payment_method()->get_method_title(); ?>
                    </td>
                    <td>
                        <a class="wc-braintree-product-gateway-enabled" href="#">
                            <span class="woocommerce-input-toggle woocommerce-input-toggle--<?php if ( ! $option->enabled() ) { ?>disabled <?php } else { ?>enabled<?php } ?>">
                            </span>
                        </a></td>
                    <td>
						<?php if ( $option->get_payment_method()->get_product_admin_options() ): ?>
                            <a href="#" class="wc-braintree-gateway-product-options"
                               data-gateway-id="<?php echo $option->get_payment_method()->id ?>"><?php esc_html_e( 'Options', 'woo-payment-gateway' ) ?></a>
						<?php endif ?>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
		<?php
		woocommerce_wp_select(
			array(
				'id'          => '_braintree_button_position',
				'value'       => ( ( $position = $product_object->get_meta( '_braintree_button_position' ) ) ? $position : 'bottom' ),
				'label'       => __( 'Button Position', 'woo-payment-gateway' ),
				'options'     => array(
					'bottom' => __( 'Below add to cart', 'woo-payment-gateway' ),
					'top'    => __( 'Above add to cart', 'woo-payment-gateway' ),
				),
				'desc_tip'    => true,
				'description' => __(
					'The location of the payment buttons in relation to the Add to Cart button.',
					'woo-payment-gateway'
				),
			)
		);
		?>
    </div>
    <p>
        <button class="button button-secondary wc-braintree-save-product-data"><?php esc_html_e( 'Save', 'woo-payment-gateway' ); ?></button>
    </p>
	<?php foreach ( self::$_options as $option ): ?>
		<?php self::output_option_settings( $option ); ?>
	<?php endforeach; ?>
</div>
