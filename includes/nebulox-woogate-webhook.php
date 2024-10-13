<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'NCGIO_NEBULOX_WEBHOOK_VERSION', '0.1' );

if ( ! function_exists( 'ncgio_nebulox_handle_webhook' ) ) {
	require_once plugin_dir_path( __FILE__ ) . './nebulox-woogate-functions.php';

	function ncgio_nebulox_handle_webhook( WP_REST_Request $request ) {
		$client_ip = '';

		if ( ! empty( $request->get_header( 'X-Forwarded-For' ) ) ) {
			$client_ip = $request->get_header( 'X-Forwarded-For' );
		} elseif ( ! empty( $request->get_header( 'REMOTE_ADDR' ) ) ) {
			$client_ip = $request->get_header( 'REMOTE_ADDR' );
		} elseif ( ! empty( $request->get_header( 'HTTP_CLIENT_IP' ) ) ) {
			$client_ip = $request->get_header( 'HTTP_CLIENT_IP' );
		} elseif ( ! empty( $request->get_header( 'HTTP_X_REAL_IP' ) ) ) {
			$client_ip = $request->get_header( 'HTTP_X_REAL_IP' );
		} elseif ( ! empty( $request->get_header( 'CF-Connecting-IP' ) ) ) {
			$client_ip = $request->get_header( 'CF-Connecting-IP' );
		}
        $nebulox_settings = get_option( 'woocommerce_nebulox_gateway_settings', array() );

		$whitelisted_ips = $nebulox_settings['white_list_ip'];

		if ( ! empty( $whitelisted_ips ) ) {

			$whitelisted_ips = explode( ',', $whitelisted_ips );

			if ( ! in_array( $client_ip, $whitelisted_ips, true ) ) {
				return new WP_REST_Response( 'Access denied: Unauthorized IP address', 403 );
			}
		}

		$payload       = $request->get_body();
		$x_hash_header = $request->get_header( 'X-hash' );

		if ( empty( $x_hash_header ) ) {
			return new WP_REST_Response( 'Some property is not included in the header', 403 );
		}

		if ( ! isset( $nebulox_settings['api_key'] ) ) {
			wc_add_notice(
				__(
					'Error: API Key is missing to encrypt the payload.',
					'nebuloxWoogate'
				),
				'error'
			);
			return false;
		}

		$key = $nebulox_settings['api_key'];
		$hmac          = hash_hmac( 'sha256', $payload, $key );
		if ( hash_equals( $x_hash_header, $hmac ) ) {
			ncgio_nebulox_process_webhook( $payload );
			return new WP_REST_Response( 'Webhook processed', 200 );
		} else {
			return new WP_REST_Response( 'Forbidden', 403 );
		}
	}
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'nebulox/v1',
			'/nebulox_webhook',
			array(
				'methods'             => 'POST',
				'callback'            => 'ncgio_nebulox_handle_webhook',
				'permission_callback' => '__return_true',
			)
		);
	}
);

if ( ! function_exists( 'ncgio_nebulox_process_webhook' ) ) {
	function ncgio_nebulox_process_webhook( $payload ) {
		$data = json_decode( $payload, true );
        $nebulox_settings = get_option( 'woocommerce_nebulox_gateway_settings', array() );

		if ( $data && isset( $data['status'] ) ) {
			$order_id = $data['orderId'];
			$order    = wc_get_order( $order_id );
			if ( $order ) {
				switch ( $data['status'] ) {
					case 'COMPLETED':
						$order->update_status(
                            $nebulox_settings['order_status'],
							__( 'Payment received via Nebulox Gateway.', 'nebuloxWoogate' )
						);
						wc_reduce_stock_levels( $order_id );
						update_post_meta( $order_id, '_transaction_id', sanitize_text_field( $data['txId'] ) );
						$url = ncgio_get_blockchain_url(
							$data['coinName'],
							$data['txId']
						);
						$order->set_transaction_id( $url );
						$order->get_checkout_order_received_url();
						$order->delete_meta_data( 'nebulox_invoice_url' );
						$order->add_meta_data(
							'transactionurl',
							ncgio_get_blockchain_url(
								$data['coinName'],
								$data['txId']
							)
						);
						$order->save();
						$order->get_checkout_order_received_url();
						break;
					case 'EXPIRED':
						$order->update_status( 'failed', __( 'Payment has been expired', 'nebuloxWoogate' ) );
						wc_increase_stock_levels( $order_id );
						break;
					case 'PARTIAL':
							$order->update_status( 'on-hold', __( 'A Part of Payment received', 'nebuloxWoogate' ) );
						$order->add_order_note( 'Paid amount : ' . $data['paidAmount'] );
						$order->set_transaction_id( $data['txId'] );
						break;
					default:
						$order->add_order_note( __( 'Unknown order status during payment', 'nebuloxWoogate' ) );
						break;
				}
			} else {
				wp_admin_notice(
					'Order not found: ' . $order_id,
					array(
						'type'        => 'error',
						'dismissible' => true,
					)
				);
			}
		} else {
			wp_admin_notice(
				'Invalid payload or status: ' . wp_json_encode( $data ),
				array(
					'type'        => 'error',
					'dismissible' => true,
				)
			);
		}
	}
}
if ( function_exists( 'ncgio_get_blockchain_url' ) ) {
	function ncgio_get_blockchain_url( $coin_name, $tx_id ) {
		return "https://blockchair.com/{$coin_name}/transaction/{$tx_id}";
	}
}
