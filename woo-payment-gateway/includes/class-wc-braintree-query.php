<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Query {

	private $endpoints = array();

	public function __construct() {
		add_action( 'init', array( $this, 'flush_rewrite_rules' ) );
		add_action( 'wp_loaded', array( $this, 'deprecated_query_vars' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'get_query_vars' ) );
	}

	public function initialize_endpoints() {
		$this->endpoints = array(
			'subscriptions'         => braintree()->subscription_settings->get_option( 'subscriptions_endpoint' ),
			'view-subscription'     => braintree()->subscription_settings->get_option( 'view_subscription_endpoint' ),
			'change-payment-method' => braintree()->subscription_settings->get_option( 'change_payment_method_endpoint' ),
			'cancel-subscription'   => 'cancel-subscripion',
		);
		foreach ( $this->endpoints as $endpoint => $value ) {
			add_filter( 'woocommerce_endpoint_' . $endpoint . '_title', array( $this, 'endpoint_title' ), 10, 2 );
		}
	}

	public function flush_rewrite_rules() {
		if ( 'yes' === get_option( 'woocommerce_queue_flush_rewrite_rules' ) ) {
			WC()->query->init_query_vars();
			WC()->query->add_endpoints();
			flush_rewrite_rules();
		}
	}

	public function endpoint_title( $title, $endpoint ) {
		global $wp;
		switch ( $endpoint ) {
			case 'subscriptions':
				$title = __( 'My Subscriptions', 'woo-payment-gateway' );
				break;
			case 'view-subscription':
				$title = sprintf( __( 'Subscription #%1$s', 'woo-payment-gateway' ), $wp->query_vars['view-subscription'] );
				break;
			case 'change-payment-method':
				$title = __( 'Change Payment Method', 'woo-payment-gateway' );
				break;
		}
		return $title;
	}

	public function parse_request() {
		global $wp;
	}

	public function get_endpoints() {
		return apply_filters( 'wcs_braintree_get_endpoints', $this->endpoints );
	}

	public function get_query_vars( $vars = array() ) {
		if ( empty( $this->endpoints ) ) {
			$this->initialize_endpoints();
		}
		return array_merge( $vars, $this->get_endpoints() );
	}

	/**
	 * WC 3.0.0 doesn't use the woocommerce_get_query_vars filter to retrieve vars when adding
	 * endpoint masks.
	 * This requires that the endpoints be merged directly with the query vars property.
	 */
	public function deprecated_query_vars() {
		if ( WC()->query ) {
			WC()->query->query_vars = array_merge( WC()->query->query_vars, $this->get_query_vars() );
			WC()->query->add_endpoints();
		}
	}
}
new WC_Braintree_Query();
