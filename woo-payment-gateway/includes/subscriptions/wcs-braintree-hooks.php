<?php
defined( 'ABSPATH' ) || exit();

add_action( 'woocommerce_braintree-subscription_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
add_action( 'woocommerce_braintree-variable-subscription_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
add_action( 'woocommerce_order_details_after_order_table', 'wcs_braintree_order_details' );
add_action( 'woocommerce_account_view-subscription_endpoint', 'wcs_braintree_view_subscription_template' );
add_action( 'woocommerce_account_subscriptions_endpoint', 'wcs_braintree_subscriptions_template' );
add_action( 'woocommerce_account_change-payment-method_endpoint', 'wcs_braintree_change_payment_method_template' );
add_filter( 'woocommerce_account_menu_items', 'wcs_braintree_account_menu_items', 10, 2 );
add_action( 'wcs_braintree_subscription_status_cancelled', 'wcs_braintree_subscription_cancelled', 10, 2 );
add_filter( 'wc_braintree_localize_script_view-subscription', 'wcs_braintree_localize_scripts', 10, 2 );

/**
 * ** Subscirption Notifications ***
 */
add_action( 'wc_braintree_webhook_notification_' . \Braintree\WebhookNotification::SUBSCRIPTION_CANCELED, 'wcs_braintree_webhook_subscription_cancelled' );
add_action( 'wc_braintree_webhook_notification_' . \Braintree\WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE, 'wcs_braintree_webhook_subscription_past_due' );
add_action( 'wc_braintree_webhook_notification_' . \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY, 'wcs_braintree_webhook_subscription_charged_successfully' );
add_action( 'wc_braintree_webhook_notification_' . \Braintree\WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY, 'wcs_braintree_webhook_subscription_payment_failed' );
add_action( 'wc_braintree_webhook_notification_' . \Braintree\WebhookNotification::SUBSCRIPTION_EXPIRED, 'wcs_braintree_webhook_subscription_expired' );
