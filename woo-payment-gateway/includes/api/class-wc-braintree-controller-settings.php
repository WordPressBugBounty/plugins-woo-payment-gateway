<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 *
 */
class WC_Braintree_Controller_Settings extends WC_Braintree_Rest_Controller {

	protected $namespace = 'admin/settings';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'connection-test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'connection_test' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			)
		);
		register_rest_route( $this->rest_uri(), '/payment_gateways/(?P<id>[\w-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_gateway_settings' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			) );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function connection_test( $request ) {
		$env           = $request->get_param( 'environment' );
		$conn_settings = array(
			'environment' => $env,
			'merchantId'  => $request->get_param( 'merchant_id' ),
			'publicKey'   => $request->get_param( 'public_key' ),
			'privateKey'  => $request->get_param( 'private_key' ),
		);
		try {
			$gateway = new \Braintree\Gateway( $conn_settings );
			$gateway->clientToken()->generate();

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => sprintf(
						sprintf(
							__(
								'%1$s connection test was successful.',
								'woo-payment-gateway'
							),
							$env == 'sandbox' ? __( 'Sandbox', 'woo-payment-gateway' ) : __( 'Production', 'woo-payment-gateway' )
						)
					),
				)
			);
		} catch ( Exception $e ) {
			if ( $e instanceof \Braintree\Exception\Configuration ) {
				$error = __( 'A Configuration exception was thrown. This error typcically happens when you have entered your API keys incorrectly or have left a value blank.', 'woo-payment-gateway' );
			}
			if ( $e instanceof \Braintree\Exception\Authentication ) {
				$error = __( 'An Authentication exception was thrown. This error typcically happens when you have entered your API keys incorrectly', 'woo-payment-gateway' );
			}
			if ( $e instanceof \Braintree\Exception\Authorization ) {
				$error = __( 'An Authorization exception was thrown. This error typically happens when you have entered an incorrect API key. Double check that you entered your API keys in the correct fields. Also, make sure you didn\'t confuse your Merchant ID with a Merchant Account ID value.', 'woo-payment-gateway' );
			}
			wc_braintree_log_error( sprintf( 'Error during connection test. Exception: %s', $e->getMessage() ) );

			return new WP_Error(
				'connection-error',
				$error,
				array(
					'status'  => 200,
					'message' => $error,
				)
			);
		}
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function update_gateway_settings( $request ) {
		/**
		 * @var WC_Braintree_Payment_Gateway $payment_method ;
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request->get_param( 'id' ) ];
		$payment_method->init_form_fields();
		$payment_method->init_settings();
		$settings = $payment_method->settings;
		try {
			foreach ( $payment_method->get_form_fields() as $key => $field ) {
				if ( isset( $request['settings'][ $key ] ) ) {
					$post_data        = array( $payment_method->get_field_key( $key ) => $request['settings'][ $key ] );
					$settings[ $key ] = $payment_method->get_field_value( $key, $field, $post_data );
				}
			}
			update_option( $payment_method->get_option_key(), $settings );

			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'settings-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}
}
