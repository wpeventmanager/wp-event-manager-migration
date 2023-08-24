<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WPEM_Migration_Import class.
 */
class WPEM_Migration_Import {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        include_once(WPEM_MIGRATION_PLUGIN_DIR.'/includes/lib/simple-xlsx.php');
        include_once(WPEM_MIGRATION_PLUGIN_DIR.'/includes/lib/excel-reader.php');

        // Ajax
        add_action('wp_ajax_get_migration_terms', array($this, 'get_migration_terms'));
    }

    /**
     * get_migration_post_type function.
     *
     * @access public
     * @param $post_type
     * @return array
     * @since 1.0
     */
    public function get_migration_post_type() {
        $post_types = array(
            'event_listing' => __('Events', 'wp-event-manager-migration'),
            'event_organizer' => __('Organizer', 'wp-event-manager-migration'),
            'event_venue' => __('Venue', 'wp-event-manager-migration'),
            'event_registration' => __('Event Registration', 'wp-event-manager-migration'),
        );
		if ( in_array('wp-event-manager-registrations/wp-event-manager-registrations.php', apply_filters('active_plugins', get_option('active_plugins'))) ){
			$post_types['event_registration'] = __('Registrations', 'wp-event-manager-migration');
		}

		if ( in_array('wp-event-manager/wp-event-manager.php', apply_filters('active_plugins', get_option('active_plugins'))) && in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
			$post_types['product'] = __('Sell Tickets', 'wp-event-manager-migration');
		}

        return apply_filters('migration_post_type', $post_types);
    }

    /**
     * get_migration_terms function.
     *
     * @access public
     * @param $post_type
     * @return 
     * @since 1.0
     */
    public function get_migration_terms() {
        if (isset($_POST['taxonomy'])) {
            $terms = get_categories(array('taxonomy' => sanitize_text_field($_POST['taxonomy']), 'hide_empty' => false));
        }
        $output = '<option value="">' . __('Select option', 'wp-event-manager-migration') . '...</option>';
        if (!empty($terms)) {
            foreach ($terms as $key => $term) {
                $output .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
            }
        }
        $output = apply_filters('customize_migration_terms', $output);
        print($output);
        wp_die();
    }

    /**
     * get_event_form_field_lists function.
     *
     * @access public
     * @param $post_type
     * @return array
     * @since 1.0
     */
    public function get_event_form_field_lists($post_type) {
        $fields = [];
        if ($post_type == 'event_listing') {
            $GLOBALS['event_manager']->forms->get_form('submit-event', array());
            $form_submit_event_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Event', 'instance'));
            $fields = $form_submit_event_instance->merge_with_custom_fields('backend');
        } else if ($post_type == 'event_organizer') {
            $GLOBALS['event_manager']->forms->get_form('submit-organizer', array());
            $form_submit_organizer_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Organizer', 'instance'));
            $fields = $form_submit_organizer_instance->merge_with_custom_fields('backend');
        } else if ($post_type == 'event_venue') {
            $GLOBALS['event_manager']->forms->get_form('submit-venue', array());
            $form_submit_venue_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Venue', 'instance'));
            $fields = $form_submit_venue_instance->merge_with_custom_fields('backend');
        } else if ($post_type == 'event_registration') {
            $fields = get_event_registration_form_fields();
        } else if ($post_type == 'product') {
            $fields = $this->event_listing_sell_tickets_fields();
        }
        $fields = apply_filters('wpem_migration_event_form_field_lists', $fields, $post_type);
        return $fields;
    }

    /**
     * get_file_data function.
     *
     * @access public
     * @param $type, $file
     * @return array
     * @since 1.0
     */
    public function get_file_data($type, $file) {
        $file_data = [];
        if ($type == 'csv') {
            $file_data = $this->get_csv_data($file);
        } else if ($type == 'xml') {
            $file_data = $this->get_xml_data($file);
        }
        do_action('wpem_migration_get_file_data', $file, $type);
        $file_data = apply_filters('wpem_migration_update_file_data', $file_data, $type);
        return $file_data;
    }

    /**
     * get_csv_data function.
     *
     * @access public
     * @param $file
     * @return array
     * @since 1.0
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
     * get_xml_data function.
     *
     * @access public
     * @param $file
     * @return array
     * @since 1.0
     */
    public function get_xml_data($file) {
        $xmlReader = new XMLReader();		
        // reader the XML file.
        $xmlReader->open($file);
        
        $xmlEvents = array();
        $key=array();
        while($xmlReader->read()) {
            // take action based on the kind of node returned
            if ($xmlReader->localName == "source") {
                continue;
            }
			switch($xmlReader->nodeType) {
                case (XMLREADER::ELEMENT):  
                    if ($xmlReader->localName == "events" || $xmlReader->localName == "organizers" ||  $xmlReader->localName == "venues") {
                        $event=array();
                    }else{
                        if(empty($xmlEvents)){
                            $key[] = $xmlReader->localName;
                        }
                        $xmlReader->read();		              	   
                        $event[]=$xmlReader->value;
                    }
                    break;
                case (XMLREADER::END_ELEMENT):
                    // do something based on when the element closes.
                    if (($xmlReader->localName == "events" || $xmlReader->localName == "organizers" ||  $xmlReader->localName == "venues") && !empty($event)){
                        if(!empty($key)){
                            array_push($xmlEvents, $key);
                            $key = null;
                        }
                        array_push($xmlEvents, $event);
                    }
                    break;			       	      	 
            }
        }
        //close file
        $xmlReader->close();
        // File is too old, refresh cache	
        // file_put_contents: If filename does not exist, the file is created. Otherwise, the existing file is overwritten, unless the FILE_APPEND flag is set.              
        $json_results = json_encode($xmlEvents); 
        $dataArray=json_decode($json_results, TRUE);	   
        return $xmlEvents;
    }

    /**
     * import_data function.
     *
     * @access public
     * @param $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_data($post_type, $params) {
        $user_id = get_current_user_id();

        $post_id = '';
        if (isset($params['_post_id']) && $params['_post_id'] != '') {
            $type = get_post_type($params['_post_id']);
            if ($post_type == $type) {
                $post_id = $params['_post_id'];
            }
            if ($post_type == 'product') {
                $product = wc_get_product($params['_post_id']);
                if (!empty($product)) {
                    if ($product->is_type('event_ticket')) {
                        $post_id = $params['_post_id'];
                    } else {
                        $post_id = '';
                    }
                } else {
                    $post_id = '';
                }
            }
        }
        if ($post_type == 'event_listing') {
            $post_title = !empty($params['_event_title']) ? $params['_event_title'] : '';
        } else if ($post_type == 'event_organizer') {
            $post_title = !empty($params['_organizer_name']) ? $params['_organizer_name'] : '';
        } else if ($post_type == 'event_venue') {
            $post_title = !empty($params['_venue_name']) ? $params['_venue_name'] : '';
        } else if ($post_type == 'event_registration') {
            $post_title = !empty($params['_attendee_name']) ? $params['_attendee_name'] : '';
        } else if ($post_type == 'product') {
            $post_title = !empty($params['ticket_name']) ? $params['ticket_name'] : '';
        }
        $post_title = apply_filters('wpem_migration_set_post_title', $post_title, $params);
        if ($post_id == '' && $post_title != '') {
            $args = [
                'post_title' => $post_title,
                'post_type' => $post_type,
                'post_author' => $user_id,
                'comment_status' => 'closed',
                'post_status' => 'publish',
            ];
            $post_id = wp_insert_post($args);
            if (isset($params['_post_id']) && $params['_post_id'] != '') {
                update_post_meta($post_id, '_migration_id', $params['_post_id']);
            }
        }
        if ($post_type == 'event_listing') {
            $this->import_event($post_id, $post_type, $params);
        } else if ($post_type == 'event_organizer') {
            $this->import_organizer($post_id, $post_type, $params);
        } else if ($post_type == 'event_venue') {
            $this->import_venue($post_id, $post_type, $params);
        } else if ($post_type == 'event_registration') {
            $this->import_registration($post_id, $post_type, $params);
        } else if ($post_type == 'product') {
            $this->import_ticket($post_id, $post_type, $params);
        }
        do_action('wpem_migration_import_file_data', $post_id, $post_type, $params);
    }

    /**
     * import_event function.
     *
     * @access public
     * @param $post_id, $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_event($post_id, $post_type, $params) {
        if ($post_id != '') {
            $post_title = !empty($params['_event_title']) ? $params['_event_title'] : '';
            $post_content = !empty($params['_event_description']) ? $params['_event_description'] : '';
            $update_event = ['ID' => $post_id];
            if ($post_title != '') {
                $update_event['post_title'] = $post_title;
            }
            if ($post_content != '') {
                $update_event['post_content'] = $post_content;
            }

            wp_update_post($update_event);
            $migration_import_fields = get_option('wpem_migration_import_fields', true);
            $migration_id = get_post_meta($post_id, '_migration_id', true);
            foreach ($params as $meta_key => $meta_value) {
                $import_fields = $migration_import_fields[$meta_key];
                if ($meta_key == '_event_banner') {
                    $is_json = is_string($meta_value) && is_array(json_decode($meta_value, true)) ? true : false;

                    if ($is_json) {
                        $arrImages = json_decode($meta_value, true);
                    } else {
                        if (strpos($meta_value, ',') !== false) {
                            $arrImages = explode(',', $meta_value);
                        } else if (strpos($meta_value, '|') !== false) {
                            $arrImages = explode('|', $meta_value);
                        } else {
                            $arrImages = [$meta_value];
                        }
                    }
                    if (!empty($arrImages)) {
                        $imageData = [];
                        foreach ($arrImages as $key => $url) {
                            $response = $this->image_exists($url);

                            if ($response) {
                                $image = $this->upload_image($url);

                                if (!empty($image)) {
                                    $imageData[] = $image['image_url'];
                                }
                            }
                        }

                        if (!empty($imageData)) {
                            update_post_meta($post_id, $meta_key, $imageData);
                        }
                    }
                } else if (in_array($meta_key, ['_event_organizer_ids', '_event_venue_ids'])) {
                    $is_json = is_string($meta_value) && is_array(json_decode($meta_value, true)) ? true : false;
                    if ($is_json) {
                        $arrID = json_decode($meta_value, true);
                    } else {
                        if (strpos($meta_value, ',') !== false) {
                            $arrID = explode(',', $meta_value);
                        } else if (strpos($meta_value, '|') !== false) {
                            $arrID = explode('|', $meta_value);
                        } 
                        else {
                            if (is_numeric($meta_value)) {
                                $arrID = [$meta_value];
                            }else{
                                $arrID = array($meta_value);
                            }
                        }
                    }
                    if (!empty($arrID)) {
                        $arrID = array_filter($arrID);
                        if ($meta_key == '_event_organizer_ids') {
                            $ids = $this->get_migration_id('event_organizer', $arrID);
                        } else if ($meta_key == '_event_venue_ids') {
                            $ids = implode(" ",$this->get_migration_id('event_venue', $arrID));
                        } else {
                            $ids = '';
                        }
                        
                        update_post_meta($post_id, $meta_key, $ids);
                    }
                } else {
                    if ($import_fields['taxonomy'] != '') {
                        if ($meta_value != '') {
                            $term = term_exists($meta_value, $import_fields['taxonomy']);
                            if (empty($term)) {
                                $term = wp_insert_term(
                                        $meta_value,
                                        $import_fields['taxonomy']
                                );
                            }
                            wp_set_post_terms($post_id, $term['term_id'], $import_fields['taxonomy'], true);
                        } else {
                            $term_id = $import_fields['default_value'];
                            if ($term_id != '') {
                                wp_set_post_terms($post_id, $term_id, $import_fields['taxonomy'], true);
                            }
                        }
                    } else {
                        if ($meta_value == '') {
                            $meta_value = $import_fields['default_value'];
                        }
                        update_post_meta($post_id, $meta_key, $meta_value);
                    }
                }
            }
        }
    }

    /**
     * import_organizer function.
     *
     * @access public
     * @param $post_id, $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_organizer($post_id, $post_type, $params) {
        if ($post_id != '') {
            $post_title = !empty($params['_organizer_name']) ? $params['_organizer_name'] : '';
            $post_content = !empty($params['_organizer_description']) ? $params['_organizer_description'] : '';
            $update_event = ['ID' => $post_id];
            if ($post_title != '') {
                $update_event['post_title'] = $post_title;
            }
            if ($post_content != '') {
                $update_event['post_content'] = $post_content;
            }
            wp_update_post($update_event);

            $migration_import_fields = get_option('wpem_migration_import_fields', true);
            foreach ($params as $meta_key => $meta_value) {
                $import_fields = $migration_import_fields[$meta_key];
                if ($meta_value == '' ) {
                    $meta_value = $import_fields['default_value'];
                }
                if ($meta_key == '_organizer_logo') {
                    $response = $this->image_exists($meta_value);
                    if ($response) {
                        $imageData = $this->upload_image($meta_value);
                        if (!empty($imageData)) {
                            $thumbnail_id = $imageData['image_id'];
                        }
                    } else {
                        $thumbnail_id = '';
                    }
                    update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
                } else {
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
            }
        }
    }

    /**
     * import_venue function.
     *
     * @access public
     * @param $post_id, $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_venue($post_id, $post_type, $params) {
        if ($post_id != '') {
            $post_title = !empty($params['_venue_name']) ? $params['_venue_name'] : '';
            $post_content = !empty($params['_venue_description']) ? $params['_venue_description'] : '';
            $update_event = ['ID' => $post_id];

            if ($post_title != '') {
                $update_event['post_title'] = $post_title;
            }
            if ($post_content != '') {
                $update_event['post_content'] = $post_content;
            }
            wp_update_post($update_event);

            $migration_import_fields = get_option('wpem_migration_import_fields', true);
            foreach ($params as $meta_key => $meta_value) {
                $import_fields = $migration_import_fields[$meta_key];

                if ($meta_value == '') {
                    $meta_value = $import_fields['default_value'];
                }
                if ($meta_key == '_venue_logo') {
                    $response = $this->image_exists($meta_value);
                    if ($response) {
                        $imageData = $this->upload_image($meta_value);
                        if (!empty($imageData)) {
                            $thumbnail_id = $imageData['image_id'];
                        }
                    } else {
                        $thumbnail_id = '';
                    }
                    update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
                } else {
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
            }
        }
    }

    /**
     * import_registration function.
     *
     * @access public
     * @param $post_id, $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_registration($post_id, $post_type, $params) {
        if ($post_id != '') {
            $attendee_name = isset($params['_attendee_name']) ? $params['_attendee_name'] : '';
			$registration_status = isset($params['_registration_status']) ? $params['_registration_status'] : '';
			$event_id = isset($params['_event_id']) ? $params['_event_id'] : '';
			$registration_date = isset($params['_registration_date']) ? $params['_registration_date'] : '';
            $args = array (
				'post_type'  => 'event_listing',
				'meta_query' => array(
					array(
						'key'   => '_migration_id',
						'value' => $event_id,
					),
				),
			);
			$events = new WP_Query( $args );
			if ( $events->have_posts() ) {
				while ( $events->have_posts() ) {
					$events->the_post();
					$event_id = $events->post->ID;
				}
			} 
			if ($attendee_name != '') {
				$registration_data = array(
					'ID'             => $post_id,
					'post_status'    => strtolower($registration_status),
					'post_date'      => date('Y-m-d H:i:s', strtotime(str_replace('-', '/', $registration_date))),
					'post_parent'    => (int)$event_id
				);
				wp_update_post($registration_data);

				update_post_meta($post_id, '_attendee_name', $attendee_name);
				$migration_import_fields = get_option('wpem_migration_import_fields', true);
				foreach ($params as $meta_key => $meta_value) {
					if($meta_key != '_registration_status' && $meta_key != '_registration_date' && $meta_key != '_event_id'){
						$import_fields = $migration_import_fields[$meta_key];

						if ($meta_value == '') {
							$meta_value = $import_fields['default_value'];
						}
						if($meta_key == '_check_in'){
							if($meta_value == 'No'){
								update_post_meta($post_id, $meta_key, 0);
							}else{
								update_post_meta($post_id, $meta_key, 1);
							}
						}
						update_post_meta($post_id, $meta_key, $meta_value);
					}
				}
			}
        }
    }

    /**
     * import_ticket function.
     *
     * @access public
     * @param $post_id, $post_type, $params
     * @return 
     * @since 1.0
     */
    public function import_ticket($post_id, $post_type, $params) {

        if ($post_id != '') {
            $product_id = $post_id;
            $ticket_name = isset($params['ticket_name']) ? $params['ticket_name'] : '';
            $ticket_quantity = isset($params['ticket_quantity']) ? $params['ticket_quantity'] : '';
            $ticket_price = isset($params['ticket_price']) ? $params['ticket_price'] : '';
            $ticket_description = isset($params['ticket_description']) ? $params['ticket_description'] : '';
            $ticket_show_description = isset($params['ticket_show_description']) ? $params['ticket_show_description'] : 0;
            $ticket_fee_pay_by = isset($params['ticket_fee_pay_by']) ? $params['ticket_fee_pay_by'] : 'ticket_fee_pay_by_attendee';
            $ticket_visibility = isset($params['ticket_visibility']) ? $params['ticket_visibility'] : 'public';
            $ticket_minimum = isset($params['ticket_minimum']) ? $params['ticket_minimum'] : '';
            $ticket_maximum = isset($params['ticket_maximum']) ? $params['ticket_maximum'] : '';
            $ticket_sales_start_date = isset($params['ticket_sales_start_date']) ? $params['ticket_sales_start_date'] : '';
            $ticket_sales_start_time = isset($params['ticket_sales_start_time']) ? $params['ticket_sales_start_time'] : '';
            $ticket_sales_end_date = isset($params['ticket_sales_end_date']) ? $params['ticket_sales_end_date'] : '';
            $ticket_sales_end_time = isset($params['ticket_sales_end_time']) ? $params['ticket_sales_end_time'] : '';
            $show_remaining_tickets = isset($params['show_remaining_tickets']) ? $params['show_remaining_tickets'] : '';
            $ticket_individually = isset($params['ticket_individually']) ? $params['ticket_individually'] : '';
            $ticket_type = isset($params['ticket_type']) ? $params['ticket_type'] : '';
            $event_id = isset($params['_event_id']) ? $params['_event_id'] : '';

            $update_event = [
                'ID' => $product_id,
                'post_status' => $ticket_visibility
            ];
            if ($ticket_name != '') {
                $update_event['post_title'] = $ticket_name;
            }
            if ($ticket_description != '') {
                $update_event['post_content'] = $ticket_description;
            }
            wp_update_post($update_event);

            //set product type as event ticket
            wp_set_object_terms($product_id, 'event_ticket', 'product_type');

            $is_virtual = apply_filters('event_ticket_is_virtual', 'yes');
            $migration_import_fields = get_option('wpem_migration_import_fields', true);

            if ($ticket_type == '' && $ticket_price == '') {
                $ticket_type = 'free';
            } else if ($ticket_type == '' && $ticket_price != '') {
                $ticket_type = 'paid';
            }

            //all woocommerce product meta keys
            update_post_meta($product_id, '_regular_price', $ticket_price);
            update_post_meta($product_id, '_price', $ticket_price);
            update_post_meta($product_id, '_stock', $ticket_quantity);
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_manage_stock', 'yes');
            update_post_meta($product_id, 'minimum_order', $ticket_minimum);  //woocommerce meta key
            update_post_meta($product_id, 'maximum_order', $ticket_maximum);  //woocommerce meta key
            update_post_meta($product_id, '_sold_individually', $ticket_individually);

            //add event id in product
            update_post_meta($product_id, '_event_id', $event_id);
            update_post_meta($product_id, '_ticket_sales_start_date', $ticket_sales_start_date);
            update_post_meta($product_id, '_ticket_sales_end_date', $ticket_sales_end_date);
            update_post_meta($product_id, '_ticket_type', $ticket_type);
            update_post_meta($product_id, '_ticket_show_description', $ticket_show_description);
            update_post_meta($product_id, '_show_remaining_tickets', $show_remaining_tickets);

            //add all fee details as custom attributes of the product, values will get from decided fees tab as get_options setttings.
            update_post_meta($product_id, '_ticket_fee_pay_by', $ticket_fee_pay_by);
            update_post_meta($product_id, '_virtual', $is_virtual);

            if ($event_id != '') {
                $updated_tickets = [];
                $get_tickets = get_post_meta($event_id, '_' . $ticket_type . '_tickets', true);
                $updated_new_tickets = [];
                $is_add_ticket = true;
                if (!empty($get_tickets)) {
                    foreach ($get_tickets as $ticket) {
                        if ($ticket['product_id'] == $product_id) {
                            $ticket['product_id'] = $product_id;
                            $ticket['ticket_name'] = $ticket_name;
                            $ticket['ticket_quantity'] = $ticket_quantity;
                            $ticket['ticket_price'] = $ticket_price;
                            $ticket['ticket_description'] = $ticket_description;
                            $ticket['ticket_show_description'] = $ticket_show_description;
                            $ticket['ticket_fee_pay_by'] = $ticket_fee_pay_by;
                            $ticket['ticket_visibility'] = $ticket_visibility;
                            $ticket['ticket_minimum'] = $ticket_minimum;
                            $ticket['ticket_maximum'] = $ticket_maximum;
                            $ticket['ticket_sales_start_date'] = $ticket_sales_start_date;
                            $ticket['ticket_sales_start_time'] = $ticket_sales_start_time;
                            $ticket['ticket_sales_end_date'] = $ticket_sales_end_date;
                            $ticket['ticket_sales_end_time'] = $ticket_sales_end_time;
                            $ticket['show_remaining_tickets'] = $show_remaining_tickets;
                            $ticket['ticket_individually'] = $ticket_individually;
                            $updated_new_tickets[] = $ticket;
                            $is_add_ticket = false;
                        } else {
                            $updated_new_tickets[] = $ticket;
                        }
                    }
                }

                if ($is_add_ticket) {
                    $new_ticket = [];
                    $new_ticket['product_id'] = $product_id;
                    $new_ticket['ticket_name'] = $ticket_name;
                    $new_ticket['ticket_quantity'] = $ticket_quantity;
                    $new_ticket['ticket_price'] = $ticket_price;
                    $new_ticket['ticket_description'] = $ticket_description;
                    $new_ticket['ticket_show_description'] = $ticket_show_description;
                    $new_ticket['ticket_fee_pay_by'] = $ticket_fee_pay_by;
                    $new_ticket['ticket_visibility'] = $ticket_visibility;
                    $new_ticket['ticket_minimum'] = $ticket_minimum;
                    $new_ticket['ticket_maximum'] = $ticket_maximum;
                    $new_ticket['ticket_sales_start_date'] = $ticket_sales_start_date;
                    $new_ticket['ticket_sales_start_time'] = $ticket_sales_start_time;
                    $new_ticket['ticket_sales_end_date'] = $ticket_sales_end_date;
                    $new_ticket['ticket_sales_end_time'] = $ticket_sales_end_time;
                    $new_ticket['show_remaining_tickets'] = $show_remaining_tickets;
                    $new_ticket['ticket_individually'] = $ticket_individually;
                    $updated_new_tickets[] = $new_ticket;
                }
                update_post_meta($event_id, '_' . $ticket_type . '_tickets', $updated_new_tickets);
            }
        }
    }

    /**
     * upload_image function.
     *
     * @access public
     * @param $url
     * @return array
     * @since 1.0
     */
    public function upload_image($url) {
        $arrData = [];

        if ($url != '') {
            
            require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
            require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
            require_once(ABSPATH . 'wp-admin' . '/includes/media.php');
            $url = stripslashes($url);
            $tmp = download_url($url);
            $file_array = array(
                'name' => basename($url),
                'tmp_name' => $tmp
            );

            /**
             * Check for download errors
             * if there are error unlink the temp file name
             */
            if (is_wp_error($tmp)) {
                @unlink($file_array['tmp_name']);
                return $tmp;
            }

            /**
             * now we can actually use media_handle_sideload
             * we pass it the file array of the file to handle
             * and the post id of the post to attach it to
             * $post_id can be set to '0' to not attach it to any particular post
             */
            $post_id = '0';
            $image_id = media_handle_sideload($file_array, $post_id);

            /**
             * We don't want to pass something to $id
             * if there were upload errors.
             * So this checks for errors
             */
            if (is_wp_error($image_id)) {
                @unlink($file_array['tmp_name']);
                return $image_id;
            }

            /**
             * No we can get the url of the sideloaded file
             * $image_url now contains the file url in WordPress
             * $id is the attachment id
             */
            $image_url = wp_get_attachment_url($image_id);
            $arrData['image_id'] = $image_id;
            $arrData['image_url'] = $image_url;
        }
        return $arrData;
    }

    /**
     * image_exists function.
     *
     * @access public
     * @param $url
     * @return boolen
     * @since 1.0
     */
    public function image_exists($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($retcode == 200) 
            return true;
        else
            return false;
    }

    /**
     * get_migration_id function.
     *
     * @access public
     * @param $post_type, $arrID
     * @return array
     * @since 1.0
     */
    public function get_migration_id($post_type, $arrID) {
        global $wpdb;

        $arr_ID = [];
        if (!empty($arrID)) {
            $ids = implode(",", $arrID);
            $query = "SELECT `post_id` FROM `" . $wpdb->prefix . "postmeta` WHERE 1
							AND `meta_value` IN (" . $ids . ") 
							AND `meta_key` = '_migration_id' 
							AND `post_id` IN (SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE `post_type` = '" . $post_type . "' AND `post_status` = 'publish')";
            $results = $wpdb->get_results($query, ARRAY_A);
            if (!empty($results)) {
                foreach ($results as $key => $result) {
                    $arr_ID[] = $result['post_id'];
                }
            }
        }
        return $arr_ID;
    }

    /**
     * event_listing_sell_tickets_fields function.
     *
     * @access public
     * @param $post_type
     * @return array
     * @since 1.0
     */
    public function event_listing_sell_tickets_fields() {
        $fields = array(
            'tickets' => array(//fields attribute must
                'ticket_name' => array(
                    'label' => __('Ticket Name', 'wp-event-manager-migration'),
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => __('Give your ticket name', 'wp-event-manager-migration'),
                    'priority' => 1
                ),
                'ticket_quantity' => array(
                    'label' => __('Ticket Quantity', 'wp-event-manager-migration'),
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => __('Enter number of tickets', 'wp-event-manager-migration'),
                    'priority' => 2
                ),
                'ticket_type' => array(
                    'label' => __('Ticket Type', 'wp-event-manager-migration'),
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => __('Ticket Type', 'wp-event-manager-migration'),
                    'priority' => 3
                ),
                'ticket_price' => array(
                    'label' => __('Ticket Price', 'wp-event-manager-migration'),
                    'type' => 'number',
                    'required' => false,
                    'placeholder' => __('Ticket price', 'wp-event-manager-migration'),
                    'priority' => 3
                ),
                'ticket_description' => array(
                    'label' => __('Ticket Description', 'wp-event-manager-migration'),
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => __('Tell your attendees more about this ticket type', 'wp-event-manager-migration'),
                    'priority' => 4
                ),
                'ticket_show_description' => array(
                    'label' => __('Show Ticket Description', 'wp-event-manager-migration'),
                    'type' => 'checkbox',
                    'required' => false,
                    'std' => 0,
                    'placeholder' => '',
                    'description' => __('Show ticket description on event page', 'wp-event-manager-migration'),
                    'priority' => 5
                ),
                'ticket_fee_pay_by' => array(
                    'label' => __('Fees Pay By', 'wp-event-manager-migration'),
                    'type' => 'select',
                    'required' => false,
                    'description' => __('Pay by attendee : fees will be added to the ticket price and paid by the attendee.', 'wp-event-manager-migration'),
                    'priority' => 6,
                    'std' => 'ticket_fee_pay_by_attendee',
                    'options' =>
                    array(
                        'ticket_fee_pay_by_attendee' => __('Pay By Attendee', 'wp-event-manager-migration'),
                        'ticket_fee_pay_by_organizer' => __('Pay By Organizer', 'wp-event-manager-migration')
                    )
                ),
                'ticket_visibility' => array(
                    'label' => __('Tickets Visibility', 'wp-event-manager-migration'),
                    'type' => 'select',
                    'required' => false,
                    'description' => __('Public ticket visible to all and Private ticket only visible to organizer.', 'wp-event-manager-migration'),
                    'priority' => 7,
                    'std' => 'public',
                    'options' =>
                    array(
                        'public' => __('Public', 'wp-event-manager-migration'),
                        'private' => __('Private', 'wp-event-manager-migration')
                    )
                ),
                'ticket_minimum' => array(
                    'label' => __('Minimum Tickets', 'wp-event-manager-migration'),
                    'type' => 'number',
                    'required' => false,
                    'placeholder' => __('Minimum tickets allowed per order', 'wp-event-manager-migration'),
                    'priority' => 8
                ),
                'ticket_maximum' => array(
                    'label' => __('Maximum Tickets', 'wp-event-manager-migration'),
                    'type' => 'number',
                    'required' => false,
                    'placeholder' => __('Maximum tickets allowed per order', 'wp-event-manager-migration'),
                    'priority' => 9
                ),
                'ticket_sales_start_date' => array(
                    'label' => __('Sales start date', 'wp-event-manager-migration'),
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => __('Sales start date', 'wp-event-manager-migration'),
                    'priority' => 10
                ),
                'ticket_sales_start_time' => array(
                    'label' => __('Sales Start Time', 'wp-event-manager-migration'),
                    'type' => 'time',
                    'required' => false,
                    'placeholder' => __('Tickets sales start time', 'wp-event-manager-migration'),
                    'attribute' => '',
                    'priority' => 11
                ),
                'ticket_sales_end_date' => array(
                    'label' => __('Sales end date', 'wp-event-manager-migration'),
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => __('Sales end date', 'wp-event-manager-migration'),
                    'priority' => 12
                ),
                'ticket_sales_end_time' => array(
                    'label' => __('Sales End Time', 'wp-event-manager-migration'),
                    'type' => 'time',
                    'required' => false,
                    'placeholder' => __('Tickets sales end time', 'wp-event-manager-migration'),
                    'priority' => 13
                ),
                'show_remaining_tickets' => array(
                    'label' => __('Show remainging tickets', 'wp-event-manager-migration'),
                    'type' => 'checkbox',
                    'required' => false,
                    'placeholder' => '',
                    'description' => __('Show remaining tickets with tickets detail at single event page', 'wp-event-manager-migration'),
                    'priority' => 14
                ),
                'ticket_individually' => array(
                    'label' => __('Sold Tickets individually', 'wp-event-manager-migration'),
                    'type' => 'checkbox',
                    'std' => '',
                    'required' => false,
                    'description' => __('Tickets will be sold one ticket per customer', 'wp-event-manager-migration'),
                    'priority' => 15
                )
            )
        );
        return apply_filters('wpem_migration_set_sell_ticket_fields', $fields);
    }
}

new WPEM_Migration_Import();