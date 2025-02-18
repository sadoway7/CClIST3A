<?php
/**
 * CC Price List
 *
 * @package   CCPriceList
 * @author    Your Name
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: CC Price List
 * Plugin URI:  https://example.com/cc-price-list
 * Description: Manages product pricing with grouping support and REST API integration
 * Version:     1.0.5
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: cc-price-list
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: sadoway7/CClIST3A
 * GitHub Plugin URI: https://github.com/sadoway7/CClIST3A
 * Primary Branch: main
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('CC_PRICE_LIST_VERSION', '1.0.0');
define('CC_PRICE_LIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CC_PRICE_LIST_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_cc_price_list() {
    require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/class-cc-price-list-activator.php';
    CC_Price_List_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_cc_price_list() {
    require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/class-cc-price-list-deactivator.php';
    CC_Price_List_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_cc_price_list');
register_deactivation_hook(__FILE__, 'deactivate_cc_price_list');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/class-cc-price-list.php';

/**
 * Begins execution of the plugin.
 */
function run_cc_price_list() {
    $plugin = new CC_Price_List();
    $plugin->run();
}

run_cc_price_list();