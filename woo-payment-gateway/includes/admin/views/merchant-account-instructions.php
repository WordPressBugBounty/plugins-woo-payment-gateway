<?php
defined( 'ABSPATH' ) || exit();

ob_start();
?>
<p><?php esc_html_e( 'To access your merchant accounts:', 'woo-payment-gateway' ); ?></p>
<ol>
	<li><?php printf( __( 'Login to your <a target="_blank" href="%1$s">Production</a> or <a target="_blank" href="%2$s">Sandbox</a> account.', 'woo-payment-gateway' ), 'https://www.braintreegateway.com/login', 'https://sandbox.braintreegateway.com/login' ); ?></li>
	<li><?php printf( __( 'Click the gear %1$s  then click <b>Business</b>', 'woo-payment-gateway' ), '<img src="' . braintree()->assets_path() . 'img/gear.svg' . '"/>' ); ?></li>
</ol>
<?php return ob_get_clean(); ?>
