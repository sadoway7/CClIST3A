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
    
    $products_table = $wpdb->prefix . 'cc_products';
    $variations_table = $wpdb->prefix . 'cc_product_variations';
    
    $query = "
        SELECT 
            p.category,
            p.item,
            v.size,
            v.price,
            v.quantity_min,
            v.quantity_max,
            v.discount
        FROM $products_table p
        LEFT JOIN $variations_table v ON p.id = v.product_id
        ORDER BY p.category, p.item, v.size, v.quantity_min
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
    
    $products_table = $wpdb->prefix . 'cc_products';
    $variations_table = $wpdb->prefix . 'cc_product_variations';
    
    $query = "
        SELECT 
            p.id,
            p.category,
            p.item,
            v.id as variation_id,
            v.size,
            v.price,
            v.quantity_min,
            v.quantity_max,
            v.discount
        FROM $products_table p
        LEFT JOIN $variations_table v ON p.id = v.product_id
        ORDER BY p.category, p.item, v.size, v.quantity_min
    ";
    
    return $wpdb->get_results($query);
}

/**
 * Create or update a product
 */
function cc_save_product($data) {
    global $wpdb;
    
    $products_table = $wpdb->prefix . 'cc_products';
    $variations_table = $wpdb->prefix . 'cc_product_variations';
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Insert or update product
        $product = [
            'category' => sanitize_text_field($data['category']),
            'item' => sanitize_text_field($data['item'])
        ];
        
        if (!empty($data['product_id'])) {
            $wpdb->update(
                $products_table,
                $product,
                ['id' => $data['product_id']]
            );
            $product_id = $data['product_id'];
        } else {
            $wpdb->insert($products_table, $product);
            $product_id = $wpdb->insert_id;
        }
        
        // Handle variations
        if (!empty($data['variations'])) {
            foreach ($data['variations'] as $variation) {
                $variation_data = [
                    'product_id' => $product_id,
                    'size' => sanitize_text_field($variation['size']),
                    'price' => (float) $variation['price'],
                    'quantity_min' => (int) $variation['quantity_min'],
                    'quantity_max' => !empty($variation['quantity_max']) ? (int) $variation['quantity_max'] : null,
                    'discount' => !empty($variation['discount']) ? (float) $variation['discount'] : null
                ];
                
                if (!empty($variation['id'])) {
                    $wpdb->update(
                        $variations_table,
                        $variation_data,
                        ['id' => $variation['id']]
                    );
                } else {
                    $wpdb->insert($variations_table, $variation_data);
                }
            }
        }
        
        $wpdb->query('COMMIT');
        return $product_id;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('save_failed', $e->getMessage());
    }
}

/**
 * Delete a product and its variations
 */
function cc_delete_product($product_id) {
    global $wpdb;
    
    $products_table = $wpdb->prefix . 'cc_products';
    
    return $wpdb->delete($products_table, ['id' => $product_id]);
}

/**
 * Delete a specific variation
 */
function cc_delete_variation($variation_id) {
    global $wpdb;
    
    $variations_table = $wpdb->prefix . 'cc_product_variations';
    
    return $wpdb->delete($variations_table, ['id' => $variation_id]);
}