
## 1. Introduction & Overview
1.1. **Project Purpose:** Develop a WordPress/WooCommerce plugin that allows e-commerce store customers to personalize specific products before adding them to the cart.
1.2. **Core Objective:** Provide a seamless, intuitive, fast, accessible, and translatable user experience for product personalization, directly integrated into the WooCommerce product page. Generate necessary data/files for order fulfillment.
1.3. **Key High-Level Features:**
    * Visual admin designer for defining personalization options and areas per product.
    * Frontend interface (on product page) for customers to apply customizations.
    * Real-time visual preview of the personalized product.
    * Saving personalization data with the cart/order item.
    * Mechanism for retrieving/viewing personalization details (including visual preview) for order fulfillment.
    * Generation of print-ready files (preferably vector PDF).
1.4. **Performance Requirements:** The plugin, especially the frontend personalization interface and live preview, must be highly performant ("lightning fast"). Prioritize efficient code, optimized asset loading, and minimal impact on overall site speed (Core Web Vitals). Performance optimization is critical regardless of the chosen JS library/framework.
1.5. **Target Platform:** WordPress (latest stable version) with WooCommerce (latest stable version) installed and active.
1.6. **Reference Inspiration:** https://www.customily.com/ (Functionality, UI/UX concepts), Admin designer screenshot provided.
1.7. **Quality Attributes:** Beyond functionality, the plugin must be:
    * **Maintainable:** Well-structured, modular code with clear comments.
    * **Accessible:** Adhere to WCAG 2.1 AA guidelines for both admin and frontend interfaces.
    * **Translatable:** All user-facing strings (admin and frontend) must be internationalized using WordPress standards (.pot file generated).
    * **Robust:** Include clear error handling and user feedback mechanisms.
1.8. **Author Attribution:** The plugin is developed for/by CustomKings Personalised Gifts (customkings.com.au). This attribution should be present in the plugin header comments and the Readme.md file.

## 2. Core Concepts & Terminology
*(Unchanged from Version 1.3)*
2.1. **Personalization Area:** Defined region on product image for customization, visually placed in admin.
2.2. **Personalization Options:** Types of customizations (Text, Image, Clipart, Color).
2.3. **Option Set:** (Term less relevant).
2.4. **Assets:** Reusable elements (Fonts, Clipart, Color Palettes).
2.5. **Product Configuration:** Setup for a product via the visual admin designer.
2.6. **Live Preview:** Real-time visual representation (Frontend).
2.7. **Personalization Data:** Customer's choices (JSON).
2.8. **Print-Ready File:** High-resolution file for production (Vector PDF preferred).

## 3. Technical Architecture & Language Choices
*(Unchanged from Version 1.3, with additions to 3.5)*
3.1. **Backend:**
    * Language: PHP (WordPress standard). Must adhere to WordPress coding standards.
    * Core Integration: Utilize WooCommerce hooks, filters, and APIs extensively.
    * Database: Use custom database tables where appropriate for performance, complex relationships, or structured data storage (e.g., assets, saved templates). Otherwise, leverage WordPress post meta and WooCommerce order item meta. Schema defined in Section 7.
    * API: Implement WordPress REST API endpoints / AJAX actions for frontend-backend and admin designer-backend communication. Secure endpoints with nonces and capability checks.
    * Modularity: Structure backend code logically (e.g., separate classes/files for admin settings, designer logic, frontend handling, database interaction). Build reusable functions/libraries where applicable.
3.2. **Frontend (Customer Customizer & Admin Designer UI):**
    * Language: JavaScript (ES6+), HTML5, CSS3.
    * Framework/Library (UI): Consider a lightweight, performant JS library (e.g., Preact, Alpine.js, optimized Vanilla JS) for the customer-facing frontend. For the complex admin designer UI, a component-based library (like React, Vue, or Alpine.js) might be beneficial, but must be implemented with strict performance optimization (code splitting, lazy loading components, efficient state management).
    * Rendering Engine (Live Preview): HTML5 Canvas API (Option A) is strongly recommended for flexibility and export capabilities. Libraries like Fabric.js or Konva.js can assist.
    * Communication: Asynchronous JavaScript (Fetch API / jQuery.ajax) for API calls. Provide clear loading states and handle potential API errors gracefully.
    * Accessibility: Implement ARIA attributes, keyboard navigation, focus management, and sufficient color contrast.
    * Modularity: Structure frontend code logically, potentially using modules or components.
3.3. **Performance Strategy:**
    * Code Optimization: Efficient algorithms, minimize loops, optimize DB queries (caching). Well-optimized JS execution.
    * Asset Loading: Lazy load images/assets, use efficient formats (WebP), optimize SVGs, load JS/CSS conditionally/asynchronously. Use CDN.
    * Frontend Rendering: Optimize Canvas/DOM manipulations. Debounce/throttle event listeners. Minimize reflows/repaints.
    * Server: Adequate hosting, server-side caching.
3.4. **Code Quality:**
    * Comments: Code must be thoroughly commented (function headers, complex logic, setup instructions).
    * Standards: Adhere to WordPress PHP and JS coding standards.
3.5. **Guiding Principles for AI Development (New Section):**
    * **3.5.1. Leverage Existing Systems:** Prioritize the use of existing WordPress and WooCommerce hooks, filters, functions, and APIs. Avoid reinventing core functionalities.
    * **3.5.2. Utilize Established Libraries:** For common tasks not adequately covered by WordPress/WooCommerce core (e.g., advanced PDF generation, complex client-side interactions if a framework is chosen), prefer well-maintained, performant, and secure third-party libraries. Justify library choices based on performance, security, and community support.
    * **3.5.3. Adherence to Standards:** All generated code must strictly adhere to WordPress coding standards (PHP, JS, HTML/CSS) and security best practices (see Section 10).
    * **3.5.4. Modularity and Reusability:** Design code with modularity and reusability in mind, both in backend PHP classes/functions and frontend JS components/modules.

## 4. Admin Area Functionality (WP Admin)
*(Unchanged from Version 1.3)*
4.1. **Main Plugin Settings Area (Dedicated Top-Level Admin Menu Item "Product Personalizer"):**
    * Interface: Single admin page, tabbed navigation. Must be a dedicated top-level menu item, not under WooCommerce settings. Accessible, clean, fast UI. Dynamic tab loading.
    * Tabs:
        * A) Modes & Global Settings: (Enable/Disable, Debug Mode, License, Defaults, API Keys, Performance, Print Settings, User Feedback definition)
        * B) Fonts: (Upload, Manage, Preview, Delete). Accessible controls.
        * C) Color Swatches: (Create/Manage Palettes & Colors). Accessible controls.
        * D) Clipart: (Upload, Manage, Categorize/Tag, Filter - per screenshot). Accessible controls, lazy loading for gallery.
4.2. **Asset Management:** (Integrated into 4.1 tabs)
4.3. **Saved Configurations/Templates (Optional):** (Save/Load reusable configurations)
4.4. **Product Configuration (Edit Product Screen - Integrated Visual Designer):** (Launch Designer, Canvas, Layers [Lock/Unlock], Tools, Properties, Save Config)

## 5. Frontend User Experience (Product Page)
*(Unchanged from Version 1.3)*
5.1. **Triggering Personalization:** "Personalize" button.
5.2. **Customizer Interface (Modal/Inline):** Accessible, clear layout, loading/error states.
5.3. **Pricing:** Dynamic updates.
5.4. **Finalizing Personalization:** "Done/Apply" button.
5.5. **Add to Cart:** Attach data, optional thumbnail.

## 6. Cart, Checkout, and Order Handling
*(Unchanged from Version 1.3)*
6.1. **Cart Display:** Thumbnail, summary, "Edit Customisation" link (functional before payment).
6.2. **Checkout Display:** Thumbnail, summary, "Edit Customisation" link (functional before payment).
6.3. **Order Storage:** Save data, preview/print URLs. Prevent edits post-payment logic.
6.4. **Admin Order View:** Display data, visual preview, link to print file.
6.5. **Print-Ready File Generation (Advanced):** Manual/Auto trigger, Vector PDF preference, embed rasters correctly.

## 7. Database Schema Design
*(Unchanged from Version 1.3)*
7.1. **Custom Tables:** (Assets, Palettes, Colors, Templates [Optional])
7.2. **Post Meta (Product):** (`_product_personalization_enabled`, `_product_personalization_config_json`)
7.3. **Order Item Meta:** (`_personalization_data`, `_personalization_preview_url`, `_personalization_print_file_url`)

## 8. API Endpoints (WordPress REST API)
*(Unchanged from Version 1.3)*
* Namespace: `product-personalizer/v1`
* Endpoints: (`/products/{id}/config` [GET/POST], `/assets`, `/templates` [Optional GET], `/preview/generate`, `/cart/item-data`, `/orders/{order_id}/item/{item_id}/generate-print-file`)

## 9. Implementation Phases (Refined & Detailed)
**This section outlines a phased approach for the AI development tool. Each phase includes key tasks, documentation updates, and expected milestones/deliverables.**

**Phase 0: Pre-Development & Environment Setup**
* **Tasks:**
    * AI Tool: Confirm understanding of all specification sections.
    * AI Tool: Set up internal project structure for code generation.
    * AI Tool: Prepare for version control integration (if applicable).
* **Documentation:**
    * AI Tool: Initialize `Readme.md` with basic plugin information (Name, Author, initial Description) as per Section 11.2.
    * AI Tool: Initialize `changelog.md` with project creation entry as per Section 11.1.
* **Milestones & Deliverables:**
    * Confirmation of spec understanding.
    * Initial `Readme.md` and `changelog.md` files.

**Phase 1: Basic Plugin Structure & Admin Settings UI Shell**
* **Tasks:**
    * AI Tool: Generate basic plugin file with header (including author attribution as per 1.8), activation/deactivation hooks.
    * AI Tool: Create the dedicated top-level admin menu item "Product Personalizer" (as per 4.1).
    * AI Tool: Implement the shell for the tabbed admin settings page (4.1) with placeholder tabs (Modes & Global Settings, Fonts, Color Swatches, Clipart). Ensure UI is accessible and clean.
    * AI Tool: Implement basic Debug Mode toggle functionality (saving to options table).
    * AI Tool: Establish PHP classes/file structure for admin settings logic.
* **Documentation:**
    * AI Tool: Update `Readme.md` with basic installation instructions.
    * AI Tool: Update `changelog.md` for Phase 1 features.
* **Milestones & Deliverables:**
    * Functional basic plugin that activates/deactivates.
    * Visible "Product Personalizer" admin menu.
    * Navigable (but empty) tabbed settings page.
    * Initial code structure for admin settings.

**Phase 2: Asset Management - Backend & Admin UI (Fonts, Colors, Clipart)**
* **Tasks:**
    * AI Tool: Design and implement database schema for Fonts, Color Palettes/Colors, and Clipart (as per 7.1 and 4.1.B, 4.1.C, 4.1.D). Include tables for categories/tags for Clipart.
    * AI Tool: Develop backend PHP logic for CRUD operations (Create, Read, Update, Delete) for Fonts, Colors/Palettes, and Clipart. Secure with nonces and capability checks.
    * AI Tool: Implement the UI for the "Fonts" tab (upload, manage, preview, delete). Ensure controls are accessible.
    * AI Tool: Implement the UI for the "Color Swatches" tab (create/manage palettes & individual colors). Ensure controls are accessible.
    * AI Tool: Implement the UI for the "Clipart" tab (upload, manage, categorize/tag, filter, lazy-loading gallery). Ensure controls are accessible.
    * AI Tool: Develop REST API endpoints (under `/assets` namespace as per Section 8) for managing these assets if needed by the UI (e.g., for dynamic loading/filtering in Clipart gallery).
* **Documentation:**
    * AI Tool: Update `Readme.md` with instructions for managing assets.
    * AI Tool: Update `changelog.md` for Phase 2 features.
* **Milestones & Deliverables:**
    * Functional asset management sections in the admin UI.
    * Database tables created and populated via UI.
    * Assets can be uploaded, viewed, and managed.

**Phase 3: Admin - Product Designer Core Structure & Basic Functionality**
* **Tasks:**
    * AI Tool: Integrate a "Personalize Product" button/meta-box on the WooCommerce product edit screen to launch the visual designer (as per 4.4).
    * AI Tool: Develop the basic structure of the visual admin designer UI (modal or dedicated view).
    * AI Tool: Implement functionality to load the product's main image onto a canvas (HTML5 Canvas API recommended, as per 3.2).
    * AI Tool: Implement basic drawing tools for defining a "Personalization Area" (e.g., a draggable, resizable rectangle) on the product image.
    * AI Tool: Define and implement the structure for `_product_personalization_config_json` (as per 7.2). This JSON should store area definitions and associated options.
    * AI Tool: Implement saving the personalization area configuration (coordinates, dimensions) to the product meta (`_product_personalization_config_json`).
    * AI Tool: Implement loading an existing configuration back into the designer.
    * AI Tool: Establish foundational JS architecture for the designer (consider performance and modularity as per 3.2).
* **Documentation:**
    * AI Tool: Update `Readme.md` with initial instructions for setting up a product for personalization.
    * AI Tool: Update `changelog.md` for Phase 3 features.
* **Milestones & Deliverables:**
    * Admin designer can be launched from the product edit screen.
    * Product image is displayed in the designer.
    * Admin can draw and save a single personalization area.
    * Configuration JSON is saved to product meta.

**Phase 4: Admin - Designer Tools & Options Implementation**
* **Tasks:**
    * AI Tool: Implement the "Layers" panel functionality (add, delete, reorder, lock/unlock layers corresponding to personalization elements like text, clipart).
    * AI Tool: Implement the "Tools" panel for adding personalization option types:
        * Text: Font selection (from managed fonts), size, color (from palettes/custom), alignment.
        * Image: Upload placeholder (actual image upload by customer is frontend).
        * Clipart: Selection from managed clipart library.
        * Color: Applying color to defined areas/elements (e.g., product parts if applicable, distinct from text/clipart color).
    * AI Tool: Implement the "Properties" panel to edit attributes of selected elements/layers (e.g., text content, font size, clipart choice, colors).
    * AI Tool: Ensure the live preview within the admin designer accurately reflects the applied options.
    * AI Tool: Update the `_product_personalization_config_json` to store these detailed options per area/element.
* **Documentation:**
    * AI Tool: Update `Readme.md` with detailed instructions on using all designer tools and options.
    * AI Tool: Update `changelog.md` for Phase 4 features.
* **Milestones & Deliverables:**
    * Fully functional admin designer with tools for text, image placeholders, and clipart.
    * Layers panel operational.
    * Properties panel allows modification of element attributes.
    * Comprehensive `_product_personalization_config_json` saved.

**Phase 5: Backend - Data Handling, Logic & Optional Templates**
* **Tasks:**
    * AI Tool: Ensure robust saving and validation of the `_product_personalization_config_json`.
    * AI Tool: Implement logic for `_product_personalization_enabled` flag (product meta, as per 7.2).
    * AI Tool: (If "Saved Configurations/Templates" feature from 4.3 is included)
        * Design DB schema for templates (as per 7.1).
        * Implement save/load functionality for configurations as reusable templates.
        * Develop UI for managing these templates.
        * Implement relevant REST API endpoints (e.g., `/templates` as per Section 8).
    * AI Tool: Develop core logic for preventing edits post-payment (as per 6.3) – this will be primarily checked before allowing edits via cart/checkout.
* **Documentation:**
    * AI Tool: Update `Readme.md` if templates are implemented.
    * AI Tool: Update `changelog.md` for Phase 5 features.
* **Milestones & Deliverables:**
    * Robust product configuration saving and loading.
    * (Optional) Template saving/loading system functional.
    * Backend logic for edit prevention drafted.

**Phase 6: Frontend - Basic Interface & Configuration Loading**
* **Tasks:**
    * AI Tool: Implement the "Personalize" button on the single product page for eligible products (as per 5.1).
    * AI Tool: Develop the frontend customizer interface (modal or inline section, as per 5.2). Ensure it's accessible and has a clean layout.
    * AI Tool: Implement AJAX/REST API call (to `/products/{id}/config` GET endpoint) to fetch the `_product_personalization_config_json` for the current product.
    * AI Tool: Parse the configuration JSON and dynamically build the frontend personalization options based on the admin setup.
    * AI Tool: Implement clear loading states while fetching data and error states if the configuration cannot be loaded (as per 5.2).
    * AI Tool: Choose and integrate the JS library/framework for the frontend customizer (as per 3.2), prioritizing performance.
* **Documentation:**
    * AI Tool: Update `Readme.md` with basic customer usage instructions.
    * AI Tool: Update `changelog.md` for Phase 6 features.
* **Milestones & Deliverables:**
    * "Personalize" button appears on product pages.
    * Frontend customizer UI loads.
    * Product configuration is fetched and options are displayed (non-interactive at this stage).
    * Loading/error states are functional.

**Phase 7: Frontend - Core Rendering Engine & Live Preview**
* **Tasks:**
    * AI Tool: Implement the chosen rendering engine (HTML5 Canvas preferred, possibly with Fabric.js/Konva.js, as per 3.2) for the live preview on the frontend.
    * AI Tool: Render the base product image on the canvas.
    * AI Tool: Render personalization elements (text, customer-uploaded images, clipart) onto the canvas based on default/loaded configuration.
    * AI Tool: Ensure the frontend rendering accurately reflects the admin-defined areas and initial options.
* **Documentation:**
    * AI Tool: Update `changelog.md` for Phase 7 features.
* **Milestones & Deliverables:**
    * Live preview canvas displays the product image.
    * Initial personalization elements (based on config) are rendered on the preview.

**Phase 8: Frontend - Interactivity & Customer Customization**
* **Tasks:**
    * AI Tool: Implement interactive controls for each personalization option type:
        * Text: Input fields for text, dropdowns for fonts, color pickers.
        * Image: File upload control for customer images, basic transformation tools (resize, rotate if specified).
        * Clipart: Selection interface for available clipart.
    * AI Tool: Update the live preview in real-time (or near real-time with debouncing) as the customer makes changes.
    * AI Tool: Implement dynamic pricing updates based on selected options/elements, if applicable (as per 5.3).
    * AI Tool: Implement "Done/Apply" button to finalize choices (as per 5.4). Store chosen personalization data locally (e.g., in a JS object).
    * AI Tool: Ensure all frontend interactions are accessible (WCAG 2.1 AA).
* **Documentation:**
    * AI Tool: Update `Readme.md` with detailed customer usage instructions for all personalization types.
    * AI Tool: Update `changelog.md` for Phase 8 features.
* **Milestones & Deliverables:**
    * Customers can interact with all defined personalization options.
    * Live preview updates dynamically.
    * Pricing updates (if applicable).
    * Personalization choices are captured.

**Phase 9: Cart & Checkout Integration**
* **Tasks:**
    * AI Tool: When "Add to Cart" is clicked (after "Done/Apply"), save the captured personalization data as order item meta (as per 5.5, 7.3 - `_personalization_data` JSON).
    * AI Tool: Generate and save a small thumbnail of the personalized product (optional, as per 5.5) and store its URL (`_personalization_preview_url` as per 7.3). This might involve a backend call to `/preview/generate` (as per Section 8).
    * AI Tool: Display the personalization summary and thumbnail in the cart and checkout pages (as per 6.1, 6.2).
    * AI Tool: Implement the "Edit Customisation" link on cart/checkout. This should repopulate the frontend customizer with the saved data. Ensure edit prevention logic (from Phase 5) is checked here – no edits if order is paid.
    * AI Tool: Ensure the REST API endpoint `/cart/item-data` (as per Section 8) can retrieve/update item data before order completion.
* **Documentation:**
    * AI Tool: Update `Readme.md` explaining how customizations appear in cart/checkout and how to edit.
    * AI Tool: Update `changelog.md` for Phase 9 features.
* **Milestones & Deliverables:**
    * Personalization data saved with cart items.
    * Summary/thumbnail visible in cart & checkout.
    * "Edit Customisation" link functional before payment.

**Phase 10: Order Integration & Admin View**
* **Tasks:**
    * AI Tool: Ensure personalization data (`_personalization_data`) and preview URL (`_personalization_preview_url`) are correctly transferred from cart item meta to order item meta upon order completion (as per 6.3).
    * AI Tool: Implement logic to prevent edits post-payment robustly (as per 6.3).
    * AI Tool: Display the personalization data and visual preview (using the saved thumbnail or by regenerating) in the WooCommerce admin order view (as per 6.4).
    * AI Tool: Provide a link/button in the admin order view to generate/access the print-ready file (as per 6.4).
* **Documentation:**
    * AI Tool: Update `Readme.md` explaining how admins view personalization data in orders.
    * AI Tool: Update `changelog.md` for Phase 10 features.
* **Milestones & Deliverables:**
    * Personalization data correctly stored with completed orders.
    * Admin can view personalization details and preview in the order screen.

**Phase 11: Print-Ready File Generation (Vector PDF)**
* **Tasks:**
    * AI Tool: Implement print-ready file generation, preferably as a vector PDF (as per 1.3, 6.5).
    * AI Tool: Research and integrate a suitable PHP library (e.g., TCPDF, FPDF) or a JS library (e.g., jsPDF for client-side generation if feasible, then upload) for PDF creation. Guide AI on handling fonts, vector data, and embedding raster images correctly (as per 12.2).
    * AI Tool: Implement manual trigger for PDF generation from the admin order view.
    * AI Tool: (Optional) Implement automatic PDF generation upon order completion or specific status change.
    * AI Tool: Save the generated print file URL to order item meta (`_personalization_print_file_url` as per 7.3).
    * AI Tool: Implement the REST API endpoint `/orders/{order_id}/item/{item_id}/generate-print-file` (as per Section 8).
* **Documentation:**
    * AI Tool: Update `Readme.md` on how print files are generated and accessed.
    * AI Tool: Update `changelog.md` for Phase 11 features.
* **Milestones & Deliverables:**
    * Functional print-ready PDF generation.
    * PDFs include all personalization details accurately.
    * PDF accessible from admin order view.

**Phase 12: Performance Tuning & Optimization**
* **Tasks:**
    * AI Tool: Review and optimize all JS code (admin designer and frontend customizer) for speed and efficiency (minimize loops, debounce/throttle events, efficient DOM manipulation).
    * AI Tool: Optimize backend PHP code, especially database queries (add caching where appropriate).
    * AI Tool: Implement asset loading strategies: lazy load images/assets, use efficient formats (WebP), optimize SVGs, load JS/CSS conditionally/asynchronously (as per 3.3).
    * AI Tool: Test and ensure minimal impact on Core Web Vitals.
    * AI Tool: Optimize canvas rendering performance for both admin and frontend.
* **Documentation:**
    * AI Tool: Add a section to `Readme.md` on performance considerations or recommended server settings if any.
    * AI Tool: Update `changelog.md` for performance improvements.
* **Milestones & Deliverables:**
    * Measurable improvements in loading times and responsiveness.
    * Optimized asset delivery.
    * Plugin meets performance requirements (as per 1.4).

**Phase 13: Quality Assurance & Testing**
* **Tasks:**
    * AI Tool: Perform comprehensive testing:
        * Cross-browser and cross-device testing (admin and frontend).
        * WordPress theme and common plugin conflict testing.
        * Usability testing for both admin and customer flows.
    * AI Tool: Conduct a thorough accessibility audit against WCAG 2.1 AA for all interfaces. Remediate any issues.
    * AI Tool: Test internationalization:
        * Ensure all user-facing strings are wrapped in WordPress translation functions (as per 1.7, 12.7).
        * Generate the `.pot` file.
        * Test with a sample translation.
    * AI Tool: Conduct code review (or prompt for human review) focusing on WordPress coding standards, security, and maintainability.
    * AI Tool: Implement robust error handling and user feedback mechanisms throughout the plugin.
* **Documentation:**
    * AI Tool: Finalize FAQ section in `Readme.md` based on testing outcomes.
    * AI Tool: Update `changelog.md` for bug fixes and QA improvements.
* **Milestones & Deliverables:**
    * All major bugs resolved.
    * Accessibility compliance achieved.
    * `.pot` file generated and tested.
    * Plugin is stable and robust.

**Phase 14: Documentation Finalization & Release Preparation**
* **Tasks:**
    * AI Tool: Review and finalize all sections of `Readme.md` (Installation, Usage Guides for Admin & Customer, FAQ, Support Info, Changelog link).
    * AI Tool: Ensure `changelog.md` is complete and accurately reflects all versions and changes.
    * AI Tool: Prepare any additional user or administrator documentation if deemed necessary (beyond Readme).
    * AI Tool: Ensure all code is thoroughly commented as per standards (3.4).
    * AI Tool: Prepare plugin for packaging/distribution.
* **Documentation:**
    * **Final `Readme.md` and `changelog.md`.**
* **Milestones & Deliverables:**
    * Comprehensive and final plugin documentation.
    * Plugin ready for deployment/distribution.

## 10. Security Considerations
*(Unchanged from Version 1.3)*
* Use WordPress nonces for all form submissions and AJAX actions.
* Sanitize all inputs (POST, GET, user-entered data).
* Escape all outputs to prevent XSS.
* Check user capabilities and permissions for all actions, especially in the admin area and for API endpoints.
* Validate file uploads (type, size, sanitize filenames).
* Secure REST API endpoints with proper authentication and authorization (nonces, capability checks).

## 11. AI Development Process, Documentation, and Version Control
**This section details critical requirements for the AI development tool regarding its process, the generation and maintenance of essential documentation, and considerations for version control. Adherence to these points is crucial for a successful project outcome.**

11.1. **Changelog (`changelog.md`):**
    * The AI **must** create and maintain a `changelog.md` file in the plugin's root directory.
    * This file **must** follow standard changelog conventions (e.g., Keep a Changelog format: `https://keepachangelog.com/`).
    * All significant changes, feature additions, bug fixes, and version updates **must** be documented here by the AI consistently throughout the development process, corresponding to the completion of tasks within each Implementation Phase (Section 9).
11.2. **Readme File (`Readme.md`):**
    * The AI **must** create and incrementally develop a comprehensive `Readme.md` file in the plugin's root directory.
    * This file **must** include:
        * Plugin Name
        * Author Name (CustomKings Personalised Gifts) and URL (customkings.com.au) (as per 1.8)
        * Brief description of the plugin's purpose and key features.
        * Detailed installation instructions.
        * Comprehensive usage guide for administrators (covering all settings, asset management, product configuration designer).
        * Clear usage guide for customers (how to use the frontend personalizer).
        * Frequently Asked Questions (FAQ) - to be populated as development progresses and potential questions arise.
        * Changelog summary (or a direct link to `changelog.md`).
        * Support information (if applicable, can be a placeholder initially).
        * Developer notes or hooks/filters available for extension (if applicable).
11.3. **Progress Logging (AI Tool Requirement):**
    * The AI development tool (e.g., Roocode) **must** maintain an internal or external log of its progress against the Implementation Phases (Section 9).
    * This log should be detailed enough (e.g., tracking individual tasks within phases) to be easily referenceable, allowing development to be paused and resumed effectively from the last completed step or checkpoint. This progress log effectively serves as the AI's interaction with and execution of this roadmap document. The AI should explicitly state when a phase or sub-task is completed and what the next immediate task will be.
11.4. **Code Commenting:**
    * The AI **must** generate well-commented code as per WordPress coding standards (PHP, JS, HTML/CSS). This includes function/method headers, explanations for complex logic, and inline comments where necessary for clarity (as per 3.4).
11.5. **Adherence to Standards:**
    * The AI **must** adhere to WordPress coding standards for PHP and JavaScript throughout the generated codebase, and follow the guiding principles outlined in Section 3.5.
11.6. **Version Control Preparedness (New Subsection):**
    * The AI **must** generate code and documentation updates in a manner conducive to version control (e.g., Git).
    * Changes should be logically grouped, ideally aligning with the tasks and phases outlined in Section 9, to facilitate clear and atomic commits.
    * The AI **should** assume that all its outputs (code, `Readme.md`, `changelog.md`) will be committed to a version control repository regularly.
    * Entries in `changelog.md` **should** be suitable for use in commit messages or release notes.

## 12. Potential Challenges & AI Guidance
*(Content Unchanged from Version 1.3, renumbered from 11. Specific AI guidance integrated into relevant Implementation Phases in Section 9 and Guiding Principles in Section 3.5)*
12.1. **Admin Designer Complexity:** Guide AI on JS structure, canvas interactions, state management, API communication, accessibility. *(Guidance: Phase 3 & 4 tasks emphasize modular JS, canvas API, and component-based thinking if a library is used).*
12.2. **Vector PDF Generation:** Guide AI on libraries (FPDF/TCPDF/jsPDF), handling fonts, vectors, rasters. *(Guidance: Phase 11 tasks specify library considerations; Section 3.5.2 encourages using established libraries).*
12.3. **Configuration JSON Structure:** Define robust schema. *(Guidance: Phase 3 & 4 tasks involve defining and evolving this schema).*
12.4. **Frontend Rendering Accuracy:** Must match admin config. *(Guidance: Phase 7 emphasizes accurate reflection of admin setup).*
12.5. **Performance (Admin & Frontend):** Constant priority. *(Guidance: Phase 12 is dedicated to this; performance is a recurring theme, also see 3.5.2).*
12.6. **Accessibility Implementation:** Guide AI on ARIA, focus, keyboard operability. *(Guidance: Phase 8 & 13 tasks specify WCAG compliance).*
12.7. **Internationalization:** Remind AI to wrap strings in translation functions. *(Guidance: Phase 13 tasks include .pot file generation and testing).*
12.8. **Preventing Edits Logic:** Ensure robust order status checks. *(Guidance: Phase 5 & 9 tasks cover this logic).*
12.9. **Cross-Browser Compatibility:** Use standard APIs. *(Guidance: Phase 13 testing covers this).*
12.10. **Theme/Plugin Conflicts:** Defensive coding. *(Guidance: Phase 13 testing includes conflict checks; Section 3.5.1 on leveraging existing systems helps).*
12.11. **Data Storage Efficiency:** Compact JSON. *(Guidance: Implied in schema design and data handling phases).*
12.12. **Font Loading/Rendering:** Efficient formats, correct rendering. *(Guidance: Asset management and PDF generation phases touch on this).*

## 13. Future Enhancements (Post-MVP)
*(Content Unchanged from Version 1.3, renumbered from 12)*
* More complex personalization options (e.g., curved text, masking).
* Integration with external asset libraries or DAMs.
* Allowing customers to save their designs for later use.
* Social sharing of personalized product previews.
* Admin dashboard with analytics on personalization usage.
* Advanced template features (e.g., sharing templates across products).
* Support for variable products with different personalization setups per variation.
