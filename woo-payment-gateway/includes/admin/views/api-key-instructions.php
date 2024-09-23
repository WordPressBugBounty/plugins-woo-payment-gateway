<?php
defined( 'ABSPATH' ) || exit();

ob_start();
?>
    <p><?php esc_html_e( 'To access your Braintree API keys do the following:', 'woo-payment-gateway' ); ?></p>
    <ol>
        <li><?php echo wp_kses_post( sprintf( __( 'Login to <a href="%1$s" target="_blank">Sandbox</a> or <a href="%2$s" target="_blank">Production</a>' ), 'https://sandbox.braintreegateway.com/login', 'https://www.braintreegateway.com/login' ) ); ?></li>
        <li><?php echo wp_kses_post( sprintf( __( 'Click the gear %1$s  then click <b>API</b>', 'woo-payment-gateway' ), '<img src="' . esc_url( braintree()->assets_path() ) . 'img/gear.svg' . '"/>' ) ); ?></li>
        <li><?php echo wp_kses_post( __( 'If you don\'t have any API keys created, click <b>Generate New API Key</b>', 'woo-payment-gateway' ) ); ?></li>
        <li><?php echo wp_kses_post( __( 'Click the <b>view</b> link located under the <b>Private Key</b> header.', 'woo-payment-gateway' ) ); ?></li>
        <li><?php echo wp_kses_post( __( 'Copy your <b>Public Key</b>, <b>Private Key</b>, and <b>Merchant ID</b> in to the API settings fields.', 'woo-payment-gateway' ) ); ?></li>
    </ol>
<?php
return ob_get_clean();
