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
class WC_Braintree_GooglePay_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	public $shipping_method_id;

	protected $has_digital_wallet = true;

	public function __construct() {
		$this->id                 = 'braintree_googlepay';
		$this->deprecated_id      = 'braintree_googlepay_gateway';
		$this->token_type         = 'GooglePay';
		$this->template           = 'googlepay.php';
		$this->method_title       = __( 'Braintree Google Pay Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'Google Pay', 'woo-payment-gateway' );
		$this->method_description = __( 'Gateway that integrates Google Pay with your Braintree account.', 'woo-payment-gateway' );
		parent::__construct();
		$this->icon = braintree()->assets_path() . 'img/googlepay/' . $this->get_option( 'icon' ) . '.svg';
	}

	public function add_hooks() {
		parent::add_hooks();
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		add_filter( 'wc_braintree_mini_cart_deps', array( $this, 'get_mini_cart_dependencies' ), 10, 2 );
	}

	public function enqueue_admin_scripts() {
		wp_register_script( 'wc-braintree-googlepay', 'https://pay.google.com/gp/p/js/pay.js', array(), braintree()->version );

		wp_enqueue_script(
			'wc-braintree-googlepay-settings',
			braintree()->assets_path() . 'js/admin/googlepay-settings.js',
			array(
				'wc-braintree-admin-settings',
				'wc-braintree-googlepay',
			),
			braintree()->version,
			true
		);
	}

	public function set_supports() {
		parent::set_supports();
		$this->supports[] = 'wc_braintree_cart_checkout';
		$this->supports[] = 'wc_braintree_product_checkout';
		$this->supports[] = 'wc_braintree_banner_checkout';
		$this->supports[] = 'wc_braintree_mini_cart';
	}

	/**
	 *
	 * @param array            $item
	 * @param WC_Payment_Token $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( 'Braintree_GooglePay' !== $payment_token->get_type() ) {
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

	public function localize_googlepay_params() {
		return $this->get_localized_standard_params();
	}

	public function get_localized_standard_params() {
		$data = array_merge_recursive(
			parent::get_localized_standard_params(),
			array(
				'button_options'       => array(
					'buttonColor' => $this->get_option( 'button_color', 'black' ),
					'buttonType'  => $this->get_option( 'button_type' ),
				),
				'button_shape'         => $this->get_option( 'button_shape' ),
				'banner_enabled'       => $this->banner_checkout_enabled(),
				'google_environment'   => wc_braintree_production_active() ? 'PRODUCTION' : 'TEST',
				'google_merchant'      => wc_braintree_production_active() ? $this->get_option( 'merchant_id' ) : '',
				'google_merchant_name' => $this->get_option( 'merchant_name' ),
				'country_code'         => WC()->countries ? WC()->countries->get_base_country() : '',
				'_3ds'                 => array(
					'enabled' => $this->is_active( '3ds_enabled' )
				)
			)
		);

		return $data;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay',
			$scripts->assets_url( 'js/frontend/googlepay.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'googlepay-v3' ),
				$scripts->get_handle( 'googlepay-pay' ),
				$scripts->get_handle( '3ds-v3' ),
			)
		);
		$scripts->localize_script( 'googlepay', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_cart_scripts()
	 */
	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-cart',
			$scripts->assets_url( 'js/frontend/googlepay-cart.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'googlepay-v3' ),
				$scripts->get_handle( 'googlepay-pay' ),
				$scripts->get_handle( '3ds-v3' )
			)
		);
		$scripts->localize_script( 'googlepay-cart', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_product_scripts()
	 */
	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-product',
			$scripts->assets_url( 'js/frontend/googlepay-product.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'googlepay-v3' ),
				$scripts->get_handle( 'googlepay-pay' ),
				$scripts->get_handle( '3ds-v3' )
			)
		);
		$scripts->localize_script( 'googlepay-product', wp_parse_args( array(
			'button_options' => array(
				'buttonColor' => $this->get_option( 'button_color', 'black' ),
				'buttonType'  => $this->product_gateway_option->get_option( 'button_type' ),
				'buttonColor' => $this->product_gateway_option->get_option( 'button_color', 'black' )
			),
			'button_shape'   => $this->product_gateway_option->get_option( 'button_shape' )
		), $this->get_localized_standard_params() ) );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->googlePayCardDetails;
	}

	/**
	 * @param      $data
	 * @param bool $incl_tax
	 *
	 * @deprecated 3.2.5
	 */
	protected function add_recurring_display_items( &$data, $incl_tax = false ) {
	}

	/**
	 *
	 * @param array $shipping_methods
	 */
	public function get_default_shipping_method( $shipping_methods = array() ) {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		if ( empty( $shipping_methods ) ) {
			reset( $chosen_shipping_methods );
			$key = key( $chosen_shipping_methods );

			return sprintf( '%s:%s', $key, $chosen_shipping_methods[ $key ] );
		} else {
			if ( is_null( $this->shipping_method_id ) ) {
				$shipping_method_ids = wp_list_pluck( $shipping_methods, 'id' );
				foreach ( $chosen_shipping_methods as $idx => $method_id ) {
					$id = $this->get_shipping_method_id( $method_id, $idx );
					if ( in_array( $id, $shipping_method_ids ) ) {
						$this->shipping_method_id = $id;
						break;
					}
				}

				return $this->shipping_method_id;
			} else {
				foreach ( $shipping_methods as $method ) {
					if ( $method['id'] === $this->shipping_method_id ) {
						return $this->shipping_method_id;
					}
				}
			}
			reset( $shipping_methods );

			return $shipping_methods[ key( $shipping_methods ) ]['id'];
		}
	}

	/**
	 * Return shipping options and the selected shipping method in the payment sheet format.
	 *
	 * @return array
	 */
	public function get_shipping_options() {
		$data                    = array();
		$chosen_shipping         = WC()->session->get( 'chosen_shipping_methods', array() );
		$data['shippingOptions'] = $this->get_formatted_shipping_methods();
		$default_shipping_method = $this->get_default_shipping_method( $data['shippingOptions'] );
		if ( $default_shipping_method ) {
			$data['defaultSelectedOptionId'] = $default_shipping_method;
		}

		return $data;
	}

	public function get_formatted_shipping_method( $method, $index, $selected, $amount, $decimals ) {
		return array(
			'id'          => $this->get_shipping_method_id( $method, $index ),
			'label'       => $this->get_shipping_method_label( $method, $amount, $decimals ),
			'description' => '',
		);
	}

	/**
	 * Return a formatted shipping method label.
	 * <strong>Example</strong>&nbsp;5 Day shipping: 5 USD
	 *
	 * @param WC_Shipping_Rate $rate
	 * @param float            $total
	 * @param int              $decimals
	 *
	 * @return
	 *
	 */
	public function get_shipping_method_label( $rate, $total, $decimals ) {
		$incl_tax = wc_braintree_display_prices_including_tax();
		$total    = number_format( $total, $decimals );
		$label    = sprintf( '%s: %s %s', $rate->get_label(), $total, get_woocommerce_currency() );
		if ( $incl_tax ) {
			if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
				$label = sprintf( '%s %s', $label, WC()->countries->inc_tax_or_vat() );
			}
		} else {
			if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$label = sprintf( '%s %s', $label, WC()->countries->ex_tax_or_vat() );
			}
		}

		return $label;
	}

	public function add_to_cart_response( $data ) {
		$data[ $this->id ] = array(
			'displayItems'    => $this->get_display_items(),
			'shippingOptions' => $this->get_formatted_shipping_methods()
		);

		return $data;
	}

	/**
	 * @param array           $data
	 * @param WP_REST_Request $request
	 *
	 * @return array|void
	 */
	public function update_shipping_response( $data, $request ) {
		$shipping_options = $this->get_shipping_options();
		// If there are not shipping options, throw an exception, since this address can't be shipped to.
		if ( empty( $shipping_options['shippingOptions'] ) ) {
			throw new Exception( __( 'No shipping methods available for your shipping address.', 'woo-payment-gateway' ) );
		}
		$data[ $this->id ] = array(
			'requestUpdate' => array(
				'newTransactionInfo'          => array(
					'countryCode'      => WC()->countries->get_base_country(),
					'currencyCode'     => get_woocommerce_currency(),
					'totalPriceStatus' => 'FINAL',
					'totalPrice'       => wc_format_decimal( WC()->cart->total, 2 ),
					'totalPriceLabel'  => __( 'Total', 'woo-payment-gateway' ),
					'displayItems'     => $this->get_display_items(),
				),
				'newShippingOptionParameters' => $shipping_options,
			),
		);
		$data['total']     = wc_format_decimal( WC()->cart->total, 2 );

		return $data;
	}

	/**
	 * @param Exception $e
	 * @param array     $data
	 *
	 * @return array|array[]
	 */
	public function update_shipping_error( $e, $data ) {
		$data[ $this->id ] = array(
			'error' => array(
				'reason'  => $e->getMessage(),
				'message' => $e->getMessage(),
				'intent'  => 'SHIPPING_ADDRESS',
			)
		);

		return $data;
	}

	public function get_display_item_for_cart( $total, $label, $type, $cart, ...$args ) {
		$item = array(
			'label'  => $label,
			'price'  => $total,
			'status' => 'FINAL'
		);
		switch ( $type ) {
			case 'item':
				$item['type'] = 'LINE_ITEM';
				break;
			default:
				$item['type'] = 'SUBTOTAL';
		}

		return $item;
	}

	public function get_display_item_for_product( $product, $decimals = 2 ) {
		return array(
			'label'  => $product->get_name(),
			'type'   => 'LINE_ITEM',
			'price'  => wc_format_decimal( $product->get_price(), $decimals ),
			'status' => 'FINAL',
		);
	}

	public function get_display_item_for_order( $total, $label, $type, $order, ...$args ) {
		$item = array(
			'label'  => $label,
			'price'  => $total,
			'status' => 'FINAL'
		);
		switch ( $type ) {
			case 'line_item':
				$item['type'] = 'LINE_ITEM';
				break;
			default:
				$item['type'] = 'SUBTOTAL';
				break;
		}

		return $item;
	}

	public function get_display_items_for_subscription( $subscription, $decimals = 2 ) {
		return array(
			array(
				'label'  => __( 'Subscription', 'woo-payment-gateway' ),
				'type'   => 'SUBTOTAL',
				'price'  => wc_format_decimal( $subscription->get_total(), $decimals ),
				'status' => 'FINAL',
			)
		);
	}

	/**
	 * @param array                         $deps
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$deps[] = $scripts->get_handle( 'googlepay-v3' );
			$deps[] = $scripts->get_handle( 'googlepay-pay' );
			$deps[] = $scripts->get_handle( '3ds-v3' );
		}

		return $deps;
	}

	public function get_product_admin_options() {
		$args                             = wp_parse_args( array(
			'button_shape' => $this->form_fields['button_shape'],
			'button_type'  => $this->form_fields['button_type'],
			'button_color' => $this->form_fields['button_color']
		), parent::get_product_admin_options() );
		$args['button_color']['default']  = $this->get_option( 'button_color' );
		$args['button_type']['default']   = $this->get_option( 'button_type' );
		$args['button_shape']['default']  = $this->get_option( 'button_shape' );
		$args['button_type']['desc_tip']  = true;
		$args['button_shape']['desc_tip'] = true;

		return $args;
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'googlepay' ) );
		}
	}

}
