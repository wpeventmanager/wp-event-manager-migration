<div class="wrap wp-event-manager-migration-wrap">
	<h2><?php _e('Event Migration Import', 'wp-event-manager-migration'); ?></h2>

    <table class="widefat">
        <tr>
            <th><?php _e('Field Name', 'wp-event-manager-migration' ); ?></th>
            <th><?php _e('Field Value', 'wp-event-manager-migration' ); ?></th>
        </tr>

        <?php if(!empty($sample_data)) :
            foreach ( $sample_data as $field_name => $field_value ) : ?>
                <tr>
                    <td><?php echo $field_name; ?></td>
                    <td><?php echo $field_value; ?></td>
                </tr>
            <?php endforeach; 
        endif; ?>
    </table>

	<form method="post" class="wp-event-manager-migration-import">
		<table class="widefat">
            <tr>
                <td>
                    <input type="hidden" name="page" value="event-migration" />
                    <input type="hidden" name="migration_post_type" value="<?php echo $migration_post_type; ?>" />
                    <input type="hidden" name="file_id" id="file_id" value="<?php echo $file_id; ?>" />
                    <input type="hidden" name="file_type" id="file_type" value="<?php echo $file_type; ?>" />
                    <input type="hidden" name="action" value="import" />
                    <input type="submit" class="button-primary" name="wp_event_manager_migration_import" value="<?php _e( 'Import', 'wp-event-manager-migration' ); ?>" />
                    <?php wp_nonce_field( 'event_manager_migration_import' ); ?>
                </td>
            </tr>
        </table>
	</form>
</div>