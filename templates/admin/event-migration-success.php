<div class="wrap wp-event-manager-migration-wrap">
	<h2><?php _e('Event Migration Successfully', 'wp-event-manager-migration'); ?></h2>
    <table class="widefat">
        <tr>
            <th>
                <?php echo sprintf( __( 'Total: <b>%s</b> %s Successfully Import', 'wp-event-manager-migration' ), $total_records, $import_type_label ); ?>
            </th>
        </tr>
        <tr>
            <th>
                <a href="<?php echo get_site_url(); ?>/wp-admin/admin.php?page=event-migration" class="button">
                    <?php _e('Import new .csv or .xml', 'wp-event-manager-migration'); ?>
                </a>
            </th>
        </tr>
    </table>
</div>