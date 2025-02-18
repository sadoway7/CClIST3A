<?php

function update_03_add_discount_column() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cc_products';

    if (!column_exists($table_name, 'discount')){
        $sql = "ALTER TABLE $table_name ADD COLUMN discount DECIMAL(10, 2) NULL DEFAULT NULL AFTER size;";
        $wpdb->query($sql);
    }
}

function column_exists($table, $column){
    global $wpdb;
    $query = $wpdb->prepare("SHOW COLUMNS FROM `$table` LIKE %s", $column);
    return $wpdb->get_var($query) == $column;
}

update_03_add_discount_column();