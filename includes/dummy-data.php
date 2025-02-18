<?php

function cc_price_list_load_dummy_data() {
    // Instantiate the data handler.  Assumes this function is called within a context where
    // the CC_Price_List_Data_Handler class is available.
    $data_handler = new CC_Price_List_Data_Handler();

    // Add some sample products.
    $data_handler->add_product(array(
        'category' => 'Clay',
        'item_name' => 'Porcelain',
        'prices' => array(
            array('size' => '2.5lb', 'quantity_min' => 1, 'quantity_max' => 9, 'price' => 12.50, 'discount' => 0),
            array('size' => '2.5lb', 'quantity_min' => 10, 'quantity_max' => null, 'price' => 10.00, 'discount' => 0),
        )
    ));

     $data_handler->add_product(array(
        'category' => 'Clay',
        'item_name' => 'Stoneware',
        'prices' => array(
            array('size' => '25lb', 'quantity_min' => 1, 'quantity_max' => 4, 'price' => 25.00, 'discount' => 0),
            array('size' => '25lb', 'quantity_min' => 5, 'quantity_max' => 9, 'price' => 22.00, 'discount' => 5),
             array('size' => '25lb', 'quantity_min' => 10, 'quantity_max' => null, 'price' => 20.00, 'discount' => 10)
        )
    ));

    $data_handler->add_product(array(
        'category' => 'Glaze',
        'item_name' => 'Clear Gloss',
        'prices' => array(
            array('size' => 'Pint', 'quantity_min' => 1, 'quantity_max' => 3, 'price' => 18.00, 'discount' => 0),
            array('size' => 'Pint', 'quantity_min' => 4, 'quantity_max' => null, 'price' => 15.00, 'discount' => 0),
             array('size' => 'Gallon', 'quantity_min' => 1, 'quantity_max' => null, 'price' => 60.00, 'discount' => 0)
        )
    ));

      $data_handler->add_product(array(
        'category' => 'Glaze',
        'item_name' => 'Matt White',
        'prices' => array(
            array('size' => 'Pint', 'quantity_min' => 1, 'quantity_max' => 5, 'price' => 16.00, 'discount' => 2.5),
            array('size' => 'Pint', 'quantity_min' => 6, 'quantity_max' => null, 'price' => 14.00, 'discount' => 5)
        )
    ));

    $data_handler->add_product(array(
        'category' => 'Tools',
        'item_name' => 'Ribbon Tool Set',
        'prices' => array(
            array('size' => null, 'quantity_min' => 1, 'quantity_max' => null, 'price' => 14.99, 'discount' => 0)
        )
    ));
}