<?php
/**
 *
 */
?>
<div
	class="options_group wc_braintree_options_group show_if_braintree-subscription">
<?php
woocommerce_wp_select(
	array(
		'id'                => 'wc_braintree_environment',
		'name'              => 'wc_braintree_environment',
		'label'             => 'Environment',
		'value'             => wc_braintree_environment(),
		'custom_attributes' => array( 'data-container' => '.options_group' ),
		'options'           => array(
			'sandbox'    => __( 'Sandbox', 'woo-payment-gateway' ),
			'production' => __( 'Production', 'woo-payment-gateway' ),
		),
		'desc_tip'          => false,
		'description'       => __(
			'This dropdown lets you toggle options for each environment. The Braintree plans used on the frontend are determined by your API settings.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'       => sprintf( __( 'Subscription Price (%s)', 'woo-payment-gateway' ), get_woocommerce_currency_symbol( get_woocommerce_currency() ) ),
		'placeholder' => '0.00',
		'id'          => '_subscription_price',
		'name'        => 'subscription_price',
		'desc_tip'    => true,
		'description' => __(
			'The price that is billed for the subscription on the period and interval that you assign.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'                => '_sandbox_subscription_period_interval',
		'name'              => 'sandbox_subscription_period_interval',
		'label'             => 'Sandbox Billing Interval',
		'wrapper_class'     => 'show_if_sandbox hide_if_production',
		'custom_attributes' => array( 'data-container' => '.options_group' ),
		'options'           => wcs_braintree_billing_interval_string(),
		'desc_tip'          => false,
		'description'       => __(
			'The billing interval that you choose will determine which Braintree Plans are available in the plans dropdown.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'                => '_production_subscription_period_interval',
		'name'              => 'production_subscription_period_interval',
		'label'             => 'Billing Interval',
		'wrapper_class'     => 'show_if_production hide_if_sandbox',
		'custom_attributes' => array( 'data-container' => '.options_group' ),
		'options'           => wcs_braintree_billing_interval_string(),
		'desc_tip'          => false,
		'description'       => __(
			'The billing interval that you choose will determine which Braintree Plans are available in the plans dropdown.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'          => '_subscription_length',
		'name'        => 'subscription_length',
		'options'     => wcs_braintree_subscription_length_string(),
		'label'       => __( 'Length', 'woo-payment-gateway' ),
		'desc_tip'    => true,
		'description' => __(
			'The duration in which the subscription will be active.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'       => __( 'Sign Up Fee', 'woo-payment-gateway' ),
		'id'          => '_subscription_sign_up_fee',
		'placeholder' => '0.00',
		'name'        => 'subscription_sign_up_fee',
		'desc_tip'    => true,
		'description' => __(
			'If you would like the subscription to have a one time sign up fee, you can add it here.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'       => __( 'Trial Length', 'woo-payment-gateway' ),
		'id'          => '_subscription_trial_length',
		'name'        => 'subscription_trial_length',
		'desc_tip'    => true,
		'description' => __(
			'The length of the trial associated with the subscription.',
			'woo-payment-gateway'
		),
	)
);

$type = get_post_meta( $post->ID, '_subscription_trial_length', true ) > 1 ? 'plural' : 'singular';
woocommerce_wp_select(
	array(
		'label'       => __( 'Trial Period', 'woo-payment-gateway' ),
		'id'          => '_subscription_trial_period',
		'name'        => 'subscription_trial_period',
		'options'     => wcs_braintree_billing_periods( $type ),
		'desc_tip'    => true,

		'description' => __(
			'The period in which the trial length is associated with. Braintree accepts days and months as trial periods.',
			'woo-payment-gateway'
		),
	)
);
?>


<p class="form-field">
		<label><button
				class="button button-secondary wc-braintree-fetch-plans"><?php esc_html_e( 'Fetch Braintree Plans', 'woo-payment-gateway' ); ?></button></label>
	</p>
	<p>
		<span class="description"><?php esc_html_e( 'A Braintree plan is a template for your subscription and is required. If you sell in multiple currencies you must add a plan for each currency you sell in.', 'woo-payment-gateway' ); ?></span>
	</p>
<?php
woocommerce_wp_select(
	array(
		'label'             => __( 'Sandbox Plans', 'woo-payment-gateway' ),
		'id'                => '_braintree_sandbox_plans',
		'name'              => 'braintree_sandbox_plans[]',
		'class'             => 'wc-enhanced-select',
		'wrapper_class'     => 'show_if_sandbox hide_if_production',
		'custom_attributes' => array(
			'multiple'       => 'multiple',
			'data-container' => '.options_group',
		),
		'options'           => wcs_braintree_get_plan_options( 'sandbox' ),
		'desc_tip'          => false,
	)
);
// 'description' => __ ( 'A Braintree plan is a template for your subscription and is required. If you sell in multiple
// currencies you must add a plan for each currency you sell in.', 'woo-payment-gateway' )
woocommerce_wp_select(
	array(
		'label'             => __( 'Production Plans', 'woo-payment-gateway' ),
		'id'                => '_braintree_production_plans',
		'name'              => 'braintree_production_plans[]',
		'class'             => 'wc-enhanced-select',
		'wrapper_class'     => 'show_if_production hide_if_sandbox',
		'custom_attributes' => array(
			'multiple'       => 'multiple',
			'data-container' => '.options_group',
		),
		'options'           => wcs_braintree_get_plan_options( 'production' ),
		'desc_tip'          => false,
	)
);
// 'description' => __ ( 'A Braintree plan is a template for your subscription and is required.', 'woo-payment-gateway'
// )
?>


</div>
