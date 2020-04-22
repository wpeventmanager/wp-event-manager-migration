<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_Migration_Import class.
 */
class WP_Event_Manager_Migration_Import {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
	}

	/**
	 * get_event_form_field_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_event_form_field_lists() {

		if(!class_exists('WP_Event_Manager_Form_Submit_Event') ) {
			include_once( EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-abstract.php' );
			include_once( EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-submit-event.php' );	
		}
		$form_submit_event_instance = call_user_func( array( 'WP_Event_Manager_Form_Submit_Event', 'instance' ) );
		$fields = $form_submit_event_instance->merge_with_custom_fields('backend');

		return $fields;		
	}

	/**
	 * get_csv_data function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_csv_data($file) {

		$csv_data = [];
		if (($handle = fopen($file, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle)) !== FALSE) {
		        $csv_data[] = $data;
		    }
		    fclose($handle);
		}

		return $csv_data;		
	}

	/**
	 * import_event function.
	 *
	 * @access public
	 * @return void
	 */
	public function import_event($params) {
		$user_id = get_current_user_id();

		if(isset($params['_event_id']) && $params['_event_id'] != '')
    	{
    		$event_id = $params['_event_id'];    		
    	}
    	else if(isset($params['_event_title']) && $params['_event_title'] != '')
    	{
    		$args = [
	    		'post_title'     => $params['_event_title'],
				'post_type'      => 'event_listing',
	            'post_author'    => $user_id,
				'comment_status' => 'closed',
				'post_status'    => 'publish',
	    	];

	    	$event_id = wp_insert_post($args);
    	}
    	else
    	{
			$event_id = '';
    	}

		if($event_id != '')
        {
        	$update_event = ['ID' => $event_id];

	    	if(isset($params['_event_title']) && $params['_event_title'] != '')
	    	{
	    		$update_event['post_title'] = $params['_event_title'];
	    	}
	    	if(isset($params['_event_description']) && $params['_event_description'] != '')
	    	{
	    		$update_event['post_content'] = $params['_event_description'];
	    	}

	    	wp_update_post( $update_event );

        	$migration_import_fields = get_option('migration_import_fields', true);

        	foreach ($params as $meta_key => $meta_value) 
        	{
        		$import_fields = $migration_import_fields[$meta_key];

        		if($meta_key == '_event_banner')
        		{
        			$is_json = is_string($meta_value) && is_array(json_decode($meta_value, true)) ? true : false;

        			if($is_json)
        			{
        				$arrImages = json_decode($meta_value, true);
        			}
        			else
        			{
        				if( strpos($meta_value, ',') !== false )
        				{
						    $arrImages = explode(',', $meta_value);
						}
						else if( strpos($meta_value, '|') !== false )
        				{
						    $arrImages = explode('|', $meta_value);
						}
						else
        				{
						    $arrImages = [$meta_value];
						}	        				
        			}

        			if(!empty($arrImages))
        			{
        				$imageData = [];
	    				foreach ($arrImages as $key => $url) 
	    				{
	    					$response = $this->image_exists($url);

				    		if($response)
				    		{
				    			$image = $this->upload_image($url);

		    					if(!empty($image))
		    					{
		    						$imageData[] = $image['image_url'];
		    					}
							}		    						    					
	    				}

	    				if(!empty($imageData))
	    				{
	    					update_post_meta($event_id, $meta_key, $imageData);
	    				}
        			}	        			
        		}
        		else if($meta_key == '_organizer_logo')
        		{
        			$response = $this->image_exists($meta_value);

		    		if($response)
		    		{
		    			$imageData = $this->upload_image($meta_value);

    					if(!empty($imageData))
    					{
    						$thumbnail_id = $imageData['image_url'];
    					}
					}
					else
					{
						$thumbnail_id = $meta_value;
					}

        			update_post_meta($event_id, '_thumbnail_id', $thumbnail_id);	
        		}
        		else
        		{
        			if($import_fields['taxonomy'] != '')
        			{
        				$term = term_exists($meta_value, $import_fields['taxonomy']);

						if (empty($term))
						{
							$term = wp_insert_term(
										$meta_value,
										$import_fields['taxonomy']
									);								
						}

						wp_set_post_terms($event_id, $term['term_id'], $import_fields['taxonomy'], true);
        			}
        			
        			update_post_meta($event_id, $meta_key, $meta_value);
        		}
        	}
        }				
	}

	/**
	 * upload_image function.
	 *
	 * @access public
	 * @return void
	 */
	public function upload_image($url) {
    	$arrData = [];

    	if($url != '')
    	{
    		$url = stripslashes($url);

    		require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
    		require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
    		require_once(ABSPATH . 'wp-admin' . '/includes/media.php');

    		$tmp = download_url( $url );
 
			$file_array = array(
			    'name' => basename( $url ),
			    'tmp_name' => $tmp
			);
			 
			/**
			 * Check for download errors
			 * if there are error unlink the temp file name
			 */
			if ( is_wp_error( $tmp ) ) {
			    @unlink( $file_array[ 'tmp_name' ] );
			    return $tmp;
			}
			 
			/**
			 * now we can actually use media_handle_sideload
			 * we pass it the file array of the file to handle
			 * and the post id of the post to attach it to
			 * $post_id can be set to '0' to not attach it to any particular post
			 */
			$post_id = '0';
			 
			$image_id = media_handle_sideload( $file_array, $post_id );
			 
			/**
			 * We don't want to pass something to $id
			 * if there were upload errors.
			 * So this checks for errors
			 */
			if ( is_wp_error( $image_id ) ) {
			    @unlink( $file_array['tmp_name'] );
			    return $image_id;
			}
			 
			/**
			 * No we can get the url of the sideloaded file
			 * $image_url now contains the file url in WordPress
			 * $id is the attachment id
			 */
			$image_url = wp_get_attachment_url( $image_id );

			$arrData['image_id'] = $image_id;
			$arrData['image_url'] = $image_url;
    	}

    	return $arrData;
    }

    /**
	 * image_exists function.
	 *
	 * @access public
	 * @return void
	 */
    public function image_exists($url) {
		$response = wp_remote_post($url);

		$response_code = wp_remote_retrieve_response_code( $response );

	    return $response_code == 200 ? true : false;
	}


}

new WP_Event_Manager_Migration_Import();