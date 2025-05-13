# CustomKings Product Personalizer

A WordPress/WooCommerce plugin that allows customers to personalize products with a visual admin designer, live preview, and print-ready file generation.

## Author
CustomKings Personalised Gifts  
https://customkings.com.au/

## Description
This plugin enables e-commerce store customers to personalize specific products by filling in admin-defined placeholders before adding them to the cart. It features a visual admin designer, real-time preview, and generates print-ready files for order fulfillment.

## Installation
1. Upload the plugin files to the `/wp-content/plugins/customkings-product-personalizer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings via the new 'Product Personalizer' menu in the WordPress admin.

## Usage
### Admin Workflow
- Use the **Product Personalizer** menu to manage fonts, color swatches, and clipart.
- On the WooCommerce Edit Product screen, use the **Product Personalization Designer** meta box to visually design personalization templates:
  - Add text, dropdowns, color swatches, and image upload placeholders.
  - Configure properties for each placeholder (label, options, colors, etc.).
  - Save the configuration; it will be used on the product page for customers.

### Customer Workflow
- On the product page, click the **Personalize** button to open the customizer.
- Fill in text, select dropdown options, choose color swatches, and upload images as defined by the admin.
- See a live preview of your personalized product.
- Click **Apply** to attach your personalization to the product before adding to cart.

### Order Fulfillment
- Admins can view personalization data and a live preview in the WooCommerce order view.
- Generate and download print-ready files (PDF) for each order item.

## Accessibility
- All modals and forms are keyboard accessible (focus trap, ESC to close, tab order).
- ARIA roles and labels are used for dialogs and controls.
- All user-facing strings are translatable.
- Color contrast and font sizes follow accessibility best practices.

## Developer Notes
- Extend placeholder types by updating both the admin designer and frontend customizer JS.
- All major logic is modular and documented with PHPDoc/JSdoc.
- Automated tests can be added in the `tests/` directory (see below).

## Testing
- Backend: PHPUnit tests can be added in the `tests/` directory.
- Frontend: Jest or similar can be used for JS logic. To run JS tests:
  1. Install dependencies: `npm install jest` (if not already installed)
  2. Run tests: `npx jest tests/*.test.js`

## Continuous Integration (CI)
- Automated tests (PHPUnit and Jest) run on every push and pull request via GitHub Actions (see `.github/workflows/ci.yml`).
- The build must pass before merging changes.

## Deployment
- Ensure all tests pass locally and in CI before releasing.
- Tag a new release in your version control system for production deployment.
- Only commit source code and necessary assets (see `.gitignore`).

## License
GPLv2 or later 