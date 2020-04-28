jQuery(document).ready(function($) {

	$('.wp-event-mailchimp-migration-upload-file')
		.on( 'click', '.upload-file', function() {
			var upload = wp.media({
	            title: event_manager_migration_admin.media_box_title, /*Title for Media Box*/
	            multiple: false /*For limiting multiple image*/
	        })
            .on('select', function ()
            {
                var select = upload.state().get('selection');
                var attach = select.first().toJSON();

                var file = attach.filename;

                var extension = file.substr( (file.lastIndexOf('.') +1) );

                //console.log(extension);

                if(jQuery.inArray(extension, ['csv', 'xlsx'])!='-1')
                {
                    $('span.response_message').removeClass('error');

                    $('span.response_message').html(attach.filename);
                    $('input.file_id').attr('value', attach.id);
                    $('input.file_type').attr('value', extension);
                    $('input[name="wp_event_manager_migration_upload"]').attr('type', 'submit');
                } else
                {
                    $('span.response_message').addClass('error');
                    $('input.file_id').attr('value', '');
                    $('input.file_type').attr('value', '');
                    $('span.response_message').html(event_manager_migration_admin.file_type_error);
                    $('input[name="wp_event_manager_migration_upload"]').attr('type', 'button');
                }
            })
            .open();
		})
        .on( 'click', 'input[type="button"]', function() {
            $('span.response_message').addClass('error');
            $('span.response_message').html(event_manager_migration_admin.file_type_error);
        });


    $('.wp-event-manager-migration-mapping-form')
        .on( 'change', '.migration_field', function() 
        {  
            var field_val = $(this).val();            
            var field_id = $(this).attr('id');
            var field_type = $(this).find("option:selected").attr('class');

            $(this).attr( "data-type", field_type );
            $(this).attr( "data-val", field_val );

            if($(this).closest('tr').find('.add_default_value').prop("checked") == true)
            {
                $(this).closest('tr').find('.add_default_value').prop('checked', false);

                $(this).closest('tr').find('select[class*="default_value_"]').html('');
                $(this).closest('tr').find('select[class*="default_value_"]').hide();
                
                $(this).closest('tr').find('input[class*="default_value_"]').val('');
                $(this).closest('tr').find('input[class*="default_value_"]').attr('type', 'hidden');
            }


            $('body input.'+ field_id).val('');
            $('body input.'+ field_id).attr('type', 'hidden');
            $(this).closest('tr').find('input[class*="taxonomy_field_"]').val('');

            if(field_type == 'custom_field')
            {
                $('body input.'+ field_id).val('');
                $('body input.'+ field_id).attr('type', 'text');
            }
            else if(field_type == 'taxonomy')
            {
                $(this).closest('tr').find('input[class*="taxonomy_field_"]').val(field_val);
            }
            else
            {
                $('body input.'+ field_id).val(field_val);
                $('body input.'+ field_id).attr('type', 'hidden');
            }

            
        })
        .on( 'change', '.add_default_value', function() 
        {
            var migration_field_type = $(this).closest('tr').find('.migration_field').attr('data-type');
            var migration_field_val = $(this).closest('tr').find('.migration_field').attr('data-val');

            var field_id = $(this).attr('id');

            if($(this).prop("checked") == true)
            {
                $('body input.'+ field_id).val('');

                if(migration_field_type == 'taxonomy')
                {
                    $('body select.'+ field_id).show();

                    var data = {
                        action: 'get_migration_terms',
                        taxonomy: migration_field_val,
                    };
                    $.post( event_manager_migration_admin.ajax_url, data, function( response ) {
                        $('body select.'+ field_id).html(response);
                    },'html');
                }
                else
                {
                    $('body input.'+ field_id).attr('type', 'text');
                }                
            }
            else
            {
                $('body input.'+ field_id).val('');
                $('body input.'+ field_id).attr('type', 'hidden');
            }
        })
        .on( 'change', 'select[class*="default_value_"]', function() 
        {
            var field_class = $(this).attr('class');
            var field_val = $(this).val();

            $('body input.'+ field_class).val(field_val);
        });

});
