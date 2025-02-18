<?php
  2 | function update_04_show_columns() {
  3 |   global $wpdb;
  4 |   $table_name = $wpdb->prefix . 'cc_products';
  5 |     
  6 |   $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    error_log(print_r($columns, true));
  7 |   
  8 | }
  9 | 
 10 | 
 11 | update_04_show_columns();