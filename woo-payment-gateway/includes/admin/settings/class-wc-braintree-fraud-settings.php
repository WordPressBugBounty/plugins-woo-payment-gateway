<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Advanced_Settings_API' ) ) {
	return;
}

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
class WC_Braintree_Advanced_Fraud_Settings extends WC_Braintree_Advanced_Settings_API {

	const NOT_EVALUATED = 'Not Evaluated';

	const APPROVE = 'Approve';

	const REVIEW = 'Review';

	const DECLINE = 'Decline';

	public function __construct() {
		$this->id                         = 'braintree_fraud';
		$this->tab_title                  = __( 'Fraud Settings', 'woo-payment-gateway' );
		$this->braintree_documentation_id = 'fraud-settings';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_braintree_advanced_fraud_form_fields',
			array(
				'fraud'                => array(
					'type'        => 'title',
					'title'       => __( 'Advanced Fraud', 'woo-payment-gateway' ),
					'description' => __(
						'If enabled, Braintree will perform fraud checks on your transactions by capturing customer\'s device data. <a target="_blank" href="https://articles.braintreepayments.com/guides/fraud-tools/advanced/overview#enabling-advanced-fraud-tools">Read more here</a>.
						<p><ol><li>Login to <a target="_blank" href="https://www.braintreegateway.com/login">Production</a> or <a target="_blank" href="https://sandbox.braintreegateway.com/login">Sandbox</a></li>
						<li>Click the gear <img src="' . braintree()->assets_path() . 'img/gear.svg' .
						'"/> then click <b>Processing</b><li>Scroll to <b>Advanced Fraud Tools</b></li><li>Click the toggle to enable this feature</li></ol></p>',
						'woo-payment-gateway'
					),
				),
				'enabled'              => array(
					'type'        => 'checkbox',
					'title'       => __( 'Enable', 'woo-payment-gateway' ),
					'default'     => 'yes',
					'desc_tip'    => true,
					'description' => __( 'If enabled, device data will be captured by the plugin and sent to Braintree with transactions.', 'woo-payment-gateway' ),
				),
				'kount_settings'       => array(
					'type'        => 'title',
					'title'       => __( 'Kount Custom', 'woo-payment-gateway' ),
					'description' => __(
						'Read about Kount Custom <a target="_blank" href="https://articles.braintreepayments.com/guides/fraud-tools/advanced/kount-custom">here</a>. Most merchants are not using Kount Custom and can ignore these settings. All merchants receive
						integration with Kount when they sign up with Braintree and use Advanced Fraud tools. Very large merchants that want more control over their fraud checking would use Kount Custom.',
						'woo-payment-gateway'
					),
				),
				'kount_enabled'        => array(
					'type'        => 'checkbox',
					'title'       => __( 'Enabled', 'woo-payment-gateway' ),
					'default'     => 'no',
					'value'       => 'yes',
					'desc_tip'    => true,
					'description' => __(
						'If enabled, certain actions related to the order status will be taken. For example, an order that has a Kount status of review will be placed under a
								review status. This will prevent actions such as order fulfillment from taking place.',
						'woo-payment-gateway'
					),
				),
				'kount_url'            => array(
					'type'              => 'paragraph',
					'title'             => __( 'Kount API Url', 'woo-payment-gateway' ),
					'text'              => braintree()->rest_api->kount->get_rest_url(),
					'class'             => 'wc-braintree-kount-url',
					'description'       => __( 'This is the URL Kount uses to communicate with your server and send events. You will need to add this URL to your Kount settings.', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'data-show-if' => array( 'kount_enabled' => true ) ),
				),
				'kount_button'         => array(
					'type'              => 'button',
					'title'             => __( 'Generate New API Key', 'woo-payment-gateway' ),
					'class'             => 'button button-secondary kount-api-key',
					'custom_attributes' => array( 'data-show-if' => array( 'kount_enabled' => true ) ),
				),
				'kount_review_action'  => array(
					'type'              => 'select',
					'title'             => __( 'Review Status', 'woo-payment-gateway' ),
					'options'           => array(
						'review'           => __( 'Assign review status to order', 'woo-payment-gateway' ),
						'cancel'           => __( 'Cancel order', 'woo-payment-gateway' ),
						'payment_complete' => __( 'Complete order', 'woo-payment-gateway' ),
					),
					'default'           => 'review',
					'description'       => __( 'The option selected here will determine how the WooCommerce order is handled when the Kount status for the transaction is Review.', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'data-show-if' => array( 'kount_enabled' => true ) ),
				),
				'kount_decline_action' => array(
					'type'              => 'select',
					'title'             => __( 'Declined In Kount', 'woo-payment-gateway' ),
					'options'           => array(
						'do_nothing'         => __( 'Do Nothing', 'woo-payment-gateway' ),
						'cancel_transaction' => __( 'Void / Refund Transaction', 'woo-payment-gateway' ),
					),
					'default'           => 'cancel_transaction',
					'description'       => __( 'Some merchants may wish to void a transaction in WooCommerce if the order is declined in Kount. Selecting do nothing means no void or refund action will be taken.', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'data-show-if' => array( 'kount_enabled' => true ) ),
				),
			)
		);
	}

	public function enqueue_admin_scripts() {
		wp_enqueue_script( 'wc-braintree-fraud-settings', braintree()->assets_path() . 'js/admin/fraud-settings.js', array( 'wc-braintree-admin-settings' ), braintree()->version, true );
	}

	protected function get_localized_params() {
		return array(
			'url'      => array( 'api_key' => braintree()->rest_api->kount->rest_url() . 'api_key' ),
			'settings' => $this->settings,
			'_wpnonce' => wp_create_nonce( 'wp_rest' ),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param WC_Braintree_Payment_Gateway $gateway
	 * @param \Braintree\Transaction $transaction
	 * @param array $args
	 *
	 * @since 3.0.8
	 */
	public function kount_order_actions( $order, $gateway, $transaction, $args ) {
		$risk_data = $transaction->riskData;
		// set order meta data
		$order->update_meta_data( '_kount_id', $risk_data->id );
		$order->update_meta_data( '_kount_decision', $risk_data->decision );
		$order->save();

		if ( $risk_data->decision === self::APPROVE ) {
			$gateway->payment_complete_actions( $order, $transaction, $args );
		} elseif ( $risk_data->decision === self::REVIEW ) {
			$status = apply_filters( 'wc_braintree_kount_review_order_status', $this->get_option( 'kount_review_action' ) );
			if ( $status === 'review' ) {
				// customer should still get order email
				$mailer = WC()->mailer();
				if ( isset( $mailer->emails['WC_Email_Customer_Processing_Order'] ) ) {
					$email = $mailer->emails['WC_Email_Customer_Processing_Order'];
					add_action( 'woocommerce_order_status_pending_to_kount-review_notification', array( $email, 'trigger' ), 10, 2 );
				}
				$order->update_status( 'kount-review' );
			} elseif ( $status === 'cancel' ) {
				// this will trigger a void
				$order->update_status( 'cancelled' );
			}
		} elseif ( $risk_data->decision === self::DECLINE ) {
			$status = apply_filters( 'wc_braintree_kount_decline_order_action', $this->get_option( 'kount_decline_action' ) );

			if ( $status == 'cancel_transaction' ) {
				// this will trigger a void
				$order->update_status( 'cancelled' );
			} else {
				$order->payment_complete();
			}
		}
	}
}
