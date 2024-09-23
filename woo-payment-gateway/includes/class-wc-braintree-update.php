<?php
defined( 'ABSPATH' ) || exit();

/**
 * @since   3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Update {

	private static $updates = array(
		'3.0.0'  => 'update-3.0.0.php',
		'3.0.4'  => 'update-3.0.4.php',
		'3.0.9'  => 'update-3.0.9.php',
		'3.2.5'  => 'update-3.2.5.php',
		'3.2.7'  => 'update-3.2.7.php',
		'3.2.8'  => 'update-3.2.8.php',
		'3.2.29' => 'update-3.2.29.php',
		'3.2.32' => 'update-3.2.32.php',
		'3.2.50' => 'update-3.2.50.php'
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'update' ) );
	}

	public static function get_messages( $version ) {
		$messages = array(
			'3.0.0' => __( 'Version 3.0.0 is a major updated. Please check your settings and explore all of the new options.' ) .
			           sprintf( '<ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul>', 'Google Pay added on product pages, cart pages, top of checkout page', 'PayPal added to product pages', 'Local payment methods like iDEAL added.', 'Performance improvements' ),
			'3.0.4' => __( 'Version 3.0.4 updated the text domain used for translations. Make sure your translations are not affected. New text domain is woo-payment-gateway' ),
		);

		return isset( $messages[ $version ] ) ? $messages[ $version ] : false;
	}

	public static function update() {
		$current_version = get_option( 'braintree_wc_version', '2.6.65' );

		if ( version_compare( $current_version, braintree()->version, '<' ) ) {
			foreach ( self::$updates as $version => $path ) {
				/*
				 * If the current version is less than the version in the loop, then perform upgrade.
				 */
				if ( version_compare( $current_version, $version, '<' ) ) {
					$file = braintree()->plugin_path() . 'includes/updates/' . $path;
					if ( file_exists( $file ) ) {
						include $file;
					}
					$current_version = $version;
					update_option( 'braintree_wc_version', $current_version );
					add_action(
						'admin_notices',
						function () use ( $current_version ) {
							$message = sprintf( __( 'Thank you for updating Braintree for WooCommerce to version %1$s.', 'woo-payment-gateway' ), $current_version );
							if ( ( $text = self::get_messages( $current_version ) ) ) {
								$message .= ' ' . $text;
							}
							printf( '<div class="notice notice-success is-dismissible"><p>%1$s</p></div>', $message );
						}
					);
				}
			}
			// save latest version.
			update_option( 'braintree_wc_version', braintree()->version );
		}
	}

}

WC_Braintree_Update::init();
