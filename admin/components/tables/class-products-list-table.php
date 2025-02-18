<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CC_Price_List_Products_Table extends WP_List_Table {
     private $data_handler;

    public function __construct($data_handler) {
        parent::__construct([
            'singular' => 'product',
            'plural'   => 'products',
            'ajax'     => false
        ]);

        $this->data_handler = $data_handler;
    }

    public function get_columns() {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'item_name'   => __('Item Name', 'cc-price-list'),
            'category'    => __('Category', 'cc-price-list'),
            'price'       => __('Price/Size/Quantity', 'cc-price-list'),
            'actions'     => __('Actions', 'cc-price-list')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'item_name'  => array('item_name', true),
            'category'   => array('category', false),
           // 'size'       => array('size', false), //Removed as per combined column
           // 'price'      => array('price', false)  //Removed as per combined column
        );
        return $sortable_columns;
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'item_name':
            case 'category':
            //case 'size': //Removed as per combined column
                return esc_html($item[$column_name]);
            case 'price': //Now handles all variation output
                return $this->format_price_display($item);
           // case 'quantity': //Removed as per combined column
           //     return $this->format_quantity_display($item);
            case 'actions':
                return $this->get_row_actions($item);
            default:
                return print_r($item, true); //Show all the item info for debug purposes
        }
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="products[]" value="%s" />',
            $item['id']
        );
    }

  protected function format_price_display($item) {
    if (isset($item['prices']) && is_array($item['prices'])) {
        $price_display = '';
        $size = esc_html($item['size']);
        
        if ($size) {
            $price_display .="<strong>Size: {$size}</strong><br>";
        }

        foreach ($item['prices'] as $price_break) {
            $price_display .= sprintf(
                '<div class="price-break">%s: $%.2f</div>',
                $this->format_quantity_range($price_break['quantity_min'], $price_break['quantity_max']),
                $price_break['price']
            );
        }
        return $price_display;
      }
    return '';
  }
    

    protected function format_quantity_range($min, $max) {
        if ($max) {
            return sprintf('%d-%d', $min, $max);
        }
        return sprintf('%d+', $min);
    }

    protected function get_row_actions($item) {
        $actions = array(
            'edit' => sprintf(
                '<a href="?page=cc-price-list-edit&id=%s">%s</a>',
                $item['id'],
                __('Edit', 'cc-price-list')
            ),
            'delete' => sprintf(
                '<a href="?page=cc-price-list&action=delete&id=%s" onclick="return confirm(\'Are you sure?\')">%s</a>',
                $item['id'],
                __('Delete', 'cc-price-list')
            )
        );

        return $this->row_actions($actions);
    }

    public function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Get sort parameters
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'item_name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'asc';
        
        // Get filter parameters
        $category = isset($_REQUEST['category']) ? sanitize_text_field($_REQUEST['category']) : '';
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $size = isset($_REQUEST['size']) ? sanitize_text_field($_REQUEST['size']) : '';
        $price_min = isset($_REQUEST['price_min']) ? sanitize_text_field($_REQUEST['price_min']) : '';
        $price_max = isset($_REQUEST['price_max']) ? sanitize_text_field($_REQUEST['price_max']) : '';
        $quantity_min = isset($_REQUEST['quantity_min']) ? sanitize_text_field($_REQUEST['quantity_min']) : '';
        $quantity_max = isset($_REQUEST['quantity_max']) ? sanitize_text_field($_REQUEST['quantity_max']) : '';

        // Get products from data handler with grouping
        $products = $this->data_handler->get_products([
            'orderby' => $orderby,
            'order' => $order,
            'category' => $category,
            'search' => $search,
            'size'    => $size,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'quantity_min' => $quantity_min,
            'quantity_max' => $quantity_max,
            'per_page' => $per_page,
            'page' => $current_page
        ]);

        $total_items = $this->data_handler->get_products_count([
            'category' => $category,
            'search' => $search,
            'size'    => $size,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'quantity_min' => $quantity_min,
            'quantity_max' => $quantity_max
        ]);

        $this->items = $this->group_items($products);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );
    }

    private function group_items($items) {
      $grouped = array();

      foreach ($items as $item) {
          $item_name = $item['item_name'];
          
          // Initialize group if it doesn't exist
          if (!isset($grouped[$item_name])) {
              $grouped[$item_name] = array(
                  'item_name' => $item_name,
                  'category' => $item['category'],
                  'variations' => array(),
              );
          }
  
          // Add the variation to the group
          $grouped[$item_name]['variations'][] = array(
              'id'     => $item['id'],
              'size'   => $item['size'],
              'prices' => $item['prices'],
              
          );
      }
      
      // Flatten the grouped array to match the table structure
        $flat_array = [];

        foreach ($grouped as $group) {
            $base_info = [
              'id' => '',
              'item_name'   => $group['item_name'],
              'category'    => $group['category']
            ];
            foreach($group['variations'] as $variation){
              $new_item = $base_info;
              $new_item['id'] = $variation['id'];
              $new_item = array_merge($new_item, $variation);
              $flat_array[] = $new_item;
            }
        }
      return $flat_array;
    }

    public function display_rows() {
        $records = $this->items;
        
        foreach ($records as $item) {
            $this->display_single_row($item);
        }
    }

    private function display_single_row($item) {
      
        $class = 'variation-row';
        ?>
        <tr class="<?php echo esc_attr($class); ?>" data-variation-id="<?php echo esc_attr($item['id']); ?>">
            <td class="column-cb">
                <input type="checkbox" name="products[]" value="<?php echo esc_attr($item['id']); ?>" />
            </td>
            <td class="column-item_name"><?php echo esc_html($item['item_name']); ?></td>
            <td class="column-category"><?php echo esc_html($item['category']); ?></td>
            <td class="column-price">
                <?php 
                if (isset($item['prices'])) {
                    $size = esc_html($item['size']);
                    if($size){
                      echo "<strong>Size: {$size}</strong><br>";
                    }
                    foreach ($item['prices'] as $price_break) {
                        printf(
                            '<div class="price-break">%s: $%.2f</div>',
                            $this->format_quantity_range($price_break['quantity_min'], $price_break['quantity_max']),
                            $price_break['price']
                        );
                    }
                }
                ?>
            </td>
            
            <td class="column-actions">
                <?php
                $actions = array(
                    'edit' => sprintf(
                        '<a href="?page=cc-price-list-edit&id=%s">%s</a>',
                        $item['id'],
                        __('Edit', 'cc-price-list')
                    ),
                    'delete' => sprintf(
                        '<a href="?page=cc-price-list&action=delete&id=%s" onclick="return confirm(\'Are you sure?\')">%s</a>',
                        $item['id'],
                        __('Delete', 'cc-price-list')
                    )
                );
                echo $this->row_actions($actions);
                ?>
            </td>
        </tr>
        <?php
    }
}