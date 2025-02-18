<?php
/**
 * The core plugin class.
 *
 * @package CCPriceList
 */

/**
 * The core plugin class.
 * 
 * This is used to define internationalization, admin-specific hooks,
 * and other core functionality.
 */
class CC_Price_List {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var CC_Price_List_Loader
     */
    protected $loader;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = CC_PRICE_LIST_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/class-cc-price-list-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once CC_PRICE_LIST_PLUGIN_DIR . 'admin/class-cc-price-list-admin.php';

        /**
         * The class responsible for handling all database operations.
         */
        require_once CC_PRICE_LIST_PLUGIN_DIR . 'includes/class-cc-price-list-data-handler.php';

        $this->loader = new CC_Price_List_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new CC_Price_List_Admin($this->get_version());
        
        // Add admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Add admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Register REST API endpoints
        $this->loader->add_action('rest_api_init', $plugin_admin, 'register_rest_endpoints');

        // AJAX Handlers
        $this->loader->add_action('wp_ajax_get_products', $plugin_admin, 'ajax_get_products');
        $this->loader->add_action('wp_ajax_get_categories', $plugin_admin, 'ajax_get_categories');
        $this->loader->add_action('wp_ajax_add_product', $plugin_admin, 'ajax_add_product');
        $this->loader->add_action('wp_ajax_delete_group', $plugin_admin, 'ajax_delete_group');
        $this->loader->add_action('wp_ajax_bulk_delete_groups', $plugin_admin, 'ajax_bulk_delete_groups');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return CC_Price_List_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}