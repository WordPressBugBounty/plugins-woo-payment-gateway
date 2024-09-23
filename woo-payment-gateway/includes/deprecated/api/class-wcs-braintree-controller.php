<?php

/**
 * Controller that processes subscription payments when a WCS has been created in Braintree and relies
 * on the Braintree billing engine.
 *
 * @deprecated
 *
 */
class WCS_Braintree_Subscription_Controller extends WC_Braintree_Rest_Controller {

	protected $namespace = '';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			$this->deprecated_rest_uri(),
			'webhooks/test',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_sample_notification' ),
					'permission_callback' => '__return_true'
				),
			)
		);
		register_rest_route(
			$this->deprecated_rest_uri(),
			'webhooks',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'process_webhook' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'wc_braintree_notification' => array(
						'required'          => true,
						'validate_callback' => array(
							$this,
							'validate_notification',
						),
					),
				),
			)
		);
		$this->register_authenticated_route( $this->deprecated_rest_uri() . '/webhooks' );
	}

	/**
	 * Validate that the subscription exists in the system.
	 *
	 * @param \Braintree\WebhookNotification $notification
	 */
	public function validate_notification( $notification ) {
		if ( strpos( $notification->kind, 'subscription' ) !== false ) {
			$id = $notification->subscription->id;
			// check that subscription exists.
			if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
				$result = wc_get_orders( array(
					'type'   => 'shop_subscription',
					'limit'  => 1,
					'id'     => $id,
					'return' => 'ids'
				) );
			} else {
				global $wpdb;
				$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d AND post_type = %s", $id, 'shop_subscription' ) );
			}
			if ( ! $result ) {
				wc_braintree_log_info( sprintf( 'Subscription %1$d not found in your database.', 'woo-payment-gateway' ), $id );

				return new WP_Error( 'wc_braintree_webhook_error', 'Invalid subscription ID.', array( 'status' => 400 ) );
			}
			if ( 'braintree' !== get_post_meta( $id, '_subscription_type', true ) ) {
				return new WP_Error( 'wc_braintree_webhook_error', sprintf( 'Subscription %1$s is not a Braintree Subscription.', $id ) );
			}

			return true;
		}

		return true;
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_webhook( $request ) {
		$notification = $request->get_param( 'wc_braintree_notification' );
		global $wpdb;
		switch ( $notification->kind ) {
			case \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY:
				$transaction_id = $notification->subscription->transactions[0]->id;
				// try to find an order with this transaction ID. If it doesn't exist, create renewal order.
				$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts as posts INNER JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE post_type = 'shop_order' AND meta.meta_key = '_transaction_id' AND meta.meta_value = %s", $transaction_id ) );
				if ( ! $order_id ) {
					$id           = $notification->subscription->id;
					$subscription = wcs_get_subscription( $id );
					// create renewal order
					$renewal = wcs_create_renewal_order( $subscription );
					$renewal->payment_complete( $transaction_id );
					$renewal->save();
				}
				break;
			case \Braintree\WebhookNotification::SUBSCRIPTION_CANCELED:
				$id           = $notification->subscription->id;
				$subscription = wcs_get_subscription( $id );
				if ( $subscription->can_be_updated_to( 'cancelled' ) ) {
					$subscription->update_status( 'cancelled' );
				}
				break;
			case \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY:
			case \Braintree\WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE:
				$id           = $notification->subscription->id;
				$subscription = wcs_get_subscription( $id );
				if ( $subscription->can_be_updated_to( 'on-hold' ) ) {
					$subscription->add_order_note( __( 'Recurring payment for subscirption failed.', 'woo-payment-gateway' ) );
					$subscription->update_status( 'on-hold' );
				}
				break;
			case \Braintree\WebhookNotification::SUBSCRIPTION_EXPIRED:
				$id           = $notification->subscription->id;
				$subscription = wcs_get_subscription( $id );
				if ( $subscription->can_be_updated_to( 'expired' ) ) {
					$subscription->update_status( 'expired' );
				}
				break;
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Generate a sample notification signature and payload.
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_sample_notification( $request ) {
		$kind = $request->get_param( 'kind' );
		$id   = $request->get_param( 'id' );
		try {
			switch ( $kind ) {
				case \Braintree\WebhookNotification::SUBSCRIPTION_CANCELED:
					$kind = \Braintree\WebhookNotification::SUBSCRIPTION_CANCELED;
					break;
				case \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY:
					$kind = \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY;
					break;
				case \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY:
					$kind = \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY;
					break;
				case \Braintree\WebhookNotification::SUBSCRIPTION_EXPIRED:
					$kind = \Braintree\WebhookNotification::SUBSCRIPTION_EXPIRED;
					break;
				case \Braintree\WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE:
					$kind = \Braintree\WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE;
					break;
				default:
					throw new Exception( 'Invalid notification kind.' );
			}
			$subscription_xml = $this->build_object_xml( '/subscriptions', '<subscription>', '</subscription>', $id );
			$notification     = braintree()->gateway()->webhookTesting()->sampleNotification( $kind, $id, null, $subscription_xml );

			return rest_ensure_response(
				array(
					'bt_signature' => $notification['bt_signature'],
					'bt_payload'   => $notification['bt_payload'],
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'wc_braintree_sample_notification_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	private function build_object_xml( $path, $start, $end, $id ) {
		$config   = braintree()->gateway()->config;
		$http     = new \Braintree\Http( $config );
		$path     = $config->baseUrl() . $config->merchantPath() . $path . '/' . $id;
		$response = $http->_doUrlRequest( 'GET', $path );
		if ( $response['status'] !== 200 ) {
			Braintree_Util::throwStatusCodeException( $response['status'] );
		}
		$xml = $response['body'];

		return wc_braintree_parse_xml_contents( $xml, $start, $end );
	}

}
