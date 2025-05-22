# CustomKings Product Personalizer

A WordPress/WooCommerce plugin that allows customers to personalize products with a visual admin designer, live preview, and print-ready file generation.

## Author
CustomKings Personalised Gifts  
https://customkings.com.au/

## Description
This plugin enables e-commerce store customers to personalize specific products by filling in admin-defined placeholders before adding them to the cart. It features a visual admin designer, real-time preview, and generates print-ready files for order fulfillment.

**New in version 1.0.3 (2025-05-19):**
- Designer interface now has a cleaner look with no scrollbars
- Fixed font selection issues in the designer
- Designer now positions properly centered on the page regardless of WordPress sidebar
- Enhanced CSS for better overall user experience and accessibility

**Previous updates:**
- Accent color picker UI is now clean and accessible: only one preview box, a dedicated button, and no duplicate or hidden color boxes.
- Preview box is keyboard accessible and can be clicked to open the color picker.
- All debug/test text and extra UI elements have been removed from the color picker UI.
- Bug fixes for Pickr overlay, preview, and CSS issues.
- Clipart section now displays as a responsive grid gallery (max 8 columns, fixed card size) with large image previews and a tag filter.
- Clipart cards feature a new accessible delete icon (red outline circle with an 'x', fills on hover) that overlaps the image in the top-right corner.
- Improved accessibility, keyboard navigation, and visual polish throughout the admin interface.
- Image placeholders: Admins can define image upload areas. Customers can upload images that fill and cover the defined area, are always centered, and cannot be moved or resized. Uploading a new image replaces the previous one.
- All input fields above Add to Cart now use the backend label and have consistent, sanitized names/IDs.
- Numerous bug fixes and UX improvements for live preview and input validation.

## What's New in 1.1.1 (2025-05-16)
- **Accent Color Picker:** You can now change all plugin accent colors from the settings page using a modern color picker (supports HEX and RGB).
- **Modern Layers Panel:** The designer's Layers panel is now more modern and user-friendly, with a clean bar layout and improved icons.
- **Visual Images Grid:** The Images admin area now uses a visual grid layout, just like the Clipart section, making it easier to browse and manage uploads.
- **Better Documentation:** The changelog and documentation are now more user-friendly and easier to read.
- **Accessibility & Bug Fixes:** Various small bugs and accessibility improvements throughout the admin UI.

## What's New in 1.1.3 (2025-05-18)
- **Accent Color Picker UI Polished:** The accent color picker in settings now uses a single, accessible preview box and a dedicated button. No more duplicate or hidden color boxes!
- **Accessibility & UX:** The preview box is keyboard accessible and can be clicked to open the color picker. All debug/test text and extra UI elements have been removed.
- **Bug Fixes:**
  - Fixed Pickr overlay and preview issues.
  - Removed all debug/test artifacts from the color picker UI.
  - Improved CSS to ensure only the intended UI is visible.

## Version 1.1.4 – 2025-06-09

**What's New & Fixed:**
- Design cards in the admin now show live preview thumbnails. No more broken image icons—previews update instantly when you save a design.
- Modern, accessible card grid for designs. Cleaner, more robust logic for preview images.
- Fixed double/duplicate admin pages and menu items.
- Fixed redirect logic for creating and deleting designs.
- Fixed saving and loading of design previews.
- Improved reliability of AJAX save/load for designer.
- Codebase cleanup: removed legacy files and duplicate logic. All admin UI now lives in a single, modern code path.

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

== Changelog ==

= 1.1.2 – 2025-05-17 =
* Reliable image deletion from the Images admin page (no more blank screens or errors).
* Personalization details (including uploaded images) always show on cart and checkout pages.
* Updated to use the correct WooCommerce order item meta hook for compatibility.
* Bug fixes: no duplicate field labels, improved admin error handling, minor accessibility and UI improvements.

= 1.1.3 – 2025-05-18 =
* Accent color picker UI is now clean and accessible: only one preview box, a dedicated button, and no duplicate or hidden color boxes.
* Preview box is keyboard accessible and can be clicked to open the color picker.
* All debug/test text and extra UI elements have been removed from the color picker UI.
* Bug fixes for Pickr overlay, preview, and CSS issues.

### 1.0.1
- Enhancement: Live preview now shows uploaded images in real time, replacing image placeholders with the actual uploaded image.

= 1.1.4 – 2025-06-09 =
* Design cards in the admin now show live preview thumbnails. No more broken image icons—previews update instantly when you save a design.
* Modern, accessible card grid for designs. Cleaner, more robust logic for preview images.
* Fixed double/duplicate admin pages and menu items.
* Fixed redirect logic for creating and deleting designs.
* Fixed saving and loading of design previews.
* Improved reliability of AJAX save/load for designer.
* Codebase cleanup: removed legacy files and duplicate logic. All admin UI now lives in a single, modern code path. 