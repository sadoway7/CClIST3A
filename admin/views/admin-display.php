<?php
/**
 * Admin display view
 *
 * @package CCPriceList
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

include_once CC_PRICE_LIST_PLUGIN_DIR . 'admin/components/tables/class-products-list-table.php';

$products_table = new CC_Price_List_Products_Table();
$products_table->prepare_items();

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <a href="<?php echo esc_url(admin_url('admin.php?page=cc-price-list-add-new')); ?>" class="page-title-action">Add New</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=cc-price-list-import-export')); ?>" class="page-title-action">Import/Export</a>
    <hr class="wp-header-end">
    
    <div class="cc-price-list-admin-content">
        <!-- Filters -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select id="filter_category">
                    <option value="">All Categories</option>
                    <?php
                    $categories = $products_table->data_handler->get_categories();
                    foreach ($categories as $category) {
                        $selected = (isset($_GET['category']) && $_GET['category'] == $category) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr($category) . '" ' . $selected . '>' . esc_html($category) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="text" id="search_item" placeholder="Search Item Name" 
                       value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">

                <input type="text" id="filter_size" placeholder="Filter by Size"
                       value="<?php echo isset($_GET['size']) ? esc_attr($_GET['size']) : ''; ?>">

                <input type="number" id="filter_price_min" placeholder="Min Price" step="0.01"
                       value="<?php echo isset($_GET['price_min']) ? esc_attr($_GET['price_min']) : ''; ?>">
                <input type="number" id="filter_price_max" placeholder="Max Price" step="0.01"
                       value="<?php echo isset($_GET['price_max']) ? esc_attr($_GET['price_max']) : ''; ?>">
                
                <input type="number" id="filter_quantity_min" placeholder="Min Quantity"
                       value="<?php echo isset($_GET['quantity_min']) ? esc_attr($_GET['quantity_min']) : ''; ?>">

                <input type="number" id="filter_quantity_max" placeholder="Max Quantity"
                       value="<?php echo isset($_GET['quantity_max']) ? esc_attr($_GET['quantity_max']) : ''; ?>">

                <button type="button" id="filter_button" class="button">Filter</button>
                <a href="<?php echo admin_url('admin.php?page=cc-price-list')?>" class="button">Clear</a>
            </div>
            
            <div class="tablenav-pages">
                <?php $products_table->pagination('top'); ?>
            </div>
        </div>

        <!-- Products Table -->
        <form method="get">
           <input type="hidden" name="page" value="cc-price-list">
            <?php
            
            $products_table->display();
            ?>
        </form>
        
        <!-- Bulk Actions -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action" id="bulk_action">
                    <option value="-1">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="button" id="doaction" class="button action">Apply</button>
            </div>
             <div class="tablenav-pages">
                <?php $products_table->pagination('bottom'); ?>
            </div>
        </div>
        
        <!--  Expand/Collapse Buttons -->
        <div class="alignright actions">
            <button type="button" id="expand_all" class="button">Expand All</button>
            <button type="button" id="collapse_all" class="button">Collapse All</button>
        </div>
    </div>
</div>