<div class="wrap wp_event_manager_migration_wrap">
	<h2><?php _e( 'Event Migration Successfully', 'wp-event-manager-migration' ); ?></h2>

    <table class="widefat">
        <tr>
            <th><?php echo sprintf( __( 'Total: <b>%s</b> Events Successfully Import', 'wp-event-manager-migration' ), $total_records ); ?></th>
        </tr>
        <tr>
            <th><a href="<?php echo get_site_url(); ?>/wp-admin/admin.php?page=event-migration" class="button"><?php _e('Import new csv', 'wp-event-manager-migration'); ?></a></th>
        </tr>
    </table>

</div>