<?php
defined( 'ABSPATH' ) || exit();

require_once( ABSPATH . 'wp-admin/includes/file.php' );
if ( WP_Filesystem() ) {
	/**
	 *
	 * @var WP_Filesystem_Base $wp_filesystem
	 */
	global $wp_filesystem;

	/**
	 *
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @param array $dir_list
	 * @param string $path
	 */
	function wc_braintree_move_translations( $wp_filesystem, $dir_list, $path ) {
		foreach ( $dir_list as $name => $struc ) {
			$file = $path . DIRECTORY_SEPARATOR . $name;
			if ( $wp_filesystem->is_dir( $file ) ) {
				wc_braintree_move_translations( $wp_filesystem, $wp_filesystem->dirlist( $file ), $file );
			} else {
				if ( strpos( $name, 'braintree-payments' ) !== false && 'f' == $struc['type'] ) {
					$new_name    = str_replace( 'braintree-payments', 'woo-payment-gateway', $name );
					$source      = $path . DIRECTORY_SEPARATOR . $name;
					$destination = $path . DIRECTORY_SEPARATOR . $new_name;
					$wp_filesystem->move( $source, $destination );
				}
			}
		}
	}
	$dir_list = $wp_filesystem->dirlist( $wp_filesystem->wp_lang_dir() );
	if ( $dir_list ) {
		wc_braintree_move_translations( $wp_filesystem, $dir_list, $wp_filesystem->wp_lang_dir() );
	}
}

// translate WPML if it exists.
global $wpdb;
$table  = $wpdb->prefix . 'icl_strings';
$table2 = $wpdb->prefix . 'icl_string_translations';
$count  = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM information_schema.tables where table_name = %s', $table ) );
// WPML exists so update old text domains
if ( $count > 0 ) {

	// first delete any entries that have woo-payment-gateway domain and no entry in translations.
	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id NOT IN (SELECT string_id FROM $table2) AND context = %s", 'woo-payment-gateway' ) );

	// Update entries with new context
	$wpdb->query( $wpdb->prepare( "UPDATE $table as icl_strings SET icl_strings.context = %s, icl_strings.domain_name_context_md5 = md5(concat(%s, icl_strings.name, icl_strings.gettext_context)) WHERE icl_strings.context = %s", 'woo-payment-gateway', 'woo-payment-gateway', 'braintree-payments' ) );
}
