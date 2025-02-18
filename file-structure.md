# CC Price List Plugin File Structure

```
CClIST3A/
├── cc-price-list.php           # Main plugin file, handles plugin initialization and setup
├── composer.json               # Composer dependencies and project configuration
├── devplan.md                 # Development planning and roadmap documentation
├── todo.md                    # Task tracking and todo items
│
├── admin/                     # Admin-related functionality
│   ├── class-cc-price-list-admin.php  # Main admin class, handles admin UI and AJAX
│   │
│   ├── assets/               # Static resources for admin interface
│   │   ├── css/
│   │   │   └── admin.css    # Admin interface styling and layout
│   │   └── js/
│   │       └── admin.js     # Admin JavaScript functionality and AJAX handlers
│   │
│   ├── components/          # Reusable UI components
│   │   ├── forms/          # Form components
│   │   │   ├── add-product-form.php   # Form for adding new products
│   │   │   └── edit-product-form.php  # Form for editing existing products
│   │   │
│   │   └── tables/         # Table components
│   │       └── class-products-list-table.php  # WP_List_Table for products display
│   │
│   └── views/              # Admin page templates
│       ├── add-new-display.php       # Add new product page template
│       ├── admin-display.php         # Main admin interface template
│       ├── edit-display.php          # Edit product page template
│       └── import-export-display.php  # Import/Export functionality template
│
└── includes/               # Core plugin functionality
    ├── class-cc-price-list.php            # Main plugin class, core functionality
    ├── class-cc-price-list-activator.php  # Handles plugin activation
    ├── class-cc-price-list-deactivator.php # Handles plugin deactivation
    ├── class-cc-price-list-loader.php     # Action and filter hook loader
    ├── class-cc-price-list-data-handler.php # Database operations and data management
    │
    └── db_updates/        # Database migration scripts
        └── update_01_add_prices_column.php # Initial DB schema update

```

## Directory Structure Overview

### Root Directory
Contains the main plugin file and configuration files. The main plugin file initializes the plugin and sets up the basic infrastructure.

### Admin Directory
Houses all administration-related functionality, including the UI, forms, and AJAX handlers. Organized into:
- Components: Reusable UI elements like forms and tables
- Views: Page templates for different admin sections
- Assets: CSS and JavaScript files for admin interface

### Includes Directory
Contains core plugin functionality:
- Main plugin class and essential handlers
- Database operations and data management
- Hook loader for WordPress integration
- Database update scripts for schema changes

## File Purposes

### Main Files
- `cc-price-list.php`: Entry point of the plugin, registers hooks and initializes core functionality
- `class-cc-price-list-admin.php`: Manages all admin-related functionality and UI
- `class-cc-price-list-data-handler.php`: Handles all database operations and data management
- `class-cc-price-list.php`: Core plugin class that ties everything together

### Component Files
- `add-product-form.php`: Handles the UI and logic for adding new products
- `edit-product-form.php`: Manages product editing functionality
- `class-products-list-table.php`: Displays products in a sortable, filterable table

### Asset Files
- `admin.css`: Styles the admin interface, handles layout and visual presentation
- `admin.js`: Provides client-side functionality, AJAX interactions, and UI enhancements

### View Files
- `admin-display.php`: Main admin interface layout
- `add-new-display.php`: New product creation interface
- `edit-display.php`: Product editing interface
- `import-export-display.php`: Import/Export functionality interface

### Database Files
- `update_01_add_prices_column.php`: Initial database migration script, adds prices column

This structure follows WordPress plugin development best practices, with clear separation of concerns and modular organization. Each file has a specific purpose and fits into the overall architecture of the plugin.