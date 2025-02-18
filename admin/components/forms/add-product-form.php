<?php
/**
 * Add New Product admin page
 *
 * @package CCPriceList
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">

    <form id="cc-price-list-product-form" method="post" action="">
        <?php wp_nonce_field('cc_price_list_add_product', 'cc_price_list_nonce'); ?>
        
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
                            <input type="text" id="category" name="category" class="regular-text" required>
                            <p class="description">Product category (e.g., Clay, Glaze, Tools)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="item_name">Item Name</label>
                        </th>
                        <td>
                            <input type="text" id="item_name" name="item_name" class="regular-text" required>
                            <p class="description">The name of the product</p>
                        </td>
                    </tr>
                     <tr>
                        <th scope="row">
                            <label for="item_name">Size</label>
                        </th>
                        <td>
                            <input type="text" id="size" name="size" class="regular-text">
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
                    <div class="variation-template quantity-break">
                        <div class="variation-row">
                            <input type="number" name="quantity_min[]" placeholder="Min Quantity" class="small-text">
                            <input type="number" name="quantity_max[]" placeholder="Max Quantity" class="small-text">
                            <input type="number" name="price[]" placeholder="Price" class="small-text" step="0.01">
                            <button type="button" class="button remove-variation">Remove</button>
                        </div>
                    </div>

                    <!-- Active variations will be inserted here -->
                    <div id="active-variations"></div>

                    <button type="button" class="button add-variation">Add Price Break</button>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Product">
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