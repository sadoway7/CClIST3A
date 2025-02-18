<?php
/**
 * Edit Product admin page
 *
 * @package CCPriceList
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$product_id) {
    wp_die('Invalid product ID');
}

// Get product data
global $cc_price_list_data_handler;
$product = $cc_price_list_data_handler->get_product($product_id);
if (!$product) {
    wp_die('Product not found');
}

$prices = isset($product['prices']) ? $product['prices'] : [];

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html('Edit Product'); ?></h1>
    <hr class="wp-header-end">

    <form id="cc-price-list-product-form" method="post" action="">
        <?php wp_nonce_field('cc_price_list_edit_product', 'cc_price_list_nonce'); ?>
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
        
        <div class="cc-price-list-form-wrapper">
            <!-- Basic Product Information -->
            <div class="form-section">
                <h2>Basic Information</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="category">Category</label>
                        </th>
                        <td>
                            <input type="text" id="category" name="category" class="regular-text" required 
                                value="<?php echo esc_attr($product['category']); ?>">
                            <p class="description">Product category (e.g., Clay, Glaze, Tools)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="item_name">Item Name</label>
                        </th>
                        <td>
                            <input type="text" id="item_name" name="item_name" class="regular-text" required
                                value="<?php echo esc_attr($product['item_name']); ?>">
                            <p class="description">The name of the product</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="item_name">Size</label>
                        </th>
                        <td>
                            <input type="text" id="size" name="size" class="regular-text" value="<?php echo esc_attr($product['size']);?>">
                            <p class="description">Leave blank for no size variation</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Product Variations -->
            <div class="form-section" id="variations-section">
                <h2>Price Breaks</h2>
                <div id="variations-container">
                  
                    <!-- Template for quantity break -->
                    <div class="variation-template quantity-break" style="display: none;">
                        <div class="variation-row">
                            <input type="number" name="quantity_min[]" placeholder="Min Quantity" class="small-text">
                            <input type="number" name="quantity_max[]" placeholder="Max Quantity" class="small-text">
                            <input type="number" name="price[]" placeholder="Price" class="small-text" step="0.01">
                            <button type="button" class="button remove-variation">Remove</button>
                        </div>
                    </div>

                    <!-- Active variations will be inserted here -->
                    <div id="active-variations">
                        <?php if(is_array($prices)): foreach ($prices as $price_break): ?>
                            <!-- Quantity break variations -->
                           
                                <div class="variation-row">
                                    <input type="number" name="quantity_min[]" value="<?php echo esc_attr($price_break['quantity_min']); ?>" class="small-text">
                                    <input type="number" name="quantity_max[]" value="<?php echo esc_attr($price_break['quantity_max']); ?>" class="small-text">
                                    <input type="number" name="price[]" value="<?php echo esc_attr($price_break['price']); ?>" class="small-text" step="0.01">
                                    <button type="button" class="button remove-variation">Remove</button>
                                </div>
                           
                        <?php endforeach; endif; ?>
                    </div>

                    <button type="button" class="button add-variation">Add Variation</button>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Product">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cc-price-list')); ?>" class="button">Cancel</a>
            </p>
        </div>
    </form>
</div>

<style>
.cc-price-list-form-wrapper {
    max-width: 800px;
    margin-top: 20px;
}

.form-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-bottom: 20px;
}

.form-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4;
}

.variation-type-selector {
    margin-bottom: 20px;
}

.variation-type-selector label {
    margin-right: 20px;
}

.variation-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.medium-text {
    width: 200px;
}

.small-text {
    width: 100px;
}

.add-variation {
    margin-top: 10px;
}

.remove-variation {
    color: #a00;
}

.remove-variation:hover {
    color: #dc3232;
}
</style>