<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_scripts' ) );
		add_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
	}

	public function enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$js_path   = braintree()->assets_path() . 'js/';
		$css_path  = braintree()->assets_path() . 'css/';

		wp_register_style( 'wc-braintree-admin-menu', $css_path . 'admin/menu.css', array(), braintree()->version );

		// site wide
		wp_enqueue_style( 'wc-braintree-admin-menu' );

		wp_register_script( 'wc-braintree-admin-settings', $js_path . 'admin/admin-settings.js', array(
			'jquery',
			'jquery-blockui',
			'wc-backbone-modal'
		), braintree()->version, true );
		wp_register_script( 'wc-braintree-meta-boxes-order', $js_path . 'admin/meta-boxes-order.js', array(
			'jquery',
			'jquery-blockui'
		), braintree()->version, true );
		wp_register_script( 'wc-braintree-meta-boxes-subscription', $js_path . 'admin/meta-boxes-subscription.js', array(
			'jquery',
			'jquery-blockui'
		), braintree()->version, true );
		wp_register_script( 'wc-braintree-help', $js_path . 'admin/help-widget.js', array(), braintree()->version, true );
		wp_register_style( 'wc-braintree-admin-style', $css_path . 'admin/admin.css', array(), braintree()->version );
		wp_style_add_data( 'wc-braintree-admin-style', 'rtl', 'replace' );

		if ( strpos( $screen_id, 'wc-settings' ) !== false ) {
			if ( isset( $_REQUEST['section'] ) && preg_match( '/braintree_[\w]*/', $_REQUEST['section'] ) ) {
				wp_enqueue_script( 'wc-braintree-admin-settings' );
				wp_enqueue_script( 'wc-braintree-help' );
				wp_enqueue_style( 'wc-braintree-admin-style' );
				wp_localize_script(
					'wc-braintree-admin-settings',
					'wc_braintree_setting_params',
					array(
						'rest_nonce' => wp_create_nonce( 'wp_rest' ),
						'routes'     => array(
							'payment_gateways' => get_rest_url( null, 'wc-braintree/v1/admin/settings/payment_gateways' )
						),
						'templates'  => array(
							self::get_template( 'views/html-settings-modal.php' ),
							self::get_template( 'views/html-cc-settings-modal.php' )
						),
						'section'    => $_REQUEST['section']
					)
				);
			}
		}
		if ( $screen_id === 'shop_order' || $screen_id === 'woocommerce_page_wc-orders' ) {
			wp_enqueue_script( 'wc-braintree-meta-boxes-order' );
			wp_enqueue_style( 'wc-braintree-admin-style' );
			wp_localize_script(
				'wc-braintree-meta-boxes-order',
				'wc_braintree_meta_boxes_order_params',
				array(
					'urls'     => array(
						'capture'                  => braintree()->rest_api->order_actions->rest_url() . 'capture',
						'void'                     => braintree()->rest_api->order_actions->rest_url() . 'void',
						'process_payment'          => braintree()->rest_api->order_actions->rest_url() . 'process-payment',
						'customer_payment_methods' => braintree()->rest_api->order_actions->rest_url() . 'customer-payment-methods',
						'transaction'              => braintree()->rest_api->order_actions->rest_url() . 'transaction',
					),
					'messages' => array(
						'void'           => __( 'Please click OK if you wish to void this transaction.', 'woo-payment-gateway' ),
						'capture_amount' => __( 'If the capture amount is less than the order total, make sure you edit your order line items to reflect the new capture amount.', 'woo-payment-gateway' )
					),
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
		if ( $screen_id === 'bfwc_subscription' ) {
			wp_enqueue_script( 'wc-braintree-meta-boxes-subscription' );
			wp_enqueue_style( 'wc-braintree-admin-style' );
			$products = array_filter(
				wc_get_products(
					array(
						'status' => 'publish',
						'limit'  => - 1,
						'type'   => array(
							'simple',
							'grouped',
							'external',
							'variable',
							'braintree-variable-subscription',
						),
					)
				),
				function ( $product ) {
					if ( $product->get_type() === 'braintree-variable-subscription' ) {
						return true;
					}

					return ! wcs_braintree_product_is_subscription( $product );
				}
			);
			wp_localize_script(
				'wc-braintree-meta-boxes-subscription',
				'wc_braintree_meta_boxes_subscription_params',
				array(
					'excluded_products' => array_map(
						function ( $product ) {
							return $product->get_id();
						},
						$products
					),
				)
			);
		}
		if ( $screen_id === 'edit-bfwc_subscription' ) {
			wp_enqueue_style( 'wc-braintree-admin-style' );
		}
		if ( $screen_id === 'user-edit' || $screen_id === 'profile' ) {
			wp_enqueue_style( 'wc-braintree-admin-style' );
			wp_enqueue_script( 'wc-braintree-admin-users', $js_path . 'admin/users.js', array(
				'jquery',
				'jquery-blockui'
			), braintree()->version, true );
			wp_localize_script(
				'wc-braintree-admin-users',
				'wc_braintree_users_params',
				array(
					'urls'     => array( 'delete_token' => wp_nonce_url( braintree()->rest_api->tokens->rest_url() . '%id%', 'wp_rest', '_wpnonce' ) ),
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
					'messages' => array(
						'confirm_delete' => __(
							'Please click OK if you wish you delete the payment method.',
							'woo-payment-gateway'
						),
					),
				)
			);
		}
		if ( strpos( $screen_id, 'wc-braintree-data-migration' ) !== false ) {
			wp_enqueue_style( 'wc-braintree-admin-style' );
			wp_enqueue_script( 'wc-braintree-data-migration', $js_path . 'admin/data-migration.js', array(
				'jquery',
				'jquery-blockui'
			), braintree()->version, true );
			wp_localize_script(
				'wc-braintree-data-migration',
				'wc_braintree_data_migration_params',
				array(
					'route' => braintree()->rest_api->data_migration->rest_url() . 'plugin',
				)
			);
		}

		if ( $screen_id === 'product' ) {
			wp_enqueue_style( 'wc-braintree-admin-style' );
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script(
				'wc-braintree-product-data',
				$js_path . 'admin/meta-boxes-product-data.js',
				array(
					'jquery',
					'jquery-blockui',
					'jquery-ui-sortable',
					'jquery-ui-widget',
					'jquery-ui-core',
					'jquery-tiptip',
					'wc-backbone-modal'
				),
				braintree()->version,
				true
			);
			wp_localize_script(
				'wc-braintree-product-data',
				'wc_braintree_product_params',
				array(
					'routes'   => array(
						'enable_gateway' => braintree()->rest_api->product->rest_url() . 'gateway',
						'save'           => braintree()->rest_api->product->rest_url() . 'save',
					),
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}

	public static function localize_scripts() {
		global $current_section, $wc_braintree_subsection;
		if ( ! empty( $current_section ) ) {
			$wc_braintree_subsection = isset( $_GET['sub_section'] ) ? sanitize_title( $_GET['sub_section'] ) : 'braintree_merchant_account';
			do_action( 'wc_braintree_localize_' . $current_section . '_settings' );
			// added for WC 3.0.0 compatability.
			remove_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
		}
	}

	/**
	 */
	public static function get_template( $template ) {
		ob_start();
		include WC_BRAINTREE_PATH . 'includes/admin/' . $template;

		return ob_get_clean();
	}

}

new WC_Braintree_Admin_Assets();

