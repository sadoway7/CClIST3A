<?php
/**
 * Plugin Name: Ceramics Canada Price List
 * Description: Manages product pricing with size variations and quantity breaks, exposed via REST API
 * Version: 1.0.0
 * Author: Ceramics Canada
 * Text Domain: cc-price-list
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CC_PRICE_LIST_VERSION', '1.0.0');
define('CC_PRICE_LIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CC_PRICE_LIST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/data-handler.php';
require_once CC_PRICE_LIST_PLUGIN_DIR . 'admin/admin.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'cc_price_list_activate');

function cc_price_list_activate() {
    // Create custom database tables if needed
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $products_table = $wpdb->prefix . 'cc_products';
    $variations_table = $wpdb->prefix . 'cc_product_variations';
    
    $sql = "CREATE TABLE IF NOT EXISTS $products_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        category varchar(100) NOT NULL,
        item varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY category (category),
        KEY item (item)
    ) $charset_collate;";
    
    $sql .= "CREATE TABLE IF NOT EXISTS $variations_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        size varchar(50) DEFAULT NULL,
        price decimal(10,2) NOT NULL,
        quantity_min int NOT NULL DEFAULT 1,
        quantity_max int DEFAULT NULL,
        discount decimal(5,2) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY product_id (product_id),
        FOREIGN KEY (product_id) REFERENCES $products_table(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Initialize the plugin
function cc_price_list_init() {
    // Register REST API endpoints
    add_action('rest_api_init', function () {
        register_rest_route('cclist/v1', '/products', [
            'methods' => 'GET',
            'callback' => 'cc_get_products_for_api',
            'permission_callback' => '__return_true'
        ]);
    });
}
add_action('init', 'cc_price_list_init');

// Register admin scripts and styles
function cc_price_list_admin_enqueue($hook) {
    if ('toplevel_page_cc-price-list' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'cc-price-list-admin',
        CC_PRICE_LIST_PLUGIN_URL . 'admin/assets/css/admin.css',
        [],
        CC_PRICE_LIST_VERSION
    );
    
    wp_enqueue_script(
        'cc-price-list-admin',
        CC_PRICE_LIST_PLUGIN_URL . 'admin/assets/js/admin.js',
        ['jquery'],
        CC_PRICE_LIST_VERSION,
        true
    );
    
    wp_localize_script('cc-price-list-admin', 'ccPriceList', [
        'nonce' => wp_create_nonce('cc_price_list_nonce'),
        'ajaxUrl' => admin_url('admin-ajax.php')
    ]);
}
add_action('admin_enqueue_scripts', 'cc_price_list_admin_enqueue');