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
class WC_Braintree_Subscription_Settings extends WC_Braintree_Advanced_Settings_API {

	public function __construct() {
		$this->id        = 'braintree_subscription';
		$this->tab_title = __( 'Subscription Settings', 'woo-payment-gateway' );
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_braintree_subscription_form_fields',
			array(
				'enabled'                        => array(
					'type'        => 'checkbox',
					'title'       => __( 'Enabled', 'woo-payment-gateway' ),
					'default'     => 'no',
					'label'       => '',
					'description' => __( 'If enabled, you can sell subscription products on your site.', 'woo-payment-gateway' ),
				),
				'webhook_url'                    => array(
					'type'        => 'paragraph',
					'title'       => __( 'Webook URL', 'woo-payment-gateway' ),
					'text'        => get_rest_url( null, 'wc-braintree/v1/webhook/notification' ),
					'class'       => 'wc-braintree-webhook-url',
					'description' => sprintf(
						__(
							'This is the URL that Braintree uses to send notifications such as when a subscription payment is processed. The plugin listens for these
                                notifications and performs actions such as creating a renewal order. You must add this URL to your webhooks for subscriptions to stay in sync. %1$sWebhook guide%2$s',
							'woo-payment-gateway'
						),
						'<a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/webhooks"/>',
						'</a>'
					),
				),
				'combine'                        => array(
					'type'        => 'checkbox',
					'title'       => __( 'Combine Subscriptions', 'woo-payment-gateway' ),
					'default'     => 'yes',
					'desc_tip'    => true,
					'description' => __(
						'If enabled, subscriptions with the same billing period, trial length, and subscription plan will be combined into one subscription. For example, if a 
								customer selects a quanity of 2 for the same subscription product, it will create one subscription.',
						'woo-payment-gateway'
					),
				),
				'endpoints'                      => array(
					'type'  => 'title',
					'title' => __( 'Endpoints', 'woo-payment-gateway' ),
				),
				'subscriptions_endpoint'         => array(
					'title'       => __( 'Subscriptions Endpoint', 'woo-payment-gateway' ),
					'type'        => 'text',
					'default'     => 'subscriptions',
					'desc_tip'    => true,
					'description' => __( 'This is the endpoint for the Subscriptions link that appeas on the My Account page. If left blank, the link will not appear.', 'woo-payment-gateway' ),
				),
				'view_subscription_endpoint'     => array(
					'title'       => __( 'Subscriptions Endpoint', 'woo-payment-gateway' ),
					'type'        => 'text',
					'default'     => 'view-subscription',
					'desc_tip'    => true,
					'description' => __( 'This is the endpoint for the individual Subscription link that appeas on the subscriptions page.', 'woo-payment-gateway' ),
				),
				'change_payment_method_endpoint' => array(
					'title'       => __( 'Subscriptions Endpoint', 'woo-payment-gateway' ),
					'type'        => 'text',
					'default'     => 'change-payment-method',
					'desc_tip'    => true,
					'description' => __(
						'This is the endpoint for the individual Subscription link that appeas on the subscriptions page.',
						'woo-payment-gateway'
					),
				),
			)
		);
	}

	public function get_braintree_documentation_url() {
		return 'https://docs.paymentplugins.com/wc-braintree/config/#/subscriptions';
	}
}
