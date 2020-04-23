<div class="wrap wp_event_manager_migration_wrap">
	<h2><?php _e( 'Event Migration Mapping Form', 'wp-event-manager-migration' ); ?></h2>

	<form method="post" class="wp-event-manager-migration-mapping-form">
		<table class="widefat">
    		<thead>
                <tr>
                    <th width="25%"><?php _e('CSV Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="25%"><?php _e('Event Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="25%"><?php _e('Custom Field', 'wp-event-manager-migration' ); ?></th>
                    <th width="1%"><?php _e('&nbsp;', 'wp-event-manager-migration' ); ?></th>
                    <th width="24%"><?php _e('Default Value', 'wp-event-manager-migration' ); ?></th>
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
            					<select class="migration_field" name="migration_field[<?php echo $key; ?>]" id="migration_field_<?php echo $key; ?>" data-type="text">
									<option value=""><?php _e( 'Select Event Field', 'wp-event-manager-migration' ); ?>...</option>
									<?php foreach ( $migration_fields as $group_key => $group_fields ) : ?>
										<optgroup label="<?php echo $group_key; ?>">

                                            <?php if($group_key == 'event') : ?>
                                                <option class="text" value="_post_id"><?php _e( 'ID', 'wp-event-manager-migration' ); ?></option>
                                            <?php endif; ?>

											<?php foreach ( $group_fields as $name => $field ) : ?>

                                                <?php if(!in_array($field['type'], ['term-select'])) : ?>

                                                    <option class="text" value="_<?php echo esc_attr( $name ); ?>" <?php selected( $head_fields, '_'.$name ); ?> ><?php echo esc_html( $field['label'] ); ?></option>

                                                <?php endif; ?>

											<?php endforeach; ?>

										</optgroup>
									<?php endforeach; ?>

                                    <?php if(!empty($taxonomies)) : ?>
                                        <optgroup label="<?php _e('Taxonomy', 'wp-event-manager-migration') ?>">
    										<?php foreach ($taxonomies as $name => $taxonomy ) : ?>
                                                <option class="taxonomy" value="<?php echo esc_attr( $name ); ?>" ><?php echo esc_html( $taxonomy->label ); ?></option>
                                            <?php endforeach; ?>
    									</optgroup>
                                    <?php endif; ?>

                                    <optgroup label="<?php _e('Other', 'wp-event-manager-migration') ?>">
                                        <option class="custom_field" value="custom_field" ><?php _e('Custom Field', 'wp-event-manager-migration') ?></option>
                                    </optgroup>
								</select>
            				</td>
            				<td>
            					<input type="hidden" name="custom_field[<?php echo $key; ?>]" class="migration_field_<?php echo $key; ?>" value="" />
                                <input type="hidden" name="taxonomy_field[<?php echo $key; ?>]" class="taxonomy_field_<?php echo $key; ?>" value="" />
            				</td>
                            <td>
                                <input type="checkbox" class="add_default_value" id="default_value_<?php echo $key; ?>">
                            </td>
                            <td>
                                <input type="hidden" name="default_value[<?php echo $key; ?>]" class="default_value_<?php echo $key; ?>" value="" />
                                <select style="display: none;" class="default_value_<?php echo $key; ?>">
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
                        <input type="hidden" name="migration_post_type" value="<?php echo $migration_post_type; ?>" />
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