<?php
defined( 'ABSPATH' ) || exit();

use PaymentPlugins\WC_Braintree_Constants as Constants;

/**
 *
 * @since 3.1.10
 * @package Braintree/API
 * @author PaymentPlugins
 *
 */
class WC_Braintree_Controller_Product extends WC_Braintree_Rest_Controller {

	protected $namespace = 'product';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'gateway',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_gateway' ),
				'permission_callback' => array( $this, 'get_item_permission_check' ),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'save',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save' ),
				'permission_callback' => array(
					$this,
					'get_item_permission_check',
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'save/(?P<payment_method>[\w]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_gateway_settings' ),
				'permission_callback' => array( $this, 'get_item_permission_check', ),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function toggle_gateway( $request ) {
		$product        = wc_get_product( $request->get_param( 'product_id' ) );
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request->get_param( 'gateway_id' ) ];

		$option = new WC_Braintree_Product_Gateway_Option( $product, $payment_method );
		$option->set_option( 'enabled', wc_bool_to_string( ! $option->enabled() ) );
		$option->save();

		return rest_ensure_response( array(
			'enabled'  => $option->enabled(),
			'html'     => $this->get_option_html( $option ),
			'settings' => $option->settings
		) );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function save( $request ) {
		$gateways         = $request->get_param( 'gateways' );
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$product          = wc_get_product( $request->get_param( 'product_id' ) );
		$order            = array();
		$loop             = 0;
		foreach ( $gateways as $gateway ) {
			$order[ $gateway ] = $loop;
			$loop ++;
		}
		$product->update_meta_data( Constants::PRODUCT_GATEWAY_ORDER, $order );

		$product->update_meta_data( Constants::BUTTON_POSITION, $request->get_param( 'position' ) );

		$product->save();

		return rest_ensure_response( array( 'order' => $order ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @since 3.2.7
	 */
	public function save_gateway_settings( $request ) {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $request->get_param( 'payment_method' ) ];
		$options = new WC_Braintree_Product_Gateway_Option( $request['product_id'], $gateway );
		try {
			foreach ( $options->get_form_fields() as $key => $field ) {
				if ( in_array( $field['type'], array( 'title', 'description' ) ) ) {
					continue;
				}
				if ( isset( $request[ $options->get_field_key( $key ) ] ) ) {
					//$post_data = array( $options->get_field_key( $key ) => $request[ $options->get_field_key( $key ) ] );
					$options->set_option( $key, $options->get_field_value( $key, $field, $request->get_body_params() ) );
				}
			}
			$options->save();

			return rest_ensure_response( array( 'html' => $this->get_option_html( $options ), 'settings' => $options->settings ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'settings-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	public function get_item_permission_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'payment_gateways', 'write' ) ) {
			return new WP_Error( 'wc-braintree-permission', __( 'You cannot edit this resource' ) );
		}

		return true;
	}

	private function get_option_html( $option ) {
		include_once WC_BRAINTREE_PATH . 'includes/admin/meta-boxes/class-wc-braintree-meta-box-product-data.php';

		ob_start();
		\PaymentPlugins\WC_Braintree_Admin_Meta_Box_Product_Data::output_option_settings( $option );

		return ob_get_clean();
	}
}
