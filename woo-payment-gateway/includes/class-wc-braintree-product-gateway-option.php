<?php

/**
 * @since 3.1.10
 * @package Braintree/Classes
 * @author PaymentPlugins
 *
 */
class WC_Braintree_Product_Gateway_Option extends WC_Settings_API {

	use WC_Braintree_Settings_Trait;

	/**
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 *
	 * @var WC_Braintree_Payment_Gateway
	 */
	private $payment_method;

	/**
	 *
	 * @param int|WC_Product $product
	 * @param WC_Braintree_Payment_Gateway $payment_method
	 */
	public function __construct( $product, $payment_method ) {
		if ( ! is_object( $product ) ) {
			$this->product = wc_get_product( $product );
		} else {
			$this->product = $product;
		}
		$this->payment_method = $payment_method;
		$this->id             = $this->payment_method->id;
		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Return the ID of this product option.
	 */
	public function get_id() {
		return '_' . $this->payment_method->id . '_options';
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function get_field_key( $key ) {
		return $this->plugin_id . 'product_' . $this->id . '_' . $key;
	}

	/**
	 * Save the settings
	 */
	public function save() {
		$this->product->update_meta_data( $this->get_id(), $this->settings );
		$this->product->save();
	}

	/**
	 * Initialzie the settings.
	 */
	public function init_settings() {
		$this->settings = $this->product->get_meta( $this->get_id() );
		if ( ! is_array( $this->settings ) ) {
			$this->settings = $this->get_default_values();
		}
		// convert enabled bool to string representation
		if ( isset( $this->settings['enabled'] ) && is_bool( $this->settings['enabled'] ) ) {
			$this->settings['enabled'] = wc_bool_to_string( $this->settings['enabled'] );
		}
		if ( is_callable( array( $this->payment_method, 'init_product_gateway_settings' ) ) ) {
			$this->payment_method->init_product_gateway_settings( $this );
		}
	}

	public function init_form_fields() {
		$this->form_fields = $this->payment_method->get_product_admin_options();
	}

	public function get_default_values() {
		$form_fields = $this->get_form_fields();

		return array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
	}

	public function set_option( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	public function enabled() {
		return $this->is_active( 'enabled' );
	}

	/**
	 * @return WC_Braintree_Payment_Gateway
	 * @since 3.2.7
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}

	public function get_form_fields() {
		return array_map( array( $this, 'set_defaults' ), $this->form_fields );
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_checkbox_field( $key, $value ) {
		return empty( $value ) || is_null( $value ) ? 'no' : 'yes';
	}
}
