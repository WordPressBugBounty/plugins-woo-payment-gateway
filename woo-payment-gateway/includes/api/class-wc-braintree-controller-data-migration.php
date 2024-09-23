<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Data_Migration extends WC_Braintree_Rest_Controller {

	protected $namespace = 'data-migration/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'plugin',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'migrate_data' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function migrate_data( $request ) {
		global $wpdb;
		// this process could take a while.
		wc_set_time_limit( 0 );
		$plugin  = $request->get_param( 'plugin_id' );
		$message = '';
		switch ( $plugin ) {
			case 'paypal_powered_by_braintree':
				// update usermeta first. always create a new row so the old data remains
				if ( null != $request->get_param( 'users' ) ) {
					$this->create_temp_table( 'wc_temp_usermeta', $wpdb->usermeta, 'wc_braintree_customer_id' );
					$this->update_temp_table( 'wc_temp_usermeta', 'braintree_production_vault_id' );
					$this->insert_from_temp_table( 'wc_temp_usermeta', $wpdb->usermeta, array(
						'user_id',
						'meta_key',
						'meta_value'
					), 'user_id', 'meta_key' );
					wc_braintree_log_info( sprintf( '%s rows inserted in to usermeta table.', $wpdb->rows_affected ) );
					$message .= sprintf( '%s rows updated in usermeta table.', $wpdb->rows_affected );
					$this->drop_temp_table( 'wc_temp_usermeta' );
				}
				if ( null != $request->get_param( 'orders' ) ) {
					$query = $wpdb->prepare( $this->get_update_orders_statement( $wpdb->postmeta ), 'braintree_cc', 'braintree_credit_card' );
					$wpdb->query( $query );
					$message .= sprintf( '%s orders updated in postmeta table.', $wpdb->rows_affected );
				}
				if ( null != $request->get_param( 'subscriptions' ) ) {
					// _wc_paypal_braintree_payment_method_token
					$args  = array(
						array( '_wc_paypal_braintree_payment_method_token', '_payment_method_token' ),
						array( '_wc_braintree_paypal_payment_token', '_payment_method_token' ),
						array( '_wc_braintree_cc_token', '_payment_method_token' ),
					);
					$count = 0;
					foreach ( $args as $arg ) {
						$this->create_temp_table( 'wc_temp_postmeta', $wpdb->postmeta, $arg[0] );
						$this->update_temp_table( 'wc_temp_postmeta', $arg[1] );
						$this->insert_from_temp_table( 'wc_temp_postmeta', $wpdb->postmeta, array(
							'post_id',
							'meta_key',
							'meta_value'
						), 'post_id', 'meta_key' );
						$count += $wpdb->rows_affected;
						$this->drop_temp_table( 'wc_temp_postmeta' );
					}
					wc_braintree_log_info( sprintf( '%s rows inserted into postmeta table.', $count ) );
					$message .= sprintf( ' %s subscriptions updated in postmeta table.', $count );
				}
				if ( empty( $message ) ) {
					$message = 'No data updated.';
				}
				break;
		}

		return rest_ensure_response( array( 'message' => trim( $message ) ) );
	}

	private function create_temp_table( $temp_table, $table, $args = array() ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "CREATE TEMPORARY TABLE {$temp_table} SELECT * FROM {$table} as maintable WHERE maintable.meta_key = %s", $args ) );
	}

	private function drop_temp_table( $temp_table ) {
		global $wpdb;
		$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS {$temp_table}" );
	}

	private function update_temp_table( $temp_table, $args = array() ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$temp_table} SET meta_key = %s", $args ) );
	}

	private function insert_from_temp_table( $temp_table, $table, $columns, $key1, $key2 ) {
		global $wpdb;
		$wpdb->query(
			"INSERT INTO {$table} (" . implode( ', ', $columns ) . ') SELECT ' . implode( ', ', $columns ) .
			" FROM {$temp_table} t2 WHERE NOT EXISTS (SELECT 1 FROM {$table} t1 WHERE t1.{$key1} = t2.{$key1} AND t1.{$key2} = t2.{$key2})"
		);
	}

	private function get_update_orders_statement( $table ) {
		return "UPDATE {$table} AS postmeta SET postmeta.meta_value = %s WHERE postmeta.meta_value = %s;";
	}
}
