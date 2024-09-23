<?php defined( 'ABSPATH' ) || exit(); ?>
<script type="text/template" id="tmpl-wc-braintree-template-cc-settings">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
            <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e( 'Custom Form Styles', 'woo-payment-gateway' ); ?></h1>
                    <button
                            class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <div class="wc-braintree-add-style-container">
                        <button class="add-new-style button button-secondary"><?php esc_html_e( 'Add Style', 'woo-payment-gateway' ) ?></button>
                        <button class="reset button button-secondary"><?php esc_html_e( 'Reset Styles', 'woo-payment-gateway' ) ?></button>
                    </div>
                    <table class="widefat braintree-cc-table">
                        <thead>
                        <tr>
                            <th><?php esc_html_e( 'Type', 'woo-payment-gateway' ) ?></th>
                            <th><?php esc_html_e( 'Style', 'woo-payment-gateway' ) ?></th>
                            <th><?php esc_html_e( 'Value', 'woo-payment-gateway' ) ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </article>
                <footer>
                    <div class="inner">
                        <button class="button button-primary button-large save"><?php esc_html_e( 'Save', 'woo-payment-gateway' ); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
<script type="text/template" id="tmpl-wc-braintree-cc-settings-row">
    <tr>
        <td>
            <select class="wc-enhanced-select" name="style_type">
                <# data.fields.forEach(function(field){ #>
                <option
                <#if(field === data.style.field){#>selected<#}#> value="{{{field}}}">{{{field}}}</option>
                <# }) #>
            </select>
        </td>
        <td>
            <select class="wc-enhanced-select" name="style_option">
                <# data.options.forEach(function(option){ #>
                <option
                <#if(option === data.style.option){#>selected<#}#> value="{{{option}}}">{{{option}}}</option>
                <# }) #>
            </select>
        </td>
        <td><input type="text" value="{{{data.style.value}}}" name="style_value"/></td>
        <td><span class="dashicons dashicons-trash wc-braintree-delete-row delete"></span></td>
    </tr>
</script>