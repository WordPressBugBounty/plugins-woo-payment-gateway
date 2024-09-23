<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Payment_Gateway' ) ) {
	return;
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/Gateways
 *
 */
class WC_Braintree_CC_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	public $_3ds_nonce_key;

	protected $other_plugin_ids = array( 'braintree_credit_card' );

	public static $threeds_vaulted_nonce_name = '';

	public function __construct() {
		$this->id                 = 'braintree_cc';
		$this->deprecated_id      = 'braintree_payment_gateway';
		$this->template           = 'credit-card.php';
		$this->token_type         = 'CC';
		$this->method_title       = __( 'Braintree CC Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Credit/Debit Cards', 'woo-payment-gateway' );
		$this->method_description = __( 'Credit card gateway that integrates with your Braintree account', 'woo-payment-gateway' );
		$this->_3ds_nonce_key     = $this->id . '_3ds_nonce_key';
		parent::__construct();
	}

	public function add_hooks() {
		add_action( 'wc_braintree_localize_' . $this->id . '_settings', array( $this, 'get_admin_localized_params' ) );
		add_filter( 'wc_braintree_order_transaction_args', array( $this, 'add_transaction_attributes' ), 10, 3 );
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'initialize_3ds' ) );
		add_filter( 'wcs_braintree_add_payment_method_args', array( $this, 'wcs_add_payment_method_args' ), 10, 2 );
		parent::add_hooks();
	}

	public function get_icon() {
		$methods         = $this->get_option( 'payment_methods' );
		$form_fields     = $this->get_form_fields();
		$payment_options = isset( $form_fields['payment_methods']['options'] ) ?
			$form_fields['payment_methods']['options'] : array();

		return wc_braintree_get_template_html(
			'card-icons.php',
			array(
				'payment_methods' => empty( $methods ) ? array() : $methods,
				'type'            => $this->get_option( 'icon_style' ),
				'attributes'      => array_map( function ( $txt ) {
					return array(
						'alt' => $txt
					);
				}, $payment_options )
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		if ( $this->get_option( 'form_type' ) === 'custom_form' ) {
			$scripts->enqueue_script(
				'hosted-fields',
				$scripts->assets_url( 'js/frontend/credit-cards.js' ),
				array(
					'jquery',
					$scripts->get_handle( 'client-manager' ),
					$scripts->get_handle( 'hosted-fields-v3' ),
					$scripts->get_handle( 'data-collector-v3' ),
					$scripts->get_handle( '3ds-v3' ),
				)
			);
			// enqueue custom form js && css
			$form_name = $this->get_option( 'custom_form_design' );
			$form      = wc_braintree_get_custom_form( $form_name );
			$scripts->enqueue_script( $form_name, $form['javascript'], array( $scripts->get_handle( 'hosted-fields' ) ) );
			$scripts->enqueue_style( $form_name, $form['css'] );
			$scripts->localize_script( 'hosted-fields', $this->localize_form_params() );
		} else {
			$scripts->enqueue_script(
				'dropin-v3',
				$scripts->assets_url( 'js/frontend/dropin-v3.js' ),
				array(
					'jquery',
					$scripts->get_handle( 'client-manager' ),
					$scripts->get_handle( 'dropin-v3-ext' ),
					$scripts->get_handle( 'data-collector-v3' ),
					$scripts->get_handle( '3ds-v3' ),
				)
			);
			$scripts->localize_script( 'dropin-v3', $this->localize_form_params() );
		}
	}

	/**
	 *
	 * @param array  $data
	 * @param string $handle
	 *          handle of the current script that is being localized.
	 *
	 * @return array
	 */
	public function localize_form_params() {
		$path      = braintree()->assets_path() . 'img/';
		$icon_type = $this->get_option( 'icon_style' );
		$data      = array_merge(
			$this->get_localized_standard_params(),
			array(
				'_3ds'                        => array( 'verify_vault' => $this->is_active( '3ds_enable_payment_token' ), 'challengeRequested' => $this->is_active( '3ds_challenge_requested' ) ),
				'custom_fields'               => $this->get_custom_form_fields(),
				'dynamic_card_display'        => array( 'enabled' => $this->is_active( 'dynamic_card_display' ) ),
				'form_styles'                 => $this->get_hosted_form_styles(),
				'loader'                      => array( 'enabled' => $this->is_active( 'loader_enabled' ) ),
				'icon_style'                  => $icon_type,
				'html'                        => array(
					'cards' => array(
						'visa'             => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/visa.svg" ),
						'master-card'      => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/master_card.svg" ),
						'american-express' => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/amex.svg" ),
						'discover'         => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/discover.svg" ),
						'diners-club'      => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/diners_club_international.svg" ),
						'jcb'              => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/jcb.svg" ),
						'maestro'          => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/maestro.svg" ),
						'unionpay'         => sprintf( '<img src="%s"/>', $path . "payment-methods/{$icon_type}/china_union_pay.svg" ),
					),
				),
				'config_selector'             => $this->config_key,
				'_3ds_vaulted_nonce_selector' => $this->_3ds_nonce_key,
				'urls'                        => array(
					'_3ds' => array(
						'vaulted_nonce' => braintree()->rest_api->_3ds->rest_url() . 'vaulted_nonce',
						'client_token'  => braintree()->rest_api->_3ds->rest_url() . 'client_token',
					),
				),
			)
		);

		return $data;
	}

	/**
	 * Add transaction attributes specific to the Credit Card gateway.
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 * @param string   $gateway_id
	 */
	public function add_transaction_attributes( $args, $order, $gateway_id ) {
		if ( $gateway_id === $this->id ) {
			if ( ! $this->use_saved_method() && ! $this->use_3ds_vaulted_nonce() ) {
				$args['creditCard'] = array(
					'cardholderName' => sprintf( '%1$s %2$s', $order->get_billing_first_name(), $order->get_billing_last_name() )
				);
			}

			/**
			 * If the dropin form is being used, postal code is based on config.
			 * If custom form is used,
			 * postal code is based on settings.
			 */
			if ( ! $this->use_saved_method() ) {
				if ( $this->get_option( 'form_type' ) === 'custom_form' ) {
					if ( $this->is_active( 'postal_field_enabled' ) ) {
						unset( $args['billing']['postalCode'] );
					}
				} else {
					// dropin form is being used so postal code based on config
					if ( in_array( 'postal_code', $this->get_config_data()['challenges'] ) ) {
						if ( $this->is_active( 'dropin_postal_enabled' ) ) {
							unset( $args['billing']['postalCode'] );
						}
					}
				}
			}
		}

		return $args;
	}

	public function use_saved_method() {
		if ( $this->_3ds_active() && $this->use_3ds_vaulted_nonce() ) {
			return false;
		}

		return parent::use_saved_method();
	}

	/**
	 * Return true if a vaulted payment method is being used with 3DS.
	 */
	public function use_3ds_vaulted_nonce() {
		return ! empty( $_POST[ $this->_3ds_nonce_key ] ) && wc_clean( $_POST[ $this->_3ds_nonce_key ] ) === 'true';
	}

	/**
	 *
	 * @param array                      $item
	 * @param WC_Payment_Token_Braintree $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( 'Braintree_CC' !== $payment_token->get_type() ) {
			return $item;
		}
		$card_type                   = $payment_token->get_card_type();
		$item['method']['last4']     = $payment_token->get_last4();
		$item['method']['brand']     = ( ! empty( $card_type ) ? $card_type : esc_html__( 'Credit card', 'woocommerce' ) );
		$item['expires']             = $payment_token->get_expiry_month() . '/' . substr( $payment_token->get_expiry_year(), - 2 );
		$item['method_type']         = $payment_token->get_method_type();
		$item['wc_braintree_method'] = true;

		return $item;
	}

	/**
	 *
	 * @param array $data
	 */
	public function initialize_3ds( $data ) {
		if ( isset( $data['payment_method'] ) && $data['payment_method'] === $this->id && $this->_3ds_active() && $this->_3ds_validation_active() ) {
			require_once braintree()->plugin_path() . 'includes/class-wc-braintree-3ds-validation.php';
			try {
				$id = ! empty( $_POST[ $this->nonce_key ] ) ? wc_clean( $_POST[ $this->nonce_key ] ) : null;
				if ( $id ) {
					// only initialize validation if the nonce exists
					$nonce = $this->gateway->paymentMethodNonce()->find( $id );
					new WC_Braintree_3ds_Validation( $nonce, $this );
				}
			} catch ( \Braintree\Exception $e ) {
				wc_add_notice( sprintf( __( 'There was an error processing your payment. Reason: %1$s', 'woo-payment-gateway' ),
					wc_braintree_errors_from_object( $e ) ), 'error' );
			}
		}
	}

	/**
	 * Is 3DS enabled? Returns false if this request is for the add payment method page.
	 *
	 * @return boolean
	 */
	public function _3ds_enabled() {
		return $this->is_active( '3ds_enabled' );
	}

	public function _3ds_active() {
		return $this->_3ds_enabled() && wc_braintree_evaluate_condition( $this->get_option( '3ds_conditions' ) );
	}

	public function _3ds_validation_active() {
		return $this->get_option( '3ds_liability_not_shifted' ) !== 'no_action' || $this->get_option( '3ds_card_ineligible' ) !== 'no_action';
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function _3ds_authorize_order( $args, $order ) {
		$args['options']['submitForSettlement'] = false;

		/**
		 * Allow the transaction to process by stopping 3DS verification since the transaction is being authorized.
		 */
		$args['options']['threeDSecure'] = array( 'required' => false );

		return $args;
	}

	/**
	 * @param $args
	 * @param $order
	 *
	 * @return array
	 * @snce 3.2.32
	 */
	public function _3ds_accept_order( $args, $order ) {
		$args['options']['threeDSecure'] = array( 'required' => false );

		return $args;
	}

	public function _3ds_reject_order() {
		wc_add_notice( __( 'Your payment method could not be processed at this time. Reason: 3D Secure not accepted or validation failed.',
			'woo-payment-gateway' ), 'error' );
	}

	public function output_display_items( $page = '', $data = array() ) {
		parent::output_display_items( $page, array(
			'_3ds' => array(
				'enabled' => $this->_3ds_enabled(),
				'active'  => $this->_3ds_active()
			)
		) );
	}

	public function wcs_add_payment_method_args( $args, $gateway_id ) {
		if ( $gateway_id === $this->id ) {
			if ( $this->is_custom_form_active() ) {
				if ( ! $this->is_active( 'postal_field_enabled' ) && ! empty( $_POST['billing_postcode'] ) ) {
					$args['billingAddress']['postalCode'] = wc_clean( $_POST['billing_postcode'] );
				}
			} else {
				if ( ! $this->is_active( 'dropin_postal_enabled' ) && ! empty( $_POST['billing_postcode'] ) ) {
					$args['billingAddress']['postalCode'] = wc_clean( $_POST['billing_postcode'] );
				}
			}
		}

		return $args;
	}

	/**
	 * Return true if the custom form is active.
	 */
	public function is_custom_form_active() {
		return $this->get_option( 'form_type' ) === 'custom_form';
	}

	public function is_postal_code_enabled() {
		global $wp;
		if ( is_checkout() && empty( $wp->query_vars['order-pay'] ) ) {
			return $this->is_active( 'postal_field_enabled' );
		} else {
			return true;
		}
	}

	public function should_display_street() {
		if ( $this->is_active( 'street_enabled' ) ) {
			$bool = is_add_payment_method_page() || ( wcs_braintree_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment )
			        || ( wc_braintree_subscriptions_active() && wcs_braintree_is_change_payment_method_request() );
		} else {
			$bool = false;
		}

		return apply_filters( 'wc_braintree_can_display_street', $bool, $this );
	}

	/**
	 * Return an array of fields that contains values necessary for the hosted fields integration.
	 */
	public function get_custom_form_fields() {
		$fields = array(
			'number'      => array(
				'label'       => __( 'Card Number', 'woo-payment-gateway' ),
				'placeholder' => __( 'Card Number', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-card-number',
				'type'        => 'number',
			),
			'exp_date'    => array(
				'label'       => __( 'Exp Date', 'woo-payment-gateway' ),
				'placeholder' => __( 'MM / YY', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-expiration-date',
				'type'        => 'expirationDate',
			),
			'exp_month'   => array(
				'label'       => __( 'Exp Month', 'woo-payment-gateway' ),
				'placeholder' => __( 'MM', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-expiration-month',
				'type'        => 'expirationMonth',
			),
			'exp_year'    => array(
				'label'       => __( 'Exp Year', 'woo-payment-gateway' ),
				'placeholder' => __( 'YY', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-expiration-year',
				'type'        => 'expirationYear',
			),
			'cvv'         => array(
				'label'       => __( 'CVV', 'woo-payment-gateway' ),
				'placeholder' => __( 'CVV', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-cvv',
				'type'        => 'cvv',
			),
			'postal_code' => array(
				'label'       => __( 'Postal Code', 'woo-payment-gateway' ),
				'placeholder' => __( 'Postal Code', 'woo-payment-gateway' ),
				'id'          => 'wc-braintree-postal-code',
				'type'        => 'postalCode',
			),
			'save'        => array( 'label' => __( 'Save Card', 'woo-payment-gateway' ) ),
			'street'      => array( 'label' => __( 'Street Address', 'woo-payment-gateway' ) ),
		);

		/**
		 *
		 * @param array                           $fields
		 * @param WC_Braintree_CC_Payment_Gateway $this
		 */
		return apply_filters( 'wc_braintree_get_custom_form_fields', $fields, $this );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->creditCardDetails;
	}

	/**
	 * Returns an array of class names that Braintree uses to assign styles
	 * to the hosted field within the iFrame.
	 *
	 * @since 3.2.5
	 * @return mixed|void
	 */
	protected function get_hosted_form_styles() {
		$styles = $this->get_option( 'custom_form_styles' );
		if ( is_string( $styles ) ) {
			$styles = json_decode( $styles, true );
			if ( ! $styles ) {
				$styles = $this->form_fields['custom_form_styles']['default'];
			}
		}

		/**
		 * @param array
		 */
		return apply_filters( 'wc_braintree_get_hosted_form_styles', $styles, $this );
	}

	public function get_localized_params() {
		return array_merge( $this->settings, array(
			'style_options' => array(
				'appearance',
				'color',
				'direction',
				'font-family',
				'font-size',
				'font-stretch',
				'font-style',
				'font-weight',
				'font letter-spacing',
				'line-height',
				'opacity',
				'margin',
				'padding',
				'text-shadow'
			)
		) );
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'hosted-fields' ) ) || wp_script_is( $scripts->get_handle( 'dropin-v3' ) );
		}
	}

}
