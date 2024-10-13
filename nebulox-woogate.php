<?php
/**
 * @package  nebulox
 * @document  crypto gateway
 * Plugin Name: Nebulox
 * Plugin URI: https://nebulox.io/solutions/wooCommerce-crypto-payment-gateway
 * Author: Nebulox
 * Author URI: https://nebulox.io/solutions/wooCommerce-crypto-payment-gateway
 * Description: Nebulox Crypto Gateway for accepting Crypto Currencies.
 * Version: 1.0.0
 * License: GPL v2 or later
 * Stable tag: 1.6.2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: woocommerce, crypto, nebulox gateway, crypto gateway, commerce, gateway
 */

/**
 * exist if WordPress not defined
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}



require_once plugin_dir_path( __FILE__ ) . 'includes/nebulox-woogate-functions.php';

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action( 'admin_notices', 'nebulox_woocommerce_not_installed_error' );
	return;
}

add_action('permalink_structure_changed', 'permalink_change_warning');
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'nebulox_setting_page' );
add_filter( 'plugin_row_meta', 'nebulox_add_documentation_link', 10, 2 );

const NCGIO_NEBULOXCG_VERSION          = '1.0.0';
const NCGIO_NEBULOX_SUPPORTED_CURRENCY = array(
	'USD' => 'USD',
	'EUR' => 'EUR',
);
define( 'NCGIO_NEBULOX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
const NCGIO_NEBULOX_CREATE_INVOICE_API = 'https://api.nebulox.io/api/invoice/create';
/**
 * Initialize Nebulox Gateway for WooCommerce.
 *
 * @document This function initializes the Nebulox Crypto Gateway for
 * WooCommerce,
 * including setting up
 *       payment options and handling the payment process.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/nebulox-woogate-actions.php';

/**
 * Load translations for the plugin.
 *
 * This function loads the necessary language translations for the plugin
 * based on the user's locale and sets up the translation files.
 *
 * @return void
 */
function init_nebulox_gateway() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/nebulox-woogate-class.php';
	include_once plugin_dir_path( __FILE__ ) . 'includes/nebulox-woogate-block-class.php';
}
require_once plugin_dir_path( __FILE__ ) . 'includes/nebulox-woogate-webhook.php';

register_deactivation_hook( __FILE__, 'nebulox_deactive_plugin' );
register_activation_hook( __FILE__, 'nebulox_activate_plugin' );
register_uninstall_hook( __FILE__, 'nebulox_uninstall_plugin' );
add_action( 'plugins_loaded', 'init_nebulox_gateway' );
add_action( 'init', 'nebulox_load_translations' );


/**
 * Load translations for the plugin.
 *
 * This function loads the necessary language translations for the plugin
 * based on the user's locale and sets up the translation files.
 *
 * @return void
 */
function nebulox_load_translations() {
	$locale = determine_locale();
	$locale = apply_filters( 'plugin_locale', $locale, 'nebuloxWoogate' );
	unload_textdomain( 'nebuloxWoogate' );
	load_textdomain( 'nebuloxWoogate', sprintf( '%s/languages/nebulox-woogate-%s.mo', __DIR__, $locale ) );
	load_plugin_textdomain(
		'nebuloxWoogate',
		false,
		plugin_basename(
			__DIR__
		) . '/languages'
	);
}
add_filter( 'pre_update_option_permalink_structure', 'nebulox_handle_permalink_change' );
