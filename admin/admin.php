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

// Render admin page
function cc_price_list_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
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
                'variations' => []
            ];
        }
        if ($product->variation_id) {
            $grouped_products[$product->category][$product->item]['variations'][] = [
                'id' => $product->variation_id,
                'size' => $product->size,
                'price' => $product->price,
                'quantity_min' => $product->quantity_min,
                'quantity_max' => $product->quantity_max,
                'discount' => $product->discount
            ];
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Add New Product Button -->
        <a href="#" class="page-title-action" id="add-new-product">Add New Product</a>
        
        <!-- Product Form Dialog -->
        <div id="product-form-dialog" style="display:none;">
            <form id="product-form">
                <input type="hidden" name="product_id" id="product-id">
                
                <p>
                    <label for="product-category">Category:</label>
                    <input type="text" id="product-category" name="category" required>
                </p>
                
                <p>
                    <label for="product-item">Item Name:</label>
                    <input type="text" id="product-item" name="item" required>
                </p>
                
                <div id="variations-container">
                    <h3>Variations</h3>
                    <div class="variations-list"></div>
                    <button type="button" class="button add-variation">Add Variation</button>
                </div>
                
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
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="button edit-product" 
                                        data-product="<?php echo esc_attr(json_encode($item_data)); ?>"
                                        data-category="<?php echo esc_attr($category); ?>"
                                        data-item="<?php echo esc_attr($item_name); ?>">
                                    Edit
                                </button>
                                <button type="button" class="button delete-product" 
                                        data-product-id="<?php echo esc_attr($item_data['id']); ?>">
                                    Delete
                                </button>
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
            $product_data = json_decode(stripslashes($_POST['product_data']), true);
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
            
        case 'delete_variation':
            $variation_id = intval($_POST['variation_id']);
            $result = cc_delete_variation($variation_id);
            if ($result === false) {
                wp_send_json_error('Failed to delete variation');
            }
            wp_send_json_success();
            break;
            
        default:
            wp_send_json_error('Invalid action');
    }
}
add_action('wp_ajax_cc_price_list_action', 'cc_price_list_ajax_handler');