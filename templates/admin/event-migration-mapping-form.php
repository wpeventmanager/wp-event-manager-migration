<div class="wrap wp-event-manager-migration-wrap">
    <h2><?php _e('Event Migration Mapping Form', 'wp-event-manager-migration'); ?></h2>

    <?php if ($migration_post_type == 'event_listing') : ?>
        <div class="notice notice-warning">
            <p><?php _e('While import event must be select Organizer ID and Venues ID.', 'wp-event-manager-migration'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="wp-event-manager-migration-mapping-form">
        <table class="widefat">
            <thead>
                <tr>
                    <th width="25%"><?php _e('File Field', 'wp-event-manager-migration'); ?></th>
                    <th width="25%"><?php echo sprintf(__('%s Field', 'wp-event-manager-migration'), $import_type_label); ?></th>
                    <th width="25%"><?php _e('Custom Field', 'wp-event-manager-migration'); ?></th>
                    <th width="1%"><?php _e('&nbsp;', 'wp-event-manager-migration'); ?></th>
                    <th width="24%"><?php _e('Default Value', 'wp-event-manager-migration'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php if ($migration_post_type == 'event_registration') :
                    if (!empty($file_head_fields)) : 
                        $file_fields = array();
                        foreach( $file_head_fields as $element ) {
                            $file_fields[str_replace(" ", "_", strtolower($element))] = array("label" => ucwords($element), "type" => "text");
                        }
                        $migration_fields = array_merge ($file_fields, $migration_fields);
                        foreach ($file_head_fields as $key => $head_fields) : ?>
                            <tr>
                                <td>
                                    <input readonly type="text" name="file_field[<?php echo $key; ?>]" value="<?php echo str_replace(" ", "_", strtolower("_".ltrim($head_fields, " "))); ?>" />
                                </td>
                                <td>
                                    <select class="migration-field" name="migration_field[<?php echo $key; ?>]" id="migration_field_<?php echo $key; ?>" data-type="text">
                                        <option value=""><?php echo sprintf(__('Select %s Field', 'wp-event-manager-migration'), $import_type_label); ?></option>

                                        <?php $i = 1; ?>

                                        <optgroup label="Registrations">
                                            <?php
                                            foreach ($migration_fields as $name => $field) :                                    
                                                if ($i == 1) : ?>
                                                    <option class="text" value="_post_id"><?php _e('ID', 'wp-event-manager-migration'); ?></option>
                                                <?php endif; 
                                                if (!in_array($field['type'], ['term-select'])) : ?>
                                                    <option class="text" value="_<?php echo esc_attr(ltrim($name, "_")); ?>" <?php selected(str_replace(" ", "_", strtolower("_".ltrim($head_fields, " "))), '_' . ltrim($name, "_")); ?> ><?php _e(esc_attr($field['label']), 'wp-event-manager-registrations');?></option>
                                                <?php endif;
                                                $i++; 
                                            endforeach; ?>
                                        </optgroup>
                                        <optgroup label="<?php _e('Other', 'wp-event-manager-migration') ?>">
                                            <option class="custom-field" value="custom_field" ><?php _e('Custom Field', 'wp-event-manager-migration') ?></option>
                                        </optgroup>
                                    </select>
                                    <span class="wp-event-manager-migration-help-tip"></span>
                                </td>
                                <td>
                                    <input type="hidden" name="custom_field[<?php echo $key; ?>]" class="migration_field_<?php echo $key; ?>" value="" />
                                    <input type="hidden" name="taxonomy_field[<?php echo $key; ?>]" class="taxonomy_field_<?php echo $key; ?>" value="" />
                                </td>
                                <td>
                                    <input type="checkbox" class="add-default-value" id="default_value_<?php echo $key; ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="default_value[<?php echo $key; ?>]" class="default_value_<?php echo $key; ?>" value="" />
                                    <select style="display: none;" class="default_value_<?php echo $key; ?>">
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                <?php else : 
                    if (!empty($file_head_fields)) : 
                        foreach ($file_head_fields as $key => $head_fields) : ?>
                            <tr>
                                <td>
                                    <input readonly type="text" name="file_field[<?php echo $key; ?>]" value="<?php echo $head_fields; ?>" />
                                </td>
                                <td>
                                    <select class="migration-field" name="migration_field[<?php echo $key; ?>]" id="migration_field_<?php echo $key; ?>" data-type="text">
                                        <option value=""><?php echo sprintf(__('Select %s Field', 'wp-event-manager-migration'), $import_type_label); ?></option>

                                        <?php $i = 1; 
                                        foreach ($migration_fields as $group_key => $group_fields) :   ?>
                                            <optgroup label="<?php echo $group_key; ?>">
                                                <?php if ($i == 1) : ?>
                                                    <option class="text" value="_post_id" <?php selected($head_fields, '_post_id'); ?>><?php _e('ID', 'wp-event-manager-migration'); ?></option>
                                                <?php endif; 
   
                                                if ($group_key == 'tickets'){ 
                                                    if ($i == 1) : ?>
                                                        <option class="text" value="_event_id"><?php _e('Event ID', 'wp-event-manager-migration'); ?></option>
                                                    <?php endif; 

                                                    foreach ($group_fields as $name => $field) : 
                                                        if (!in_array($field['type'], ['term-select'])) : ?>
                                                            <option class="text" value="<?php echo esc_attr($name); ?>" <?php selected($head_fields, $name); ?> ><?php _e(esc_attr($field['label']), 'wp-event-manager'); ?></option>
                                                        <?php endif;
                                                    endforeach;
                                                }else {
                                                    foreach ($group_fields as $name => $field) : 
                                                        if (!in_array($field['type'], ['term-select'])) : 
                                                            if($head_fields == '_thumbnail_id' && $group_key=='event' && $name=='event_banner'){ ?>
                                                                <option class="text" value="_<?php echo esc_attr($name); ?>" selected ><?php _e(esc_attr($field['label']), 'wp-event-manager'); ?></option>
                                                            <?php }else{  ?>
                                                                <option class="text" value="_<?php echo esc_attr($name); ?>" <?php selected($head_fields, '_' . $name); ?> ><?php _e(esc_attr($field['label']), 'wp-event-manager'); ?></option>
                                                            <?php }
                                                        endif;
                                                    endforeach;
                                                } ?>
                                            </optgroup>

                                            <?php $i++; 
                                        endforeach;

                                        if (!empty($taxonomies)) : ?>
                                            <optgroup label="<?php _e('Taxonomy', 'wp-event-manager-migration') ?>">
                                                <?php foreach ($taxonomies as $name => $taxonomy) : ?>
                                                    <option class="taxonomy" value="<?php echo esc_attr($name); ?>" <?php selected($head_fields, $name); ?> ><?php echo esc_html($taxonomy->label); ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>

                                        <optgroup label="<?php _e('Other', 'wp-event-manager-migration') ?>">
                                            <option class="custom-field" value="custom_field" ><?php _e('Custom Field', 'wp-event-manager-migration') ?></option>
                                        </optgroup>
                                    </select>
                                    <span class="wp-event-manager-migration-help-tip"></span>
                                </td>
                                <td>
                                    <input type="hidden" name="custom_field[<?php echo $key; ?>]" class="migration_field_<?php echo $key; ?>" value="" />
                                    <input type="hidden" name="taxonomy_field[<?php echo $key; ?>]" class="taxonomy_field_<?php echo $key; ?>" value="" />
                                </td>
                                <td>
                                    <input type="checkbox" class="add-default-value" id="default_value_<?php echo $key; ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="default_value[<?php echo $key; ?>]" class="default_value_<?php echo $key; ?>" value="" />
                                    <select style="display: none;" class="default_value_<?php echo $key; ?>"></select>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif;
                 endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="5">
                        <input type="hidden" name="page" value="event-migration" />
                        <input type="hidden" name="migration_post_type" value="<?php echo $migration_post_type; ?>" />
                        <input type="hidden" name="file_id" id="file_id" value="<?php echo $file_id; ?>" />
                        <input type="hidden" name="file_type" id="file_type" value="<?php echo $file_type; ?>" />
                        <input type="hidden" name="action" value="mapping" />
                        <input type="submit" class="button-primary" name="wp_event_manager_migration_mapping" value="<?php esc_attr_e('Step 2', 'wp-event-manager-migration'); ?>" />
                        <?php wp_nonce_field('event_manager_migration_mapping'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
</div>