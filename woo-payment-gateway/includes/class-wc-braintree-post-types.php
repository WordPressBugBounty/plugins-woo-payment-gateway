<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Classes
 *
 */
class WC_Braintree_Post_Types {

	public static function init() {
		add_action( 'woocommerce_after_register_post_type', array( __CLASS__, 'register_posts' ) );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( __CLASS__, 'register_post_statuses' ) );
	}

	public static function register_posts() {
		if ( ! wc_braintree_subscriptions_active() ) {
			return;
		}
		wc_register_order_type(
			'bfwc_subscription',
			array(
				'label'                            => __( 'Subscription', 'woo-payment-gateway' ),
				'labels'                           => array(
					'name'                  => __( 'Subscriptions', 'woocommerce' ),
					'singular_name'         => _x( 'Subscription', 'wcs_braintree post type singular name', 'woocommerce' ),
					'add_new'               => __( 'Add Subscription', 'woocommerce' ),
					'add_new_item'          => __( 'Add New Subscription', 'woocommerce' ),
					'edit'                  => __( 'Edit', 'woocommerce' ),
					'edit_item'             => __( 'Edit Subscription', 'woocommerce' ),
					'new_item'              => __( 'New Subscription', 'woocommerce' ),
					'view'                  => __( 'View Subscription', 'woocommerce' ),
					'view_item'             => __( 'View Subscription', 'woocommerce' ),
					'search_items'          => __( 'Search Subscriptions', 'woocommerce' ),
					'not_found'             => __( 'No Subscriptions found', 'woocommerce' ),
					'not_found_in_trash'    => __( 'No Subscriptions found in trash', 'woocommerce' ),
					'parent'                => __( 'Parent Orders', 'woocommerce' ),
					'menu_name'             => _x( 'Subscriptions', 'Admin menu name', 'woocommerce' ),
					'filter_items_list'     => __( 'Filter subscriptions', 'woocommerce' ),
					'items_list_navigation' => __( 'Subscriptions navigation', 'woocommerce' ),
					'items_list'            => __( 'Subscriptions list', 'woocommerce' ),
				),
				'capabilities'                     => array( 'create_posts' => true ),
				'description'                      => __( 'Subscription made through the Braintree Gateway.', 'woo-payment-gateway' ),
				'public'                           => false,
				'show_ui'                          => true,
				'capability_type'                  => 'shop_order',
				'map_meta_cap'                     => true,
				'publicly_queryable'               => false,
				'exclude_from_search'              => true,
				'show_in_menu'                     => current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true,
				'hierarchical'                     => false,
				'show_in_nav_menus'                => false,
				'rewrite'                          => false,
				'query_var'                        => false,
				'supports'                         => array( 'title', 'comments', 'custom-fields' ),
				'has_archive'                      => false,

				// wc_register_order_type() params
				'exclude_from_orders_screen'       => true,
				'add_order_meta_boxes'             => true,
				'exclude_from_order_count'         => true,
				'exclude_from_order_views'         => true,
				'exclude_from_order_webhooks'      => true,
				'exclude_from_order_reports'       => true,
				'exclude_from_order_sales_reports' => true,
				'class_name'                       => 'WC_Braintree_Subscription',
			)
		);
	}

	public static function register_post_statuses( $order_statuses ) {
		$statuses = wc_braintree_order_statuses_for_registration();
		if ( wc_braintree_subscriptions_active() ) {
			$statuses = array_merge( $statuses, wcs_braintree_get_subscription_statuses_for_registration() );
		}
		return array_merge( $order_statuses, $statuses );
	}
}
WC_Braintree_Post_Types::init();
