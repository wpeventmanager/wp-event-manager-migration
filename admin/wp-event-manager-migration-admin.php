<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_Migration_Admin class.
 */
class WP_Event_Manager_Migration_Admin {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page( __( 'Event Migration', 'wp-event-manager-migration' ), __( 'Event Migration', 'wp-event-manager-migration' ), 'manage_options', 'event-migration', [$this, 'event_migration']);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'wp-event-manager-integration-admin', EVENT_MANAGER_MIGRATION_PLUGIN_URL . '/assets/css/admin.css', '', EVENT_MANAGER_MIGRATION_VERSION );

		wp_register_script( 'wp-event-manager-migration-admin', EVENT_MANAGER_MIGRATION_PLUGIN_URL . '/assets/js/admin-migration.js', array('jquery'), EVENT_MANAGER_MIGRATION_VERSION, true);
		wp_localize_script( 'wp-event-manager-migration-admin', 'event_manager_migration_admin', array( 
							'ajax_url' 	 => admin_url('admin-ajax.php'),
							'file_type_error' => __( 'Please select csv file', 'wp-event-manager-migration'),
							)
						  );
		
		wp_enqueue_script( 'wp-event-manager-migration-admin');
	}

	/**
	 * event_migration function.
	 *
	 * @access public
	 * @return void
	 */
	public function event_migration() 
	{
		global $wpdb;
		wp_enqueue_media();

		if ( ! empty( $_POST['wp_event_manager_migration_upload'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_migration_upload' ) ) 
		{
			if($_POST['action'] == 'upload' && $_POST['file_id'] != '')
			{
				$file = get_attached_file($_POST['file_id']);

                $csv_data = $this->get_csv_data($file);

                $csv_head_fields = array_shift($csv_data);

                $event_fields = $this->get_event_form_field_lists();

                get_event_manager_template( 
					'event-migration-mapping-form.php', 
					array(
						'file_id' => $_POST['file_id'],
						'csv_head_fields' => $csv_head_fields,
						'event_fields' => $event_fields,
					), 
					'wp-event-manager-migration', 
					EVENT_MANAGER_MIGRATION_PLUGIN_DIR . '/templates/admin/'
				);
			}
		}
		else if ( ! empty( $_POST['wp_event_manager_migration_mapping'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_migration_mapping' ) ) 
		{
			$migration_import_fields = [];
			if(!empty($_POST['event_field']))
			{				
				foreach ($_POST['event_field'] as $key => $field) 
				{
					if($field != '')
					{
						$csv_field = [];
						$csv_field['key'] = $key;
						$csv_field['name'] = $_POST['csv_field'][$key];
						$csv_field['taxonomy'] = $_POST['event_taxonomy'][$key];
						$migration_import_fields[$field] = $csv_field;
					}
				}
			}

			update_option('migration_import_fields', $migration_import_fields);

			if($_POST['action'] == 'mapping' && $_POST['file_id'] != '')
			{
				$file = get_attached_file($_POST['file_id']);

				$csv_data = $this->get_csv_data($file);

                $csv_head_fields = array_shift($csv_data);
                $csv_sample_data = $csv_data[0];

                $sample_data = [];

                foreach ($migration_import_fields as $field_name => $field_date) 
                {
                	$sample_data[$field_name] = $csv_sample_data[$field_date['key']];
                }

				get_event_manager_template( 
					'event-migration-import.php', 
					array(
						'file_id' => $_POST['file_id'],
						'migration_import_fields' => $migration_import_fields,
						'sample_data' => $sample_data,
					), 
					'wp-event-manager-migration', 
					EVENT_MANAGER_MIGRATION_PLUGIN_DIR . '/templates/admin/'
				);
			}
		}
		else if ( ! empty( $_POST['wp_event_manager_migration_import'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_migration_import' ) ) 
		{
			if($_POST['action'] == 'import' && $_POST['file_id'] != '')
			{
				$migration_import_fields = get_option('migration_import_fields', true);

				$file = get_attached_file($_POST['file_id']);

                $csv_data = $this->get_csv_data($file);
                
                $csv_head_fields = array_shift($csv_data);

                if(!empty($csv_data))
                {
                	for($i=0; $i < count($csv_data); $i++)
                	{
                		$import_data = [];
                		foreach ($migration_import_fields as $field_name => $field_date) 
		                {
		                	$import_data[$field_name] = $csv_data[$i][$field_date['key']];
		                }

		                $this->import_event($import_data);
                	}
                }

                get_event_manager_template( 
					'event-migration-success.php', 
					array(
						'total_records' => count($csv_data),
					), 
					'wp-event-manager-migration', 
					EVENT_MANAGER_MIGRATION_PLUGIN_DIR . '/templates/admin/'
				);

                
			}
		}
		else
		{
			/*
			$term = term_exists('Appearance or Signing', 'event_listing_type');
			wp_set_post_terms(276, $term['term_id'],'event_listing_type', true);

			echo '<pre>';
			print_r($term);
			echo '</pre>' . __FILE__ . ' ( Line Number ' . __LINE__ . ')';
			die;
			*/
			get_event_manager_template( 
				'event-migration-file-upload.php', 
				array(
				), 
				'wp-event-manager-migration', 
				EVENT_MANAGER_MIGRATION_PLUGIN_DIR . '/templates/admin/'
			);
		}		

	}


	public function get_event_form_field_lists() {

		if(!class_exists('WP_Event_Manager_Form_Submit_Event') ) {
			include_once( EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-abstract.php' );
			include_once( EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-submit-event.php' );	
		}
		$form_submit_event_instance = call_user_func( array( 'WP_Event_Manager_Form_Submit_Event', 'instance' ) );
		$fields = $form_submit_event_instance->merge_with_custom_fields('backend');

		return $fields;		
	}

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

	public function import_event($params) 
	{
		if($params['_event_title'] != '')
		{
			$user_id = get_current_user_id();

			$args = [
	    		'post_title'     => $params['_event_title'],
				'post_type'      => 'event_listing',
	            'post_author'    => $user_id,
				'comment_status' => 'closed',
				'post_status'    => 'publish',
	    	];

	    	$event_id = wp_insert_post($args);

	        if($event_id != '')
	        {
	        	$my_event = [
		    		'ID'           => $event_id,
		    	];

		    	if(isset($params['_event_description']) && $params['_event_description'] != '')
		    	{
		    		$my_event['post_content'] = $params['_event_description'];
		    	}

		    	wp_update_post( $my_event );

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
				
	}

	public function upload_image($url)
    {
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

    public function image_exists($url)
	{
		$response = wp_remote_post($url);

		$response_code = wp_remote_retrieve_response_code( $response );

	    return $response_code == 200 ? true : false;
	}


}

new WP_Event_Manager_Migration_Admin();