<?php
/**
 * Fired during plugin deactivation
 *
 * @package CCPriceList
 */

/**
 * Class CC_Price_List_Deactivator
 * 
 * This class defines all code necessary to run during the plugin's deactivation.
 * Note: We don't delete tables on deactivation, only on uninstall.
 */
class CC_Price_List_Deactivator {

    /**
     * Plugin deactivation handler
     *
     * Long Description.
     */
    public static function deactivate() {
        // Clear any scheduled hooks, transients, or cache if needed
        wp_clear_scheduled_hook('cc_price_list_hourly_event');
        
        // Note: We intentionally don't remove database tables here
        // Tables should only be removed on plugin uninstall, not deactivation
    }
}