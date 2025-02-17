<?php
/**
 * Admin page handler
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function cc_price_list_admin_menu() {
    add_menu_page(
        'Price List Manager',
        'Price List',
        'manage_options',
        'cc-price-list',
        'cc_price_list_admin_page',
        'dashicons-list-view',
        30
    );
}
add_action('admin_menu', 'cc_price_list_admin_menu');

// Process CSV import
function cc_process_csv_import($file) {
    error_log('Starting CSV import process...');
    if (!current_user_can('manage_options')) {
        error_log('Insufficient permissions for CSV import.');
        return new WP_Error('permission_error', 'Insufficient permissions');
    }

    $csv_data = array_map('str_getcsv', file($file['tmp_name']));

    if (empty($csv_data)) {
        error_log('CSV file is empty or could not be read.');
        return new WP_Error('empty_file', 'CSV file is empty or could not be read.');
    }
    
    error_log('CSV data read: ' . print_r($csv_data, true));
    $headers = array_shift($csv_data); // Remove headers
    error_log('CSV headers: ' . print_r($headers, true));

    $expected_headers = ['category', 'item', 'size', 'price', 'quantity_min', 'quantity_max', 'discount'];
    if ($headers !== $expected_headers) {
        error_log('CSV format error: Headers do not match expected format.');
        return new WP_Error('invalid_format', 'CSV format does not match expected columns');
    }

    $products = [];
    foreach ($csv_data as $row_num => $row) {
        error_log('Processing row ' . ($row_num + 1) . ': ' . print_r($row, true));
        if (count($row) !== count($expected_headers)) {
            error_log('Skipping row ' . ($row_num + 1) . ': Incorrect number of columns.');
            continue; // Skip rows with incorrect number of columns
        }
        
        $product_key = $row[0] . '|' . $row[1]; // category|item as key
        
        if (!isset($products[$product_key])) {
            $products[$product_key] = [
                'category' => $row[0],
                'item' => $row[1],
                'variations' => [] // We are not using variations table - keep structure
            ];
        }

        //we arent usign variations table
        $products[$product_key]['variations'][] = [
            'size' => $row[2] ?: null,
            'price' => floatval($row[3]),
            'quantity_min' => intval($row[4]),
            'quantity_max' => !empty($row[5]) ? intval($row[5]) : null,
            'discount' => !empty($row[6]) ? floatval($row[6]) : null
        ];
    }

    error_log('Processed products array: ' . print_r($products, true));
    
    foreach ($products as $product) {
        $result = cc_save_product($product);
        if (is_wp_error($result)) {
            error_log('Error saving product: ' . $result->get_error_message() . ' - Data: ' . print_r($product, true));
        } else {
            error_log('Product saved successfully. Product ID: ' . $result);
        }
    }
    
    error_log('CSV import process completed.');
    return true;
}

// Generate example CSV content
function cc_get_example_csv() {
    return "category,item,size,price,quantity_min,quantity_max,discount\n" .
           "CLAY,Buffstone (Plainsman),20kg,35.65,1,9,\n" .
           "CLAY,Buffstone (Plainsman),20kg,32.50,10,39,\n" .
           "CLAY,Buffstone (Plainsman),20kg,31.65,40,79,\n" .
           "CLAY,Buffstone (Plainsman),20kg,30.65,80,,\n" .
           "MINERALS,Cobalt Carbonate,125g,29.80,1,,,\n" .
           "MINERALS,Cobalt Carbonate,250g,57.80,1,,,\n" .
           "MINERALS,Cobalt Carbonate,500g,113.75,1,,,\n" .
           "MINERALS,Cobalt Carbonate,2.5kg,562.00,1,,,\n" .
           "MAYCO GLAZES,Stroke & Coat,Pint,30.10,1,11,\n" .
           "MAYCO GLAZES,Stroke & Coat,Pint,30.10,12,,0.20";
}

// Render admin page
function cc_price_list_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle CSV import
    if (isset($_FILES['csv_import'])) {
        $result = cc_process_csv_import($_FILES['csv_import']);
        if (is_wp_error($result)) {
            echo '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="updated"><p>CSV import completed successfully!</p></div>';
        }
    }
    
    // Get all products for display
    $products = cc_get_all_products();
    
    // Group products by category and item
    $grouped_products = [];
    foreach ($products as $product) {
        if (!isset($grouped_products[$product->category])) {
            $grouped_products[$product->category] = [];
        }
        if (!isset($grouped_products[$product->category][$product->item])) {
            $grouped_products[$product->category][$product->item] = [
                'id' => $product->id,
                 //we arent using variations
                'variations' => [
                  [
                    'size' => $product->size,
                    'price' => $product->price,
                    'quantity_min' => $product->quantity_min,
                    'quantity_max' => $product->quantity_max,
                    'discount' => $product->discount,
                  ]
                ]
            ];
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Import CSV Section -->
        <div class="card" style="max-width: 800px; margin-bottom: 20px; padding: 20px;">
            <h2 class="title" style="margin-top: 0;">Import Products from CSV</h2>
            <p>Upload a CSV file with the following columns:</p>
            <code style="display: block; margin: 10px 0; padding: 10px; background: #f8f9fa;">category,item,size,price,quantity_min,quantity_max,discount</code>
            <p>Not sure about the format? <a href="#" id="download-example-csv">Download example CSV file</a></p>
            
            <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="file" name="csv_import" accept=".csv" style="flex: 1;">
                    <input type="submit" class="button button-primary button-large" value="Import CSV" style="min-width: 120px;">
                </div>
            </form>
        </div>
        
        <!-- Add New Product Button -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="#" class="page-title-action button button-primary button-large" id="add-new-product">Add New Product</a>
            </div>
        </div>
        
        <!-- Product Form Dialog -->
        <div id="product-form-dialog" style="display:none;">
            <form id="product-form">
                <input type="hidden" name="product_id" id="product-id">
                
                <p>
                    <label for="product-category">Category:</label>
                    <input type="text" id="product-category" name="category">
                </p>
                
                <p>
                    <label for="product-item">Item Name:</label>
                    <input type="text" id="product-item" name="item">
                </p>
                
                <!-- We are not using variations for this diagnostic step -->
                <div id="variations-container" style="display:none;">
                    <h3>Variations</h3>
                    <div class="variations-list"></div>
                    <button type="button" class="button add-variation">Add Variation</button>
                </div>
                
                 <p>
                    <label for="product-size">Size:</label>
                    <input type="text" id="product-size" name="size">
                </p>
                <p>
                    <label for="product-price">Price:</label>
                    <input type="number" step="0.01" id="product-price" name="price">
                </p>
                 <p>
                    <label for="product-quantity-min">quantity_min:</label>
                    <input type="text" id="product-quantity-min" name="quantity_min">
                </p>
                <p>
                    <label for="product-quantity-max">quantity_max:</label>
                    <input type="text" id="product-quantity-max" name="quantity_max">
                </p>
                <p>
                    <label for="product-discount">discount:</label>
                    <input type="text" id="product-discount" name="discount">
                </p>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Save Product">
                    <button type="button" class="button cancel-form">Cancel</button>
                </p>
            </form>
        </div>
        
        <!-- Products Table -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select id="filter-category">
                    <option value="">All Categories</option>
                    <?php
                    $categories = array_keys($grouped_products);
                    foreach ($categories as $category) {
                        echo '<option value="' . esc_attr($category) . '">' . esc_html($category) . '</option>';
                    }
                    ?>
                </select>
                <input type="text" id="search-products" placeholder="Search items...">
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Variations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped_products as $category => $items): ?>
                    <?php foreach ($items as $item_name => $item_data): ?>
                        <tr class="product-row" data-category="<?php echo esc_attr($category); ?>">
                            <td><?php echo esc_html($category); ?></td>
                            <td><?php echo esc_html($item_name); ?></td>
                            <td>
                                <div class="variations-display">
                                    <?php foreach ($item_data['variations'] as $variation): ?>
                                        <div class="variation-entry">
                                            <?php
                                            $size_text = $variation['size'] ? esc_html($variation['size']) . ': ' : '';
                                            $quantity_text = $variation['quantity_max'] 
                                                ? sprintf('%d-%d', $variation['quantity_min'], $variation['quantity_max'])
                                                : sprintf('%d+', $variation['quantity_min']);
                                            echo $size_text . '$' . number_format($variation['price'], 2) . 
                                                 ' (' . $quantity_text . ')';
                                            if ($variation['discount']) {
                                                echo ' [' . $variation['discount'] . '% off]';
                                            }
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                            <!-- We are not supporting edit/delete at this diagnostic step -->
                            <!--
                                <button type="button" class="button edit-product" 
                                        data-product="<?php //echo esc_attr(json_encode($item_data)); ?>"
                                        data-category="<?php //echo esc_attr($category); ?>"
                                        data-item="<?php //echo esc_attr($item_name); ?>">
                                    Edit
                                </button>
                                <button type="button" class="button delete-product" 
                                        data-product-id="<?php //echo esc_attr($item_data['id']); ?>">
                                    Delete
                                </button>
                                -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Handle AJAX actions
function cc_price_list_ajax_handler() {
    check_ajax_referer('cc_price_list_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $action = $_POST['cc_action'];
    
    switch ($action) {
        case 'save_product':
          $product_data = [
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
            'item' => isset($_POST['item']) ? sanitize_text_field($_POST['item']) : '',
            'size' => isset($_POST['size']) ? sanitize_text_field($_POST['size']) : null,
            'price' => isset($_POST['price']) ? (float) $_POST['price'] : 0,
            'quantity_min' => isset($_POST['quantity_min']) ? (int) $_POST['quantity_min'] : 1,
            'quantity_max' => isset($_POST['quantity_max']) ? (int) $_POST['quantity_max'] : null,
            'discount' => isset($_POST['discount']) ? (float) $_POST['discount'] : null
          ];
          error_log('Received product data: ' . print_r($product_data, true));
          $result = cc_save_product($product_data);
          if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
          }
          wp_send_json_success(['product_id' => $result]);
        break;
            
        case 'delete_product':
            $product_id = intval($_POST['product_id']);
            $result = cc_delete_product($product_id);
            if ($result === false) {
                wp_send_json_error('Failed to delete product');
            }
            wp_send_json_success();
            break;
          //we arent supporting variations
            /*
        case 'delete_variation':
            $variation_id = intval($_POST['variation_id']);
            $result = cc_delete_variation($variation_id);
            if ($result === false) {
                wp_send_json_error('Failed to delete variation');
            }
            wp_send_json_success();
            break;
            */
        case 'get_example_csv':
            wp_send_json_success(['content' => cc_get_example_csv()]);
            break;
            
        default:
            wp_send_json_error('Invalid action');
    }
}
add_action('wp_ajax_cc_price_list_action', 'cc_price_list_ajax_handler');
