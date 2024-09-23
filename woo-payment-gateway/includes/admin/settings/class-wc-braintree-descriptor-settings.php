<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.0.0
 * @package Braintree/Admin
 *
 */
if ( ! class_exists( 'WC_Braintree_Advanced_Settings_API' ) ) {
	return;
}

class WC_Braintree_Descriptor_Settings extends WC_Braintree_Advanced_Settings_API {

	public function __construct() {
		$this->id                         = 'braintree_descriptor';
		$this->tab_title                  = __( 'Descriptor Settings', 'woo-payment-gateway' );
		$this->braintree_documentation_id = 'descriptor-settings';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_braintree_descriptor_form_fields',
			array(
				'descriptors'      => array(
					'title'       => __( 'Dynamic Descriptor', 'woo-payment-gateway' ),
					'type'        => 'title',
					'description' => __(
						'<i><b>Note:</b> Descriptors are not required. Braintree will send your business name by default and we recommend you leave these values blank to prevent transaction errors if you enter a descriptor name incorrectly.</i> Dynamic descriptors are sent on a per-order basis and define what will appear on your customers\' credit card statements for a specific purchase.
						The clearer the description of your product, the less likely customers will issue chargebacks due to confusion or non-recognition. ',
						'woo-payment-gateway'
					),
				),
				'enabled'          => array(
					'type'        => 'checkbox',
					'title'       => __( 'Enabled', 'woo-payment-gateway' ),
					'default'     => 'no',
					'desc_tip'    => true,
					'description' => __( 'If enabled, your custom descriptors will be used in transactions.', 'woo-payment-gateway' ),
				),
				'descriptor_name'  => array(
					'type'        => 'text',
					'title'       => __( 'Descriptor Name', 'woo-payment-gateway' ),
					'default'     => '',
					'value'       => '',
					'description' => __(
						'The value in the business name field of a customer\'s statement. Company name/DBA section must be
								either 3, 7 or 12 characters and the product descriptor can be up to 18, 14, or 9 characters respectively (with an * in between for a total descriptor name of 22 characters). <b>Example:</b> company*my product',
						'woo-payment-gateway'
					),
				),
				'descriptor_phone' => array(
					'type'              => 'text',
					'title'             => __( 'Descriptor Phone', 'woo-payment-gateway' ),
					'value'             => '',
					'default'           => '',
					'class'             => '',
					'description'       => __(
						'The value in the phone number field of a customer\'s statement. Phone must be 10-14 characters and can
								only contain numbers, dashes, parentheses and periods.',
						'woo-payment-gateway'
					),
					'custom_attributes' => array( 'maxlength' => 22 ),
				),
				'descriptor_url'   => array(
					'type'              => 'text',
					'title'             => __( 'Descriptor URL', 'woo-payment-gateway' ),
					'maxlength'         => 13,
					'value'             => '',
					'default'           => '',
					'class'             => '',
					'description'       => __( 'The value in the URL/web address field of a customer\'s statement. The URL must be 13 characters or shorter. <b>Example:</b> company.com', 'woo-payment-gateway' ),
					'custom_attributes' => array( 'maxlength' => 13 ),
				),
			)
		);
	}
}
