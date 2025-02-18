<?php
/**
 * Fired during plugin activation
 *
 * @package CCPriceList
 */

/**
 * Class CC_Price_List_Activator
 * 
 * This class defines all code necessary to run during the plugin's activation.
 */
class CC_Price_List_Activator {

    /**
     * Create the necessary database tables for the plugin
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Products table
        $table_name = $wpdb->prefix . 'cc_products';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            category varchar(100) NOT NULL,
            item_name varchar(200) NOT NULL,
            size varchar(50) DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            quantity_min int NOT NULL DEFAULT 1,
            quantity_max int DEFAULT NULL,
            discount decimal(5,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY item_name (item_name),
            KEY category (category)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set version in options
        add_option('cc_price_list_version', CC_PRICE_LIST_VERSION);

        // Include and run migrations
        include_once( plugin_dir_path( __FILE__ ) . 'db_updates/update_01_add_prices_column.php');
        update_01_add_prices_column();
    }
}