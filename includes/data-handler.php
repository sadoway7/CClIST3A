<?php
/**
 * Handles database operations and REST API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get products for REST API response
 */
function cc_get_products_for_api(\WP_REST_Request $request) {
    global $wpdb;

    // TEMPORARY: Using old plugin's table name for debugging
    $products_table = $wpdb->prefix . 'cclist2a_products';
    
    $query = "
        SELECT 
            category,
            item,
            size,
            price,
            quantity_min,
            quantity_max,
            discount
        FROM $products_table
        ORDER BY category, item, size, quantity_min
    ";
    
    $results = $wpdb->get_results($query);
    
    if (!$results) {
        return new WP_REST_Response([], 200);
    }
    
    $formatted_results = array_map(function($row) {
        return [
            'category' => $row->category,
            'item' => $row->item,
            'size' => $row->size,
            'price' => (float) $row->price,
            'quantity_min' => (int) $row->quantity_min,
            'quantity_max' => $row->quantity_max ? (int) $row->quantity_max : null,
            'discount' => $row->discount ? (float) $row->discount : null
        ];
    }, $results);
    
    return new WP_REST_Response($formatted_results, 200);
}

/**
 * Get all products for admin display
 */
function cc_get_all_products() {
    global $wpdb;
    
    // TEMPORARY: Using old plugin's table name for debugging
    $products_table = $wpdb->prefix . 'cclist2a_products';
    
    $query = "
        SELECT 
            id,
            category,
            item,
            size,
            price,
            quantity_min,
            quantity_max,
            discount
        FROM $products_table
        ORDER BY category, item, size, quantity_min
    ";
    
    return $wpdb->get_results($query);
}

/**
 * Create or update a product
 */
function cc_save_product($data) {
    global $wpdb;
    
    // TEMPORARY: Using old plugin's table name for debugging
    $products_table = $wpdb->prefix . 'cclist2a_products';
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Insert or update product
        $product = [
            'category' => isset($data['category']) ? sanitize_text_field($data['category']) : '',
            'item' => isset($data['item']) ? sanitize_text_field($data['item']) : ''
        ];
        
        if (!empty($data['product_id'])) {
          //We dont have an update because we are using the old plugins db
            /*$wpdb->update(
                $products_table,
                $product,
                ['id' => $data['product_id']]
            );
            $product_id = $data['product_id'];
            */
        } else {
            $wpdb->insert($products_table, $product);
            $product_id = $wpdb->insert_id;
        }
        
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }

        // Handle variations
        if (!empty($data['variations']) && is_array($data['variations'])) {
            foreach ($data['variations'] as $variation) {
                $variation_data = [
                    'category' => $product['category'], // Use the main product's category
                    'item' => $product['item'],       // Use the main product's item
                    'size' => isset($variation['size']) ? sanitize_text_field($variation['size']) : null,
                    'price' => isset($variation['price']) ? (float) $variation['price'] : 0,
                    'quantity_min' => isset($variation['quantity_min']) ? (int) $variation['quantity_min'] : 1,
                    'quantity_max' => isset($variation['quantity_max']) ? (int) $variation['quantity_max'] : null,
                    'discount' => isset($variation['discount']) ? (float) $variation['discount'] : null
                ];
                
                if (!empty($variation['id'])) {
                    //we dont have an update - use insert
                    /*
                    $wpdb->update(
                        $products_table,
                        $variation_data,
                        ['id' => $variation['id']]
                    );
                    */
                     $wpdb->insert($products_table, $variation_data);
                } else {
                    $wpdb->insert($products_table, $variation_data);
                }
                
                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            }
        }
        
        $wpdb->query('COMMIT');
        return isset($product_id) ? $product_id : true;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('CC Price List Error: ' . $e->getMessage());
        return new WP_Error('save_failed', $e->getMessage());
    }
}
/**
 * Adds a category to the categories table if it does not already exist.
 *
 * @param string $category The category name to add.
 */
function add_category_if_not_exists($category){
    global $wpdb;
    $table_categories = $wpdb->prefix . 'cclist2a_categories'; // Using the old plugin's categories table
    if( !$wpdb->get_row("SELECT * FROM $table_categories WHERE category_name = '" . $category . "'") ){
        $wpdb->insert($table_categories, array('category_name' => $category));
        error_log("Adding category: " . $category);
    }
}
/**
 * Delete a product and its variations
 */
function cc_delete_product($product_id) {
    global $wpdb;
    
    // TEMPORARY: Using old plugin's table name for debugging
    $products_table = $wpdb->prefix . 'cclist2a_products';
    
    return $wpdb->delete($products_table, ['id' => $product_id]);
}