<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Meta_Box_Braintree_Subscription_Data {

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 50 );
		add_filter( 'product_type_options', array( __CLASS__, 'product_type_options' ) );
		add_filter( 'product_type_selector', array( __CLASS__, 'product_type_selector' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'output_general_data' ) );
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping_data' ) );
		add_action( 'woocommerce_process_product_meta_braintree-subscription', array( __CLASS__, 'save_subscription_data' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'output_variation_data' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_data' ), 10, 2 );
		add_action( 'woocommerce_variable_product_before_variations', array( __CLASS__, 'output_button' ) );
		add_action( 'woocommerce_json_search_found_products', array( __CLASS__, 'filter_found_products' ) );
	}

	public static function enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$js_path   = braintree()->assets_path() . 'js/';
		$css_path  = braintree()->assets_path() . 'css/';
		if ( $screen_id === 'product' ) {
			wp_enqueue_style( 'wc-braintree-admin-style' );
			wp_enqueue_script( 'wc-braintree-subscription-product-data', $js_path . 'admin/meta-boxes-product.js', array(
				'jquery',
				'jquery-blockui'
			), braintree()->version, true );
			wp_localize_script(
				'wc-braintree-subscription-product-data',
				'wc_braintree_meta_box_product_params',
				array(
					'_wpnonce'     => wp_create_nonce( 'wp_rest' ),
					'url'          => array( 'plans' => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->plans->rest_uri() . '/fetch' ) ),
					'plans'        => array(
						'sandbox'    => wcs_braintree_get_plans( 'sandbox' ),
						'production' => wcs_braintree_get_plans( 'production' ),
					),
					'messages'     => array( 'duplicate_currency' => __( 'There is already a plan assigned for currency %currency%.', 'woo-payment-gateway' ) ),
					'trial_period' => array(
						'day'   => array(
							'singular' => _n( 'day', 'days', 1, 'woo-payment-gateway' ),
							'plural'   => _n( 'day', 'days', 2, 'woo-payment-gateway' ),
						),
						'month' => array(
							'singular' => _n( 'month', 'months', 1, 'woo-payment-gateway' ),
							'plural'   => _n(
								'month',
								'months',
								2,
								'woo-payment-gateway'
							),
						),
					),
				)
			);
		}
	}

	public static function product_type_options( $options ) {
		$options['virtual']['wrapper_class']      = $options['virtual']['wrapper_class'] . ' show_if_braintree-subscription';
		$options['downloadable']['wrapper_class'] = $options['virtual']['wrapper_class'] . ' show_if_braintree-subscription';

		return $options;
	}

	public static function product_type_selector( $product_types ) {
		return array_merge(
			$product_types,
			array(
				'braintree-subscription'          => __( 'Braintree Subscription', 'woo-payment-gateway' ),
				'braintree-variable-subscription' => __(
					'Braintree Variable Subscription',
					'woo-payment-gateway'
				),
			)
		);
	}

	public static function product_data_tabs( $tabs ) {
		$tabs['inventory']['class'][]  = 'show_if_braintree-subscription';
		$tabs['inventory']['class'][]  = 'show_if_braintree-variable-subscription';
		$tabs['variations']['class'][] = 'show_if_braintree-variable-subscription';

		return $tabs;
	}

	public static function output_general_data() {
		global $thepostid, $product_object;
		$post = get_post( $thepostid );
		include 'views/html-product-data-general.php';
	}

	public static function output_shipping_data() {
		global $thepostid, $product_object;
		woocommerce_wp_checkbox(
			array(
				'wrapper_class' => 'show_if_braintree-subscription',
				'label'         => __( 'One Time Shipping', 'woo-payment-gateway' ),
				'id'            => '_subscription_one_time_shipping',
				'name'          => 'subscription_one_time_shipping',
				'cbvalue'       => 'yes',
				'desc_tip'      => true,
				'description'   => __(
					'Select if you only want shipping to be charged during checkout. Note: one time shipping does not apply to subscriptions with trial periods.',
					'woo-payment-gateway'
				),
			)
		);
	}

	/**
	 *
	 * @param int $loop
	 * @param array $variation_data
	 * @param WP_Post $variation
	 */
	public static function output_variation_data( $loop, $variation_data, $variation ) {
		include 'views/html-product-data-variations.php';
	}

	public static function output_button() {
		echo '<div class="toolbar toolbar-variations-defaults show_if_braintree-variable-subscription"><button class="button button-secondary wc-braintree-fetch-plans">' .
		     __( 'Fetch Braintree Plans', 'woo-payment-gateway' ) . '</button></div>';
	}

	/**
	 *
	 * @param int $product_id
	 */
	public static function save_subscription_data( $product_id ) {
		$classname = WC_Product_Factory::get_product_classname( $product_id, 'braintree-subscription' );
		$product   = new $classname( $product_id );
		$fields    = array(
			'subscription_price',
			'sandbox_subscription_period_interval',
			'production_subscription_period_interval',
			'subscription_length',
			'subscription_sign_up_fee',
			'subscription_trial_length',
			'subscription_trial_period',
			'subscription_one_time_shipping',
			'braintree_sandbox_plans',
			'braintree_production_plans',
		);
		$props     = array();
		foreach ( $fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? wc_clean( $_POST[ $field ] ) : '';
			switch ( $field ) {
				case 'subscription_sign_up_fee':
					if ( empty( $value ) ) {
						$value = 0;
					} else {
						$value = wc_format_decimal( $value );
					}
					break;
				case 'subscription_trial_length':
				case 'subscription_length':
					if ( empty( $value ) ) {
						$value = 0;
					}
					break;
			}
			$props[ $field ] = $value;
		}
		$product->set_props( $props );
		$product->save();
	}

	/**
	 *
	 * @param int $variation_id
	 * @param int $i
	 */
	public static function save_variation_data( $variation_id, $i ) {
		if ( 'braintree-variable-subscription' === $_POST['product-type'] ) {
			$variation = new WC_Product_Braintree_Subscription_Variation( $variation_id );
			$fields    = array(
				'variable_subscription_price',
				'variable_sandbox_subscription_period_interval',
				'variable_production_subscription_period_interval',
				'variable_subscription_length',
				'variable_subscription_sign_up_fee',
				'variable_subscription_trial_length',
				'variable_subscription_trial_period',
				'variable_braintree_sandbox_plans',
				'variable_braintree_production_plans',
				'variable_subscription_one_time_shipping',
			);
			$props     = array();
			foreach ( $fields as $field ) {
				$value = isset( $_POST[ $field ][ $i ] ) ? wc_clean( $_POST[ $field ][ $i ] ) : '';
				switch ( $field ) {
					case 'variable_subscription_sign_up_fee':
						if ( empty( $value ) ) {
							$value = 0;
						} else {
							$value = wc_format_decimal( $value );
						}
						break;
					case 'variable_subscription_trial_length':
					case 'variable_subscription_length':
						if ( empty( $value ) ) {
							$value = 0;
						}
						break;
				}
				$key           = str_replace( 'variable_', '', $field );
				$props[ $key ] = $value;
			}
			$variation->set_props( $props );
			$variation->save();

			WC_Product_Braintree_Variable_Subscription::sync_product( $variation->get_parent_id() );
		}
	}

	/**
	 *
	 * @param array $products
	 */
	public static function filter_found_products( $products ) {
		if ( isset( $_GET['exclude'] ) ) {
			$exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : array();
			foreach ( $products as $product_id => $product_name ) {
				if ( in_array( $product_id, $exclude_ids ) ) {
					unset( $products[ $product_id ] );
				}
			}
		}

		return $products;
	}
}

WC_Meta_Box_Braintree_Subscription_Data::init();
