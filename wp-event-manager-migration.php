<?php
/**
Plugin Name: WP Event Manager Migration

Plugin URI: https://www.wp-eventmanager.com/

Description: Event Migration

Author: WP Event Manager

Author URI: https://www.wp-eventmanager.com

Text Domain: wp-event-manager-migration

Domain Path: /languages

Version: 1.0

Since: 1.0

Requires WordPress Version at least: 4.1

Copyright: 2020 WP Event Manager

License: GNU General Public License v3.0

License URI: http://www.gnu.org/licenses/gpl-3.0.html

**/

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	
	exit;
}

/**
 * WP_Event_Manager class.
 */

class WP_Event_Manager_Migration {

	/**
	 * Constructor - get the plugin hooked in and ready
	 */
	public function __construct() {
		// Define constants
		define( 'EVENT_MANAGER_MIGRATION_VERSION', '1.0' );
		define( 'EVENT_MANAGER_MIGRATION_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EVENT_MANAGER_MIGRATION_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
				
		
		if ( is_admin() ) {
			include( 'admin/wp-event-manager-migration-admin.php' );
		}
		
		// Actions
		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );	
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {

		$domain = 'wp-event-manager-migration';       

        $locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-migration/".$domain."-" .$locale. ".mo" );

		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

$GLOBALS['event_manager_migration'] = new WP_Event_Manager_Migration();