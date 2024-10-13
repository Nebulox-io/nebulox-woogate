<?php


use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class NebuloxGatewayBlock extends AbstractPaymentMethodType {

	protected $name = 'nebulox_gateway_payments';

	public function __construct() {
		$this->initialize();
	}

	public function initialize() {
		$this->settings = get_option( 'woocommerce_nebulox_gateway_settings', array() );
	}

	public function is_active() {
		return $this->get_setting( 'enabled', false );
	}

	// Register the payment method's JavaScript files
	public function get_payment_method_script_handles() {
		wp_register_script(
			'nebulox_gateway_block_js',
			plugin_dir_url( __FILE__ ) . 'block/checkout.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			NCGIO_NEBULOXCG_VERSION,
			true
		);

		return array( 'nebulox_gateway_block_js' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'enabled'     => $this->get_setting( 'enabled' ),
			'icon'        => plugin_dir_url( __FILE__ ) . 'assets/logo/favicon.png',
		);
	}
}

if ( ! function_exists( 'register_nebulox_gateway_block' ) ) {
	function register_nebulox_gateway_block( $payment_method_registry ) {
		$payment_method_registry->register( new NebuloxGatewayBlock() );
	}
	add_action( 'woocommerce_blocks_payment_method_type_registration', 'register_nebulox_gateway_block' );

}
