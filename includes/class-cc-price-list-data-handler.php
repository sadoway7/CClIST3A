<?php
/**
 * Handles all data operations for the plugin
 *
 * @package CCPriceList
 */

/**
 * Class CC_Price_List_Data_Handler
 */
class CC_Price_List_Data_Handler {

    /**
     * The table name for products
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cc_products';
    }

    /**
     * Get all products with optional filtering
     *
     * @param array $filters Optional. Filters to apply to the query.
     * @return array
     */
    public function get_products($filters = array()) {
      global $wpdb;

      $where = array('1=1');
      $values = array();

      if (!empty($filters['category'])) {
          $where[] = 'category = %s';
          $values[] = $filters['category'];
      }

      if (!empty($filters['search'])) {
          $where[] = '(item_name LIKE %s OR category LIKE %s)';
          $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
          $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
      }
      
      if (!empty($filters['size'])) {
          $where[] = 'size = %s';
          $values[] = $filters['size'];
      }

      if (!empty($filters['price_min'])) {
          $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 2), ':', -1) AS DECIMAL(10,2)) >= %f";
          $values[] = (float) $filters['price_min'];
      }
    
      if (!empty($filters['price_max'])) {
          $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 2), ':', -1) AS DECIMAL(10,2)) <= %f";

          $values[] = (float) $filters['price_max'];
      }

      if (!empty($filters['quantity_min'])) {
        $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 4), ':', -1) as UNSIGNED) >= %d";
        $values[] = (int) $filters['quantity_min'];
    }

      if (!empty($filters['quantity_max'])) {
          $where[] = "CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 5), ':', -1), ''), '4294967295') as UNSIGNED) <= %d";
          $values[] = (int) $filters['quantity_max'];
      }
      
      $limit_clause = '';
      if (isset($filters['per_page']) && isset($filters['page'])) {
          $offset = ($filters['page'] - 1) * $filters['per_page'];
          $limit_clause = $wpdb->prepare("LIMIT %d, %d", $offset, $filters['per_page']);
      }

      $orderby_clause = 'ORDER BY item_name ASC, size ASC, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, \':\', 4), \':\', -1) as UNSIGNED) ASC';
      if (!empty($filters['orderby'])) {
        $order = (!empty($filters['order']) && in_array(strtoupper($filters['order']), ['ASC', 'DESC'])) ? strtoupper($filters['order']) : 'ASC';
        $orderby_clause = $wpdb->prepare("ORDER BY {$filters['orderby']} {$order}");
      }

      $query = "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $where) . " {$orderby_clause} {$limit_clause}";

      if (!empty($values)) {
          $query = $wpdb->prepare($query, $values);
      }

      $results = $wpdb->get_results($query, ARRAY_A);

      // Unserialize the 'prices' column
        foreach ($results as &$product) {
          if (!empty($product['prices'])) {
            $product['prices'] = unserialize($product['prices']);
          }
        }
      
      return $results;
    }
    
    /**
     * Get total number of products (for pagination)
     * @param array $filters Filters
     * @return int Total count
     */
    public function get_products_count($filters = []) {
         global $wpdb;

        $where = array('1=1');
        $values = array();

        if (!empty($filters['category'])) {
            $where[] = 'category = %s';
            $values[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(item_name LIKE %s OR category LIKE %s)';
            $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }
        if (!empty($filters['size'])) {
            $where[] = 'size = %s';
            $values[] = $filters['size'];
        }

        if (!empty($filters['price_min'])) {
          $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 2), ':', -1) AS DECIMAL(10,2)) >= %f";
          $values[] = (float) $filters['price_min'];
      }
    
        if (!empty($filters['price_max'])) {
          $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 2), ':', -1) AS DECIMAL(10,2)) <= %f";
            $values[] = (float) $filters['price_max'];
        }
    
        if (!empty($filters['quantity_min'])) {
          $where[] = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 4), ':', -1) as UNSIGNED) >= %d";
          $values[] = (int) $filters['quantity_min'];
        }

        if (!empty($filters['quantity_max'])) {
          $where[] = "CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 5), ':', -1), ''), '4294967295') as UNSIGNED) <= %d";

            $values[] = (int) $filters['quantity_max'];
        }

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE " . implode(' AND ', $where);

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
    * Get products formatted for API response.
    *
    * This function retrieves product data from the database and formats it
    * according to the required API response structure. It groups products
    * by item_name and creates a 'prices' object for variations with multiple
    * price breaks.
    * @return array The formatted array of products.
    */
   public function get_products_for_api() {
       global $wpdb;
       
       $query = "SELECT * FROM {$this->table_name} ORDER BY item_name ASC, size ASC, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(prices, ':', 4), ':', -1) as UNSIGNED) ASC";
       $results = $wpdb->get_results($query, ARRAY_A);

       if(empty($results)){
           wp_send_json_error( array( 'message' => 'No products found' ) );
       }
       
       $grouped_products = array();
       
       foreach ($results as $product) {
            $product['prices'] = unserialize($product['prices']);
           $item_name = $product['item_name'];
           $size = $product['size'];

           // Initialize item in the grouped array if not already present.
           if (!isset($grouped_products[$item_name])) {
                $grouped_products[$item_name] = array(
                    'item_name' => $item_name,
                    'variations' => array()
                );
            }

            // Check for existing size within the item
           if($size !== null){
               if (!isset($grouped_products[$item_name]['variations'][$size])) {
                   $grouped_products[$item_name]['variations'][$size] = array(
                       'category' => $product['category'],
                       'size' => $size,
                       'prices' => array() // Initialize prices array
                   );
               }
           } else {
               // If size doesn't exists, create a generic name based on min and max, if available
               if (!isset($grouped_products[$item_name]['variations']['nosize'])) {
                   $grouped_products[$item_name]['variations']['nosize'] = array(
                       'category' => $product['category'],
                       'size' => $size,
                       'prices' => array() // Initialize prices array
                   );
               }
           }

           $size_key = ($size !== null) ? $size : 'nosize';

            // Add price break to the 'prices' array
           if ($product['quantity_min'] !== null) {
                $grouped_products[$item_name]['variations'][$size_key]['prices'][] = array(
                    'price' => (float) $product['price'],
                    'quantity_min' => (int) $product['quantity_min'],
                    'quantity_max' => $product['quantity_max'] ? (int) $product['quantity_max'] : null,
                    'discount' => $product['discount'] ? (float) $product['discount'] : 0
                );
            } else {
                $grouped_products[$item_name]['variations'][$size_key]['prices'][] = [
                  'price' => (float)$product['price']
                ];
           }
       }
        // Flatten the structure and remove the size key
       $final_products = [];
       foreach($grouped_products as $item_name => $item_data){
           $item_entry = ['item_name' => $item_name];
           foreach($item_data['variations'] as $size => $variation){
                if(count($variation['prices']) == 1 && $variation['prices'][0]['quantity_min'] == 1 && $variation['prices'][0]['quantity_max'] == null){
                   $final_products[] = array(
                        'category' => $variation['category'],
                        'item_name' => $item_name,
                        'size' => $variation['size'],
                        'price' => $variation['prices'][0]['price'],
                        'quantity_min' => 1,
                        'quantity_max' => null,
                        'discount' => $variation['prices'][0]['discount']
                    );
                } else {
                    $final_products[] = array(
                       'category' => $variation['category'],
                       'item_name' => $item_name,
                       'size' => $variation['size'],
                       'prices' => $variation['prices']
                    );
                }

           }
           //$final_products[] = $item_entry;
       }

       return $final_products;
   }

    
    /**
    * Get a single product by ID
    *
    * @param int $id The product ID
    * @return array|null The product data, or null if not found
    */
    public function get_product($id) {
        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        $product = $wpdb->get_row($query, ARRAY_A);
      if (isset($product['prices'])) {
        $product['prices'] = unserialize($product['prices']);
      }      
        return $product;
    }

   
    /**
     * Group products by item name
     *
     * @param array $products Array of products from database
     * @return array
     */
    private function group_products($products) {
      $grouped = array();

      foreach ($products as $product) {
        $item_name = $product['item_name'];
        $size      = $product['size'];

        if (!isset($grouped[$item_name])) {
          $grouped[$item_name] = array(
            'item_name' => $item_name,
            'category' => $product['category'],
            'variations' => array()
          );
        }

        // Ensure 'prices' key exists and is an array
        $prices = isset($product['prices']) ? $product['prices'] : array();

        $grouped[$item_name]['variations'][] = [
            'id'          => $product['id'],
            'size'        => $size,
            'prices'      => $prices
        ];

      }

      return array_values($grouped);
    }

    /**
     * Add a new product
     *
     * @param array $data Product data
     * @return int|false The number of rows inserted, or false on error
     */
   public function add_product($data)
   {
       global $wpdb;

       $defaults = array(
           'category' => '',
           'item_name' => '',
           'size' => null,
           'prices' => null
       );

       $data = wp_parse_args($data, $defaults);

       // Prepare the 'prices' data
       $prices_data = [];

       if (is_array($data['prices'])) {
          $prices_data = $data['prices'];
       }
       
       return $wpdb->insert(
        $this->table_name,
        array(
            'category' => sanitize_text_field($data['category']),
            'item_name' => sanitize_text_field($data['item_name']),
            'size' => $data['size'] ? sanitize_text_field($data['size']) : null,
            'prices' =>  serialize($prices_data) 
        ),
        array(
            '%s', // category
            '%s', // item_name
            '%s', // size
            '%s'  // prices
        )
    );
       
       
   }

    /**
     * Update a product
     *
     * @param int   $id   Product ID
     * @param array $data Product data
     * @return int|false The number of rows updated, or false on error
     */
    public function update_product($id, $data) {
        global $wpdb;
      
        $defaults = array(
           'category' => '',
           'item_name' => '',
           'size' => null,
           'prices' => null
       );

       $data = wp_parse_args($data, $defaults);
       // Prepare the 'prices' data
       $prices_data = [];
      
       if (is_array($data['prices'])) {
          $prices_data = $data['prices'];
       }

        return $wpdb->update(
            $this->table_name,
            array(
                'category' => sanitize_text_field($data['category']),
                'item_name' => sanitize_text_field($data['item_name']),
                'size' => $data['size'] ? sanitize_text_field($data['size']) : null,
                'prices' =>  serialize($prices_data)
            ),
            array('id' => $id),
            array(
                '%s', // category
                '%s', // item_name
                '%s', // size
                '%s'  // prices
            ),
            array('%d') // id format
        );
    }

    /**
     * Delete a product
     *
     * @param int $id Product ID
     * @return int|false The number of rows deleted, or false on error
     */
    public function delete_product($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Delete all products in a group
     *
     * @param string $item_name The item name that defines the group
     * @return int|false The number of rows deleted, or false on error
     */
    public function delete_group($item_name) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array('item_name' => $item_name),
            array('%s')
        );
    }

    /**
     * Get all unique categories
     *
     * @return array
     */
    public function get_categories() {
        global $wpdb;
        $query = "SELECT DISTINCT category FROM {$this->table_name} ORDER BY category ASC";
        return $wpdb->get_col($query);
    }
    
    /**
    * Import products from array data
    *
    * @param array $data
    * @return int Number of products imported
    */
    public function import_products($data) {
        $imported_count = 0;
        foreach ($data as $row) {
            // Basic validation
            if (empty($row['category']) || empty($row['item_name'])) {
                continue; // Skip rows with missing required fields
            }

            // Handle variations based on provided data
            if (!empty($row['size'])) {
                // It's a size variation
                $this->add_product(array(
                    'category' => $row['category'],
                    'item_name' => $row['item_name'],
                    'size' => $row['size'],
                    'price' => $row['price'],
                    'quantity_min' => 1,
                    'quantity_max' => null,
                    'discount' => null
                ));
                $imported_count++;
            } elseif (isset($row['quantity_min'])) {
                // It's a quantity break
                $this->add_product(array(
                    'category' => $row['category'],
                    'item_name' => $row['item_name'],
                    'size' => null,
                    'price' => $row['price'],
                    'quantity_min' => $row['quantity_min'],
                    'quantity_max' => $row['quantity_max'],
                    'discount' => null
                ));
                $imported_count++;
            } else {
                // If neither size nor quantity is available add as a single item
                $this->add_product(array(
                   'category' => $row['category'],
                    'item_name' => $row['item_name'],
                    'size' => null,
                    'price' => $row['price'],
                    'quantity_min' => 1,
                    'quantity_max' => null,
                    'discount' => null
                ));
            }
        }

        return $imported_count;
    }
    
    
    /**
     * Export products to a CSV-ready array
     *
     * @return array
     */
    public function export_products() {
        global $wpdb;
        $query = "SELECT * FROM {$this->table_name} ORDER BY item_name ASC, size ASC, quantity_min ASC";
        $results = $wpdb->get_results($query, ARRAY_A);
    
        $export_data = array();
        $headers = array('category', 'item_name', 'size', 'price', 'quantity_min', 'quantity_max', 'discount');
        $export_data[] = $headers; // Add headers as the first row
    
        foreach ($results as $product) {
            $row = array(
                $product['category'],
                $product['item_name'],
                $product['size'],
                $product['price'],
                $product['quantity_min'],
                $product['quantity_max'],
                $product['discount']
            );
            $export_data[] = $row;
        }
        return $export_data;
    }
}