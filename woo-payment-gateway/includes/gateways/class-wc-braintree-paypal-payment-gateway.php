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
class WC_Braintree_PayPal_Payment_Gateway extends WC_Braintree_Payment_Gateway {

	protected $has_digital_wallet = true;

	protected $line_item_validations = array(
		'commodityCode'  => 12,
		'description'    => 127,
		'discountAmount' => 2,
		'name'           => 127,
		'productCode'    => 127,
		'taxAmount'      => 2,
		'totalAmount'    => 2,
		'unitAmount'     => 2,
		'unitOfMeasure'  => 12,
		'unitTaxAmount'  => 2,
	);

	/**
	 * @var string
	 * @since 3.2.5
	 */
	protected $paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_CHECKOUT;

	private $sandbox_client_id = 'AZDxjDScFpQtjWTOUtWKbyN_bDt4OgqaF4eYXlewfBP4-8aqX3PiV8e1GWU6liB2CUXlkA59kJXE7M6R';

	public function __construct() {
		$this->id                 = 'braintree_paypal';
		$this->deprecated_id      = 'braintree_paypal_payments';
		$this->token_type         = 'PayPal';
		$this->template           = 'paypal.php';
		$this->method_title       = __( 'Braintree PayPal Gateway', 'woo-payment-gateway' );
		$this->tab_title          = __( 'PayPal', 'woo-payment-gateway' );
		$this->method_description = __( 'Gateway that integrates your PayPal account with Braintree.', 'woo-payment-gateway' );
		$this->icon               = braintree()->assets_path() . 'img/paypal/paypal_long.svg';
		parent::__construct();
		$this->new_method_label   = __( 'New Account', 'woo-payment-gateway' );
		$this->saved_method_label = __( 'Saved Account', 'woo-payment-gateway' );
	}

	public function add_hooks() {
		parent::add_hooks();
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		add_filter( 'wc_braintree_after_checkout_validation_notice', array( $this, 'after_checkout_validation_notice' ), 10, 2 );
		add_filter( 'wc_braintree_mini_cart_deps', array( $this, 'get_mini_cart_dependencies' ), 10, 2 );
		add_filter( 'script_loader_tag', array( $this, 'add_partner_attribution_id' ), 10, 3 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'update_order_review_fragments' ) );
	}

	public function enqueue_admin_scripts() {
		wp_register_script( 'wc-braintree-paypal-objects', sprintf( 'https://www.paypal.com/sdk/js?client-id=%s&components=buttons', $this->sandbox_client_id ), array(), null, true );
		wp_enqueue_script( 'wc-braintree-paypal-settings', braintree()->assets_path() . 'js/admin/paypal-settings.js', array(
			'wc-braintree-admin-settings',
			'wc-braintree-paypal-objects',
			'jquery-ui-slider'
		), braintree()->version, true );
	}

	public function set_supports() {
		parent::set_supports();
		$this->supports[] = 'wc_braintree_cart_checkout';
		$this->supports[] = 'wc_braintree_banner_checkout';
		$this->supports[] = 'wc_braintree_product_checkout';
		$this->supports[] = 'wc_braintree_mini_cart';
	}

	/**
	 *
	 * @param array            $item
	 * @param WC_Payment_Token $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( 'Braintree_PayPal' !== $payment_token->get_type() ) {
			return $item;
		}
		$item['method']['brand']     = $payment_token->get_payment_method_title( $this->get_option( 'method_format' ) );
		$item['expires']             = __( 'N/A', 'woo-payment-gateway' );
		$item['method_type']         = $payment_token->get_method_type();
		$item['wc_braintree_method'] = true;

		return $item;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		wp_enqueue_script( 'wc-braintree-paypal-checkout' );
		$scripts->localize_script( 'paypal-checkout', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_cart_scripts()
	 */
	public function enqueue_cart_scripts( $scripts ) {
		$this->register_paypal_script( $scripts );
		$scripts->enqueue_script(
			'paypal-cart',
			$scripts->assets_url( 'js/frontend/paypal-cart.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'paypal-checkout' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'paypal-checkout-v3' ),
			)
		);
		$scripts->localize_script( 'paypal-cart', $this->get_localized_standard_params() );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::enqueue_product_scripts()
	 */
	public function enqueue_product_scripts( $scripts ) {
		$this->register_paypal_script( $scripts );
		$scripts->enqueue_script(
			'paypal-product',
			$scripts->assets_url( 'js/frontend/paypal-product.js' ),
			array(
				$scripts->get_handle( 'client-manager' ),
				$scripts->get_handle( 'paypal-checkout' ),
				$scripts->get_handle( 'data-collector-v3' ),
				$scripts->get_handle( 'paypal-checkout-v3' ),
			)
		);
		$params = $this->get_localized_standard_params();
		$params = wp_parse_args( array(
			'button_style'  => array(
				'label'  => $this->product_gateway_option->get_option( 'smartbutton_label' ),
				'color'  => $this->product_gateway_option->get_option( 'smartbutton_color' ),
				'shape'  => $this->product_gateway_option->get_option( 'smartbutton_shape' ),
				'height' => intval( $this->get_option( 'button_height' ) ),
			),
			'credit_button' => array( 'color' => $this->product_gateway_option->get_option( 'credit_button_color' ) ),
			'bnpl'          => array(
				'enabled'   => $this->product_gateway_option->is_active( 'bnpl_enabled' ),
				'button'    => array( 'color' => $this->product_gateway_option->get_option( 'bnpl_button_color' ) ),
				'msg'       => array(
					'enabled' => $this->product_gateway_option->is_active( 'pay_later_msg_enabled' ) && $this->can_show_bnpl_msg()
				),
				'txt_color' => $this->product_gateway_option->get_option( 'pay_later_txt_color' )
			),
			'display_type'  => "paypal-{$this->product_gateway_option->get_option( 'display_type' )}",
		), $params );
		$scripts->localize_script( 'paypal-product', $params );
	}

	public function get_localized_standard_params() {
		$data = array_merge_recursive(
			parent::get_localized_standard_params(),
			array(
				'query_params'      => $this->get_paypal_script_query_args(),
				'button_style'      => $this->get_button_options(),
				'card_icons'        => $this->is_active( 'smartbutton_cards' ),
				'tokenize_response' => WC()->session ? WC()->session->get( $this->id . '_tokenized_response' ) : null,
				'banner_enabled'    => $this->banner_checkout_enabled(),
				'options'           => array(
					'flow'        => $this->get_paypal_flow(),
					'intent'      => $this->get_option( 'charge_type' ),
					'currency'    => $this->get_checkout_currency(),
					'displayName' => $this->get_option( 'display_name' )
				),
				'credit_button'     => array(
					'color' => $this->get_option( 'credit_button_color' )
				),
				'bnpl'              => array(
					'enabled'   => $this->is_active( 'bnpl_enabled' ),
					'button'    => array(
						'color' => $this->get_option( 'bnpl_button_color' )
					),
					'sections'  => $this->get_credit_sections(),
					'msg'       => array(
						'can_show' => $this->can_show_bnpl_msg() && $this->is_active( 'bnpl_enabled' ),
						'sections' => $this->get_pay_later_sections()
					),
					'txt_color' => $this->get_option( 'pay_later_txt_color' )
				),
				'card_button'       => array(
					'color' => $this->get_option( 'card_button_color' )
				),
				'button_sorting'    => array(
					'paypal',
					'credit',
					'paylater',
					'card'
				),
				'routes'            => array(
					'paypal_shipping' => WC_Braintree_Rest_API::get_endpoint( braintree()->rest_api->paypal->rest_uri() . '/shipping' )
				)
			)
		);

		return apply_filters( 'wc_braintree_localized_paypal_params', $data );
	}

	public function localize_paypal_params() {
		return $this->get_localized_standard_params();
	}

	/**
	 * @return array
	 */
	public function get_button_options() {
		$options = array(
			'label'  => $this->get_option( 'smartbutton_label' ),
			'color'  => $this->get_option( 'smartbutton_color' ),
			'shape'  => $this->get_option( 'smartbutton_shape' ),
			'height' => intval( $this->get_option( 'button_height' ) ),
		);

		/**
		 * @param array                        $options
		 * @param WC_Braintree_Payment_Gateway $this
		 *
		 * @since 3.2.4
		 */
		return apply_filters( 'wc_braintree_paypal_button_options', $options, $this );
	}

	/**
	 * @return bool
	 * @deprecated 3.2.8
	 */
	public function is_paypal_credit_active() {
		return $this->is_active( 'credit_enabled' ) && wc_braintree_evaluate_condition( $this->get_option( 'credit_conditions', '' ) );
	}

	/**
	 * @since 3.2.8
	 * @return bool
	 */
	public function is_bnpl_active() {
		return $this->is_active( 'bnpl_enabled' );
	}

	/**
	 * Returns either "checkout" or "vault" depending on conditions such as if the cart contains
	 * subscriptions etc.
	 *
	 * @param string $page
	 *
	 * @return string
	 */
	public function get_paypal_flow( $page = '' ) {
		if ( in_array( $page, array( 'cart', 'checkout' ) ) || is_checkout() || is_cart() ) {
			global $wp;
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
				if ( wcs_braintree_active() ) {
					if ( WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment || wcs_order_contains_subscription( $order ) ) {
						$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
					}
				} elseif ( wc_braintree_subscriptions_active() && wcs_braintree_order_contains_subscription( $order ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				} elseif ( wc_braintree_pre_orders_active() && WC_Pre_Orders_Order::order_contains_pre_order( $order ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				}
			} else {
				if ( wcs_braintree_active() && ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				} elseif ( wc_braintree_subscriptions_active() && wcs_braintree_cart_contains_subscription() ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				} elseif ( wc_braintree_pre_orders_active() && WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				}
			}
		} elseif ( 'product' === $page || is_product() ) {
			// first check if the cart requires vault. If not, check if product requires vault.
			if ( \PaymentPlugins\WC_Braintree_Constants::PAYPAL_CHECKOUT === $this->get_paypal_flow( 'cart' ) ) {
				global $product;
				if ( wcs_braintree_active() && is_a( $product, 'WC_Product' ) && WC_Subscriptions_Product::is_subscription( $product ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				} elseif ( wc_braintree_subscriptions_active() && is_a( $product, 'WC_Product' ) && wcs_braintree_product_is_subscription( $product ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				} elseif ( wc_braintree_pre_orders_active() && is_a( $product, 'WC_Product' ) && WC_Pre_Orders_Product::product_can_be_pre_ordered( $product ) ) {
					$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
				}
			}
		} elseif ( $this->is_change_payment_request() ) {
			$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
		} elseif ( is_add_payment_method_page() ) {
			$this->paypal_flow = \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT;
		} else {
			// if no matches then resort to what's in cart to determine flow.
			return $this->get_paypal_flow( 'cart' );
		}

		return apply_filters( 'wc_braintree_get_paypal_flow', $this->paypal_flow, $this, $page );
	}

	public function remove_session_checkout_vars() {
		unset( WC()->session->{$this->id . '_tokenized_response'} );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payment_Gateway::get_payment_method_from_transaction()
	 */
	public function get_payment_method_from_transaction( $transaction ) {
		return $transaction->paypalDetails;
	}

	/**
	 * Method that adds to the validation notice when the selected payment method is PayPal.
	 *
	 * @param string $notice
	 * @param array  $data
	 */
	public function after_checkout_validation_notice( $notice, $data ) {
		if ( $this->id === $data['payment_method'] ) {
			if ( current_user_can( 'administrator' ) ) {
				$notice .= sprintf(
					' ' .
					__( 'Admin Notice: For virtual products, PayPal must send back the billing address. Click %1$shere%2$s to read how to enable this functionality.', 'woo-payment-gateway' ),
					'<a target="_blank" href="https://docs.paymentplugins.com/wc-braintree/config/#/braintree_paypal?id=enable-billing-address">',
					'</a>'
				);
			}
		}

		return $notice;
	}

	/**
	 * Returns an array of locales supported by the PayPal smartbuttons.
	 *
	 * @since 3.0.2
	 * @deprecated
	 */
	public function get_supported_locales() {
		return apply_filters( 'wc_braintree_paypal_supported_locales', array() );
	}

	/**
	 * Determine the user's locale based on their browser settings.
	 *
	 * @since 3.0.4
	 * @deprecated
	 */
	public function get_user_locale() {
		$this->get_option( 'locale' );
	}

	/**
	 * Decorate the response with data specific to PayPal.
	 *
	 * @param array $data
	 *
	 * @retun array
	 */
	public function update_shipping_method_response( $data ) {
		$data['cart_totals'] = wc_braintree_get_template_html( 'paypal-cart-totals.php', array( 'gateway' => $this ) );

		return $data;
	}

	protected function add_order_line_items( &$args, $order, &$items = array() ) {
		/**
		 * @since 3.2.28 - only allow lines items if this filter returns true. The line item validation on the PayPal side
		 * is prone to errors and it's best to disable it by default.
		 */
		if ( apply_filters( 'wc_braintree_paypal_order_line_items_enabled', false, $args, $order, $this ) ) {
			/**
			 * @since 3.2.21 - added a check for prices including tax because there are issues mapping
			 * Braintree fields to PayPal when prices include tax and PayPal fails validation.
			 *
			 * An example is in the patch call, if prices include tax, then the tax_amount field is set to $0 since
			 * all other line items have tax. When the Braintree transaction is processed, tax amount is not $0 and PayPal
			 * fails validation since the patch had $0 tax.
			 *
			 * It's easier to just bypass line items given that scenario.
			 */
			if ( ! wc_braintree_display_prices_including_tax() ) {
				parent::add_order_line_items( $args, $order, $items );


				if ( isset( $args['lineItems'] ) ) {
					// calculate the sum of all the totals.
					$total = array_sum( array_map( function ( $totals ) {
						return $totals['subtotal'];
					}, $this->order_totals ) );

					// Unset the lineItems if totals do not equal order total. This will prevent validation errors.
					// In some cases, the line item totals won't equal the order total because of rounding performed
					// by the plugin to ensure decimal restrictions imposed by PayPal aren't violated.
					// Other settings like, round at subtotal vs line item could affect this to.
					if ( abs( $total - $order->get_total() ) > 0.00001 ) {
						unset( $args['lineItems'] );
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	private function register_paypal_script( $scripts ) {
		if ( $scripts->client_token ) {
			// If flow is vault and script has already been enqueued, then skip. Vault always takes precedence.
			if ( wp_script_is( $scripts->get_handle( 'paypal-checkout' ), 'registered' ) ) {
				if ( $this->paypal_flow === \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT ) {
					return;
				} else {
					// de-register so the latest script will be used.
					wp_deregister_script( $scripts->get_handle( 'paypal-checkout' ) );
				}
			}
			$query_args = $this->get_paypal_script_query_args();

			wp_register_script( $scripts->get_handle( 'paypal-checkout' ), add_query_arg( $query_args, 'https://www.paypal.com/sdk/js' ), array(), null, true );
		} else {
			static $retries = 0;
			if ( $retries < 1 ) {
				$retries += 1;
				$scripts->generate_client_token();

				return $this->register_paypal_script( $scripts );
			}
		}
	}

	public function get_paypal_script_query_args() {
		$scripts = braintree()->scripts();

		$query_args = array(
			'components'     => 'buttons,messages',
			'currency'       => wc_braintree_get_currency(),
			'enable-funding' => 'paylater',
			'vault'          => $this->get_paypal_flow() === 'vault' ? 'true' : 'false'
		);

		if ( ! did_action( 'woocommerce_checkout_update_order_review' ) ) {
			if ( ! $scripts->client_token ) {
				$scripts->generate_client_token();
			}
			$query_args['client-id'] = $scripts->client_token->paypal->clientId;
		}

		if ( is_checkout() && WC()->cart && WC()->cart->needs_shipping() ) {
			$query_args['commit'] = 'false';
		}
		/* Only add intent arg if this is a checkout flow.
		 * https://github.com/paypal/paypal-checkout-components/issues/1290
		 */
		if ( $query_args['vault'] === 'false' ) {
			$query_args['intent'] = $this->get_option( 'charge_type' );
		} else {
			$query_args['intent'] = 'tokenize';
		}

		return $query_args;
	}

	/**
	 * @param array           $data
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws Exception
	 */
	public function update_shipping_response( $data, $request ) {
		$incl_tax         = wc_braintree_display_prices_including_tax();
		$shipping_options = $this->get_formatted_shipping_methods();
		if ( ! $shipping_options ) {
			throw new Exception( __( 'No shipping methods for provided address', 'woo-payment-gateway' ) );
		}
		$currency = get_woocommerce_currency();
		$decimals = ( ( $decimals = wc_get_price_decimals() ) < 2 ? $decimals : 2 );
		function get_fee_tax() {
			return array_sum( array_map( function ( $fee ) {
				return $fee->tax;
			}, WC()->cart->get_fees() ) );
		}

		$totals     = array(
			'item_total' => wc_format_decimal( $incl_tax ? WC()->cart->subtotal + WC()->cart->fee_total + get_fee_tax() : WC()->cart->subtotal_ex_tax + WC()->cart->fee_total, $decimals ),
			'shipping'   => wc_format_decimal( $incl_tax ? WC()->cart->shipping_total + WC()->cart->shipping_tax_total : WC()->cart->shipping_total, $decimals ),
			'tax_total'  => wc_format_decimal( $incl_tax ? 0 : WC()->cart->get_taxes_total(), $decimals ),
			'discount'   => wc_format_decimal( $incl_tax ? WC()->cart->discount_cart + WC()->cart->discount_cart_tax : WC()->cart->discount_cart, $decimals )
		);
		$cart_total = wc_format_decimal( WC()->cart->total, $decimals );

		$response = array(
			'patch' => array(
				array(
					'op'    => 'replace',
					'path'  => '/purchase_units/@reference_id==\'default\'/amount',
					'value' => array(
						'currency_code' => $currency,
						'value'         => $cart_total,
						'breakdown'     => array(
							'item_total' => array(
								'currency_code' => $currency,
								'value'         => $totals['item_total']
							),
							'shipping'   => array(
								'currency_code' => $currency,
								'value'         => $totals['shipping']
							),
							'tax_total'  => array(
								'currency_code' => $currency,
								'value'         => $totals['tax_total']
							),
							'discount'   => array(
								'currency_code' => $currency,
								'value'         => $totals['discount']
							)
						)
					)
				),
				array(
					'op'    => ! $request->get_param( 'shipping_method' ) ? 'add' : 'replace',
					'path'  => '/purchase_units/@reference_id==\'default\'/shipping/options',
					'value' => $this->get_formatted_shipping_methods()
				)
			),
			'html'  => wc_braintree_get_template_html(
				'paypal-shipping-methods.php',
				array(
					'gateway'                 => $this,
					'packages'                => $this->get_shipping_packages(),
					'chosen_shipping_methods' => WC()->session->get(
						'chosen_shipping_methods',
						array()
					),
				)
			)
		);

		// if breakdown doesn't match amount, don't include it.
		$breakdown_total = array_sum( $totals );
		if ( $breakdown_total != $cart_total ) {
			unset( $response['patch'][0]['value']['breakdown'] );
		}

		$data[ $this->id ] = $response;

		return $data;
	}

	/**
	 * @param WC_Shipping_Rate $method
	 * @param int              $index
	 * @param bool             $selected
	 * @param float            $amount
	 * @param int              $decimals
	 *
	 * @return array
	 */
	public function get_formatted_shipping_method( $method, $index, $selected, $amount, $decimals ) {
		/**
		 * @since 3.2.9 use the $method->cost since that's the before tax amount.
		 */
		return array(
			'id'       => $this->get_shipping_method_id( $method, $index ),
			'label'    => $method->get_label(),
			'type'     => 'SHIPPING',
			'selected' => $selected,
			'amount'   => array(
				'value'         => wc_format_decimal( $amount, $decimals ),
				'currency_code' => get_woocommerce_currency()
			)
		);
	}

	/**
	 * @param array                         $deps
	 * @param WC_Braintree_Frontend_Scripts $scripts
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$this->register_paypal_script( $scripts );
			$deps[] = $scripts->get_handle( 'paypal-checkout' );
			$deps[] = $scripts->get_handle( 'paypal-checkout-v3' );
		}

		return $deps;
	}

	public function add_partner_attribution_id( $tag, $handle, $src ) {
		if ( 'wc-braintree-paypal-checkout' === $handle ) {
			$tag = str_replace( 'src', 'data-partner-attribution-id="' . braintree()->partner_code . '" src', $tag );
		}

		return $tag;
	}

	/**
	 * @since 3.2.7
	 * @return array
	 */
	private function get_pay_later_sections() {
		return $this->get_option( 'pay_later_msg' );
	}

	/**
	 * @since 3.2.7
	 * @return array
	 */
	private function get_credit_sections() {
		return $this->get_option( 'bnpl_sections' );
	}


	/**
	 *
	 * @since 3.2.7
	 * @return bool
	 */
	private function can_show_bnpl_msg() {
		// Can't show Pay Later messaging if the merchant is not selling in USD or sells subscription products.
		/*if ( wcs_braintree_active() || wc_braintree_subscriptions_active() ) {
			return false;
		}*/
		if ( \PaymentPlugins\WC_Braintree_Constants::PAYPAL_VAULT === $this->get_paypal_flow() ) {
			return false;
		}
		if ( ! is_admin() && ! in_array( get_woocommerce_currency(), array( 'USD', 'GBP', 'AUD', 'EUR' ) ) ) {
			return false;
		}

		return true;
	}

	public function get_product_admin_options() {
		$args                                   = wp_parse_args( array(
			'smartbutton_color'     => $this->form_fields['smartbutton_color'],
			'smartbutton_shape'     => $this->form_fields['smartbutton_shape'],
			'smartbutton_label'     => $this->form_fields['smartbutton_label'],
			'pay_later'             => $this->form_fields['pay_later'],
			'bnpl_enabled'          => $this->form_fields['bnpl_enabled'],
			'bnpl_button_color'     => $this->form_fields['bnpl_button_color'],
			'display_type'          => array(
				'title'       => __( 'Display type', 'woo-payment-gateway' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'vertical',
				'options'     => array(
					'inline'   => __( 'Inline', 'woo-payment-gateway' ),
					'vertical' => __( 'Vertical', 'woo-payment-gateway' )
				),
				'desc_tip'    => true,
				'description' => __( 'This option determines if the PayPal button and Credit button are side by side or vertical.', 'woo-payment-gateway' )
			),
			'pay_later_msg_enabled' => array(
				'title'       => __( 'Enable Pay Later Msg', 'woo-payment-gateway' ),
				'type'        => 'checkbox',
				'default'     => wc_bool_to_string( in_array( 'product', $this->get_pay_later_sections() ) ),
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => '',
			),
			'pay_later_txt_color'   => $this->form_fields['pay_later_txt_color']
		), parent::get_product_admin_options() );
		$args['bnpl_enabled']['default']        = wc_bool_to_string( $this->is_active( 'bnpl_enabled' ) && in_array( 'product', $this->get_credit_sections() ) );
		$args['smartbutton_color']['default']   = $this->get_option( 'smartbutton_color' );
		$args['smartbutton_shape']['default']   = $this->get_option( 'smartbutton_shape' );
		$args['pay_later_txt_color']['default'] = $this->get_option( 'pay_later_txt_color' );

		/*if ( is_admin() && ! $this->can_show_bnpl_msg() ) {
			unset( $args['pay_later'], $args['pay_later_msg_enabled'] );
			$args['no_pay_later'] = array(
				'title' => __( 'Enable Pay Later Msg', 'woo-payment-gateway' ),
				'type'  => 'paragraph',
				'text'  => __( 'Pay Later Messaging cannot be offered on sites that sell subscriptions due to payment regulations.', 'woo-payment-gateway' )
			);
		}*/

		return $args;
	}

	/**
	 * @param WC_Braintree_Product_Gateway_Option $product_option
	 */
	public function init_product_gateway_settings( $product_option ) {
		// if the credit enabled setting exists then it hasn't been converted to bnpl
		if ( isset( $product_option->settings['credit_enabled'] ) ) {
			$product_option->settings['bnpl_enabled'] = $this->settings['credit_enabled'];

			// unset credit_enabled so we know next time that the settings have been converted to use bnpl
			unset( $product_option->settings['credit_enabled'] );
			$product_option->save();
		}
	}

	public function has_enqueued_scripts( $scripts, $context = 'checkout' ) {
		switch ( $context ) {
			case 'checkout':
				return wp_script_is( $scripts->get_handle( 'paypal' ) );
		}
	}

	public function update_order_review_fragments( $fragments ) {
		if ( $this->is_available() ) {
			$fragments['paypal_query_args'] = $this->get_paypal_script_query_args();
			$fragments['paypal_options']    = array(
				'flow' => $this->get_paypal_flow()
			);
		}

		return $fragments;
	}

}
