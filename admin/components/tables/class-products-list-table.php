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
            'size'        => __('Size', 'cc-price-list'),
            'price'       => __('Price', 'cc-price-list'),
            'quantity'    => __('Quantity', 'cc-price-list'),
            'actions'     => __('Actions', 'cc-price-list')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'item_name'  => array('item_name', true),
            'category'   => array('category', false),
            'size'       => array('size', false),
            'price'      => array('price', false)
        );
        return $sortable_columns;
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'item_name':
            case 'category':
            case 'size':
                return esc_html($item[$column_name]);
            case 'price':
                return $this->format_price_display($item);
            case 'quantity':
                return $this->format_quantity_display($item);
            case 'actions':
                return $this->get_row_actions($item);
            default:
                return print_r($item, true);
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
            foreach ($item['prices'] as $price_break) {
                $price_display .= sprintf(
                    '<div class="price-break">%s: $%.2f</div>',
                    $this->format_quantity_range($price_break['quantity_min'], $price_break['quantity_max']),
                    $price_break['price']
                );
            }
            return $price_display;
        }
        return sprintf('$%.2f', $item['price']);
    }

    protected function format_quantity_display($item) {
        if (isset($item['quantity_min'])) {
            return $this->format_quantity_range($item['quantity_min'], $item['quantity_max']);
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
        $current_group = null;
        
        foreach ($items as $item) {
            if ($current_group === null || $current_group['item_name'] !== $item['item_name']) {
                if ($current_group !== null) {
                    $grouped[] = $current_group;
                }
                $current_group = array(
                    'id' => $item['id'],
                    'item_name' => $item['item_name'],
                    'category' => $item['category'],
                    'variations' => array()
                );
            }
            
            $current_group['variations'][] = array(
                'id' => $item['id'],
                'size' => $item['size'],
                'price' => $item['price'],
                'quantity_min' => $item['quantity_min'],
                'quantity_max' => $item['quantity_max'],
                'prices' => isset($item['prices']) ? $item['prices'] : null
            );
        }
        
        if ($current_group !== null) {
            $grouped[] = $current_group;
        }
        
        return $grouped;
    }

    public function display_rows() {
        $records = $this->items;
        
        foreach ($records as $group) {
            $this->display_group_row($group);
        }
    }

    private function display_group_row($group) {
        $class = 'group-row';
        ?>
        <tr class="<?php echo esc_attr($class); ?>" data-group-id="<?php echo esc_attr($group['id']); ?>">
            <td colspan="<?php echo count($this->get_columns()); ?>">
                <div class="group-header">
                    <span class="toggle-group dashicons dashicons-arrow-down"></span>
                    <strong><?php echo esc_html($group['item_name']); ?></strong>
                    <span class="group-category"><?php echo esc_html($group['category']); ?></span>
                </div>
            </td>
        </tr>
        <?php
        foreach ($group['variations'] as $variation) {
            $this->display_variation_row($variation, $group['item_name'], $group['category']);
        }
    }

    private function display_variation_row($variation, $item_name, $category) {
        $class = 'variation-row';
        ?>
        <tr class="<?php echo esc_attr($class); ?>" data-variation-id="<?php echo esc_attr($variation['id']); ?>">
            <td class="column-cb">
                <input type="checkbox" name="products[]" value="<?php echo esc_attr($variation['id']); ?>" />
            </td>
            <td class="column-item_name"><?php echo esc_html($item_name); ?></td>
            <td class="column-category"><?php echo esc_html($category); ?></td>
            <td class="column-size size-highlight"><?php echo esc_html($variation['size']); ?></td>
            <td class="column-price">
                <?php 
                if (isset($variation['prices'])) {
                    foreach ($variation['prices'] as $price_break) {
                        printf(
                            '<div class="price-break">%s: $%.2f</div>',
                            $this->format_quantity_range($price_break['quantity_min'], $price_break['quantity_max']),
                            $price_break['price']
                        );
                    }
                } else {
                    printf('$%.2f', $variation['price']);
                }
                ?>
            </td>
            <td class="column-quantity">
                <?php echo $this->format_quantity_range($variation['quantity_min'], $variation['quantity_max']); ?>
            </td>
            <td class="column-actions">
                <?php
                $actions = array(
                    'edit' => sprintf(
                        '<a href="?page=cc-price-list-edit&id=%s">%s</a>',
                        $variation['id'],
                        __('Edit', 'cc-price-list')
                    ),
                    'delete' => sprintf(
                        '<a href="?page=cc-price-list&action=delete&id=%s" onclick="return confirm(\'Are you sure?\')">%s</a>',
                        $variation['id'],
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