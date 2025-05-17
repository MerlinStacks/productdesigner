# CustomKings Product Personalizer – Changelog

## Version 1.1.4 – 2025-06-09

**What's New & Fixed:**
- **Design Preview Thumbnails:** Design cards in the admin now show live preview thumbnails. No more broken image icons—previews update instantly when you save a design.
- **Admin UI Polished:** Modern, accessible card grid for designs. Cleaner, more robust logic for preview images.
- **Bug Fixes:**
  - Fixed double/duplicate admin pages and menu items.
  - Fixed redirect logic for creating and deleting designs.
  - Fixed saving and loading of design previews.
  - Improved reliability of AJAX save/load for designer.
- **Codebase Cleanup:** Removed legacy files and duplicate logic. All admin UI now lives in a single, modern code path.

## Version 1.1.2 – 2025-05-17

**What's New & Fixed:**
- **Reliable Image Deletion:** You can now delete uploaded images from the Images admin page without errors or blank screens. All errors are handled gracefully with clear admin notices.
- **Personalization Details in Cart/Checkout:** Customers always see their personalization details (including uploaded images) on the cart and checkout pages, with a clean, accessible layout.
- **WooCommerce Compatibility:** Updated to use the `woocommerce_new_order_item` hook for saving personalization data to order items (no more deprecated warnings).
- **Bug Fixes & Improvements:**
  - Prevent duplicate field labels in cart/checkout.
  - Improved error handling and admin feedback for image management.
  - Minor accessibility and UI improvements in admin.

## Version 1.1.3 – 2025-05-18

**What's New & Fixed:**
- **Accent Color Picker UI Polished:** The accent color picker in settings now uses a single, accessible preview box and a dedicated button. No more duplicate or hidden color boxes!
- **Accessibility & UX:** The preview box is keyboard accessible and can be clicked to open the color picker. All debug/test text and extra UI elements have been removed.
- **Bug Fixes:**
  - Fixed Pickr overlay and preview issues.
  - Removed all debug/test artifacts from the color picker UI.
  - Improved CSS to ensure only the intended UI is visible.

---

## Version 1.1.1 – 2025-05-16

**What's New & Improved:**
- **Pick Your Accent Color:** Choose your favorite accent color for the plugin in the settings. The new color picker supports both HEX and RGB!
- **Easier Image Management:** The Images admin page now shows your uploads in a clean, visual grid (just like Clipart). Browsing and deleting images is much easier.
- **Modern Layers Panel:** The designer's Layers panel is now sleek and user-friendly, with a clear bar layout and easy-to-use icons.
- **Clearer Documentation:** We've made the changelog and help docs easier to read and more helpful.
- **Accessibility & Fixes:** Lots of small improvements to make the admin easier for everyone, plus bug fixes throughout.

---

## Version 1.1.0 – 2024-06-08

- **Drag & Drop Layers:** Easily reorder layers in the designer by dragging them up or down.
- **Delete with Keyboard:** Press Delete or Backspace to remove selected objects in the designer.
- **Smart Centering:** New objects (text, image, shape) are always perfectly centered on the canvas.
- **Image Placeholders:** Customers can upload images that fill the defined area and are always centered. Uploading a new image replaces the previous one.
- **Consistent Input Fields:** All input fields above Add to Cart now use the backend label and have consistent, sanitized names/IDs.
- **Bug Fixes:** Many small improvements for live preview and input validation.

---

## Version 1.0.0 – 2024-06-07

- **First Release!**
- Visual admin designer for WooCommerce products.
- Customers can personalize products with text, dropdowns, color swatches, and image uploads.
- Live preview for customers as they personalize.
- Print-ready PDF files for each order item.
- Admin UI for managing fonts, color swatches, and clipart.
- Accessible modals and forms (keyboard navigation, ARIA roles, color contrast).
- All user-facing strings are translatable.
- Helpful documentation and FAQ included.

---

## Coming Soon / Planned
- Grouping and multi-select tools in the designer.
- More arrangement tools (align, distribute, bring to front/back).
- Save and load design templates.
- "Edit Customisation" link in cart/checkout.
- Even better print-ready file generation.
- Performance and accessibility audits.
- More helpful documentation and internationalization support. 