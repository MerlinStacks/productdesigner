# CustomKings Product Personalizer

A WordPress/WooCommerce plugin that allows customers to personalize products with a visual admin designer, live preview, and print-ready file generation.

## Author
CustomKings Personalised Gifts  
https://customkings.com.au/

## Description
This plugin enables e-commerce store customers to personalize specific products by filling in admin-defined placeholders before adding them to the cart. It features a visual admin designer, real-time preview, and generates print-ready files for order fulfillment.

**New in this version:**
- Major admin UI color update: All blue accents replaced with gold (#fec610) for a modern, branded look.
- Clipart section now displays as a responsive grid gallery (max 8 columns, fixed card size) with large image previews and a tag filter.
- Clipart cards feature a new accessible delete icon (red outline circle with an 'x', fills on hover) that overlaps the image in the top-right corner.
- Improved accessibility, keyboard navigation, and visual polish throughout the admin interface.
- Image placeholders: Admins can define image upload areas. Customers can upload images that fill and cover the defined area, are always centered, and cannot be moved or resized. Uploading a new image replaces the previous one.
- All input fields above Add to Cart now use the backend label and have consistent, sanitized names/IDs.
- Numerous bug fixes and UX improvements for live preview and input validation.

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
- Fill in text, select dropdown options, choose color swatches, upload images (for image placeholders) as defined by the admin.
- See a live preview of your personalized product. Uploaded images will fill the defined area and cannot be moved or resized.
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

## Security

If you discover a security vulnerability, please see [SECURITY.md](./SECURITY.md) for our responsible disclosure policy and contact instructions.

We encourage all contributors to follow WordPress security best practices and review the SECURITY.md file before submitting code.

## FAQ

### Q: Why can't I see the Personalize button on my product page?
A: Make sure you have enabled personalization for the product and saved a valid configuration in the Product Personalizer meta box.

### Q: Why are my uploaded fonts or clipart not appearing?
A: Check that the file type is allowed and the upload was successful. Only admins can upload fonts and clipart.

### Q: How do I add required fields for customers?
A: In the admin designer, mark the relevant layer as required in the Properties panel. Only required fields will be enforced on the frontend.

### Q: How do I generate a print-ready file for an order?
A: In the WooCommerce order view, click the Generate Print-Ready File button in the personalization section for the order item.

### Q: How do I report a bug or request a feature?
A: Please open an issue on GitHub or contact support@customkings.com.au.

## License
GPLv2 or later 