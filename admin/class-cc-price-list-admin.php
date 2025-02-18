<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package CCPriceList
 */

/**
 * Include the Products Table class
 */
require_once plugin_dir_path(__FILE__) . 'components/tables/class-products-list-table.php';

/**
 * Class CC_Price_List_Admin
 * 
 * Defines all functionality for the admin area of the plugin.
 */
class CC_Price_List_Admin {

    /**
     * The version of this plugin.
     *
     * @var string
     */
    private $version;

    /**
     * Instance of the data handler
     *
     * @var CC_Price_List_Data_Handler
     */
    private $data_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $version The version of this plugin.
     */
    public function __construct($version) {
        $this->version = $version;
        $this->data_handler = new CC_Price_List_Data_Handler();
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'cc-price-list-admin',
            CC_PRICE_LIST_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'cc-price-list-admin',
            CC_PRICE_LIST_PLUGIN_URL . 'admin/assets/js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize the script with new data
        wp_localize_script(
            'cc-price-list-admin',
            'ccPriceList',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cc_price_list_nonce'),
                'adminUrl' => admin_url()
            )
        );
    }

    /**
     * Add plugin admin menu.
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'CC Price List', // Page title
            'CC Price List', // Menu title
            'manage_options', // Capability
            'cc-price-list', // Menu slug
            array($this, 'display_plugin_admin_page'), // Function to display the page
            'dashicons-list-view', // Icon
            30 // Position
        );

        // Add submenu pages
        add_submenu_page(
            'cc-price-list', // Parent slug
            'Products', // Page title
            'Products', // Menu title
            'manage_options', // Capability
            'cc-price-list', // Menu slug (same as parent to keep it as main page)
            array($this, 'display_plugin_admin_page') // Function
        );

        add_submenu_page(
            'cc-price-list', // Parent slug
            'Add New Product', // Page title
            'Add New', // Menu title
            'manage_options', // Capability
            'cc-price-list-add-new', // Menu slug
            array($this, 'display_add_new_page') // Function
        );
        
        add_submenu_page(
            'cc-price-list', // Parent slug
            'Edit Product', // Page title
            'Edit Product', // Menu title
            'manage_options', // Capability
            'cc-price-list-edit', // Menu slug - MUST be unique
            array($this, 'display_edit_page'), // Function
            11
        );

        add_submenu_page(
            'cc-price-list', // Parent slug
            'Import/Export', // Page title
            'Import/Export', // Menu title
            'manage_options', // Capability
            'cc-price-list-import-export', // Menu slug
            array($this, 'display_import_export_page') // Function
        );
    }

    /**
     * Render the main admin page.
     */
    public function display_plugin_admin_page() {
        $products_table = new CC_Price_List_Products_Table($this->data_handler);
        $products_table->prepare_items();
        include_once 'views/admin-display.php';
    }

    /**
     * Render the add new product page.
     */
    public function display_add_new_page() {
        
        include_once 'components/forms/add-product-form.php';
    }
    
    /**
     * Render the edit product page.
     */
    public function display_edit_page() {
        include_once 'components/forms/edit-product-form.php';
    }

    /**
     * Render the import/export page.
     */
    public function display_import_export_page() {
        include_once 'views/import-export-display.php';
    }

    /**
     * Register REST API endpoints.
     */
    public function register_rest_endpoints() {
        register_rest_route('cclist/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this->data_handler, 'get_products_for_api'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * AJAX handler for getting products
     */
    public function ajax_get_products() {

        check_ajax_referer('cc_price_list_nonce', 'nonce');

        $filters = array();
        if (isset($_GET['category'])) {
            $filters['category'] = sanitize_text_field($_GET['category']);
        }
        if (isset($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        if (isset($_GET['orderby'])) {
            $filters['orderby'] = sanitize_text_field($_GET['orderby']);
        }
        if (isset($_GET['order'])) {
            $filters['order'] = sanitize_text_field($_GET['order']);
        }
          if (isset($_GET['size'])) {
            $filters['size'] = sanitize_text_field($_GET['size']);
        }

        if (isset($_GET['price_min'])) {
            $filters['price_min'] = sanitize_text_field($_GET['price_min']);
        }

        if (isset($_GET['price_max'])) {
            $filters['price_max'] = sanitize_text_field($_GET['price_max']);
        }

        if (isset($_GET['quantity_min'])) {
            $filters['quantity_min'] = sanitize_text_field($_GET['quantity_min']);
        }

        if (isset($_GET['quantity_max'])) {
            $filters['quantity_max'] = sanitize_text_field($_GET['quantity_max']);
        }
        if (isset($_GET['per_page'])) {
            $filters['per_page'] = intval($_GET['per_page']);
        }
        if (isset($_GET['page'])) {
            $filters['page'] = intval($_GET['page']);
        }

        
        $products = $this->data_handler->get_products($filters);
        
        // Convert data to format expected by admin.js and the WP_List_Table
        $formatted_products = array_map(function($item) {
            // Extract base product info
            $base_info = array(
                'id' => $item['id'],
                'item_name' => $item['item_name'],
                'category' => $item['category'],
            );

            // Check if it's a grouped item or a single variation
            if (isset($item['variations']) && is_array($item['variations'])) {
                // Grouped item - include variations
                $variations = array_map(function($variation) use ($base_info) {
                    return array_merge($base_info, $variation);
                }, $item['variations']);
            } else {
                $variations[] = $item;
            }

            return $variations;
        }, $products);
        // Flatten the array structure
        $formatted_products = call_user_func_array('array_merge', $formatted_products);

        wp_send_json_success($formatted_products);
    }

    /**
     * AJAX handler for getting categories
     */
    public function ajax_get_categories() {
        check_ajax_referer('cc_price_list_nonce', 'nonce');
        $categories = $this->data_handler->get_categories();

        wp_send_json_success($categories);
    }

     /**
     * AJAX handler to add a product
     */
      public function ajax_add_product()
      {
          check_ajax_referer('cc_price_list_add_product', 'nonce');
  
          $data = $_POST;
          // error_log(print_r($data, true));
  
          // Basic validation
          if (empty($data['category']) || empty($data['item_name'])) {
              wp_send_json_error(array('message' => 'Category and Item Name are required.'));
          }
  
          // Process Price Breaks
          $prices = [];
          if (!empty($data['quantity_min']) && is_array($data['quantity_min'])) {
              $count = count($data['quantity_min']);
  
              for ($i = 0; $i < $count; $i++) {
                  if (!empty($data['quantity_min'][$i]) && !empty($data['price'][$i])) {
                      $prices[] = [
                          'size' => $data['size'],
                          'quantity_min' => (int)$data['quantity_min'][$i],
                          'quantity_max' => isset($data['quantity_max'][$i]) ? (int)$data['quantity_max'][$i] : null,
                          'price' => (float)$data['price'][$i],
                      ];
                  }
              }
          }
  
          $product_data = [
              'category' => $data['category'],
              'item_name' => $data['item_name'],
              'size'  => $data['size'],
              'prices'    => $prices
          ];
  
          // Add the product using the data handler
          $result = $this->data_handler->add_product($product_data);
  
          if ($result) {
              wp_send_json_success(array('message' => 'Product added successfully!'));
          } else {
              wp_send_json_error(array('message' => 'Failed to add product.'));
          }
      }
    
    /**
    * AJAX handler for editing products
    */
    public function ajax_edit_product() {
        check_ajax_referer( 'cc_price_list_edit_product', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID.' ) );
        }
      
        $data = $_POST;
        if ( empty( $data['category'] ) || empty( $data['item_name'] ) ) {
            wp_send_json_error( array( 'message' => 'Category and Item Name are required.' ) );
        }

        // Process variations based on variation type
        $prices = array();
           if ( !empty($data['quantity_min']) && is_array($data['quantity_min']) ) {
              $count = count($data['quantity_min']);
    
              for ( $i=0; $i < $count; $i++ ) {
                if ( !empty($data['quantity_min'][$i]) && !empty($data['price'][$i]) ) { // Ensure that we at least have a min quantity and a price
                    $prices[] = [
                        'size' => $data['size'],
                        'quantity_min' => (int)$data['quantity_min'][$i],
                        'quantity_max' => isset($data['quantity_max'][$i]) ? (int)$data['quantity_max'][$i] : null,
                        'price' => (float)$data['price'][$i]
                    ];
                }
              }
            }

        // Prepare the product data
        $product_data = [
            'category'  => $data['category'],
            'item_name' => $data['item_name'],
            'size'      => $data['size'],
            'prices'       => $prices
        ];

        // Update product using data handler
        $result = $this->data_handler->update_product($product_id, $product_data);

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Product updated successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update product.' ) );
        }
    }

    /**
     * AJAX handler to delete a group
     */
    public function ajax_delete_group() {
        check_ajax_referer('cc_price_list_nonce', 'nonce');

        $group_id = isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : null;

        if (!$group_id) {
            wp_send_json_error(array('message' => 'Group ID is required.'));
        }

        
        $result = $this->data_handler->delete_group($group_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Group deleted successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete group.'));
        }
    }

     /**
    * AJAX handler for bulk group deletion
    */
    public function ajax_bulk_delete_groups()
    {
        check_ajax_referer('cc_price_list_nonce', 'nonce');
        $group_ids = isset($_POST['group_ids']) ? $_POST['group_ids'] : array();
        if (empty($group_ids)) {
            wp_send_json_error(array('message' => 'No groups selected for deletion.'));
        }
        
        // Sanitize each group ID
        $sanitized_group_ids = array_map('sanitize_text_field', $group_ids);

        

        $success_count = 0;
        $error_count = 0;

        foreach($sanitized_group_ids as $group_id){
            $result = $this->data_handler->delete_group($group_id);
            if($result){
                $success_count++;
            } else {
                $error_count++;
            }
        }

        if ($error_count > 0) {
            wp_send_json_error(array('message' => "Failed to delete some groups. Success: $success_count, Error: $error_count"));
        } else {
            wp_send_json_success(array('message' => 'All selected groups deleted successfully.'));
        }
    }
    
    /**
    * AJAX handler for importing products
    */
    public function ajax_import_products() {
        check_ajax_referer('cc_price_list_nonce', 'nonce');

        if (empty($_FILES['import_file'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }

        $file = $_FILES['import_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
             wp_send_json_error(array('message' => 'File upload error: ' . $file['error']));
        }

        // Check file type
        $file_type = wp_check_filetype($file['name'], array('csv' => 'text/csv'));
        if ($file_type['type'] != 'text/csv') {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload a CSV file.'));
        }

        
        $data = array();

        // Read the file
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                 // Map CSV columns to product data
                $data[] = array(
                    'category' => isset($row[0]) ? $row[0] : '',
                    'item_name' => isset($row[1]) ? $row[1] : '',
                    'size' => isset($row[2]) ? $row[2] : null,
                    'price' => isset($row[3]) ? $row[3] : null,
                    'quantity_min' => isset($row[4]) ? $row[4] : null,
                    'quantity_max' => isset($row[5]) ? $row[5] : null,
                    'discount' => isset($row[6]) ? $row[6] : null,
                );
            }
            fclose($handle);
            
            $import_result = $this->data_handler->import_products($data);
            wp_send_json_success(array('message' => "Imported {$import_result} products successfully."));

        } else {
             wp_send_json_error(array('message' => 'Failed to read the uploaded file.'));
        }
    }
    
    /**
     * AJAX handler for exporting products
     */
    
    public function ajax_export_products(){
        check_ajax_referer('cc_price_list_nonce', 'nonce');
        $export_data = $this->data_handler->export_products();
        
        // Generate CSV file name
        $filename = 'cc-price-list-export-' . date('Ymd-His') . '.csv';
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Write data to CSV
         foreach ($export_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    }
    
    /**
    *  Register admin actions
    */
    public function register_admin_actions(){
        add_action('admin_post_cc_price_list_edit_product', array( $this, 'handle_edit_product' ));
    }
    
    /**
     * Handle edit product form submission
     */
    public function handle_edit_product(){
        check_admin_referer( 'cc_price_list_edit_product', 'cc_price_list_nonce' );
        
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        
        if (!empty($_POST['submit']) && $product_id){
            $data = $_POST;

            // Process variations based on variation type
            $variations = array();
            if ( isset($data['variation_type']) && $data['variation_type'] === 'size') {
                $sizes = isset($data['size']) ? $data['size'] : [];
                $prices = isset($data['price']) ? $data['price'] : [];
                // Ensure we have the same number of sizes and prices.
                if(count($sizes) !== count($prices)){
                    $error_message = 'Sizes and prices do not match';
                    wp_redirect(admin_url('admin.php?page=cc-price-list-edit&id=' . $product_id . '&error=' . urlencode($error_message)));
                    exit;
                }
                for($i = 0; $i<count($sizes); $i++){
                    if(!empty($sizes[$i]) && !empty($prices[$i])){
                        $variations[] = array(
                            'size' => $sizes[$i],
                            'price' => $prices[$i],
                            'quantity_min' => 1, // Default value
                            'quantity_max' => null, // Default value
                            'discount' => null //Default value
                        );
                    }
                }
            } else if (isset($data['variation_type']) && $data['variation_type'] === 'quantity') {
                $min_quantities = isset($data['quantity_min']) ? $data['quantity_min'] : [];
                $max_quantities = isset($data['quantity_max']) ? $data['quantity_max'] : [];
                $prices = isset($data['price']) ? $data['price'] : [];

                 if(count($min_quantities) !== count($max_quantities) || count($min_quantities) !== count($prices)){
                    $error_message = 'Quantity breaks and prices do not match';
                    wp_redirect(admin_url('admin.php?page=cc-price-list-edit&id=' . $product_id . '&error=' . urlencode($error_message)));
                    exit;
                 }

                for($i = 0; $i<count($min_quantities); $i++){
                    if(!empty($min_quantities[$i]) && !empty($prices[$i])){
                        $variations[] = array(
                            'size' => null, // Default for quantity breaks
                            'price' => $prices[$i],
                            'quantity_min' => $min_quantities[$i],
                            'quantity_max' => $max_quantities[$i],
                            'discount' => null
                        );
                    }
                }
            }
            

            // Update logic: Delete existing variations and re-add
            // First delete all current variations for this item_name
            $existing_product = $this->data_handler->get_product($product_id);
            if (!$existing_product) {
                $error_message = 'Failed to edit product. Product to edit not found.';
                wp_redirect(admin_url('admin.php?page=cc-price-list-edit&id=' . $product_id . '&error=' . urlencode($error_message)));
                exit;
            }

            $this->data_handler->delete_group($existing_product['item_name']);

            // Add each variation as individual product
            foreach($variations as $variation){
                $result = $this->data_handler->add_product(array_merge(array(
                    'category' => $data['category'],
                    'item_name' => $data['item_name']
                ), $variation));
                if ( ! $result ) {
	                $error_message = 'Failed to add product variation.';
                    wp_redirect(admin_url('admin.php?page=cc-price-list-edit&id=' . $product_id . '&error=' . urlencode($error_message)));
                    exit;
                }
            }
            wp_redirect(admin_url('admin.php?page=cc-price-list&updated=true'));
            exit;

        } else {
            wp_redirect(admin_url('admin.php?page=cc-price-list'));
            exit;
        }
    }
}