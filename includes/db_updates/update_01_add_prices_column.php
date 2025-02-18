<?php
function update_01_add_prices_column() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'cc_products';
    
    if (!column_exists($table_name, 'prices')){
        $sql = "ALTER TABLE $table_name ADD COLUMN prices TEXT DEFAULT NULL AFTER discount;";
        $wpdb->query($sql);

        // Convert existing price and quantity data to new prices format for each product
        $products = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        foreach($products as $product){
            $prices = [];
            //If it's a quantity variation:
            if ($product['quantity_min'] != 1 || $product['quantity_max'] != null){
                $prices[] = [
                    'quantity_min' => (int) $product['quantity_min'],
                    'quantity_max' => $product['quantity_max'] ? (int) $product['quantity_max'] : null,
                    'price' => (float) $product['price'],
                    'discount' => $product['discount'] ? (float) $product['discount'] : null
                ];
            } else {
                $prices[] = [
                  'price' => (float)$product['price']
                ];
            }
            $wpdb->update($table_name, ['prices' => serialize($prices)], ['id' => $product['id']]);

        }

        //Drop old columns
        $sql = "ALTER TABLE $table_name DROP COLUMN price, DROP COLUMN quantity_min, DROP COLUMN quantity_max, DROP COLUMN discount;";
        $wpdb->query($sql);
    }
}

function column_exists($table, $column){
    global $wpdb;
    $query = $wpdb->prepare("SHOW COLUMNS FROM `$table` LIKE %s", $column);
    return $wpdb->get_var($query) == $column;
}

update_01_add_prices_column();