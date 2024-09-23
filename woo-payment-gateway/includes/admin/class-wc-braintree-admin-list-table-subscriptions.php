<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Admin_List_Table_Subscriptions {

	protected $post_type = 'bfwc_subscription';

	public function __construct() {
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'render_subscription_columns' ) );
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'subscription_columns' ) );
		add_filter( 'request', array( $this, 'request_query' ) );
		add_action( 'parse_query', array( $this, 'search_subscriptions' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
	}

	public function subscription_columns( $existing_columns ) {
		unset( $existing_columns['title'] );
		unset( $existing_columns['date'] );
		unset( $existing_columns['comments'] );
		$columns = array(
			'cb'              => '<input type="checkbox"/>',
			'status'          => __( 'Status', 'woo-payment-gateway' ),
			'subscription'    => __( 'Subscription', 'woo-payment-gateway' ),
			'items'           => __( 'Items', 'woo-payment-gateway' ),
			'recurring_total' => __( 'Total', 'woo-payment-gateway' ),
			'start_date'      => __( 'Start Date', 'woo-payment-gateway' ),
			'trial_end'       => __( 'Trial End', 'woo-payment-gateway' ),
			'next_payment'    => __( 'Next Payment Date', 'woo-payment-gateway' ),
			'end_date'        => __( 'End Date', 'woo-payment-gateway' ),
		);

		return wp_parse_args( $existing_columns, $columns );
	}

	public function render_subscription_columns( $column ) {
		global $post;
		$subscription = wc_get_order( $post->ID );
		$status       = wcs_braintree_get_subscription_status_name( $subscription->get_status() );

		switch ( $column ) {
			case 'status':
				$notes       = wc_get_order_notes(
					array(
						'order_id' => $post->ID,
						'limit'    => 1,
						'orderby'  => 'date_created_gmt',
					)
				);
				$latest_note = empty( $notes ) ? '' : current( $notes );
				$tool_tip    = ! $latest_note ? '' : $latest_note->content;
				echo '<div class="' . $subscription->get_status() . '"><mark class="' . $subscription->get_status() . ' tips" data-tip="' . $tool_tip . '">' .
					 $status . '</mark></div>';
				break;
			case 'subscription':
				if ( empty( $user ) ) {
					printf( '<a href="%s"><strong>#%s</strong></a>', get_edit_post_link( $post->ID ), $post->ID );
				} else {
					printf( '<div class="tips"><a href="%s"><strong>#%s</strong></a> %s <a href="%s">%s %s</a> </div>', get_edit_post_link( $post->ID ), $subscription->get_id(), __( 'for', 'woo-payment-gateway' ), get_edit_user_link( $user->ID ), $user->user_firstname, $user->user_lastname );
				}
				break;
			case 'items':
				foreach ( $subscription->get_items() as $item_id => $item ) {
					$product = wc_get_product( $item['product_id'] );
					printf( '<div class="order-item"><a href="%s">%s</a></div>', get_edit_post_link( $item['product_id'] ), $product ? $product->get_title() : '' );
				}
				break;
			case 'recurring_total':
				echo $subscription->get_formatted_total();
				printf( '<small class="meta">%1$s %2$s</small>', __( 'Via', 'woo-payment-gateway' ), $subscription->get_payment_method_title() );
				break;
			case 'start_date':
				$date = $subscription->get_formatted_date( 'start' );
				printf( '<time class="start_date">%s</time>', $date );
				break;
			case 'trial_end':
				if ( $subscription->has_trial() ) {
					printf( '<time class="start_date">%s</time>', $subscription->get_formatted_date( 'trial_end' ) );
				} else {
					echo __( 'N/A', 'woo-payment-gateway' );
				}
				break;
			case 'next_payment':
				printf( '<time class="next_payment">%s</time>', $subscription->get_formatted_date( 'next_payment' ) );
				break;
			case 'end_date':
				if ( $subscription->never_expires() ) {
					echo __( 'Never Expires', 'woo-payment-gateway' );
				} else {
					printf( '<time class="end_date">%s</time>', $subscription->get_formatted_date( 'end' ) );
				}
				break;
		}
	}

	public function request_query( $query_vars ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( $screen_id === 'edit-bfwc_subscription' ) {
			if ( empty( $query_vars['post_status'] ) ) {
				$query_vars['post_status'] = array_keys( array_merge( wc_get_order_statuses(), wcs_braintree_get_subscription_statuses() ) );
			}
		}
		return $query_vars;
	}

	/**
	 *
	 * @param WP_Query $wp
	 */
	public function search_subscriptions( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $_REQUEST['s'] ) || empty( $wp->query_vars['s'] ) ||
			 ( isset( $wp->query_vars['post_type'] ) && $wp->query_vars['post_type'] !== 'bfwc_subscription' ) ) {
			return;
		}

		// get all the post ID's from the search
		/**
		 *
		 * @var WC_Order_Data_Store_CPT $data_store
		 */
		$data_store = WC_Data_Store::load( 'braintree_subscription' );

		$ids = $data_store->search_orders( wp_unslash( $_REQUEST['s'] ) );

		if ( ! empty( $ids ) ) {
			unset( $wp->query_vars['s'] );
			$wp->query_vars['post__in'] = array_merge( $ids, array( 0 ) );
		}
	}

	public function search_label( $s ) {
		global $pagenow, $typenow;
		if ( 'edit.php' !== $pagenow || ! empty( $s ) || 'bfwc_subscription' !== $typenow ) {
			return;
		}
		return wp_unslash( $_GET['s'] );
	}
}
new WC_Braintree_Admin_List_Table_Subscriptions();
