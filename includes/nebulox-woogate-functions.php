<?php


if ( ! defined( 'ABSPATH' ) ) {
	die;
}

function nebulox_woocommerce_not_installed_error() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p><?php esc_attr_e( ' Thank you for installing Nebulox Plugin. Please activate or install', 'ncgio' ); ?>
			<a href="https://wordpress.org/plugins/woocommerce/"><?php esc_attr_e( 'WooCommerce', 'ncgio' ); ?></a>
			<?php esc_attr_e( 'to use this plugin.', 'ncgio' ); ?></p>
	</div>
	<?php
}

/**
 * Add settings link on plugin page.
 *
 * @document This function adds a settings link on the plugin page in the
 * WordPress
 * admin.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function nebulox_setting_page( $links ) {
	$admin_url     = esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nebulox_gateway' ) );
	$settings_link = '<a href="' . $admin_url . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

function permalink_change_warning() {
	$message = __( 'Warning: The permalink structure has been changed. Please update your webhook URL in Nebulox website.', 'nebuloxWoogate' );
	add_settings_error(
		'permalink_update_warning',
		'permalink_structure_warning',
		$message,
		'warning'
	);
}


function nebulox_add_documentation_link( $links, $file ) {
	if ( str_contains( $file, 'nebulox' ) !== false ) {
		$new_links = array(
			'<a href="https://docs.nebulox.io/plugins/woocommerce" target="_blank">Docs</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}


function nebulox_save_white_list_ips( $value ) {
	$ips = array_map( 'trim', explode( ',', $value ) );
	$ips = array_filter(
		$ips,
		function ( $ip ) {
			return filter_var( $ip, FILTER_VALIDATE_IP );
		}
	);

	return implode( ',', $ips );
}
