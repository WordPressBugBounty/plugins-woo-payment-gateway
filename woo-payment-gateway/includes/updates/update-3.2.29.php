<?php
/**
 * Delete the old option names that were used prior to version 3.0.0
 */
delete_option( 'braintree_payment_settings' );
delete_option( 'bfwc_default_settings' );
delete_option( 'bfwc_admin_notices' );
delete_option( 'braintree_gateway_log_current_post' );
delete_option( 'braintree_for_woocommerce_version' );
delete_option( 'braintree_wc_production_plans' );
delete_option( 'braintree_wc_sandbox_plans' );