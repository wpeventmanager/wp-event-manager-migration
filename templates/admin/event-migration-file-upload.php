<div class="wrap wp-event-manager-migration-wrap">
	<h2><?php _e('Event Migration', 'wp-event-manager-migration'); ?></h2>

	<div class="notice notice-warning">
	    <p><?php _e('If you have separate Organizer and Venues file then first import Organizer then Venues. After import Organizer and Venues then import Event.', 'wp-event-manager-migration'); ?></p>
	</div>

	<form method="post" class="wp-event-manager-migration-upload-file">
		<table class="widefat">
			<tr>
		        <th><?php _e('Choose File', 'wp-event-manager-migration' ); ?></th>
		        <td>
					<a href="javascript:void(0)" class="upload-file"><?php _e('Upload .csv or.xml file', 'wp-event-manager-migration' ); ?></a>
					<span class="response-message"></span>
					<input type="hidden" name="file_id" id="file_id" value="" />
					<input type="hidden" name="file_type" id="file_type" value="" />
		        </td>
		    </tr>
		    <tr>
		        <th><?php _e('Content Type', 'wp-event-manager-migration' ); ?></th>
		        <td>
					<select id="migration_post_type" name="migration_post_type">
						<?php foreach ( $migration_post_type as $name => $label ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>" ><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
		        </td>
		    </tr>
		    <tr>
		        <td colspan="2">
		        	<input type="hidden" name="page" value="event-migration" />
		        	<input type="hidden" name="action" value="upload" />
		            <input type="button" class="button-primary" name="wp_event_manager_migration_upload" value="<?php _e( 'Step 1', 'wp-event-manager-migration' ); ?>" />
		            <?php wp_nonce_field( 'event_manager_migration_upload' ); ?>
		        </td>
		    </tr>
		</table>
	</form>
</div>