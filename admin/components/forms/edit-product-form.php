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

// Get all variations for this product
$variations = $cc_price_list_data_handler->get_product_variations($product_id);
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
                </table>
            </div>

            <!-- Product Variations -->
            <div class="form-section" id="variations-section">
                <h2>Product Variations</h2>
                <div class="variation-type-selector">
                    <label>
                        <input type="radio" name="variation_type" value="size" 
                            <?php echo (!isset($product['quantity_min']) ? 'checked' : ''); ?>>
                        Size Variations
                    </label>
                    <label>
                        <input type="radio" name="variation_type" value="quantity"
                            <?php echo (isset($product['quantity_min']) ? 'checked' : ''); ?>>
                        Quantity Price Breaks
                    </label>
                </div>

                <div id="variations-container">
                    <!-- Template for size variation -->
                    <div class="variation-template size-variation" style="display: none;">
                        <div class="variation-row">
                            <input type="text" name="size[]" placeholder="Size (e.g., 500g, 1kg)" class="medium-text">
                            <input type="number" name="price[]" placeholder="Price" class="small-text" step="0.01">
                            <button type="button" class="button remove-variation">Remove</button>
                        </div>
                    </div>

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
                        <?php if (isset($product['quantity_min'])): ?>
                            <!-- Quantity break variations -->
                            <?php foreach ($variations as $variation): ?>
                                <div class="variation-row">
                                    <input type="number" name="quantity_min[]" value="<?php echo esc_attr($variation['quantity_min']); ?>" class="small-text">
                                    <input type="number" name="quantity_max[]" value="<?php echo esc_attr($variation['quantity_max']); ?>" class="small-text">
                                    <input type="number" name="price[]" value="<?php echo esc_attr($variation['price']); ?>" class="small-text" step="0.01">
                                    <button type="button" class="button remove-variation">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Size variations -->
                            <?php foreach ($variations as $variation): ?>
                                <div class="variation-row">
                                    <input type="text" name="size[]" value="<?php echo esc_attr($variation['size']); ?>" class="medium-text">
                                    <input type="number" name="price[]" value="<?php echo esc_attr($variation['price']); ?>" class="small-text" step="0.01">
                                    <button type="button" class="button remove-variation">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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