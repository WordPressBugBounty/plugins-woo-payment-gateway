<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Settings_API' ) ) {
	return;
}

/**
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 *
 */
abstract class WC_Braintree_Settings_API extends WC_Settings_API {

	use WC_Braintree_Settings_Trait;

	protected $tab_title = '';

	private $messages = array();

	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		add_action( 'woocommerce_settings_checkout_' . $this->id, array( $this, 'output' ) );
		add_filter( 'wc_braintree_admin_settings_tabs', array( $this, 'admin_settings_tabs' ) );
		add_action( 'wc_braintree_localize_' . $this->id . '_settings', array( $this, 'get_admin_localized_params' ) );
	}

	public function output() {
		global $current_section;
		if ( count( $this->get_errors() ) > 0 ) {
			$this->display_errors();
		}
		if ( count( $this->get_messages() ) > 0 ) {
			$this->display_messages();
		}
		$this->admin_options();
	}

	public function admin_options() {
		global $current_section;
		$this->output_settings_nav();
		printf( '<input type="hidden" id="wc_braintree_prefix" name="wc_braintree_prefix" value="%1$s"/>', $this->get_prefix() );
		echo '<div class="wc-braintree-settings-container">';
		parent::admin_options();
		echo '</div>';
	}

	public function add_message( $message ) {
		$this->messages[] = $message;
	}

	public function get_messages() {
		return $this->messages;
	}

	public function display_messages() {
		if ( $this->get_messages() ) {
			echo '<div id="woocommerce_messages" class="updated notice is-dismissible">';
			foreach ( $this->get_messages() as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}
			echo '</div>';
		}
	}

	public function enqueue_admin_scripts() {
	}
}
