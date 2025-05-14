# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Layers panel now supports drag-and-drop reordering. The top of the list is the topmost (frontmost) object on the canvas.
- Keyboard accessibility: You can now delete the selected object using the Delete or Backspace key, in line with accessibility standards.
- New objects (text, image, shape) are always robustly centered on the canvas using Fabric.js's `centerObject` method, regardless of zoom or pan.
- Image placeholders now fully supported: users can upload images that fill and cover the defined area, are always centered, and cannot be moved or resized.
- Uploading a new image replaces the previous one for that placeholder.
- All input fields above Add to Cart now use the backend label and have consistent, sanitized names/IDs.

### Fixed
- Numerous bug fixes and UX improvements for live preview and input validation.

## [1.0.0] - 2024-06-07

### Added
- Project structure, main plugin file, and documentation files created.
- Top-level "Product Personalizer" admin menu and tabbed settings page.
- Tabs for Modes & Global Settings, Fonts, Color Swatches, Clipart.
- Debug mode toggle and settings registration.
- Custom database tables for fonts, color palettes/colors, and clipart/tags.
- Secure CRUD logic for all asset types (upload, delete, list).
- Admin UI for managing fonts, color palettes/colors, and clipart.
- Accessible controls and feedback for asset management.
- "Designs" admin section with a visual designer (Fabric.js-based).
- Add/edit/save/load designs as JSON.
- Tools for adding text, rectangles, dropdowns, color swatches, and image upload placeholders.
- Properties panel for editing selected element properties.
- Layers panel with reordering, lock/unlock, show/hide, and delete.
- Save/load logic for design configuration.
- Focus trap and ARIA roles for accessibility.
- Layers panel supports naming, reordering, locking, showing/hiding, and deleting.
- Context menu for duplicate, bring to front, send to back, and delete.
- Properties panel for all element types.
- Tools for all placeholder types (except possibly advanced ones like clipart/choice).
- Designs are saved as custom post types with config JSON.
- "Personalize" button on product page if config exists.
- Modal customizer UI with dynamic form generation for all placeholder types (text, dropdown, swatch, image upload).
- Accessible modal with focus trap and ARIA roles.
- Loading and error states.
- Fabric.js-powered live preview in the frontend customizer.
- Canvas renders all preview-visible elements and updates as customer interacts.
- Customer input updates live preview in real time.
- Data is captured as JSON and attached to cart item.
- Accessibility for all controls.
- Personalization data is saved with cart items.
- Summary and preview shown in cart/checkout.
- Personalization data and preview shown in admin order view.
- Print-ready file generation button in admin order view.
- Print file URL saved to order item meta.
- Print-ready PDF generated using TCPDF with personalization data (basic text output).
- Download link for print file in admin order view.

### Changed
- None

### Deprecated
- None

### Removed
- None

### Security
- None

---

## Remaining MVP Tasks

### Admin Designer
- [ ] Grouping/ungrouping of elements in the designer.
- [ ] Multi-select and arrangement tools (align, distribute).
- [ ] View mode switcher (Preview vs. Print Layout).

### Templates
- [ ] "Save as Template"/"Load Template" UI and logic for reusable configurations.

### Cart/Checkout
- [ ] "Edit Customisation" link in cart/checkout for editing before payment.

### Print-Ready File Generation
- [ ] Full vector rendering of the design/canvas in the generated PDF (not just text).

### Performance & QA
- [ ] Performance tuning (JS/CSS minification, lazy loading, etc.).
- [ ] Accessibility audit (WCAG 2.1 AA compliance).
- [ ] Internationalization testing and .pot file generation.
- [ ] Final documentation polish and FAQ.

## [0.0.1] - Project Initialization
- Initial plugin structure created.
- Added main plugin file with header and author attribution.
- Added Readme.md with installation instructions.

## [Unreleased]

### Admin Designer Improvements
- Layers panel now supports drag-and-drop reordering. The top of the list is the topmost (frontmost) object on the canvas.
- Improved accessibility: You can now delete the selected object using the Delete or Backspace key, in line with accessibility standards.
- New objects (text, image, shape) are always robustly centered on the canvas using Fabric.js's `centerObject` method, regardless of zoom or pan. 