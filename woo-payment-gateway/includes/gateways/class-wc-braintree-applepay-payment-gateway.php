<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Braintree_Payment_Gateway' ) ) {
	return;
}

/**
 *
 * @since   3.0.0
 * @package Braintree/Classes/Gateways
 */
class WC_Braintree_ApplePay_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	protected $has_digital_wallet = true;

	/**
	 * Error that is set during validation of Apple Wallet address.
	 *
	 * @var WP_Error
	 */
	private $api_error;

	public function __construct() {
		$this->id                 = 'braintree_applepay';
		$this->deprecated_id      = 'braintree_applepay_payments';
		$this->template           = 'applepay.php';
		$this->token_type         = 'ApplePay';
		$this->method_title       = __( 'Braintree Apple Pay Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Apple Pay', 'woo-payment-gateway' );
		$this->method_description = __( 'Gateway that integrates Apple Pay with your Braintree account.', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/applepay/apple_pay_mark.svg';
		parent::__construct();
	}

	public function add_hooks() {
		parent::add_hooks();
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		add_filter( 'wc_braintree_update_address_error', array( $this, 'update_address_error' ), 10, 3 );
		add_filter( 'wc_braintree_mini_cart_deps', array( $this, 'get_mini_cart_dependencies' ), 10, 2 );
	}

	public function set_supports() {
		parent::set_supports();
		$this->supports[] = 'wc_braintree_cart_checkout';
		$this->supports[] = 'wc_braintree_banner_checkout';
		$this->supports[] = 'wc_braintree_product_checkout';
		$this->supports[] = 'wc_braintree_mini_cart';
	}

	public function enqueue_admin_scripts() {
		wp_localize_script(
			'wc-braintree-admin-settings',
			'wc_braintree_applepay_params',
			array(
				'routes' => array( 'domain_association' => braintree()->rest_api->rest_url() . 'applepay/domain-association' ),
			)
		);
	}

	/**
	 * @param bool $encode
	 *
	 * @return array array of line items in the Apple Wallet format
	 * @deprecated 3.2.5
	 */
	public function get_line_items( $encode = false ) {
		return $this->get_display_items();
	}

	/**
	 * @param      $data
	 * @param bool $incl_tax
	 *
	 * @deprecated 3.2.5
	 */
	protected function add_recurring_display_items( &$data, $incl_tax = false ) {
	}

	public function get_shipping_methods() {
		return $this->get_formatted_shipping_methods();
	}

	public function get_localized_standard_params() {
		$data = array_merge_recursive(
			parent::get_localized_standard_params(),
			array(
				'store_name'     => $this->get_option( 'store_name' ),
				'button_html'    => wc_braintree_get_template_html(
					'applepay-button.php',
					array(
						'gateway'      => $this,
						'button'       => $this->get_option( 'button' ),
						'type'         => $this->get_option( 'button_type_checkout' ),
						'style'        => $this->get_applepay_button_style(),
						'button_style' => $this->get_option( 'button_style', 'standard' )
					)
				),
				'banner_enabled' => $this->banner_checkout_enabled(),
				'messages'       => array(
					'errors' => array( 'invalid_administrativeArea' => __( 'Invalid state provided. Use uppercase state abbreviation. Example: California = CA' ) ),
				),
				'routes'         => array( 'applepay_payment_method' => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->applepay->rest_uri() . '/payment-method' ) ),
			)
		);

		return $data;
	}

	public function localize_applepay_params() {
		return $this->get_localized_standard_params();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay',
			$scripts->assets_url( 'js/frontend/applepay.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'applepay-v3' ),
			)
		);
		$scripts->localize_script( 'applepay', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueu_cart_scripts()
	 */
	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-cart',
			$scripts->assets_url( 'js/frontend/applepay-cart.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'applepay-v3' ),
			)
		);
		$scripts->localize_script( 'applepay-cart', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_product_scripts()
	 */
	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-product',
			$scripts->assets_url( 'js/frontend/applepay-product.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'applepay-v3' ),
			)
		);
		$scripts->localize_script( 'applepay-product', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->applePayCardDetails;
	}

	/**
	 *
	 * @param array $data
	 */
	public function update_shipping_address_response( $data ) {
		$data['shippingContactUpdate'] = array(
			'newLineItems'       => $this->get_display_items(),
			'newTotal'           => array(
				'type'   => 'final',
				'label'  => $this->get_option( 'store_name' ),
				'amount' => WC()->cart->total,
			),
			'newShippingMethods' => $this->get_formatted_shipping_methods(),
		);

		return $data;
	}


	/**
	 * @return float
	 * @deprecated 3.2.5
	 */
	public function get_cart_total() {
		return WC()->cart->total;
	}

	/**
	 *
	 * @param array $address
	 */
	public function validate_wallet_address( $address ) {
		$states = WC()->countries->get_states( $address['country'] );
		$state  = isset( $address['state'] ) ? $address['state'] : null;
		if ( $states && $state ) {
			if ( ! isset( $states[ $state ] ) ) {
				// customer needs to update their wallet's shipping state.
				$this->set_error_callback(
					new WP_Error(
						'shippingContactInvalid',
						sprintf( __( '%s is not a valid state. Use uppercase state abbreviation. California = CA', 'woo-payment-gateway' ), $state ),
						array(
							'status'             => 200,
							'newTotal'           => array(
								'type'   => 'final',
								'label'  => $this->get_option( 'store_name' ),
								'amount' => wc_format_decimal( WC()->cart->total, 2 )
							),
							'newShippingMethods' => $this->get_formatted_shipping_methods(),
							'contactField'       => 'administrativeArea',
						)
					)
				);

				return false;
			}
		}

		return true;
	}

	/**
	 * Manipulate the request callback error so data is sent in a format Apple wallet can use.
	 *
	 * @param WP_Error $error
	 */
	private function set_error_callback( $error ) {
		$this->api_error = $error;
		add_action( 'rest_request_before_callbacks', array( $this, 'request_before_callbacks' ) );
	}

	/**
	 * Send back a response that contains the error message from the validation callback.
	 *
	 * @param WP_HTTP_Response $response
	 */
	public function request_before_callbacks( $response ) {
		return rest_ensure_response( $this->api_error );
	}

	/**
	 *
	 * @param WP_Error        $error
	 * @param string          $payment_method
	 * @param WP_REST_Request $request
	 */
	public function update_address_error( $error, $payment_method, $request ) {
		if ( $payment_method === $this->id ) {
			if ( $error->get_error_code() === 'shipping-address' ) {
				$error = new WP_Error(
					'addressUnserviceable',
					$error->get_error_message(),
					array(
						'status'                => 200,
						'shippingContactUpdate' => array(
							'newLineItems'       => $this->get_display_items(),
							'newTotal'           => array(
								'type'   => 'final',
								'label'  => $this->get_option( 'store_name' ),
								'amount' => wc_format_decimal( WC()->cart->total, 2 ),
							),
							'newShippingMethods' => $this->get_formatted_shipping_methods(),
						),
					)
				);
			}
		}

		return $error;
	}

	/**
	 *
	 * @param array $data
	 */
	public function update_shipping_method_response( $data ) {
		$data['shippingMethodUpdate'] = array(
			'newLineItems' => $this->get_display_items(),
			'newTotal'     => array(
				'type'   => 'final',
				'label'  => $this->get_option( 'store_name' ),
				'amount' => wc_format_decimal( WC()->cart->total, 2 )
			),
		);

		return $data;
	}

	/**
	 *
	 * @param array            $item
	 * @param WC_Payment_Token $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( 'Braintree_ApplePay' !== $payment_token->get_type() ) {
			return $item;
		}
		$item['method']['brand']     = $payment_token->get_payment_method_title( $this->get_option( 'method_format' ) );
		$item['expires']             = $payment_token->get_expiry_month() . '/' . substr( $payment_token->get_expiry_year(), - 2 );
		$item['method_type']         = $payment_token->get_method_type();
		$item['wc_braintree_method'] = true;

		return $item;
	}

	/**
	 * Outputs the Apple Pay line items used to build a PaymentRequest
	 *
	 * @deprecated 3.2.5
	 */
	public function output_line_items() {
	}

	public function add_to_cart_response( $data ) {
		$data[ $this->id ] = array(
			'lineItems' => $this->get_display_items()
		);

		return $data;
	}

	/**
	 * @return array
	 */
	public function get_formatted_shipping_methods() {
		$methods                 = parent::get_formatted_shipping_methods();
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen_method_ids       = array();

		foreach ( $chosen_shipping_methods as $idx => $method_id ) {
			$chosen_method_ids[] = $this->get_shipping_method_id( $method_id, $idx );
		}
		/**
		 * Sort the payment methods so the selected method is the first in the array. This ensures
		 * Apple Pay will select that as the default shipping option.
		 */
		usort( $methods, function ( $a, $b ) use ( $chosen_method_ids ) {
			return in_array( $a['identifier'], $chosen_method_ids ) ? - 1 : 1;
		} );

		return $methods;
	}

	/**
	 * @param       $method
	 * @param int   $index
	 * @param bool  $selected
	 * @param float $amount
	 * @param int   $decimals
	 *
	 * @return array
	 */
	public function get_formatted_shipping_method( $method, $index, $selected, $amount, $decimals ) {
		return array(
			'label'      => $method->get_label(),
			'detail'     => '',
			'amount'     => wc_format_decimal( $amount, $decimals ),
			'identifier' => $this->get_shipping_method_id( $method, $index ),
		);
	}

	public function get_display_item_for_cart( $total, $label, $type, $cart, ...$args ) {
		return array(
			'type'   => 'final',
			'label'  => $label,
			'amount' => $total
		);
	}

	public function get_display_item_for_order( $total, $label, $type, $order, ...$args ) {
		return array(
			'type'   => 'final',
			'label'  => $label,
			'amount' => $total
		);
	}

	public function get_display_item_for_product( $product, $decimals = 2 ) {
		return array(
			'type'   => 'final',
			'label'  => $product->get_name(),
			'amount' => wc_format_decimal( $product->get_price(), $decimals )
		);
	}

	/**
	 * @param array                         $deps
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$deps[] = $scripts->get_handle( 'applepay-v3' );
		}

		return $deps;
	}

	public function get_product_admin_options() {
		$args                                   = wp_parse_args( array(
			'button_type_product' => $this->form_fields['button_type_product'],
			'button'              => $this->form_fields['button']
		), parent::get_product_admin_options() );
		$args['button_type_product']['options'] = wp_parse_args( array(
			'check-out' => __( 'Checkout With Apple Pay', 'woo-payment-gateway' ),
			'subscribe' => __( 'Subscribe With Apple Pay', 'woo-payment-gateway' )
		), $args['button_type_product']['options'] );
		$args['button_type_product']['default'] = $this->get_option( 'button_type_product' );
		$args['button']['description']          = '';
		$args['button']['default']              = $this->get_option( 'button' );

		return $args;
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'applepay' ) );
		}
	}

	/**
	 * @since 3.2.25
	 */
	public function get_applepay_button_style() {
		$style = $this->get_option( 'button' );
		switch ( $style ) {
			case 'apple-pay-button-white':
				return 'white';
			case 'apple-pay-button-white-with-line':
				return 'white-outline';
			default:
				return 'black';
		}
	}

}
