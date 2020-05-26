<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPEM_Migration_Install class.
 */
class WPEM_Migration_Install {

	/**
     * install function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.0
     */
	public static function install() 
	{
		update_option( 'wpem_migration_version', WPEM_MIGRATION_VERSION );
	}
}