<?php

/*
 * Plugin Name: Blueodin
 * Plugin URI: https://www.blueodin.io/plugin
 * Description: Plugin to add extra functionality to Blue Odin.
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author: Blue Odin
 * Author URI: http://www.blueodin.io
 * License: GPL2
 * WC requires at least: 3.0.0
 * WC tested up to: 6.5.1
*/

require_once dirname(__FILE__)."/vendor/autoload.php";

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
use BlueOdin\WordPress\BlueOdin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
const BLUE_ODIN_VERSION = '1.0.0';

register_activation_hook( __FILE__, '\BlueOdin\WordPress\BlueOdinActivator::activate' );
add_action( 'wp_initialize_site', '\BlueOdin\WordPress\BlueOdinActivator::action_wp_initialize_site', 10, 2);
register_deactivation_hook( __FILE__, '\BlueOdin\WordPress\BlueOdinDeactivator::deactivate' );
register_uninstall_hook( __FILE__, '\BlueOdin\WordPress\BlueOdinUninstaller::uninstall' );

require plugin_dir_path( __FILE__ ) . 'includes/functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_blue_odin() {
	$plugin = new BlueOdin();
	$plugin->run();
}
run_blue_odin();
