<div class="show_if_braintree-variable-subscription hide_if_variable">
<?php
woocommerce_wp_select(
	array(
		'id'                => "wc_braintree_environment_$loop",
		'name'              => "wc_braintree_environment[$loop]",
		'label'             => 'Environment',
		'wrapper_class'     => 'show_if_braintree-variable-subscription form-row form-row-first',
		'value'             => wc_braintree_environment(),
		'custom_attributes' => array( 'data-container' => '.woocommerce_variation' ),
		'options'           => array(
			'sandbox'    => __( 'Sandbox', 'woo-payment-gateway' ),
			'production' => __( 'Production', 'woo-payment-gateway' ),
		),
		'desc_tip'          => true,
		'description'       => __(
			'This dropdown lets you toggle options for each environment. The Braintree plans used on the frontend are determined by your API environment setting.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_checkbox(
	array(
		'wrapper_class' => 'form-row form-row-last wc-braintree-shipping',
		'label'         => __( 'One Time Shipping', 'woo-payment-gateway' ),
		'id'            => "variable_subscription_one_time_shipping_$loop",
		'name'          => "variable_subscription_one_time_shipping[$loop]",
		'value'         => get_post_meta( $variation->ID, '_subscription_one_time_shipping', true ),
		'cbvalue'       => 'yes',
		'desc_tip'      => true,
		'description'   => __(
			'Select if you only want shipping to be charged during checkout. Note: one time shipping does not apply to subscriptions with trial periods.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'         => sprintf( __( 'Subscription Price (%s)', 'woo-payment-gateway' ), get_woocommerce_currency_symbol( get_woocommerce_currency() ) ),
		'placeholder'   => '0.00',
		'id'            => "variable_subscription_price_$loop",
		'name'          => "variable_subscription_price[$loop]",
		'wrapper_class' => 'show_if_braintree-variable-subscription form-row form-row-first',
		'value'         => get_post_meta( $variation->ID, '_subscription_price', true ),
		'desc_tip'      => true,
		'description'   => __(
			'The price that is billed for the subscription on the period and interval that you assign.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'                => "variable_sandbox_subscription_period_interval_$loop",
		'name'              => "variable_sandbox_subscription_period_interval[$loop]",
		'value'             => get_post_meta( $variation->ID, '_sandbox_subscription_period_interval', true ),
		'label'             => 'Sandbox Billing Interval',
		'wrapper_class'     => 'show_if_sandbox hide_if_production form-row form-row-last',
		'options'           => wcs_braintree_billing_interval_string(),
		'custom_attributes' => array( 'data-container' => '.woocommerce_variation' ),
		'desc_tip'          => true,
		'description'       => __(
			'Braintree only allows monthly subscriptions. The frequency can be customized by changing the Braintree plan that is assigned.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'                => "variable_production_subscription_period_interval_$loop",
		'name'              => "variable_production_subscription_period_interval[$loop]",
		'value'             => get_post_meta( $variation->ID, '_production_subscription_period_interval', true ),
		'label'             => 'Billing Interval',
		'wrapper_class'     => 'show_if_production hide_if_sandbox form-row form-row-last',
		'options'           => wcs_braintree_billing_interval_string(),
		'custom_attributes' => array( 'data-container' => '.woocommerce_variation' ),
		'desc_tip'          => true,
		'description'       => __(
			'Braintree only allows monthly subscriptions. The frequency can be customized by changing the Braintree plan that is assigned.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'id'            => "variable_subscription_length_$loop",
		'name'          => "variable_subscription_length[$loop]",
		'value'         => get_post_meta( $variation->ID, '_subscription_length', true ),
		'options'       => wcs_braintree_subscription_length_string(),
		'label'         => __( 'Length', 'woo-payment-gateway' ),
		'wrapper_class' => 'show_if_braintree-variable-subscription form-row form-row-first',
		'desc_tip'      => true,
		'description'   => __(
			'The duration in which the subscription will be active.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'         => __( 'Sign Up Fee', 'woo-payment-gateway' ),
		'id'            => "variable_subscription_sign_up_fee_$loop",
		'value'         => get_post_meta( $variation->ID, '_subscription_sign_up_fee', true ),
		'placeholder'   => '0.00',
		'name'          => "variable_subscription_sign_up_fee[$loop]",
		'wrapper_class' => 'show_if_braintree-variable-subscription form-row form-row-last',
		'desc_tip'      => true,
		'description'   => __(
			'If you would like the subscription to have a one time sign up fee, you can add it here.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_text_input(
	array(
		'label'         => __( 'Trial Length', 'woo-payment-gateway' ),
		'id'            => "variable_subscription_trial_length_$loop",
		'name'          => "variable_subscription_trial_length[$loop]",
		'wrapper_class' => 'show_if_braintree-variable-subscription form-row form-row-first',
		'value'         => get_post_meta( $variation->ID, '_subscription_trial_length', true ),
		'desc_tip'      => true,
		'description'   => __(
			'The length of the trial associated with the subscription.',
			'woo-payment-gateway'
		),
	)
);

$type = get_post_meta( $variation->ID, '_subscription_trial_length', true ) > 1 ? 'plural' : 'singular';
woocommerce_wp_select(
	array(
		'label'         => __( 'Trial Period', 'woo-payment-gateway' ),
		'id'            => "variable_subscription_trial_period_$loop",
		'name'          => "variable_subscription_trial_period[$loop]",
		'wrapper_class' => 'show_if_braintree-variable-subscription form-row form-row-last',
		'options'       => wcs_braintree_billing_periods( $type ),
		'desc_tip'      => true,
		'description'   => __(
			'The period in which the trial length is associated with. Braintree accepts days and months as trial periods.',
			'woo-payment-gateway'
		),
	)
);
woocommerce_wp_select(
	array(
		'label'             => __( 'Sandbox Plans', 'woo-payment-gateway' ),
		'id'                => "variable_braintree_sandbox_plans[$loop]",
		'name'              => "variable_braintree_sandbox_plans[$loop][]",
		'value'             => get_post_meta( $variation->ID, '_braintree_sandbox_plans', true ),
		'class'             => 'wc-enhanced-select',
		'wrapper_class'     => 'form-row form-row-full show_if_sandbox hide_if_production',
		'custom_attributes' => array(
			'multiple'       => 'multiple',
			'data-container' => '.woocommerce_variation',
		),
		'options'           => wcs_braintree_get_plan_options( 'sandbox' ),
		'desc_tip'          => false,
	)
);
woocommerce_wp_select(
	array(
		'label'             => __( 'Production Plans', 'woo-payment-gateway' ),
		'id'                => "variable_braintree_production_plans_$loop",
		'name'              => "variable_braintree_production_plans[$loop][]",
		'value'             => get_post_meta( $variation->ID, '_braintree_production_plans', true ),
		'class'             => 'wc-enhanced-select',
		'wrapper_class'     => 'form-row form-row-full show_if_production hide_if_sandbox',
		'custom_attributes' => array(
			'multiple'       => 'multiple',
			'data-container' => '.woocommerce_variation',
		),
		'options'           => wcs_braintree_get_plan_options( 'production' ),
		'desc_tip'          => false,
	)
);
?>
</div>
