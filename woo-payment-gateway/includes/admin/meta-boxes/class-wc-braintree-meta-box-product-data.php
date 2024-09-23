<?php

namespace PaymentPlugins;

defined( 'ABSPATH' ) || exit();

use \PaymentPlugins\WC_Braintree_Constants as Constants;

class WC_Braintree_Admin_Meta_Box_Product_Data {

	private static $_gateways = array();

	private static $_options = array();

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'output_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
	}

	public static function product_data_tabs( $tabs ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$tabs['braintree'] = array(
				'label'    => __( 'Braintree Settings', 'woo-payment-gateway' ),
				'target'   => 'braintree_product_data',
				'class'    => array( 'hide_if_external' ),
				'priority' => 100,
			);
		}

		return $tabs;
	}

	public static function output_panel() {
		global $product_object;

		self::init_gateways( $product_object );
		if ( current_user_can( 'manage_woocommerce' ) ) {
			include 'views/html-product-data.php';
		}
	}

	private static function init_gateways( $product ) {
		$order = $product->get_meta( Constants::PRODUCT_GATEWAY_ORDER );
		$order = ! $order ? array() : $order;
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway->supports( 'wc_braintree_product_checkout' ) ) {
				$option = new \WC_Braintree_Product_Gateway_Option( $product, $gateway );
				if ( isset( $order[ $gateway->id ] ) ) {
					self::$_options[ $order[ $gateway->id ] ] = $option;
				} else {
					self::$_options[] = $option;
				}
			}
		}
		ksort( self::$_options );
	}

	private static function get_product_option( $gateway_id ) {
		return self::$_options[ $gateway_id ];
	}

	/**
	 *
	 * @param \WC_Product $product
	 */
	public static function save( $product ) {
		// only update the settings if something has been changed.
		if ( empty( $_POST['wc_braintree_update_product'] ) ) {
			return;
		}
		$loop  = 0;
		$order = array();
		self::init_gateways( $product );

		if ( isset( $_POST['braintree_gateway_order'] ) ) {
			foreach ( $_POST['braintree_gateway_order'] as $i => $gateway ) {
				$order[ $gateway ] = $loop;
				$loop ++;
			}
		}
		if ( isset( $_POST[ Constants::BUTTON_POSITION ] ) ) {
			$product->update_meta_data( Constants::BUTTON_POSITION, wc_clean( $_POST[ Constants::BUTTON_POSITION ] ) );
		}
		$product->update_meta_data( Constants::PRODUCT_GATEWAY_ORDER, $order );
	}

	public static function output_option_settings( $option ) {
		?>
        <script type="text/template" id="tmpl-wc-product-<?php echo $option->get_payment_method()->id ?>">
            <div class="wc-backbone-modal">
                <div class="wc-backbone-modal-content">
                    <section class="wc-backbone-modal-main" role="main">
                        <header class="wc-backbone-modal-header">
                            <h1><?php printf( __( '%s Settings', 'woo-payment-gateway' ), $option->get_payment_method()->get_method_title() ); ?></h1>
                            <button
                                    class="modal-close modal-close-link dashicons dashicons-no-alt">
                                <span class="screen-reader-text">Close modal panel</span>
                            </button>
                        </header>
                        <article>
                            <#if(['subscription', 'variable-subscription', 'braintree-subscription', 'braintree-variable-subscription'].indexOf(data.product_type) > -1){#>
							<?php esc_html_e( 'Subscription products don\'t support Pay Later due to regulations, but if enabled, PayPal Credit will show.', 'woo-payment-gateway' ) ?>
                            <#}else if(data.is_preorder){#>
                                <?php esc_html_e('Pre-order products don\'t support Pay Later due to regulations, but if enabled, PayPal Credit will show.', 'woo-payment-gateway')?>
                            <#}#>
                            <form class="wc-braintree-product-form">
								<?php $option->admin_options() ?>
                            </form>
                        </article>
                        <footer>
                            <div class="inner">
                                <button class="button button-primary button-large btn-save-product-options"><?php esc_html_e( 'Save', 'woo-payment-gateway' ); ?></button>
                            </div>
                        </footer>
                    </section>
                </div>
            </div>
            <div class="wc-backbone-modal-backdrop modal-close"></div>
        </script>
		<?php
	}
}

\PaymentPlugins\WC_Braintree_Admin_Meta_Box_Product_Data::init();
