var AdminMigration = function () {

    return {

        init: function(){
            jQuery( 'body' ).on('click', '.wp-event-manager-migration-upload-file input[type="button"]', AdminMigration.actions.checkFile);
            jQuery( 'body' ).on('click', '.wp-event-manager-migration-upload-file .upload-file', AdminMigration.actions.uploadFile);

            jQuery( 'body' ).on('change', '.wp-event-manager-migration-mapping-form .migration-field', AdminMigration.actions.selectDataField);
            jQuery( 'body' ).on('click', '.wp-event-manager-migration-mapping-form .add-default-value', AdminMigration.actions.addDefaultValue);
            jQuery( 'body' ).on('change', '.wp-event-manager-migration-mapping-form select[class*="default_value_"]', AdminMigration.actions.selectDefaultValue);
        },
        actions:{
            /**
             * checkFile function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            checkFile: function (e){
                jQuery('span.response-message').addClass('error');
                jQuery('span.response-message').html(event_manager_migration_admin.file_type_error);
            },

            /**
             * uploadFile function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            uploadFile: function (e){
                var upload = wp.media({
                    title: event_manager_migration_admin.media_box_title, /*Title for Media Box*/
                    multiple: false /*For limiting multiple image*/
                })
                .on('select', function (){
                    var select = upload.state().get('selection');
                    var attach = select.first().toJSON();

                    var file = attach.filename;
                    var extension = file.substr( (file.lastIndexOf('.') +1) );

                    if(jQuery.inArray(extension, ['csv', 'xlsx', 'xls', 'xml'])!='-1'){
                        jQuery('span.response-message').removeClass('error');
                        jQuery('span.response-message').html(attach.filename);
                        jQuery('input#file_id').attr('value', attach.id);
                        jQuery('input#file_type').attr('value', extension);
                        jQuery('input[name="wp_event_manager_migration_upload"]').attr('type', 'submit');
                    } else{
                        jQuery('span.response-message').addClass('error');
                        jQuery('input#file_id').attr('value', '');
                        jQuery('input#file_type').attr('value', '');
                        jQuery('span.response-message').html(event_manager_migration_admin.file_type_error);
                        jQuery('input[name="wp_event_manager_migration_upload"]').attr('type', 'button');
                    }
                })
                .open();
            },

            /**
             * selectDataField function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            selectDataField: function (e){   
                var field_val = jQuery(e.target).val();            
                var field_id = jQuery(e.target).attr('id');
                var field_type = jQuery(e.target).find("option:selected").attr('class');

                jQuery(e.target).attr( "data-type", field_type );
                jQuery(e.target).attr( "data-val", field_val );

                if(jQuery(e.target).closest('tr').find('.add-default-value').prop("checked") == true){
                    jQuery(e.target).closest('tr').find('.add-default-value').prop('checked', false);

                    jQuery(e.target).closest('tr').find('select[class*="default_value_"]').html('');
                    jQuery(e.target).closest('tr').find('select[class*="default_value_"]').hide();
                    
                    jQuery(e.target).closest('tr').find('input[class*="default_value_"]').val('');
                    jQuery(e.target).closest('tr').find('input[class*="default_value_"]').attr('type', 'hidden');
                }


                jQuery('body input.'+ field_id).val('');
                jQuery('body input.'+ field_id).attr('type', 'hidden');
                jQuery(e.target).closest('tr').find('input[class*="taxonomy_field_"]').val('');

                if(field_type == 'custom-field'){
                    jQuery('body input.'+ field_id).val('');
                    jQuery('body input.'+ field_id).attr('type', 'text');
                }else if(field_type == 'taxonomy'){
                    jQuery(e.target).closest('tr').find('input[class*="taxonomy_field_"]').val(field_val);
                }else{
                    jQuery('body input.'+ field_id).val(field_val);
                    jQuery('body input.'+ field_id).attr('type', 'hidden');
                }

                if(jQuery.inArray(field_val, ['_post_id', '_event_organizer_ids', '_event_venue_ids'])!='-1'){
                    jQuery(e.target).closest('tr').find('span.wp-event-manager-migration-help-tip').addClass('show-help-tip');

                    if(field_val == '_post_id'){
                        jQuery(e.target).closest('tr').find('span.wp-event-manager-migration-help-tip').attr('title', event_manager_migration_admin._post_id);
                    }else if(field_val == '_event_organizer_ids'){
                        jQuery(e.target).closest('tr').find('span.wp-event-manager-migration-help-tip').attr('title', event_manager_migration_admin._event_organizer_ids);
                    }else if(field_val == '_event_venue_ids'){
                        jQuery(e.target).closest('tr').find('span.wp-event-manager-migration-help-tip').attr('title', event_manager_migration_admin._event_venue_ids);
                    }
                }
            },

            /**
             * addDefaultValue function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            addDefaultValue: function (e){
                var migration_field_type = jQuery(e.target).closest('tr').find('.migration-field').attr('data-type');
                var migration_field_val = jQuery(e.target).closest('tr').find('.migration-field').attr('data-val');

                var field_id = jQuery(e.target).attr('id');

                if(jQuery(e.target).prop("checked") == true){
                    jQuery('body input.'+ field_id).val('');

                    if(migration_field_type == 'taxonomy'){
                        jQuery('body select.'+ field_id).show();

                        var data = {
                            action: 'get_migration_terms',
                            taxonomy: migration_field_val,
                        };
                        jQuery.post( event_manager_migration_admin.ajax_url, data, function( response ) {
                            jQuery('body select.'+ field_id).html(response);
                        },'html');
                    }else{
                        jQuery('body input.'+ field_id).attr('type', 'text');
                    }                
                }else{
                    jQuery('body input.'+ field_id).val('');
                    jQuery('body input.'+ field_id).attr('type', 'hidden');

                    jQuery('body select.'+ field_id).html('');
                    jQuery('body select.'+ field_id).hide();
                }
            },

            /**
             * selectDefaultValue function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            selectDefaultValue: function (e){
                var field_class = jQuery(e.target).attr('class');
                var field_val = jQuery(e.target).val();

                jQuery('body input.'+ field_class).val(field_val);
            },
        } /* end of action */
    }; /* enf of return */
}; /* end of class */

AdminMigration = AdminMigration();

jQuery(document).ready(function($) {
   AdminMigration.init();
});
