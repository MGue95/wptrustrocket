<?php
/**
 * Plugin Name: WPTrustRocket
 * Plugin URI:  https://github.com/MGue95/wptrustrocket
 * Description: Bewertungen von Trusted Shops (und weiteren Quellen) abrufen, kuratieren und anzeigen — mit Oxygen Builder Integration.
 * Version:     2.1.0
 * Author:      WPTrustRocket
 * Author URI:  https://github.com/MGue95/wptrustrocket
 * License:     GPL-2.0-or-later
 * Text Domain: wptrustrocket
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPTR_VERSION', '2.1.0' );
define( 'WPTR_PLUGIN_FILE', __FILE__ );
define( 'WPTR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPTR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPTR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load all includes explicitly (no autoloader — avoids class name conflicts).
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-db.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-activator.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-api.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-cron.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-renderer.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-rest.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-admin.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-shortcode.php';
require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-schema.php';

// Activation.
register_activation_hook( __FILE__, [ 'WPTR_Activator', 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, function () {
	WPTR_Cron::unschedule();
} );

// Auto-create tables if missing (handles failed activation or manual upload).
add_action( 'admin_init', function () {
	if ( get_option( 'wptr_db_version' ) !== WPTR_VERSION ) {
		WPTR_Activator::activate();
	}
} );

// Initialize plugin.
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wptrustrocket', false, dirname( WPTR_PLUGIN_BASENAME ) . '/languages' );

	new WPTR_Admin();
	new WPTR_Rest();
	new WPTR_Shortcode();
	new WPTR_Cron();
	new WPTR_Schema();
} );

// Oxygen Builder integration — must run on 'init' AFTER Oxygen has loaded.
add_action( 'init', function () {
	if ( class_exists( 'OxyEl' ) ) {
		require_once WPTR_PLUGIN_DIR . 'includes/class-wptr-oxygen.php';
		wptr_register_oxygen_elements();
	}
} );
