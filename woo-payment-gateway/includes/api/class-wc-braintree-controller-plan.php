<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/API
 *
 */
class WC_Braintree_Controller_Plan extends WC_Braintree_Rest_Controller {

	protected $namespace = 'plans/';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'fetch',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_plans' ),
				'permission_callback' => array( $this, 'admin_permission_check' )
			)
		);
	}

	/**
	 * Fetch the plans from Braintree and update the Database.
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_plans( $request ) {
		$envs = array( 'sandbox', 'production' );
		foreach ( $envs as $env ) {
			if ( ( $gateway = braintree()->gateway( $env ) ) !== null ) {
				try {
					$subscription_plans = array();
					$plans              = $gateway->plan()->all();
					foreach ( $plans as $plan ) {
						$subscription_plans[ $plan->id ] = array(
							'id'                    => $plan->id,
							'name'                  => $plan->name,
							'numberOfBillingCycles' => $plan->numberOfBillingCycles,
							'billingDayOfMonth'     => $plan->billingDayOfMonth,
							'billingFrequency'      => $plan->billingFrequency,
							'createdAt'             => $plan->createdAt,
							'currencyIsoCode'       => $plan->currencyIsoCode,
							'description'           => $plan->description,
							'price'                 => $plan->price,
							'trialDuration'         => $plan->price,
							'trialDurationUnit'     => $plan->trialDurationUnit,
							'trialPeriod'           => $plan->trialPeriod,
							'updatedAt'             => $plan->updatedAt,
						);
					}
					update_option( "wc_braintree_{$env}_plans", $subscription_plans );
				} catch ( \Braintree\Exception $e ) {
					$message = sprintf( __( 'Error fetching plans. Environment: %1$s. Reason: %2$s', 'woo-payment-gateway' ), $env, wc_braintree_errors_from_object( $e ) );
					wc_braintree_log_error( $message );

					return new WP_Error( 'plan-error', $message, array( 'status' => 200 ) );
				} catch ( Exception $e ) {
					$message = sprintf( __( 'Error fetching plans. Environment: %1$s. Message: %2$s', 'woo-payment-gateway' ), $e->getMessage() );
					wc_braintree_log_error( $message );

					return new WP_Error( 'plan-error', $message, array( 'status' => 200 ) );
				}
			}
		}

		return rest_ensure_response(
			array(
				'sandbox'    => get_option( 'wc_braintree_sandbox_plans', array() ),
				'production' => array(
					get_option(
						'wc_braintree_production_plans',
						array()
					),
				),
			)
		);
	}
}
