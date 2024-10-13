<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! function_exists( 'nebulox_uninstall_plugin' ) ) {
	function nebulox_uninstall_plugin() {
		if ( current_user_can( 'manage_options' ) ) {
			delete_option( 'woocommerce_nebulox_gateway_settings' );
		}
	}
}

if ( ! function_exists( 'nebulox_activate_plugin' ) ) {
	function nebulox_activate_plugin() {
		add_action( 'admin_notices', 'nebulox_activation_message' );
	}

}

if ( ! function_exists( 'nebulox_deactive_plugin' ) ) {
	function nebulox_deactive_plugin() {
		if ( current_user_can( 'manage_options' ) ) {
			$setting = get_option( 'woocommerce_nebulox_gateway_settings', array() );
			error_log( isset( $setting['api_key'] ) ? $setting['api_key'] : 'No API key set' );
			if ( ! isset( $setting['api_key'] ) ) {
				delete_option( 'woocommerce_nebulox_gateway_settings' );
			}
		}
	}
}

if ( ! function_exists( 'nebulox_activation_message' ) ) {

	function nebulox_activation_message() {

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				esc_attr_e(
					' Thank you for installing Nebulox Plugin
            .Please Read the Instruction to  enable this plugin',
					'nebuloxWoogate'
				);
				?>
				<a href="https://docs.nebulox.io/plugins/woocommerce">
					<?php
					esc_attr_e( 'nebulox', 'nebuloxWoogate' );
					?>
				</a>
				<?php esc_attr_e( 'to use this plugin.', 'nebuloxWoogate' ); ?></p>
		</div>
		<?php
	}


}

