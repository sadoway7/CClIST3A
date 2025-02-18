<?php
/**
 * Import/Export admin page
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

    <div class="cc-price-list-import-export-wrapper">
        <div class="import-section">
            <h2>Import Products</h2>
            <p>Import products from a CSV file.</p>
            <form id="import-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('cc_price_list_nonce', 'cc_price_list_nonce'); ?>
                <input type="file" name="import_file" id="import_file">
                <input type="button" id="import_submit" class="button button-primary" value="Import">
            </form>
        </div>

        <div class="export-section">
            <h2>Export Products</h2>
            <p>Export products to a CSV file.</p>
            <form id="export-form" method="post">
                <?php wp_nonce_field('cc_price_list_nonce', 'cc_price_list_nonce'); ?>
                <input type="button" id="export_submit" class="button button-primary" value="Export">
            </form>
        </div>
    </div>
</div>

<style>
.cc-price-list-import-export-wrapper {
    max-width: 800px;
    margin-top: 20px;
    display: flex;
    gap: 20px;
}

.import-section,
.export-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    flex: 1;
}

.import-section h2,
.export-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4;
}
</style>