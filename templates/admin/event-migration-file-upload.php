<div class="wrap wp_event_manager_migration_wrap">
	<h2><?php _e( 'Event Migration', 'wp-event-manager-migration' ); ?></h2>

	<form method="post" class="wp-event-mailchimp-migration-upload-file">
		<table class="widefat">
			<tr>
		        <th><?php _e('Choose File', 'wp-event-manager-migration' ); ?></th>
		        <td>
					<a href="javascript:void(0)" class="upload-file">Upload CSV File</a>
					<span class="response_message"></span>
					<input type="hidden" name="file_id" class="file_id" value="" />
					<input type="hidden" name="file_type" class="file_type" value="" />
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