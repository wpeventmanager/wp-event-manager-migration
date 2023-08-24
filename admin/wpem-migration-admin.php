<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WPEM_Migration_Admin class.
 */
class WPEM_Migration_Admin {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        include ('wpem-migration-import.php');

        add_action('admin_menu', array($this, 'admin_menu'), 12);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        $this->import_class = new WPEM_Migration_Import();
    }

    /**
     * admin_menu function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.0
     */
    public function admin_menu() {
        add_menu_page(__('Event Migration', 'wp-event-manager-migration'), __('Event Migration', 'wp-event-manager-migration'), 'manage_options', 'event-migration', [$this, 'event_migration'], 'dashicons-upload', 30);
    }

    /**
     * admin_enqueue_scripts function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.0
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style('wp-event-manager-integration-admin', WPEM_MIGRATION_PLUGIN_URL . '/assets/css/admin.min.css', '', WPEM_MIGRATION_VERSION);

        wp_register_script('wp-event-manager-migration-admin', WPEM_MIGRATION_PLUGIN_URL . '/assets/js/admin-migration.min.js', array('jquery'), WPEM_MIGRATION_VERSION, true);
        wp_localize_script('wp-event-manager-migration-admin', 'event_manager_migration_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'media_box_title' => __('Choose .csv or.xml file', 'wp-event-manager-migration'),
            '_post_id' => __('Must select Post ID', 'wp-event-manager-migration'),
            '_event_organizer_ids' => __('Must select Organizer ID', 'wp-event-manager-migration'),
            '_event_venue_ids' => __('Must select Venue ID', 'wp-event-manager-migration'),
            'file_type_error' => __('Please select .csv or.xml or file', 'wp-event-manager-migration'),
            )
        );
        wp_enqueue_script('wp-event-manager-migration-admin');
    }

    /**
     * event_migration function.
     *
     * @access public
     * @param 
     * @return
     * @since 1.0
     */
    public function event_migration() {
        global $wpdb;
        wp_enqueue_media();

        if (!empty($_POST['wp_event_manager_migration_upload']) && wp_verify_nonce($_POST['_wpnonce'], 'event_manager_migration_upload')) {
            if ($_POST['action'] == 'upload' && $_POST['file_id'] != '') {
                $file = get_attached_file(absint($_POST['file_id']));
                $file_data = $this->import_class->get_file_data($_POST['file_type'], $file);
                $file_head_fields = array_shift($file_data);
                $migration_fields = $this->import_class->get_event_form_field_lists(sanitize_text_field($_POST['migration_post_type']));
                $taxonomies = get_object_taxonomies(sanitize_text_field($_POST['migration_post_type']), 'objects');
                $migration_post_type = $this->import_class->get_migration_post_type();
                $import_type_label = $migration_post_type[sanitize_text_field($_POST['migration_post_type'])];

                get_event_manager_template(
                    'event-migration-mapping-form.php',
                    array(
                        'file_id' => sanitize_text_field($_POST['file_id']),
                        'file_type' => sanitize_text_field($_POST['file_type']),
                        'file_head_fields' => $file_head_fields,
                        'migration_fields' => $migration_fields,
                        'import_type_label' => $import_type_label,
                        'migration_post_type' => sanitize_text_field($_POST['migration_post_type']),
                        'taxonomies' => $taxonomies,
                    ),
                    'wp-event-manager-migration',
                    WPEM_MIGRATION_PLUGIN_DIR . '/templates/admin/'
                );
            }
        } else if (!empty($_POST['wp_event_manager_migration_mapping']) && wp_verify_nonce($_POST['_wpnonce'], 'event_manager_migration_mapping')) {
            $migration_import_fields = [];
            if (!empty($_POST['migration_field']) && is_array($_POST['migration_field'])) {
                foreach ($_POST['migration_field'] as $key => $field) {
                    if ($field != '') {
                        if ($field == 'custom_field') {
                            $field = sanitize_text_field($_POST['custom_field'][$key]);
                        }
                        $file_field = [];
                        $file_field['key'] = $key;
                        $file_field['file_field'] = sanitize_text_field($_POST['file_field'][$key]);
                        $file_field['taxonomy'] = sanitize_text_field($_POST['taxonomy_field'][$key]);
                        $file_field['default_value'] = sanitize_text_field($_POST['default_value'][$key]);
                        $migration_import_fields[$field] = $file_field;
                    }
                }
            }

            update_option('wpem_migration_import_fields', $migration_import_fields);

            if ($_POST['action'] == 'mapping' && $_POST['file_id'] != '') {
                $file = get_attached_file($_POST['file_id']);
                $file_data = $this->import_class->get_file_data(sanitize_text_field($_POST['file_type']), $file);
                $file_head_fields = array_shift($file_data);
                $file_sample_data = $file_data[0];
                $sample_data = [];
                foreach ($migration_import_fields as $field_name => $field_data) {
                    $value = !empty($file_sample_data[$field_data['key']]) ? $file_sample_data[$field_data['key']] : $field_data['default_value'];
                    $sample_data[$field_name] = $value;
                }

                get_event_manager_template(
                    'event-migration-import.php',
                    array(
                        'file_id' => sanitize_text_field($_POST['file_id']),
                        'file_type' => sanitize_text_field($_POST['file_type']),
                        'migration_import_fields' => $migration_import_fields,
                        'migration_post_type' => sanitize_text_field($_POST['migration_post_type']),
                        'sample_data' => $sample_data,
                    ),
                    'wp-event-manager-migration',
                    WPEM_MIGRATION_PLUGIN_DIR . '/templates/admin/'
                );
            }
        } else if (!empty($_POST['wp_event_manager_migration_import']) && wp_verify_nonce($_POST['_wpnonce'], 'event_manager_migration_import')) {
            if ($_POST['action'] == 'import' && $_POST['file_id'] != '') {
                $migration_import_fields = get_option('wpem_migration_import_fields', true);

                $file = get_attached_file(sanitize_text_field($_POST['file_id']));
                $file_data = $this->import_class->get_file_data(sanitize_text_field($_POST['file_type']), $file);
                $file_head_fields = array_shift($file_data);
                if (!empty($file_data)) {
                    for ($i = 0; $i < count($file_data); $i++) {
                        $import_data = [];
                        foreach ($migration_import_fields as $field_name => $field_date) {
                            if(array_key_exists($field_date['key'],$file_data[$i])){
                                $import_data[$field_name] = $file_data[$i][$field_date['key']];
                            }
                        }
                        $this->import_class->import_data(sanitize_text_field($_POST['migration_post_type']), $import_data);
                    }
                }

                $migration_post_type = $this->import_class->get_migration_post_type();
                $import_type_label = $migration_post_type[sanitize_text_field($_POST['migration_post_type'])];
                get_event_manager_template(
                    'event-migration-success.php',
                    array(
                        'total_records' => count($file_data),
                        'import_type_label' => $import_type_label,
                    ),
                    'wp-event-manager-migration',
                    WPEM_MIGRATION_PLUGIN_DIR . '/templates/admin/'
                );
            }
        } else {
            $migration_post_type = $this->import_class->get_migration_post_type();
            get_event_manager_template(
                'event-migration-file-upload.php',
                array(
                    'migration_post_type' => $migration_post_type,
                ),
                'wp-event-manager-migration',
                WPEM_MIGRATION_PLUGIN_DIR . '/templates/admin/'
            );
        }
    }
}
new WPEM_Migration_Admin();