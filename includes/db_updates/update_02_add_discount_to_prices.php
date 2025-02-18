<?php
function update_02_add_discount_to_prices() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cc_products';

    // Get all products
    $products = $wpdb->get_results("SELECT id, prices FROM $table_name", ARRAY_A);

    foreach ($products as $product) {
        $prices = unserialize($product['prices']);

        // Check if prices is an array and not empty
        if (is_array($prices) && !empty($prices)) {
            // Initialize a flag to determine if any change was made
            $updated = false;

            // Check the structure of the prices array
            if (isset($prices['price'])) {
              // Single price entry structure
              if (!isset($prices['discount'])){
                $prices['discount'] = null;
                $updated = true;
              }
            }
            else {
              // Loop through each price entry in the array
              foreach ($prices as &$price_entry) {
                if(is_array($price_entry)){
                  if (!isset($price_entry['discount'])) {
                      $price_entry['discount'] = null; // Set default discount
                      $updated = true; // Mark as updated
                  }
                }
              }
            }

            // Update the database entry only if changes were made
            if ($updated) {
                $wpdb->update(
                    $table_name,
                    array('prices' => serialize($prices)),
                    array('id' => $product['id']),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
}

update_02_add_discount_to_prices();