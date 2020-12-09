<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/dangrussell/
 * @since             1.0.0
 * @package           Eventbrite_Listings
 *
 * @wordpress-plugin
 * Plugin Name:       Eventbrite Listings
 * Plugin URI:        https://github.com/dangrussell/eventbrite-listings
 * Description:       Display events from the Eventbrite API
 * Version:           1.0.0
 * Author:            Dan Russell
 * Author URI:        https://github.com/dangrussell/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eventbrite-listings
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EVENTBRITE_LISTINGS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-eventbrite-listings-activator.php
 */
function activate_eventbrite_listings() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-eventbrite-listings-activator.php';
	Eventbrite_Listings_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-eventbrite-listings-deactivator.php
 */
function deactivate_eventbrite_listings() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-eventbrite-listings-deactivator.php';
	Eventbrite_Listings_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_eventbrite_listings' );
register_deactivation_hook( __FILE__, 'deactivate_eventbrite_listings' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-eventbrite-listings.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_eventbrite_listings() {

	$plugin = new Eventbrite_Listings();
	$plugin->run();

}
run_eventbrite_listings();
