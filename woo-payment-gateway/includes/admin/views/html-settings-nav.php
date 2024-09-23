<?php
defined( 'ABSPATH' ) || exit();

global $current_section;
$tabs = apply_filters( 'wc_braintree_admin_settings_tabs', array() );
?>
<div class="wc-braintree-settings-logo">
    <img src="<?php echo esc_url( braintree()->assets_path() ) . 'img/braintree-logo-black-2.svg'; ?>"/>
</div>
<div class="braintree-settings-nav">
	<?php foreach ( $tabs as $id => $tab ) : ?>
        <a class="wc-braintree-nav-tab <?php if ( $current_section === $id || ( $this instanceof WC_Braintree_Advanced_Settings_API && $id === 'braintree_merchant_account' ) || ( $this instanceof WC_Braintree_Local_Payment_Gateway && $id === 'braintree_ideal' ) ) {
			echo ' nav-tab-active';
		} ?>"
           href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="wc-braintree-docs">
    <a target="_blank" class="button button-secondary"
       href="<?php echo esc_url( $this->get_braintree_documentation_url() ); ?>"><?php esc_html_e( 'Documentation', 'woo-payment-gateway' ); ?></a>
</div>
