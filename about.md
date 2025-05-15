# About CustomKings Product Personalizer

## Introduction

CustomKings Product Personalizer is a WordPress/WooCommerce plugin developed for CustomKings Personalised Gifts (https://customkings.com.au/). The plugin enables e-commerce store owners to offer product personalization capabilities to their customers. It features a visual admin designer for creating personalization templates, a frontend customizer for customers to personalize products, and tools for generating print-ready files for order fulfillment.

The plugin is currently at version 1.1.0, with ongoing development for additional features and improvements.

## Core Features

### Admin Features

1. **Visual Designer**: A comprehensive design tool that allows admins to:
   - Create personalization templates with various design elements
   - Define placeholders for customer input (text, images, colors, choices)
   - Set properties for each element (position, size, font, color, etc.)
   - Manage layers with drag-and-drop reordering
   - Configure visibility settings for preview and print outputs
   - Save designs as templates for reuse

2. **Asset Management**: Tools for managing:
   - Fonts: Upload and manage custom fonts for use in designs
   - Clipart: Upload, categorize, and manage clipart elements
   - Color Swatches: Create and manage color palettes for customer selection

3. **Order Management**: Features for handling personalized orders:
   - View personalization data in the admin order screen
   - Generate print-ready files (PDF) for production
   - Download and manage print files

### Customer Features

1. **Product Personalization**: Interface for customers to:
   - Input text in defined text placeholders
   - Upload images for image placeholders
   - Select colors from predefined swatches
   - Choose options from dropdowns or radio buttons

2. **Live Preview**: Real-time visual preview showing how personalization choices affect the product

3. **Cart & Checkout Integration**: Personalization data is:
   - Saved with cart items
   - Displayed in cart and checkout summaries
   - Editable before payment is completed

## Technical Architecture

### Backend (PHP)

- Built on WordPress and WooCommerce frameworks
- Uses custom database tables for fonts, clipart, and color palettes
- Stores product configuration as JSON in product meta
- Stores personalization data as JSON in order item meta
- Implements WordPress REST API endpoints for frontend-backend communication
- Uses TCPDF for generating print-ready PDF files

### Frontend (JavaScript)

- Uses Fabric.js for canvas manipulation in both admin designer and frontend preview
- Implements dynamic form generation based on placeholder types
- Features real-time preview updates as customers input data
- Ensures accessibility with ARIA attributes and keyboard navigation

## Key Components

### 1. Admin UI (class-admin-ui.php)
Handles the main plugin settings interface, including tabs for global settings, fonts, clipart, and product assignments.

### 2. Fonts Management (class-fonts.php)
Manages font uploads, storage, and retrieval. Creates a custom database table for fonts and provides methods for CRUD operations.

### 3. Clipart Management (class-clipart.php)
Handles clipart uploads, categorization, and management. Creates custom database tables for clipart and tags.

### 4. Product Designer (class-product-designer.php)
Implements the visual designer for creating personalization templates. Registers a custom post type for designs and provides tools for creating, editing, and managing designs.

### 5. Frontend Customizer (class-frontend-customizer.php)
Handles the customer-facing personalization interface. Generates dynamic forms based on the product configuration, renders the live preview, and manages personalization data.

### 6. JavaScript Components
- **designer.js**: Powers the admin visual designer interface
- **customizer.js**: Handles the frontend personalization experience

## User Workflows

### Admin Workflow

1. **Setup**: Configure global settings, upload fonts and clipart
2. **Design Creation**: Use the visual designer to create personalization templates
   - Add text, image, and other placeholders
   - Configure properties for each element
   - Set visibility for preview and print
   - Save the design
3. **Product Assignment**: Assign designs to specific WooCommerce products
4. **Order Fulfillment**: View personalization data in orders and generate print-ready files

### Customer Workflow

1. **Product Selection**: Browse products with personalization options
2. **Personalization**: Click "Personalize" button to open the customizer
   - Input text, upload images, select colors/options
   - See real-time preview of personalized product
3. **Add to Cart**: Apply personalization and add to cart
4. **Review & Edit**: View personalization in cart/checkout, edit if needed before payment
5. **Purchase**: Complete checkout with personalization data attached to order

## Development Status

The plugin is currently at version 1.1.0 with the following recent additions:

- Image placeholders: Customers can upload images that fill defined areas
- Improved layer management with drag-and-drop reordering
- Enhanced keyboard accessibility
- Consistent naming and labeling for input fields
- Bug fixes and UX improvements for live preview and validation

Planned features and improvements include:

- Grouping/ungrouping of elements in the designer
- Multi-select and arrangement tools (align, distribute)
- View mode switcher (Preview vs. Print Layout)
- "Save as Template"/"Load Template" functionality
- "Edit Customisation" link in cart/checkout
- Full vector rendering in generated PDFs
- Performance optimizations
- Accessibility and internationalization improvements

## Technical Considerations

### Performance

The plugin is designed with performance in mind:
- Efficient asset loading with conditional scripts and styles
- Optimized database queries
- Canvas rendering optimizations

### Security

Security measures include:
- WordPress nonces for all form submissions and AJAX actions
- Input sanitization and output escaping
- User capability checks for admin actions
- Secure file upload handling

### Accessibility

The plugin aims for WCAG 2.1 AA compliance:
- ARIA roles and attributes for dynamic content
- Keyboard navigation support
- Focus management for modals
- Color contrast considerations

### Internationalization

All user-facing strings are translatable using WordPress standards.

## Conclusion

CustomKings Product Personalizer is a comprehensive solution for WooCommerce stores that want to offer product personalization capabilities. It provides a powerful admin designer for creating templates and a user-friendly frontend interface for customers to personalize products. The plugin is actively developed with ongoing improvements and new features.