<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}



if ( ! class_exists( 'Nebulox_Gateway' ) ) {
	/**
	 * Nebulox Crypto Gateway Class.
	 *
	 * @document This class handles the Nebulox Crypto Gateway for
	 * WooCommerce,
	 * including processing payments
	 *       and managing gateway settings.
	 */
	require_once plugin_dir_path( __FILE__ ) . './nebulox-woogate-functions.php';
	class Nebulox_Gateway extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'nebulox_gateway';
			$this->method_title       = __( 'Nebulox Crypto Gateway', 'nebuloxWoogate' );
			$this->method_description = __( 'Nebulox Crypto Gateway for accepting cryptocurrencies payment', 'nebuloxWoogate' );
			$this->has_fields         = false;
			$this->init_form_fields();
			$this->init_settings();
			$this->enabled                   = $this->get_option( 'enabled', 'no' );
			$this->description               = $this->get_option( 'description', 'Nebulox is a crypto gateway that user can use it to pay their orders in cryptoCurrencies' );
			$this->title                     = $this->get_option( 'title', 'Nebulox Gateway' );
			$this->icon                      = plugin_dir_url( __FILE__ ) . './assets/logo/favicon.png';
			$this->api_key                   = $this->get_option( 'api_key', '' );
			$this->white_list_ip             = $this->get_option( 'white_list_ip', '' );
			$this->invoice_regeneration_time = $this->get_option( 'expired_in', 60 );
			add_action(
				'woocommerce_update_options_payment_gateways_' .
				$this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * Initialize form fields.
		 *
		 * @document This function initializes the form fields for the
		 * gateway
		 * settings in WooCommerce.
		 */
		public function init_form_fields() {
			if ( is_admin() ) {
				$this->form_fields = array(
					'enabled'       => array(
						'title'   => __( 'Enable/Disable', 'nebuloxWoogate' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable/Disable Nebulox Gateway', 'nebuloxWoogate' ),
						'default' => 'no',
					),
					'title'         => array(
						'title'             => __( 'Title', 'nebuloxWoogate' ),
						'type'              => 'text',
						'custom_attributes' => array( 'readonly' => 'readonly' ),
					),
					'description'   => array(
						'title'       => __( 'Description', 'nebuloxWoogate' ),
						'type'        => 'textarea',
						'description' => __( 'Payment method description shown in the Checkout', 'nebuloxWoogate' ),
					),
					'order_status'  => array(
						'title'       => __( 'Complete Order on Status', 'nebuloxWoogate' ),
						'type'        => 'select',
						'options'     => array(
							'completed'  => __( 'Completed', 'nebuloxWoogate' ),
							'processing' => __( 'Processing', 'nebuloxWoogate' ),
						),
						'default'     => 'processing',
						'description' => __( 'The order status to be set when payment is completed.', 'nebuloxWoogate' ),
					),
					'api_key'       => array(
						'title'       => __( 'API Key', 'nebuloxWoogate' ),
						'type'        => 'password',
						'required'    => true,
						'description' => __(
							'API key you get from our site.',
							'nebuloxWoogate'
						),
					),
					'webhook_url'   => array(
						'title'             => __( 'Webhook URL', 'nebuloxWoogate' ),
						'type'              => 'text',
						'description'       => __( "Read this <a href='https://docs.nebulox.io/plugins/woocommerce' target='_blank'>instruction</a> to implement your first gateway", 'nebuloxWoogate' ),
						'default'           => get_rest_url() . 'nebulox/v1/nebulox_webhook',
						'custom_attributes' => array( 'readonly' => 'readonly' ),
					),
					'redirect_url'  => array(
						'title'             => __( 'Redirect URL', 'nebuloxWoogate' ),
						'type'              => 'text',
						'description'       => __( "If you don't have the Thank You page after checkout, we recommend you use this URL when creating a gateway.", 'nebuloxWoogate' ),
						'default'           => wc_get_endpoint_url(
							'order-received',
							'',
							$this->get_return_url()
						),
						'custom_attributes' => array( 'readonly' => 'readonly' ),
					),
					'white_list_ip' => array(
						'title'             => __( 'Ip White List', 'nebuloxWoogate' ),
						'type'              => 'text',
						'description'       => __(
							'Allow Nebulox Ips to send request.',
							'nebuloxWoogate'
						),
						'default'           => '',
						'desc_tip'          => true,
						'sanitize_callback' => 'nebulox_save_white_list_ips',
					),
					'expired_in'    => array(
						'title'       => __( 'Expirations Time', 'nebuloxWoogate' ),
						'type'        => 'text',
						'description' => __(
							'Invoice will be regenerated for orders with a status of pending after this time, in minutes.',
							'nebuloxWoogate'
						),
						'default'     => '60',
						'desc_tip'    => true,
					),
				);
			}
		}

		/**
		 * Process payment.
		 *
		 * @documentument This function processes the payment through the
		 * Nebulox Gateway.
		 *
		 * @param int $order_id The ID of the order being processed.
		 * @return array The result of the payment process.
		 */
		public function process_payment( $order_id ) {
			$order        = wc_get_order( $order_id );
			$status       = $order->get_status();
			$invoice_url  = $order->get_meta( 'nebulox_invoice_url' );
			$created_date = strtotime( $order->get_date_created() ) + $this->invoice_regeneration_time;
			if ( $status == 'pending' && $invoice_url && $created_date < time() ) {
				return array(
					'result'   => 'success',
					'redirect' => $invoice_url,
				);
			}

			$currency = $order->get_currency();
			$apiKey   = $this->api_key;
			$price    = $order->get_total();
			if ( ! in_array( $currency, NCGIO_NEBULOX_SUPPORTED_CURRENCY ) ) {

				wc_add_notice( 'Currency not supported. Please try another payment method', 'error' );
				return 0;
			}
			$body = array(
				'orderId'      => $order_id,
				'baseCurrency' => $currency,
				'price'        => $price,
				'apiKey'       => $apiKey,
			);

			$args = array(
				'body'    => json_encode( $body ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 60,
			);

			$response = wp_remote_post( NCGIO_NEBULOX_CREATE_INVOICE_API, $args );
			if ( is_wp_error( $response ) ) {
				return array(
					'result'  => 'failure',
					'message' => 'Payment processing failed. Please try again.',
				);
			}
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 201 ) {
				$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $response_body['result']['id'] ) && isset( $response_body['result']['url'] ) ) {
					WC()->cart->empty_cart();
					$order->update_status( 'pending_payment', __( 'Payment status has been changed.', 'nebuloxWoogate' ) );
					$order->update_meta_data(
						'nebulox_invoice_url',
						$response_body['result']['url']
					);
					$order->save();
					return array(
						'result'   => 'success',
						'redirect' => $response_body['result']['url'],
					);
				} else {
					$order->update_status( 'draft', __( 'Unexpected response from the payment gateway.', 'nebuloxWoogate' ) );
					return array(
						'result'          => 'failure',
						'message'         => 'Unexpected response from the payment gateway.',
						'open_in_new_tab' => true,
					);
				}
			}
			return array(
				'result'  => 'failure',
				'message' => 'Payment was not successful. Please try again.',
			);
		}

		/**
		 * Process admin options.
		 *
		 * @document This function processes and validates the admin options
		 * set
		 * for the gateway.
		 */
		public function process_admin_options() {
			if ( $this->validate_fields() ) {
					parent::process_admin_options();

			} else {
				$this->enabled = 'no';
			}
		}

		/**
		 * Validate fields.
		 *
		 * @document This function validates the fields in the gateway
		 * settings.
		 *
		 * @return bool True if validation passes, false otherwise.
		 */
		public function validate_fields() {
			if ( $_POST[ 'woocommerce_' . $this->id . '_expired_in' ] < 60 ) {
				WC_Admin_Settings::add_error(
					__(
						'Error:Expiration time should be greater or equal to 60',
						'error'
					)
				);
				return false;
			}
			if ( strlen( $_POST[ 'woocommerce_' . $this->id . '_description' ] ) < 50 ) {
				WC_Admin_Settings::add_error( __( 'Description is too short. It should be more than 50 characters.', 'nebuloxWoogate' ) );
				return false;
			}
			if ( ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $_POST[ 'woocommerce_' . $this->id . '_api_key' ] ) !== 1 ) ) {
					WC_Admin_Settings::add_error(
						__(
							'Error:API key format is invalid',
							'error'
						)
					);
					return false;
			}
			return true;
		}
	}
}

if ( ! function_exists( 'include_nebulox_gateway' ) ) {
	function include_nebulox_gateway( $gateways ) {
		$gateways[] = 'Nebulox_Gateway';
		return $gateways;
	}

	add_filter( 'woocommerce_payment_gateways', 'include_nebulox_gateway' );
}
