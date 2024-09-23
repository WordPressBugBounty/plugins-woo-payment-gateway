<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.7
 * @package Braintree/Traits
 *
 */
trait WC_Braintree_Settings_Trait {

	public function get_tab_title() {
		return $this->tab_title;
	}

	public function admin_settings_tabs( $tabs ) {
		$tabs[ $this->id ] = $this->get_tab_title();

		return $tabs;
	}

	public function get_prefix() {
		return $this->plugin_id . $this->id . '_';
	}

	public function is_active( $key ) {
		return $this->get_option( $key ) === 'yes';
	}

	public function get_custom_attribute_html( $attribs ) {
		if ( ! empty( $attribs['custom_attributes'] ) && is_array( $attribs['custom_attributes'] ) ) {
			foreach ( $attribs['custom_attributes'] as $k => $v ) {
				if ( is_array( $v ) ) {
					$attribs['custom_attributes'][ $k ] = htmlspecialchars( wp_json_encode( $v ) );
				}
			}
		}

		return parent::get_custom_attribute_html( $attribs );
	}

	public function generate_multiselect_html( $key, $data ) {
		$value           = (array) $this->get_option( $key, array() );
		$data['options'] = array_merge( array_flip( $value ), $data['options'] );

		return parent::generate_multiselect_html( $key, $data );
	}

	public function generate_slider_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'select_buttons'    => false,
			'options'           => array(),
		);

		$data                                               = wp_parse_args( $data, $defaults );
		$value                                              = $this->get_option( $key, array() );
		$data['custom_attributes']['data-options']['value'] = $value;

		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/html-settings-slider.php';

		return ob_get_clean();
	}

	public function generate_button_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'label'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/html-button.php';

		return ob_get_clean();
	}

	public function generate_description_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args(
			$data,
			array(
				'class'       => '',
				'style'       => '',
				'description' => '',
			)
		);
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/html-description.php';

		return ob_get_clean();
	}

	public function generate_paragraph_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'label'             => '',
			'class'             => '',
			'css'               => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);
		$data      = wp_parse_args( $data, $defaults );
		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/paragraph-html.php';

		return ob_get_clean();
	}

	public function get_admin_localized_params() {
		wp_localize_script( 'wc-braintree-admin-settings', 'woocommerce_' . $this->id . '_settings_params', $this->get_localized_params() );
	}

	protected function get_localized_params() {
		return $this->settings;
	}

	public function output_settings_nav() {
		include braintree()->plugin_path() . 'includes/admin/views/html-settings-nav.php';
	}

	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( ! in_array( $this->get_field_type( $field ), array( 'title', 'button', 'description', 'custom' ) ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}

	public function get_braintree_documentation_url() {
		return sprintf( 'https://docs.paymentplugins.com/wc-braintree/config/#/%s', $this->id );
	}

	public function generate_multi_select_countries_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = (array) $this->get_option( $key );
		$data      = wp_parse_args(
			$data,
			array(
				'title'       => '',
				'class'       => '',
				'style'       => '',
				'description' => '',
				'desc_tip'    => false,
				'id'          => $field_key,
				'options'     => ! empty( $this->countries ) ? $this->countries : array()
			)
		);
		ob_start();
		include braintree()->plugin_path() . 'includes/admin/views/html-multi-select-countries.php';

		return ob_get_clean();
	}

	public function validate_multi_select_countries_field( $key, $value ) {
		return is_array( $value ) ? array_map( 'wc_clean', array_map( 'stripslashes', $value ) ) : '';
	}
}
