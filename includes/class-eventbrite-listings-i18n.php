<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/dangrussell/
 * @since      1.0.0
 *
 * @package    Eventbrite_Listings
 * @subpackage Eventbrite_Listings/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Eventbrite_Listings
 * @subpackage Eventbrite_Listings/includes
 * @author     Dan Russell <dan.g.russell@gmail.com>
 */
class Eventbrite_Listings_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'eventbrite-listings',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
