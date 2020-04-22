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
		include ('wp-event-manager-migration-import.php');

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		$this->import_class = new WP_Event_Manager_Migration_Import();
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page( __( 'Event Migration', 'wp-event-manager-migration' ), __( 'Event Migration', 'wp-event-manager-migration' ), 'manage_options', 'event-migration', [$this, 'event_migration'], '
dashicons-upload', 30);
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
	public function event_migration() {
		global $wpdb;
		wp_enqueue_media();

		if ( ! empty( $_POST['wp_event_manager_migration_upload'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_migration_upload' ) ) 
		{
			if($_POST['action'] == 'upload' && $_POST['file_id'] != '')
			{
				$file = get_attached_file($_POST['file_id']);

                $csv_data = $this->import_class->get_csv_data($file);

                $csv_head_fields = array_shift($csv_data);

                $event_fields = $this->import_class->get_event_form_field_lists();

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

				$csv_data = $this->import_class->get_csv_data($file);

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

                $csv_data = $this->import_class->get_csv_data($file);
                
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

		                $this->import_class->import_event($import_data);
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

}

new WP_Event_Manager_Migration_Admin();