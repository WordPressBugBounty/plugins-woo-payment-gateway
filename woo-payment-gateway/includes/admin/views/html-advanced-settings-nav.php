<?php
defined( 'ABSPATH' ) || exit();

$tabs = apply_filters( 'wc_braintree_advanced_settings_tabs', array() );
?>
<div class="wc-braintree-advanced-settings-nav">
	<?php foreach ( $tabs as $id => $tab ) : ?>
        <a class="nav-link <?php
		if ( $current_section === $id ) {
			echo 'nav-link-active';
		}
		?>"
           href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
