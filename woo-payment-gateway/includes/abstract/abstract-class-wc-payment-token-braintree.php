<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @version 3.0.0
 * @package Braintree/Abstracts
 *
 */
abstract class WC_Payment_Token_Braintree extends WC_Payment_Token {

	protected $object_type = 'payment_token';

	protected $extra_data = array(
		'payment_instrument_type' => '',
		'format'                  => '',
		'method_type'             => '',
		'environment'             => 'production',
	);

	/**
	 * @var array
	 * @since 3.2.13
	 */
	protected $payment_formats = array();

	protected $braintree_data = array();

	/**
	 * The format of the payment method
	 *
	 * @var string
	 */
	protected $format = '';

	public function __construct( $token = '' ) {
		// use reflection to merge all extra data keys.
		$this->extra_data = array_merge( $this->extra_data, $this->get_braintree_data( $this ) );
		$this->init_payment_formats();
		parent::__construct( $token );
	}

	public function set_format( $value ) {
		$this->format = $value;
	}

	public function set_payment_instrument_type( $value ) {
		$this->set_prop( 'payment_instrument_type', $value );
	}

	public function set_method_type( $value ) {
		$this->set_prop( 'method_type', $value );
	}

	public function set_environment( $value ) {
		$this->set_prop( 'environment', $value );
	}

	public function get_method_type() {
		return $this->get_prop( 'method_type' );
	}

	public function get_format() {
		return $this->format;
	}

	public function get_environment() {
		return $this->get_prop( 'environment' );
	}

	public function get_payment_instrument_type() {
		return $this->get_prop( 'payment_instrument_type' );
	}

	/**
	 * Return a human readable representation of the payment method.
	 */
	public function get_payment_method_title( $format = '' ) {
		$format = empty( $format ) ? $this->get_format() : $format;
		$data   = $this->get_props_data();
		$format = $this->get_formats()[ $format ]['format'];

		return apply_filters( 'wc_braintree_token_payment_method_title', str_replace( array_keys( $data ), $data, $format ), $this );
	}

	/**
	 * Return payment method formats used to display a human readable representation of the token.
	 */
	public function get_formats() {
		/**
		 * @param array
		 * @param WC_Payment_Token_Braintree
		 *
		 * @since 3.2.13
		 */
		return apply_filters( 'wc_braintree_payment_token_formats', $this->payment_formats, $this );
	}

	/**
	 * Populate the token attributes from a Braintree object.
	 *
	 * @param mixed $method
	 */
	public abstract function init_from_payment_method( $method );

	public function get_props_data() {
		$data = array();
		foreach ( $this->extra_data as $k => $v ) {
			if ( method_exists( $this, "get_$k" ) ) {
				$data[ '{' . $k . '}' ] = $this->{"get_$k"}();
			} else {
				$data[ '{' . $k . '}' ] = $this->get_prop( $k );
			}
		}

		return $data;
	}

	/**
	 * Returns an array of merged attributes comprised of the $braintree_data property.
	 *
	 * @param object $instance
	 */
	public function get_braintree_data( $instance ) {
		$data = array();
		try {
			$class = new ReflectionClass( $instance );
			$props = $class->getDefaultProperties();
			if ( isset( $props['braintree_data'] ) ) {
				$data = $props['braintree_data'];
			}
			if ( is_subclass_of( get_parent_class( $instance ), 'WC_Payment_Token_Braintree' ) ) {
				$data = array_merge( $this->get_braintree_data( get_parent_class( $instance ) ), $data );
			}

			return $data;
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Return a json array of data that represents the object.
	 *
	 * @return array
	 */
	public function to_json() {
		return apply_filters(
			'wc_braintree_get_' . $this->object_type . '_json',
			array(
				'id'          => $this->get_id(),
				'type'        => $this->get_type(),
				'method_type' => $this->get_method_type(),
				'token'       => $this->get_token(),
				'title'       => $this->get_payment_method_title(),
			)
		);
	}

	/**
	 * Allows a payment token to add metadata to an order.
	 *
	 * @param WC_Order $order
	 *
	 * @since 3.1.9
	 */
	public function add_meta_data_to_order( $order ) {
	}

	/**
	 * @since 3.2.13
	 */
	public abstract function init_payment_formats();
}
