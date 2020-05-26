<?php
/**
Plugin Name: WP Event Manager - Migration

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
 * WPEM_Migration class.
 */

class WPEM_Migration {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.0
	 */
	private static $_instance = null;

	/**
	 * Main WP Event Manager Migration Instance.
	 *
	 * Ensures only one instance of WP Event Manager Migration is loaded or can be loaded.
	 *
	 * @since  1.0
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
		define( 'WPEM_MIGRATION_VERSION', '1.0' );
		define( 'WPEM_MIGRATION_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_MIGRATION_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		
		include( 'includes/wpem-migration-install.php' );

		if ( is_admin() ) {
			include( 'admin/wpem-migration-admin.php' );
		}

		// Activation - works with symlinks
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'activate' ) );
		
		// Actions
		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_init', array( $this, 'updater' ) );
	}

	/**
     * activate function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.0
     */
	public function activate() {

		WPEM_Migration_Install::install();
	}

	/**
     * updater function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.0
     */
	public function updater() {
		if ( version_compare( WPEM_MIGRATION_VERSION, get_option( 'wpem_migration_version' ), '>' ) ) {

			WPEM_Migration_Install::install();
			flush_rewrite_rules();
		}
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