<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Webhook extends WC_Braintree_Rest_Controller {

	protected $namespace = 'webhook/';

	public function register_routes() {
		/*if ( wc_braintree_environment() === 'sandbox' ) {
			register_rest_route(
				$this->rest_uri(),
				'test',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'get_sample_notification' ),
						'permission_callback' => '__return_true'
					),
				)
			);
		}*/
		register_rest_route(
			$this->rest_uri(),
			'notification',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_webhook' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'wc_braintree_notification' => array(
							'required'          => false,
							'validate_callback' => array(
								$this,
								'validate_notification',
							),
						),
					),
				),
			)
		);

		$this->register_authenticated_route( $this->rest_uri() . '/notification' );
	}

	/**
	 *
	 * @param \Braintree\WebhookNotification $notification
	 */
	public function validate_notification( $notification ) {
		if ( strpos( $notification->kind, 'subscription' ) !== false ) {
			$id = $notification->subscription->id;
			// check that subscription exists.
			if ( \PaymentPlugins\Braintree\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
				$result = wc_get_orders( array(
					'type'   => 'bfwc_subscription',
					'limit'  => 1,
					'id'     => $id,
					'return' => 'ids'
				) );
			} else {
				global $wpdb;
				$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d AND post_type = %s", $id, 'bfwc_subscription' ) );
			}
			if ( ! $result ) {
				wc_braintree_log_info( sprintf( 'Subscription %1$d not found in your database.', 'woo-payment-gateway' ), $id );

				return new WP_Error( 'wc_braintree_webhook_error', 'Invalid subscription ID.', array( 'status' => 400 ) );
			}

			return true;
		}

		return true;
	}

	/**
	 * Generate a sample notification signature and payload.
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_sample_notification( $request ) {
		$kind = $request->get_param( 'kind' );
		$id   = $request->get_param( 'id' );
		$xml  = null;
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
				case \Braintree\WebhookNotification::LOCAL_PAYMENT_COMPLETED:
					$kind = \Braintree\WebhookNotification::LOCAL_PAYMENT_COMPLETED;
					break;
				case \Braintree\WebhookNotification::CHECK:
					$kind = \Braintree\WebhookNotification::CHECK;
					break;
				default:
					throw new Exception( 'Invalid notification kind.' );
			}
			if ( strstr( $kind, 'subscription' ) ) {
				$xml = $this->build_object_xml( '/subscriptions', '<subscription>', '</subscription>', $id );
			}
			//$notification = braintree()->gateway()->webhookTesting()->sampleNotification( $kind, $id, null, $xml );
			$notification = braintree()->gateway()->webhookTesting()->sampleNotification( $kind, $id );

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

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_webhook( $request ) {
		$notification = $request->get_param( 'wc_braintree_notification' );
		try {
			// developers can use this hook to process webhooks to suite business needs.
			do_action( 'wc_braintree_webhook_notification_' . $notification->kind, $notification, $request );

			return rest_ensure_response( array( 'success' => true ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'wc_braintree_webhook_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
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
