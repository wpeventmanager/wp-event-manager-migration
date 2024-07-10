<?php
/**
* Plugin Name: WP Event Manager Migration
* Plugin URI: https://www.wp-eventmanager.com/
* Description: Migrate your events in few seconds from The Event Calendar, Modern Event Calendar, Event Manager, Meetups, Eventon, Event Expresso and others.
* Author: WP Event Manager
* Author URI: https://www.wp-eventmanager.com/the-team
* Text Domain: wp-event-manager-migration
* Domain Path: /languages
* Version: 1.0.2
* Since: 1.0.0
* Requires WordPress Version at least: 5.4.1
* Copyright: 2020 WP Event Manager
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*
**/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pre_check_before_installing_migration() {
	/*
	 * Check weather WP Event Manager is installed or not
 	 */
	if (! in_array( 'wp-event-manager/wp-event-manager.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		global $pagenow;
		if( $pagenow == 'plugins.php' ){
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'WP Event Manager is require to use WP Event Manager - Migration' , 'wp-event-manager-migration');
			echo '</p></div>';		
		}
	}
}
add_action( 'admin_notices', 'pre_check_before_installing_migration' );

/**
 * WPEM_Migration class.
 */

class WPEM_Migration {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * Main WP Event Manager Migration Instance.
	 *
	 * Ensures only one instance of WP Event Manager Migration is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see WPEM_Migration()
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor - get the plugin hooked in and ready
	 */
	public function __construct() {
		// Define constants
		define( 'WPEM_MIGRATION_VERSION', '1.0.2' );
		define( 'WPEM_MIGRATION_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_MIGRATION_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		if ( is_admin() ) {
			include( 'admin/wpem-migration-admin.php' );
		}
		
		// Actions
		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
	}
	
	/**
     * Localisation function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.0
     */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-migration';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-migration/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Main instance of WP Event Manager Migration.
 *
 * Returns the main instance of WP Event Manager Migration to prevent the need to use globals.
 *
 * @since  1.0
 * @return WPEM_Migration
 */
function WPEM_Migration() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
	return WPEM_Migration::instance();
}
$GLOBALS['event_manager_migration'] =  WPEM_Migration();