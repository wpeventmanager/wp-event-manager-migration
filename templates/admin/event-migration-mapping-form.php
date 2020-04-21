<div class="wrap wp_event_manager_migration_wrap">
	<h2><?php _e( 'Event Migration Mapping Form', 'wp-event-manager-migration' ); ?></h2>

	<form method="post" class="wp-event-manager-migration-mapping-form">
		<table class="widefat">
    		<thead>
                <tr>
                    <th width="25%"><?php _e('CSV Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="25%"><?php _e('Event Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="25%"><?php _e('Custom Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="25%"><?php _e('Is Texonimy', 'wp-event-manager-migration' ); ?></th>
                </tr>
            </thead>

            <tbody>
            	<?php if(!empty($csv_head_fields)) : ?>
            		<?php foreach ( $csv_head_fields as $key => $head_fields ) : ?>

            			<tr>
            				<td>
            					<input readonly type="text" name="csv_field[<?php echo $key; ?>]" value="<?php echo $head_fields; ?>" />
            				</td>
            				<td>
            					<select class="event_field" id="event_field_<?php echo $key; ?>">
									<option value=""><?php _e( 'Select Event Field', 'wp-event-manager-migration' ); ?>...</option>
									<?php foreach ( $event_fields as $group_key => $group_fields ) : ?>
										<optgroup label="<?php echo $group_key; ?>">

											<?php foreach ( $group_fields as $name => $field ) : ?>
												<option value="_<?php echo esc_attr( $name ); ?>" ><?php echo esc_html( $field['label'] ); ?></option>
											<?php endforeach; ?>

										</optgroup>
									<?php endforeach; ?>
									<optgroup label="<?php _e('Other', 'wp-event-manager-migration') ?>">
										<option value="custom_field" ><?php _e('Custom Field', 'wp-event-manager-migration') ?></option>
									</optgroup>
								</select>
            				</td>
            				<td>
            					<input type="hidden" name="event_field[<?php echo $key; ?>]" class="event_field_<?php echo $key; ?>" value="" />
            				</td>
                            <td>
                                <select name="event_taxonomy[<?php echo $key; ?>]" class="event_taxonomy">
                                    <option value=""><?php _e( 'Select Event Field', 'wp-event-manager-migration' ); ?>...</option>
                                    <?php foreach ( get_object_taxonomies( 'event_listing', 'objects' ) as $name => $taxonomies ) : ?>
                                        <option value="<?php echo esc_attr( $name ); ?>" ><?php echo esc_html( $taxonomies->label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
            			</tr>

            		<?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="4">
                        <input type="hidden" name="page" value="event-migration" />
                    	<input type="hidden" name="file_id" class="file_id" value="<?php echo $file_id; ?>" />
		        		<input type="hidden" name="action" value="mapping" />

                    	<input type="submit" class="button-primary" name="wp_event_manager_migration_mapping" value="<?php esc_attr_e( 'Step 2', 'wp-event-manager-migration' ); ?>" />

                    	<?php wp_nonce_field( 'event_manager_migration_mapping' ); ?>
                    </td>
                </tr>
            </tfoot>

        </table>
	</form>

</div>