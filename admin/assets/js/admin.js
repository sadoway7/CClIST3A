jQuery(document).ready(function($) {
    'use strict';

    // Cache DOM elements
    const container = $('.wp-list-table');
    const filterCategory = $('#filter_category');
    const searchItem = $('#search_item');
    const filterButton = $('#filter_button');
    const expandAllBtn = $('#expand_all');
    const collapseAllBtn = $('#collapse_all');
    const bulkActionSelect = $('select[name="bulk_action"]');
    const bulkActionButton = $('#doaction');
    
    const filterSize = $('#filter_size');
    const filterPriceMin = $('#filter_price_min');
    const filterPriceMax = $('#filter_price_max');
    const filterQuantityMin = $('#filter_quantity_min');
    const filterQuantityMax = $('#filter_quantity_max');
    

    // Add Product Form elements
    const addProductForm = $('#cc-price-list-product-form');
    // Edit product form elements
    const editProductForm = $('#cc-price-list-product-form');

    const variationTypeSelector = $('input[name="variation_type"]');
    const variationsContainer = $('#variations-container');
    const activeVariationsContainer = $('#active-variations');
    
    // Import/Export Form elements
    const importForm = $('#import-form');
    const importSubmit = $('#import_submit');
    const exportForm = $('#export-form');
    const exportSubmit = $('#export_submit');

    // Templates
    const sizeVariationTemplate = $('.variation-template.size-variation').html();
    const quantityBreakTemplate = $('.variation-template.quantity-break').html();

    // State
    let currentFilters = {
        category: '',
        search: '',
        size: '',
        price_min: '',
        price_max: '',
        quantity_min: '',
        quantity_max: ''
    };

    /**
     * Initialize the admin interface
     */
    function init() {
        bindEvents();
        initializeGroups();
        switchVariationTemplateOnLoad();
    }
    
    /**
    * Check variation type on page load and show the correct template
    */
    function switchVariationTemplateOnLoad() {
       if ($('input[name="variation_type"]:checked').val() === 'size') {
            $('.variation-template.quantity-break').hide();
            $('.variation-template.size-variation').show();
        } else if ($('input[name="variation_type"]:checked').val() === 'quantity') {
            $('.variation-template.size-variation').hide();
            $('.variation-template.quantity-break').show();
        }
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Filter events
        filterButton.on('click', handleFilter);
        searchItem.on('keypress', function(e) {
            if (e.which === 13) handleFilter();
        });

        // Group expansion events
        expandAllBtn.on('click', expandAllGroups);
        collapseAllBtn.on('click', collapseAllGroups);
        container.on('click', '.group-header', toggleGroup);

        // Add product form events
        if (addProductForm.length) {
            addProductForm.on('submit', handleAddProduct);
        }

        // Edit product form events
        if (editProductForm.length){
            editProductForm.on('submit', handleEditProduct);
        }
        
        variationTypeSelector.on('change', switchVariationTemplate);
        variationsContainer.on('click', '.add-variation', addVariation);
        variationsContainer.on('click', '.remove-variation', removeVariation);

        // Bulk delete confirmation
        $(document).on('click', '.delete-action', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
        
        // Import/Export events
        if(importForm.length){
            importSubmit.on('click', handleImport);
        }
        if(exportForm.length){
            exportSubmit.on('click', handleExport);
        }

        // Initialize tooltips
        $('.cc-price-list-tooltip').tooltip();
    }

    /**
     * Initialize group display state
     */
    function initializeGroups() {
        // Initially hide all variation rows
        $('.variation-row').addClass('hidden');
        
        // Show variations for initially expanded groups
        $('.group-header:not(.collapsed)').each(function() {
            showGroupVariations($(this).closest('.group-row'));
        });
    }

    /**
     * Toggle a product group's expansion state
     * @param {Event} e 
     */
    function toggleGroup(e) {
        const $groupRow = $(this).closest('.group-row');
        const $icon = $groupRow.find('.toggle-group');
        const isCollapsed = $groupRow.hasClass('collapsed');

        if (isCollapsed) {
            $groupRow.removeClass('collapsed');
            $icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            showGroupVariations($groupRow);
        } else {
            $groupRow.addClass('collapsed');
            $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            hideGroupVariations($groupRow);
        }
    }

    /**
     * Show variations for a group
     * @param {jQuery} $groupRow 
     */
    function showGroupVariations($groupRow) {
        const groupId = $groupRow.data('group-id');
        $(`.variation-row[data-group-id="${groupId}"]`).removeClass('hidden');
    }

    /**
     * Hide variations for a group
     * @param {jQuery} $groupRow 
     */
    function hideGroupVariations($groupRow) {
        const groupId = $groupRow.data('group-id');
        $(`.variation-row[data-group-id="${groupId}"]`).addClass('hidden');
    }

    /**
     * Expand all product groups
     */
    function expandAllGroups() {
        $('.group-row').removeClass('collapsed')
            .find('.toggle-group')
            .removeClass('dashicons-arrow-right')
            .addClass('dashicons-arrow-down');
        $('.variation-row').removeClass('hidden');
    }

    /**
     * Collapse all product groups
     */
    function collapseAllGroups() {
        $('.group-row').addClass('collapsed')
            .find('.toggle-group')
            .removeClass('dashicons-arrow-down')
            .addClass('dashicons-arrow-right');
        $('.variation-row').addClass('hidden');
    }

    /**
     * Handle filter button click
     */
   function handleFilter() {
        const params = new URLSearchParams(window.location.search);
        params.set('category', filterCategory.val());
        params.set('s', searchItem.val());
        params.set('size', filterSize.val());
        params.set('price_min', filterPriceMin.val());
        params.set('price_max', filterPriceMax.val());
        params.set('quantity_min', filterQuantityMin.val());
        params.set('quantity_max', filterQuantityMax.val());
        window.location.search = params.toString();
    }

    // ------ Add Product Form Functionality ------

    /**
     * Handle add product form submission
     * @param {Event} e 
     */
    function handleAddProduct(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        const productData = {
            action: 'add_product',
            nonce: ccPriceList.nonce
        };

        // Convert form data to object
        formData.forEach(field => {
            productData[field.name] = field.value;
        });

        // Add variations data
        const variationType = $('input[name="variation_type"]:checked').val();

        if (variationType === 'size') {
            productData.size = [];
            productData.price = [];
            $('.variation-row', activeVariationsContainer).each(function() {
                productData.size.push($('input[name="size[]"]', this).val());
                productData.price.push($('input[name="price[]"]', this).val());
            });
        } else if(variationType === 'quantity'){
            productData.quantity_min = [];
            productData.quantity_max = [];
            productData.price = [];
            $('.variation-row', activeVariationsContainer).each(function() {
                productData.quantity_min.push($('input[name="quantity_min[]"]', this).val());
                productData.quantity_max.push($('input[name="quantity_max[]"]', this).val());
                productData.price.push($('input[name="price[]"]', this).val());
            });
        }
        
        $.ajax({
            url: ccPriceList.ajaxUrl,
            method: 'POST',
            data: productData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    addProductForm[0].reset();
                    activeVariationsContainer.html('');
                    setTimeout(() => {
                        window.location.href = ccPriceList.adminUrl + '?page=cc-price-list';
                    }, 1500);
                } else {
                    showFormError(response.data.message);
                }
            },
            error: function() {
                showFormError('Failed to add product. Please try again.');
            }
        });
    }

    /**
    * Handle edit product form submission
    */
    function handleEditProduct(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        
        const productData = {
            action: 'edit_product',
            nonce: ccPriceList.nonce
        };

        // Convert form data to object
        formData.forEach(field => {
            productData[field.name] = field.value;
        });

        // Add variations data
        const variationType = $('input[name="variation_type"]:checked').val();
        if (variationType === 'size') {
            productData.size = [];
            productData.price = [];
            $('.variation-row', activeVariationsContainer).each(function() {
                productData.size.push($('input[name="size[]"]', this).val());
                productData.price.push($('input[name="price[]"]', this).val());
            });
        } else if (variationType === 'quantity') {
            productData.quantity_min = [];
            productData.quantity_max = [];
            productData.price = [];
            $('.variation-row', activeVariationsContainer).each(function() {
                productData.quantity_min.push($('input[name="quantity_min[]"]', this).val());
                productData.quantity_max.push($('input[name="quantity_max[]"]', this).val());
                productData.price.push($('input[name="price[]"]', this).val());
            });
        }
        $.ajax({
            url: ccPriceList.ajaxUrl,
            method: 'POST',
            data: productData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    setTimeout(function(){
                        window.location.href = ccPriceList.adminUrl + '?page=cc-price-list';
                    }, 1500)
                } else {
                    showFormError(response.data.message);
                }
            },
            error: function() {
                showFormError('Failed to update product. Please try again.');
            }
        });
    }

    /**
     * Switch variation template based on selection
     */
    function switchVariationTemplate() {
        const selectedType = $(this).val();
        activeVariationsContainer.html(''); // Clear current variations

        if (selectedType === 'size') {
            $('.variation-template.quantity-break').hide();
            $('.variation-template.size-variation').show();
        } else {
            $('.variation-template.size-variation').hide();
            $('.variation-template.quantity-break').show();
        }
    }

    /**
     * Add variation row
     */
    function addVariation() {
        const selectedType = $('input[name="variation_type"]:checked').val();
        let template = '';
        if (selectedType === 'size') {
           template = sizeVariationTemplate;
        } else {
            template = quantityBreakTemplate;
        }

        activeVariationsContainer.append(template);
    }

    /**
     * Remove variation row
     */
    function removeVariation() {
        $(this).closest('.variation-row').remove();
    }
    
    /**
    * Handle import products
    */
    function handleImport(e){
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'import_products');
        formData.append('nonce', ccPriceList.nonce);
        formData.append('import_file', $('#import_file')[0].files[0]);
        
        $.ajax({
            url: ccPriceList.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response){
                if(response.success){
                    showSuccess(response.data.message);
                } else {
                    showFormError(response.data.message)
                }
            },
            error: function(){
                showFormError('Import failed. Please check the file and try again.')
            }
        })
    }
    
    /**
    * Handle export products
    */
    function handleExport(e){
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'export_products');
        formData.append('nonce', ccPriceList.nonce);
        
         $.ajax({
            url: ccPriceList.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response){
               if (response instanceof Blob) {
                    // Create a blob URL
                    const blobUrl = URL.createObjectURL(response);

                    // Create a link and trigger the download
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = 'cc-price-list-export.csv';
                    document.body.appendChild(link);
                    link.click();

                    // Clean up
                    document.body.removeChild(link);
                    URL.revokeObjectURL(blobUrl);
                    showSuccess('Export successful!');
                } else if (response && response.success === false) {
                    // Check for a direct error message
                    showFormError(response.data.message);
                } else {
                    // Generic error
                    showFormError('Export failed. Please try again.');
                }
            },
            error: function(){
                showFormError('Export failed. Please try again.')
            }
        })
    }

    /**
     * Show success message
     * @param {string} message 
     */
    function showSuccess(message) {
        $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>')
            .insertBefore(editProductForm.length ? editProductForm : (addProductForm.length ? addProductForm : importForm))
            .delay(5000)
            .fadeOut(function() {
                $(this).remove();
            });
    }

    /**
     * Show error message on add product form
     * @param {string} message 
     */
    function showFormError(message) {
        $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>')
            .insertBefore(editProductForm.length ? editProductForm : (addProductForm.length ? addProductForm : importForm));
    }

    // Initialize the admin interface
    init();
});