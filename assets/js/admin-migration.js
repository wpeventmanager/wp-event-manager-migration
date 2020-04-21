jQuery(document).ready(function($) {

	$('.wp-event-mailchimp-migration-upload-file')
		.on( 'click', '.upload-file', function() {
			var upload = wp.media({
	            title: 'Choose CSV File', /*Title for Media Box*/
	            multiple: false /*For limiting multiple image*/
	        })
            .on('select', function ()
            {
                var select = upload.state().get('selection');
                var attach = select.first().toJSON();

                //console.log(attach);

                if (attach.subtype == 'csv')
                {
                    $('span.file_name').removeClass('error');

                    $('span.file_name').html(attach.filename);
                    $('input.file_id').attr('value', attach.id);
                    $('input.file_type').attr('value', attach.subtype);
                } else
                {
                    $('span.file_name').addClass('error');
                    $('input.file_id').attr('value', '');
                    $('input.file_type').attr('value', '');
                    $('span.file_name').html(event_manager_migration_admin.file_type_error);
                }
            })
            .open();
		});


    $('.wp-event-manager-migration-mapping-form')
        .on( 'change', '.event_field', function() {
            var field_val = $(this).val();
            
            var field_id = $(this).attr('id');

            if(field_val == 'custom_field')
            {
                $('body input.'+ field_id).attr('type', 'text');
            }
            else
            {
                $('body input.'+ field_id).val(field_val);
            }
        });

});
