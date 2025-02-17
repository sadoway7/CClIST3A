jQuery(document).ready(function($) {
    // Variation template for adding new variations
    const variationTemplate = `
        <div class="variation-row">
            <input type="hidden" name="variation_id" value="">
            <p>
                <label>Size:</label>
                <input type="text" name="size" class="variation-size">
            </p>
            <p>
                <label>Price:</label>
                <input type="number" name="price" class="variation-price" step="0.01">
            </p>
            <p>
                <label>Min Quantity:</label>
                <input type="number" name="quantity_min" class="variation-quantity-min" value="1">
            </p>
            <p>
                <label>Max Quantity:</label>
                <input type="number" name="quantity_max" class="variation-quantity-max">
                <span class="description">Leave empty for no upper limit</span>
            </p>
            <p>
                <label>Discount (%):</label>
                <input type="number" name="discount" class="variation-discount" step="0.01" min="0" max="100">
            </p>
            <button type="button" class="button remove-variation">Remove</button>
        </div>
    `;

    // Download example CSV
    $('#download-example-csv').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cc_price_list_action',
                cc_action: 'get_example_csv',
                nonce: ccPriceList.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create blob and download
                    const blob = new Blob([response.data.content], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'price-list-example.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    alert('Failed to get example CSV');
                }
            },
            error: function() {
                alert('Failed to get example CSV');
            }
        });
    });

    // Show/hide product form dialog
    $('#add-new-product').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('#product-form-dialog').show();
    });

    $('.cancel-form').on('click', function() {
        $('#product-form-dialog').hide();
    });

    // Add new variation
    $('.add-variation').on('click', function() {
        $('.variations-list').append(variationTemplate);
    });

    // Remove variation
    $(document).on('click', '.remove-variation', function() {
        $(this).closest('.variation-row').remove();
    });

    // Edit product
    $('.edit-product').on('click', function() {
        const productData = $(this).data('product');
        const category = $(this).data('category');
        const item = $(this).data('item');

        resetForm();
        
        $('#product-id').val(productData.id);
        $('#product-category').val(category);
        $('#product-item').val(item);

        // Add existing variations
        productData.variations.forEach(variation => {
            const $variationRow = $(variationTemplate);
            $variationRow.find('[name="variation_id"]').val(variation.id);
            $variationRow.find('[name="size"]').val(variation.size);
            $variationRow.find('[name="price"]').val(variation.price);
            $variationRow.find('[name="quantity_min"]').val(variation.quantity_min);
            $variationRow.find('[name="quantity_max"]').val(variation.quantity_max);
            $variationRow.find('[name="discount"]').val(variation.discount);
            $('.variations-list').append($variationRow);
        });

        $('#product-form-dialog').show();
    });

    // Delete product
    $('.delete-product').on('click', function() {
        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        const productId = $(this).data('product-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cc_price_list_action',
                cc_action: 'delete_product',
                product_id: productId,
                nonce: ccPriceList.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to delete product: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Response:', xhr.responseText);
                alert('Failed to delete product. Please try again.');
            }
        });
    });

    // Handle form submission
    $('#product-form').on('submit', function(e) {
        e.preventDefault();

        const productData = {
            product_id: $('#product-id').val(),
            category: $('#product-category').val() || '',
            item: $('#product-item').val() || '',
            variations: []
        };

        // Collect variation data
        $('.variation-row').each(function() {
            const $row = $(this);
            const variationData = {
                id: $row.find('[name="variation_id"]').val(),
                size: $row.find('[name="size"]').val() || null,
                price: $row.find('[name="price"]').val() ? parseFloat($row.find('[name="price"]').val()) : 0,
                quantity_min: $row.find('[name="quantity_min"]').val() ? parseInt($row.find('[name="quantity_min"]').val()) : 1,
                quantity_max: $row.find('[name="quantity_max"]').val() 
                    ? parseInt($row.find('[name="quantity_max"]').val())
                    : null,
                discount: $row.find('[name="discount"]').val()
                    ? parseFloat($row.find('[name="discount"]').val())
                    : null
            };
            
            // Only add variations that have at least one field filled out
            if (variationData.size || variationData.price || variationData.quantity_min || 
                variationData.quantity_max || variationData.discount) {
                productData.variations.push(variationData);
            }
        });

        console.log('Saving product data:', productData);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cc_price_list_action',
                cc_action: 'save_product',
                product_data: JSON.stringify(productData),
                nonce: ccPriceList.nonce
            },
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to save product: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Response:', xhr.responseText);
                alert('Failed to save product. Please try again.');
            }
        });
    });

    // Filter products by category
    $('#filter-category').on('change', function() {
        const category = $(this).val();
        if (category) {
            $('.product-row').hide();
            $('.product-row[data-category="' + category + '"]').show();
        } else {
            $('.product-row').show();
        }
    });

    // Search products
    $('#search-products').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.product-row').each(function() {
            const $row = $(this);
            const itemName = $row.find('td:nth-child(2)').text().toLowerCase();
            if (itemName.includes(searchTerm)) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });

    // Helper function to reset the form
    function resetForm() {
        $('#product-form')[0].reset();
        $('#product-id').val('');
        $('.variations-list').empty();
    }

    // Add initial empty variation row for new products
    $('#add-new-product').on('click', function() {
        if ($('.variations-list').is(':empty')) {
            $('.add-variation').click();
        }
    });
});