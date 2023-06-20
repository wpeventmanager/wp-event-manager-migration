<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = array(
	'wpem_migration_import_fields'
);

foreach ( $options as $option ) {
	delete_option( $option );
}