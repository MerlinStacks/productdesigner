## 1. Introduction & Overview
1.1. **Project Purpose:** Develop a WordPress/WooCommerce plugin that allows e-commerce store customers to personalize specific products (by filling in admin-defined placeholders) before adding them to the cart.
1.2. **Core Objective:** Provide a seamless, intuitive, fast, accessible, and translatable user experience for product personalization, directly integrated into the WooCommerce product page. Generate necessary data/files for order fulfillment based on admin-defined print specifications.
1.3. **Key High-Level Features:**
    * Visual admin designer for defining personalization areas, **placeholders** (including their frontend control types like text input, dropdowns, color swatches), and distinct settings/elements for **customer preview** versus **production print files**, with robust layer management and element arrangement tools.
    * Frontend interface (on product page) for customers to input content into these **placeholders** using dynamically generated controls, rendered according to preview settings.
    * Real-time visual preview of the personalized product as customers interact with placeholders.
    * Saving personalization data (customer inputs for placeholders) with the cart/order item.
    * Mechanism for retrieving/viewing personalization details (including visual preview and print-ready data) for order fulfillment.
    * Generation of print-ready files (preferably vector PDF) based on print-specific configurations.
1.4. **Performance Requirements:** The plugin, especially the frontend personalization interface and live preview, must be highly performant ("lightning fast"). Prioritize efficient code, optimized asset loading, and minimal impact on overall site speed (Core Web Vitals). Performance optimization is critical regardless of the chosen JS library/framework.
1.5. **Target Platform:** WordPress (latest stable version) with WooCommerce (latest stable version) installed and active.
1.6. **Reference Inspiration:** https://www.customily.com/ (Functionality, UI/UX concepts, particularly the idea of admin-defined templates with distinct preview and print settings, customer-fillable areas, and Design Studio features like layer management and element tools), Admin designer screenshot provided, Frontend UI screenshot provided (showing various input types).
1.7. **Quality Attributes:** Beyond functionality, the plugin must be:
    * **Maintainable:** Well-structured, modular code with clear comments.
    * **Accessible:** Adhere to WCAG 2.1 AA guidelines for both admin and frontend interfaces.
    * **Translatable:** All user-facing strings (admin and frontend) must be internationalized using WordPress standards (.pot file generated).
    * **Robust:** Include clear error handling and user feedback mechanisms.
1.8. **Author Attribution:** The plugin is developed for/by CustomKings Personalised Gifts (customkings.com.au). This attribution should be present in the plugin header comments and the Readme.md file.

## 2. Core Concepts & Terminology
2.1. **Personalization Area:** A defined region on a product image (or a designated canvas area) where personalization elements can be placed by the admin. Visually positioned in the admin designer.
2.2. **Personalization Options:** The types of customizations available, primarily realized through **Placeholders**.
2.3. **Option Set:** (Term less relevant).
2.4. **Assets (Libraries):** Reusable elements (Fonts, Clipart, Color Palettes) managed by the admin and potentially used within placeholder definitions or by customers. These form the admin's "libraries" for design.
2.5. **Product Configuration:** The complete setup for a specific product, defined by the admin via the visual designer. This configuration is the blueprint for personalization and **explicitly contains distinct settings for both the customer-facing preview and the production print file.** It includes:
    * The layout of Personalization Areas.
    * The definition and properties of **Placeholders** and other **Design Elements** within those areas (each element can be named by the admin). This includes defining the **Frontend Control Type** for placeholders.
    * **Preview Settings:** How placeholders and other design elements appear to the customer (e.g., on a mockup image, potentially with preview-only visual effects or hidden print-specific marks).
    * **Print Settings:** How placeholders and other design elements are formatted for the actual production file (e.g., print dimensions, resolution, inclusion of print-only marks like cut-lines, exclusion of preview-only effects).
    * Stored as JSON.
2.6. **Live Preview:** Real-time visual representation of the product on the frontend, rendered according to the **Preview Settings** within the Product Configuration, as the customer fills in the defined **Placeholders**.
2.7. **Personalization Data:** The specific choices and inputs made by the customer for each **Placeholder** in a Product Configuration. Stored as JSON with the order item.
2.8. **Print-Ready File:** High-resolution file for production (Vector PDF preferred), generated based on the **Print Settings** within the Product Configuration and the customer's Personalization Data.
2.9. **Placeholder:** An admin-defined **Design Element** within a Personalization Area that marks a spot for customer input. Its properties include visibility settings for preview vs. print, a custom name, and its **Frontend Control Type**. Examples:
    * **Text Placeholder:** Admin defines location, default text, font constraints, color options, max characters, visibility, name, and sets Frontend Control Type to "Text Input". Customer provides actual text.
    * **Image Placeholder:** Admin defines location, target dimensions/aspect ratio, visibility, name, and sets Frontend Control Type to "Image Upload". Customer uploads image.
    * **Clipart Placeholder:** Admin might pre-select, allow choice, define visibility, name. Frontend Control Type might be "Dropdown" or "Image Grid Select".
    * **Choice Placeholder (e.g., for Hair Color, Cat Breed):** Admin defines the available options (text, optionally with associated images/colors), visibility, name, and sets Frontend Control Type to "Dropdown", "Radio Buttons", or "Swatch Group". Customer selects one option.
    * **Color Swatch Placeholder (e.g., for Skin Color):** Admin defines a set of color options (name, hex value, preview image), visibility, name, and sets Frontend Control Type to "Color Swatch Group". Customer selects a color.
2.10. **Design Element:** Any item placed on the admin design canvas, including Placeholders, static text, static images/clipart, or decorative shapes. Each can have a custom **name** set by the admin and properties defining its appearance and visibility in the preview versus the print output.
2.11. **Frontend Control Type:** A property of a Placeholder that dictates how it will be presented to the customer for input on the product page (e.g., 'Text Input', 'Dropdown', 'Color Swatch Group', 'Image Upload').

## 3. Technical Architecture & Language Choices
3.1. **Backend:**
    * Language: PHP (WordPress standard). Must adhere to WordPress coding standards.
    * Core Integration: Utilize WooCommerce hooks, filters, and APIs extensively.
    * Database: Use custom database tables where appropriate for performance, complex relationships, or structured data storage (e.g., assets, saved templates). Otherwise, leverage WordPress post meta and WooCommerce order item meta. Schema defined in Section 7.
    * API: Implement WordPress REST API endpoints / AJAX actions for frontend-backend and admin designer-backend communication. Secure endpoints with nonces and capability checks.
    * Modularity: Structure backend code logically (e.g., separate classes/files for admin settings, designer logic, frontend handling, database interaction). Build reusable functions/libraries where applicable.
3.2. **Frontend (Customer Customizer & Admin Designer UI):**
    * Language: JavaScript (ES6+), HTML5, CSS3.
    * **Framework/Library (UI):**
        * **Admin Designer UI:** A component-based library (like React, Vue, or Alpine.js) is **strongly recommended** due to the increased complexity of managing distinct preview and print settings, element properties, layer management, element arrangement tools, and potentially a mode-switching UI. Performance optimization remains critical.
        * **Customer Customizer UI (Frontend):** Prioritize "lightning fast" performance (Section 1.4). A lightweight, performant JS library (e.g., Preact, Alpine.js, optimized Vanilla JS) is preferred. However, if the complexity of managing placeholder inputs, state, and interactions with the live preview significantly benefits from a more structured approach, React (or Preact for smaller bundle size) can be considered. The AI must evaluate this trade-off, ensuring minimal performance impact.
    * **Rendering Engine (Live Preview & Admin Designer Canvas):** **HTML5 Canvas API (2D Context)** with libraries like **Fabric.js or Konva.js** remains strongly recommended for both the admin designer canvas and the frontend live preview. This supports the object model needed for defining elements and their properties, and for vector PDF export.
        * **WebGL is NOT recommended.**
    * Communication: Asynchronous JavaScript (Fetch API / jQuery.ajax) for API calls. Provide clear loading states and handle potential API errors gracefully.
    * Accessibility: Implement ARIA attributes, keyboard navigation, focus management, and sufficient color contrast for all controls, including custom ones like color swatches.
    * Modularity: Structure frontend code logically, potentially using modules or components.
3.3. **Performance Strategy:**
    * Code Optimization: Efficient algorithms, minimize loops, optimize DB queries (caching). Well-optimized JS execution.
    * Asset Loading: Lazy load images/assets, use efficient formats (WebP), optimize SVGs, load JS/CSS conditionally/asynchronously. Use CDN.
    * Frontend Rendering: Optimize Canvas/DOM manipulations. Debounce/throttle event listeners. Minimize reflows/repaints.
    * Server: Adequate hosting, server-side caching.
3.4. **Code Quality:**
    * Comments: Code must be thoroughly commented (function headers, complex logic, setup instructions).
    * Standards: Adhere to WordPress PHP and JS coding standards.
3.5. **Guiding Principles for AI Development:**
    * **3.5.1. Leverage Existing Systems:** Prioritize the use of existing WordPress and WooCommerce hooks, filters, functions, and APIs. Avoid reinventing core functionalities.
    * **3.5.2. Utilize Established Libraries:** For common tasks not adequately covered by WordPress/WooCommerce core (e.g., advanced PDF generation, complex client-side interactions if a framework is chosen, canvas manipulation), prefer well-maintained, performant, and secure third-party libraries (e.g., Fabric.js/Konva.js for canvas, TCPDF/FPDF for PDF). Justify library choices based on performance, security, community support, and suitability for vector output where applicable.
    * **3.5.3. Adherence to Standards:** All generated code must strictly adhere to WordPress coding standards (PHP, JS, HTML/CSS) and security best practices (see Section 10).
    * **3.5.4. Modularity and Reusability:** Design code with modularity and reusability in mind, both in backend PHP classes/functions and frontend JS components/modules.

## 4. Admin Area Functionality (WP Admin)
4.1. **Main Plugin Settings Area (Dedicated Top-Level Admin Menu Item "Product Personalizer"):**
    * Interface: Single admin page, tabbed navigation. Must be a dedicated top-level menu item, not under WooCommerce settings. Accessible, clean, fast UI. Dynamic tab loading.
    * Tabs:
        * A) Modes & Global Settings: (Enable/Disable, Debug Mode, License, Defaults, API Keys, Performance, Print Settings, User Feedback definition)
        * B) Fonts: (Upload, Manage, Preview, Delete). Accessible controls.
        * C) Color Swatches: (Create/Manage Palettes & Colors). Accessible controls. Used for defining options for Color Swatch Placeholders.
        * D) Clipart: (Upload, Manage, Categorize/Tag, Filter - per screenshot). Accessible controls, lazy loading for gallery.
4.2. **Asset Management (Libraries):** (Integrated into 4.1 tabs)
4.3. **Saved Configurations/Templates (Optional):** (Save/Load reusable Product Configurations)
4.4. **Product Configuration (Edit Product Screen - Integrated Visual Designer):**
    * **Function:** Allows admin to visually design the personalization template for a product, defining all **Design Elements** (including Placeholders), their properties, names, arrangement, and their appearance/behavior for **both the customer preview and the final print output.**
    * **Core Components of the Designer:**
        * **Canvas Area:** The main workspace where design elements are placed and manipulated. Supports zoom and pan.
        * **Tools Panel:** For adding various Design Elements (Text, Image, Clipart, Choice, Color Swatch Placeholders; static Text, Images, Shapes).
        * **Properties Panel:** Dynamically displays and allows editing of attributes for the selected Design Element(s) or global settings. This includes:
            * Common attributes: Name (editable by admin), position, size, rotation, opacity.
            * **Frontend Control Type** (for Placeholders): Dropdown to select (e.g., 'Text Input', 'Dropdown', 'Color Swatch Group', 'Image Upload').
            * Element-specific attributes: Text content, font, color, image source, **options for Choice/Color Swatch Placeholders (e.g., list of values, link to Color Palette)**, **character limits for Text Placeholders**.
            * **Visibility Settings:** Toggles for "Visible in Preview," "Visible in Print."
            * (Advanced/Future) Preview-specific appearance (e.g., effects).
            * (Advanced/Future) Print-specific adjustments.
        * **Layers Panel:** Lists all Design Elements, allowing for:
            * Selection of elements.
            * Renaming elements.
            * Changing stacking order (reordering).
            * Grouping/ungrouping elements.
            * Locking/unlocking elements.
            * Showing/hiding elements (distinct from print/preview visibility, more for admin workflow).
        * **Arrangement Tools:** For selected multiple elements: align (left, center, right, top, middle, bottom), distribute (horizontally, vertically), group/ungroup.
        * **View Mode Switcher:** Buttons/tabs to toggle the Canvas Area display between:
            * **"Customer Preview Mode":** Simulates what the customer will see, using `previewSettings`.
            * **"Print Layout Mode":** Shows the design based on `printSettings`, including print-only elements and guides.
    * **Process:**
        * Launch Designer.
        * Load base product image (typically for preview mockup using `previewSettings.mockupImageUrl`) or use a blank canvas. Define/confirm **Print Area/Dimensions** (from `printSettings`).
        * Add and configure **Design Elements**. Name elements for clarity. For Placeholders, define their **Frontend Control Type** and associated options (e.g., list of choices for a dropdown, character limits for text).
        * Set **Visibility Settings** for each element.
        * Define **Global Print Settings** (final print dimensions, resolution/DPI, bleed/margin guides).
        * Define **Global Preview Settings** (mockup image URL, background color for preview area).
        * Utilize Layers panel and Arrangement tools for precise layout.
        * Use View Mode Switcher to verify both outputs.
        * Save the complete Product Configuration JSON to product meta.

## 5. Frontend User Experience (Product Page)
5.1. **Triggering Personalization:** "Personalize" button on the product page for products with a defined Product Configuration.
5.2. **Customizer Interface (Modal/Inline):**
    * Loads the Product Configuration.
    * Dynamically displays appropriate HTML input fields and controls corresponding to each **admin-defined Placeholder** that is marked as visible in the preview, based on its configured **Frontend Control Type**. Examples:
        * Text input (with character count if specified) for "Text Input" type.
        * `select` dropdown or radio buttons for "Dropdown" or "Radio Buttons" type.
        * Clickable color swatches (button group) for "Color Swatch Group" type.
        * File upload control for "Image Upload" type.
    * Presents options (e.g., font choices, colors, dropdown items) as defined by the admin in the Product Configuration.
    * Accessible, clear layout, loading/error states.
5.3. **Pricing:** Dynamic updates based on customer choices for placeholders (if specific choices incur extra costs, e.g., premium font, more text).
5.4. **Finalizing Personalization:** "Done/Apply" button. Customer inputs for placeholders are captured as Personalization Data.
5.5. **Add to Cart:** Attach Personalization Data (JSON) and optional thumbnail of the live preview to the cart item.

## 6. Cart, Checkout, and Order Handling
6.1. **Cart Display:** Thumbnail, summary of personalization (derived from placeholder inputs), "Edit Customisation" link (functional before payment).
6.2. **Checkout Display:** Thumbnail, summary of personalization, "Edit Customisation" link (functional before payment).
6.3. **Order Storage:** Save Personalization Data (customer inputs for placeholders), preview/print URLs with the order item meta. Prevent edits post-payment logic.
6.4. **Admin Order View:** Display Personalization Data, visual preview (reconstructed from config and customer data), link to print file.
6.5. **Print-Ready File Generation (Advanced):** Manual/Auto trigger, Vector PDF preference (generated by applying customer's Personalization Data to the admin's Product Configuration), embed rasters correctly.

## 7. Database Schema Design
7.1. **Custom Tables:** (Assets, Palettes, Colors, Templates [Optional])
7.2. **Post Meta (Product):** (`_product_personalization_enabled`, `_product_personalization_config_json` - *JSON now includes element names, Frontend Control Types for placeholders, character limits, choice options, and potentially more complex layer/grouping info*).
7.3. **Order Item Meta:** (`_personalization_data`, `_personalization_preview_url`, `_personalization_print_file_url`)

## 8. API Endpoints (WordPress REST API)
* Namespace: `product-personalizer/v1`
* Endpoints: (`/products/{id}/config` [GET/POST], `/assets` (for fonts, clipart, color palettes), `/templates` [Optional GET], `/preview/generate`, `/cart/item-data`, `/orders/{order_id}/item/{item_id}/generate-print-file`)

## 9. Implementation Phases (Granular Task Breakdown with Enhanced Admin Designer Features & Frontend Controls)
**This section outlines a phased approach for the AI development tool. Each phase includes key tasks, documentation updates, and expected milestones/deliverables.**

**Phase 0: Pre-Development & Environment Setup**
* **Tasks:**
    * 0.1. AI Tool: Confirm understanding of all specification sections, especially the admin-defines-placeholders workflow, the distinction between preview and print configurations, enhanced admin designer tools (Section 4.4), **frontend control types (Section 2.9, 2.11, 5.2)**, and technical choices (Section 3.2).
    * 0.2. AI Tool: Set up internal project structure for code generation, including main plugin file, and directories for PHP classes, JS, CSS, assets, etc.
    * 0.3. AI Tool: Prepare for version control integration (if applicable by the AI's process).
* **Documentation:**
    * 0.4. AI Tool: Initialize `Readme.md` with Plugin Name, Author Name/URL (1.8), and a brief initial description (1.1, 1.2).
    * 0.5. AI Tool: Initialize `changelog.md` with a "Version 0.0.1 - Project Initialization" entry.
* **Milestones & Deliverables:**
    * Confirmation of spec understanding.
    * Initial `Readme.md` and `changelog.md` files.
    * Basic plugin file structure created.

**Phase 1: Basic Plugin Structure & Admin Settings UI Shell**
* **Tasks:**
    * 1.1. AI Tool: Generate the main plugin PHP file with standard WordPress plugin header comments, including Author Attribution (1.8).
    * 1.2. AI Tool: Implement basic activation and deactivation hooks (e.g., for flushing rewrite rules if custom post types were used, or setting default options).
    * 1.3. AI Tool: Register the dedicated top-level admin menu item "Product Personalizer" with an appropriate icon (as per 4.1).
    * 1.4. AI Tool: Create the main admin page for "Product Personalizer."
    * 1.5. AI Tool: Implement the HTML structure for a tabbed navigation interface on this admin page (as per 4.1).
    * 1.6. AI Tool: Create placeholder content areas for each initial tab: "Modes & Global Settings," "Fonts," "Color Swatches," "Clipart."
    * 1.7. AI Tool: Ensure the basic admin page UI is accessible (basic ARIA roles, keyboard navigable tabs) and has a clean, WordPress-standard appearance.
    * 1.8. AI Tool: Implement a basic Debug Mode toggle in the "Modes & Global Settings" tab (simple checkbox) and save its state to the WordPress options table.
    * 1.9. AI Tool: Establish the initial PHP class structure for managing admin settings (e.g., a main admin settings class, potentially separate handlers for each tab's logic later).
* **Documentation:**
    * 1.10. AI Tool: Update `Readme.md` with basic installation instructions (how to activate the plugin).
    * 1.11. AI Tool: Update `changelog.md` for Phase 1 features (e.g., "Added basic plugin structure, admin menu, and settings page shell").
* **Milestones & Deliverables:**
    * Functional basic plugin that activates/deactivates.
    * Visible "Product Personalizer" admin menu leading to a page.
    * Navigable (but mostly empty) tabbed settings page.
    * Debug mode toggle functional.
    * Initial code structure for admin settings.

**Phase 2: Asset Management - Backend & Admin UI (Fonts, Colors, Clipart)**
* **Tasks:**
    * **Fonts (Tab B):**
        * 2.1.1. AI Tool: Design and implement custom database table schema for Fonts (name, file path/URL, preview, etc.) (as per 7.1, 4.1.B).
        * 2.1.2. AI Tool: Develop backend PHP logic for CRUD operations for Fonts (uploading font files - ttf/otf/woff/woff2, saving metadata, listing, deleting). Secure with nonces and capability checks.
        * 2.1.3. AI Tool: Implement the UI for the "Fonts" tab: file upload control, list of uploaded fonts (with preview if possible), delete buttons. Ensure controls are accessible.
    * **Color Swatches & Palettes (Tab C):**
        * 2.2.1. AI Tool: Design and implement custom database table schema for Color Palettes (name) and Colors (palette ID, name, hex code) (as per 7.1, 4.1.C). These will be usable for defining options for "Color Swatch Group" Placeholders.
        * 2.2.2. AI Tool: Develop backend PHP logic for CRUD operations for Color Palettes and their associated Colors. Secure with nonces and capability checks.
        * 2.2.3. AI Tool: Implement the UI for the "Color Swatches" tab: create/manage palettes, add/edit/delete colors within palettes (with color pickers). Ensure controls are accessible.
    * **Clipart (Tab D):**
        * 2.3.1. AI Tool: Design and implement custom database table schema for Clipart (name, image file path/URL, categories/tags) and Clipart Categories/Tags (as per 7.1, 4.1.D).
        * 2.3.2. AI Tool: Develop backend PHP logic for CRUD operations for Clipart (uploading images - SVG/PNG, categorizing/tagging, listing, deleting). Secure with nonces and capability checks.
        * 2.3.3. AI Tool: Implement the UI for the "Clipart" tab: image upload, category/tag management, filterable/searchable gallery of clipart items (implement lazy loading for gallery). Ensure controls are accessible.
    * **General Asset Management:**
        * 2.4.1. AI Tool: Develop REST API endpoints (under `/assets` namespace as per Section 8) if needed for dynamic UI interactions (e.g., fetching clipart for gallery, font previews, color palettes for designer).
* **Documentation:**
    * 2.5. AI Tool: Update `Readme.md` with instructions for managing Fonts, Color Swatches, and Clipart.
    * 2.6. AI Tool: Update `changelog.md` for Phase 2 features.
* **Milestones & Deliverables:**
    * Functional asset management sections in the admin UI.
    * Database tables for assets created and populated via UI.
    * Fonts, Colors/Palettes, and Clipart can be uploaded, viewed, and managed.

**Phase 3: Admin - Product Designer Core Structure & Basic Placeholder Functionality**
* **Tasks:**
    * 3.1. AI Tool: Add a meta-box to the WooCommerce product edit screen with a "Configure Personalization" button (or similar) to launch the visual designer (as per 4.4).
    * 3.2. AI Tool: Develop the basic HTML/JS structure for the visual admin designer UI (e.g., a modal window or a dedicated view that overlays the product edit screen). Emphasize React or similar for component structure (as per 3.2).
    * 3.3. AI Tool: Implement functionality to load a mockup/base product image and define print area/dimensions. Add basic **canvas zoom/pan controls**. Use Fabric.js or Konva.js.
    * 3.4. AI Tool: Implement a basic tool to draw/define a "Personalization Area" (e.g., a draggable, resizable rectangle) on the canvas.
    * 3.5. AI Tool: Implement a basic tool to add a "Text Placeholder" object onto the canvas within a Personalization Area.
    * 3.6. AI Tool: Implement a basic tool to add an "Image Placeholder" object onto the canvas within a Personalization Area.
    * 3.7. AI Tool: Define the initial JSON schema for `_product_personalization_config_json`.
        * `previewSettings`: { `mockupImageUrl`: null, `backgroundColor`: '#fff' }
        * `printSettings`: { `width`: '210mm', `height`: '297mm', `dpi`: 300 }
        * An array for Personalization Areas.
        * Within each area, an array for **Design Element objects** (Placeholders initially). Each element object: `id`, `name: "Element 1"` (default name), `type` ('textPlaceholder', 'imagePlaceholder'), `frontendControlType`: null, `x`, `y`, `width`, `height`, `properties`: {}, `visibility`: { `preview`: true, `print`: true }.
    * 3.8. AI Tool: Implement PHP logic to save the `_product_personalization_config_json` to product meta. Use the `/products/{id}/config` POST endpoint (Section 8).
    * 3.9. AI Tool: Implement PHP logic to load an existing `_product_personalization_config_json` from product meta and pass it to the JS designer. Use the `/products/{id}/config` GET endpoint.
    * 3.10. AI Tool: Implement JS logic in the designer to parse loaded configuration and render elements. Implement **basic element selection** on canvas.
    * 3.11. AI Tool: Establish foundational JS architecture for the designer (e.g., main designer class/component, canvas manager, state manager if using React/Vue).
* **Documentation:**
    * 3.12. AI Tool: Update `Readme.md` with initial instructions for launching the designer, basic canvas navigation (zoom/pan), setting print dimensions, adding placeholders with default visibility and names.
    * 3.13. AI Tool: Update `changelog.md` for Phase 3 features.
* **Milestones & Deliverables:**
    * Admin designer can be launched. Product image/canvas displayed. Admin can draw Personalization Areas. Admin can add basic Text and Image Placeholders. Product Configuration JSON (with element names, initial preview/print settings, element visibility) is saved/loaded. Basic zoom/pan and element selection functional.

**Phase 4: Admin - Designer Tools & Advanced Element Configuration (with Preview/Print Distinction & Enhanced Tools)**
* **Tasks:**
    * **Layers Panel (as per 4.4):**
        * 4.1.1. AI Tool: Implement UI for a Layers panel listing all Design Elements with their **names**.
        * 4.1.2. AI Tool: Implement selection synchronization between canvas and Layers panel.
        * 4.1.3. AI Tool: Implement **renaming elements** directly in the Layers panel or via Properties panel.
        * 4.1.4. AI Tool: Implement drag-and-drop reordering (stacking order) in Layers panel.
        * 4.1.5. AI Tool: Implement grouping/ungrouping of elements via Layers panel or context menu.
        * 4.1.6. AI Tool: Implement lock/unlock and show/hide (for admin workflow) toggles in Layers panel.
    * **Tools Panel (as per 4.4):**
        * 4.2.1. AI Tool: Implement tool for adding Text Placeholders.
        * 4.2.2. AI Tool: Implement tool for adding Image Placeholders.
        * 4.2.3. AI Tool: Implement tool for adding Clipart Placeholders.
        * 4.2.4. AI Tool: Implement tool for adding Choice Placeholders (for dropdowns, radio groups).
        * 4.2.5. AI Tool: Implement tool for adding Color Swatch Group Placeholders.
        * 4.2.6. AI Tool: Implement tools for adding static/decorative Design Elements (static Text, Images, Shapes).
    * **Properties Panel (as per 4.4 - for any selected Design Element):**
        * 4.3.1. AI Tool: Implement UI for a Properties panel.
        * 4.3.2. **Common Element Properties:** Display/edit **Name**, Position, size, rotation, opacity.
        * 4.3.3. **Placeholder-Specific - Frontend Control Type:** Dropdown to select (e.g., 'Text Input', 'Dropdown', 'Color Swatch Group', 'Image Upload', 'Radio Group'). This field dictates which other properties below are relevant/shown.
        * 4.3.4. **Visibility Controls:** Checkboxes/toggles for "Visible in Preview" and "Visible in Print".
        * 4.3.5. **Text Element/Placeholder Properties:** Text content (for static), default text (for placeholder), font family (from managed Fonts), font size, color, alignment, **character limits (for placeholder)**.
        * 4.3.6. **Image Element/Placeholder Properties:** Image source (for static), target dimensions, aspect ratio constraints.
        * 4.3.7. **Clipart Element/Placeholder Properties:** Clipart selection (from managed Clipart), scaling, rotation, colorization options.
        * 4.3.8. **Choice Placeholder Properties (if Frontend Control Type is 'Dropdown' or 'Radio Group'):** Define choice options (value, label, optional image/color).
        * 4.3.9. **Color Swatch Group Placeholder Properties (if Frontend Control Type is 'Color Swatch Group'):** Define color options (name, hex value, preview image, link to managed Color Palettes).
        * 4.3.10. **Personalization Area Properties:** Name/label, background color/image, dimensions.
        * 4.3.11. **Global Settings:** Edit `previewSettings` and `printSettings`.
    * **Arrangement Tools (New - as per 4.4):**
        * 4.4.1. AI Tool: Implement functionality to select multiple Design Elements on the canvas (e.g., shift-click, marquee select).
        * 4.4.2. AI Tool: Implement alignment tools (align top, middle, bottom, left, center, right) for selected elements.
        * 4.4.3. AI Tool: Implement distribution tools (distribute horizontally, vertically) for selected elements.
    * **Canvas Interaction & Configuration Updates:**
        * 4.5.1. AI Tool: Ensure selecting an element on the canvas highlights it and populates the Properties panel with relevant fields based on element type and Frontend Control Type.
        * 4.5.2. AI Tool: Ensure changes made in the Properties panel update the selected element on the canvas in real-time and update the corresponding object in the JS configuration state.
        * 4.5.3. AI Tool: Update `_product_personalization_config_json` schema to store all detailed properties, names, group information, Frontend Control Types, choice options, character limits, and global preview/print settings.
        * 4.5.4. AI Tool: Refine saving logic to include all detailed properties.
    * **Admin Designer View Modes (as per 4.4):**
        * 4.6.1. AI Tool: Implement UI controls to switch canvas view between **"Preview Mode"** and **"Print Layout Mode."**
        * 4.6.2. AI Tool: Ensure canvas rendering updates correctly based on visibility flags and relevant settings for each mode.
* **Documentation:**
    * 4.7. AI Tool: Update `Readme.md` with detailed instructions on: Layers panel (renaming, grouping, ordering), Properties panel (naming elements, setting Frontend Control Types, defining options for choices/swatches, character limits), multi-select, arrangement tools, visibility settings, global preview/print settings, and using "Preview Mode" vs. "Print Layout Mode."
    * 4.8. AI Tool: Update `changelog.md` for Phase 4 features.
* **Milestones & Deliverables:**
    * Fully functional admin designer allowing configuration of various Design Elements with names and Frontend Control Types. Enhanced Layers Panel. Multi-select and arrangement tools functional. Admin can control visibility, global settings, and switch view modes. Comprehensive `_product_personalization_config_json` saved.

**Phase 5: Backend - Data Handling, Logic & Optional Templates**
* **Tasks:**
    * 5.1. AI Tool: Implement robust server-side validation for the `_product_personalization_config_json` structure and its values upon saving (e.g., ensure required fields exist, data types are correct, options are valid for choice types).
    * 5.2. AI Tool: Implement logic for the `_product_personalization_enabled` product meta flag (as per 7.2) – this could be a checkbox on the product edit screen, outside the main designer.
    * **Saved Configurations/Templates (Optional Feature - as per 4.3):**
        * 5.3.1. AI Tool: If implementing, design DB schema for storing saved configurations/templates (name, config_json) (as per 7.1).
        * 5.3.2. AI Tool: Implement "Save as Template" functionality in the admin designer.
        * 5.3.3. AI Tool: Implement "Load Template" functionality.
        * 5.3.4. AI Tool: Develop a simple UI for managing saved templates.
        * 5.3.5. AI Tool: Implement REST API endpoints for template operations.
    * 5.4. AI Tool: Develop core backend logic for checking order status to prevent edits to personalization data post-payment (as per 6.3). This function will be used in Phase 9.
* **Documentation:**
    * 5.5. AI Tool: Update `Readme.md` to explain the `_product_personalization_enabled` flag and the (optional) Saved Configurations/Templates feature.
    * 5.6. AI Tool: Update `changelog.md` for Phase 5 features.
* **Milestones & Deliverables:**
    * Robust product configuration saving with server-side validation. Product personalization can be enabled/disabled per product. (Optional) Template saving/loading system functional. Backend logic for edit prevention drafted.

**Phase 6: Frontend - Basic Interface & Placeholder-Driven Form Generation**
* **Tasks:**
    * 6.1. AI Tool: Implement PHP logic to display a "Personalize" button on the single product page if the product has `_product_personalization_enabled` set to true and a valid `_product_personalization_config_json` exists (as per 5.1).
    * 6.2. AI Tool: Develop the basic HTML structure for the frontend customizer interface (modal or inline section, as per 5.2).
    * 6.3. AI Tool: Implement JavaScript logic (using chosen framework/library from 3.2) to handle the display of this customizer interface.
    * 6.4. AI Tool: Implement an AJAX/REST API call to fetch `_product_personalization_config_json`.
    * 6.5. AI Tool: Implement JS logic to parse the fetched Product Configuration JSON.
    * 6.6. AI Tool: **Implement JS logic to dynamically generate customer-facing HTML input controls based on Placeholders in the config JSON that are `visibility.preview: true` and their defined `frontendControlType`:**
        * 'Text Input': `<input type="text">` or `<textarea>`, apply character limits (e.g., with a visual counter like "4/12").
        * 'Image Upload': `<input type="file" accept="image/*">`.
        * 'Dropdown': `<select>` element with `<option>` tags from placeholder's choice definitions.
        * 'Radio Group': A set of `<input type="radio">` elements from placeholder's choice definitions.
        * 'Color Swatch Group': A group of clickable `<div>` or `<button>` elements, styled with colors from placeholder's color options.
    * 6.7. AI Tool: Populate input fields with default values from the placeholder definitions.
    * 6.8. AI Tool: Implement clear loading state UI.
    * 6.9. AI Tool: Implement clear error state UI.
* **Documentation:**
    * 6.10. AI Tool: Update `Readme.md` with basic customer usage instructions: how the "Personalize" button appears and how they will be presented with various field types.
    * 6.11. AI Tool: Update `changelog.md` for Phase 6 features.
* **Milestones & Deliverables:**
    * "Personalize" button appears. Frontend customizer UI loads. Product Configuration fetched. **Input controls for each preview-visible placeholder are dynamically generated according to its `frontendControlType` (text inputs, dropdowns, color swatches etc.).** Loading/error states functional.

**Phase 7: Frontend - Core Rendering Engine & Live Preview from Placeholder Inputs**
* **Tasks:**
    * 7.1. AI Tool: Integrate the chosen HTML5 Canvas library (Fabric.js/Konva.js as per 3.2) into the frontend customizer interface.
    * 7.2. AI Tool: Initialize the canvas element for the live preview.
    * 7.3. AI Tool: Render the base product mockup image (from `previewSettings.mockupImageUrl` in Product Configuration) onto the frontend canvas if available. Apply background color from `previewSettings`.
    * 7.4. AI Tool: Based on the fetched Product Configuration, render the initial state of all **Design Elements (including Placeholders) that are marked as `visibility.preview: true`** onto the canvas, using their preview-specific properties.
    * 7.5. AI Tool: Ensure the initial frontend canvas rendering accurately reflects the admin-defined **preview setup**.
* **Documentation:**
    * 7.6. AI Tool: Update `changelog.md` for Phase 7 features.
* **Milestones & Deliverables:**
    * Live preview canvas is set up. Base product mockup/background from `previewSettings` rendered. Initial visual state of all **preview-visible** Design Elements rendered accurately.

**Phase 8: Frontend - Interactivity: Customer Fills Placeholders & Preview Updates**
* **Tasks:**
    * **Link Inputs to Canvas Objects:**
        * 8.1.1. AI Tool: For each dynamically generated input control (from Phase 6), establish a link or reference to its corresponding object on the canvas (from Phase 7).
    * **Implement Real-time Updates:**
        * 8.2.1. **Text Placeholders:** Add event listeners to text input fields. Update canvas text object. Display character count feedback if limit is set.
        * 8.2.2. **Image Placeholders:** Add event listeners to file input fields. Update canvas image object.
        * 8.2.3. **Dropdown/Radio Group Placeholders:** Add event listeners. Update corresponding canvas element (could be text, or trigger change in a linked image/clipart object).
        * 8.2.4. **Color Swatch Group Placeholders:** Add event listeners to swatch buttons. Update color property of the linked canvas element(s). Visually indicate selected swatch.
        * 8.2.5. **Clipart Placeholders (if choice allowed):** Implement selection logic. Update canvas clipart object.
    * **Dynamic Pricing (as per 5.3):**
        * 8.3.1. AI Tool: Implement JS logic to calculate and display price adjustments.
    * **Finalize Personalization:**
        * 8.4.1. AI Tool: Implement the "Done/Apply" button functionality.
        * 8.4.2. AI Tool: When clicked, capture the customer's final choices for each placeholder into `Personalization Data` JSON.
    * **Accessibility:**
        * 8.5.1. AI Tool: Ensure all frontend interactive elements adhere to WCAG 2.1 AA.
* **Documentation:**
    * 8.6. AI Tool: Update `Readme.md` with detailed customer usage instructions for interacting with each type of placeholder control.
    * 8.7. AI Tool: Update `changelog.md` for Phase 8 features.
* **Milestones & Deliverables:**
    * Customers can input data into all placeholder fields using appropriate controls. Live preview updates dynamically. Dynamic pricing (if applicable) functional. `Personalization Data` captured. Frontend interactions accessible.

**Phase 9: Cart & Checkout Integration**
* **Tasks:**
    * 9.1. AI Tool: When "Add to Cart" is clicked, use `woocommerce_add_cart_item_data` to add `Personalization Data` JSON to cart item data.
    * 9.2. AI Tool: (Optional thumbnail generation) Implement client-side canvas to data URL, send to backend, save URL to cart item data.
    * 9.3. AI Tool: Use WooCommerce hooks to display personalization summary and thumbnail in cart/checkout.
    * 9.4. AI Tool: Implement "Edit Customisation" link in cart/checkout.
        * 9.4.1. Re-open frontend customizer.
        * 9.4.2. Populate customizer with saved `Personalization Data`.
    * 9.5. AI Tool: Ensure `/cart/item-data` REST API endpoint can retrieve/update item data.
* **Documentation:**
    * 9.6. AI Tool: Update `Readme.md` explaining how customizations appear in cart/checkout and how to edit.
    * 9.7. AI Tool: Update `changelog.md` for Phase 9 features.
* **Milestones & Deliverables:**
    * `Personalization Data` saved with cart items. Summary/thumbnail visible in cart & checkout. "Edit Customisation" link functional before payment.

**Phase 10: Order Integration & Admin View**
* **Tasks:**
    * 10.1. AI Tool: Use `woocommerce_checkout_create_order_line_item` to transfer `Personalization Data` and preview URL to order item meta.
    * 10.2. AI Tool: Fully implement logic to prevent edits post-payment.
    * 10.3. AI Tool: In admin order view, display `Personalization Data` and visual preview.
    * 10.4. AI Tool: Provide "Generate Print File" link in admin order view.
* **Documentation:**
    * 10.5. AI Tool: Update `Readme.md` explaining how admins view personalization data in orders.
    * 10.6. AI Tool: Update `changelog.md` for Phase 10 features.
* **Milestones & Deliverables:**
    * `Personalization Data` and preview URL stored with order items. Edits prevented post-payment. Admin can view data and preview in order screen. Link for print file generation present.

**Phase 11: Print-Ready File Generation (Vector PDF)**
* **Tasks:**
    * 11.1. AI Tool: Select PHP library for vector PDF generation (TCPDF, FPDF, etc.).
    * 11.2. AI Tool: Implement core PDF generation logic (takes `_product_personalization_config_json` and `_personalization_data`).
    * 11.3. AI Tool: Inside PDF logic:
        * Set up PDF page according to `printSettings`.
        * Iterate through Design Elements in config. If `visibility.print: true`:
            * Render Text Placeholders/Elements with customer/static text, correct font/properties.
            * Embed Image Placeholders/Elements (customer/static images) with correct resolution/position.
            * Embed Clipart Placeholders/Elements.
            * Render print-only guides.
    * 11.4. AI Tool: Implement manual trigger for PDF generation from admin order view via REST API.
    * 11.5. AI Tool: REST API endpoint fetches data, calls PDF logic, saves file, stores URL in `_personalization_print_file_url`, returns success/URL.
    * 11.6. AI Tool: (Optional) Implement automatic PDF generation on order status change.
* **Documentation:**
    * 11.7. AI Tool: Update `Readme.md` explaining print file generation and access.
    * 11.8. AI Tool: Update `changelog.md` for Phase 11 features.
* **Milestones & Deliverables:**
    * Functional print-ready vector PDF generation based on **print-specific settings and visibility rules.** Fonts embedded. Rasters at correct resolution. PDF accessible from admin order view. Print file URL saved.

**Phase 12: Performance Tuning & Optimization**
* **Tasks:**
    * **JavaScript Optimization (Admin & Frontend):**
        * 12.1.1. AI Tool: Review JS code for inefficiencies.
        * 12.1.2. AI Tool: Implement debouncing/throttling for event listeners.
        * 12.1.3. AI Tool: Optimize canvas rendering calls.
        * 12.1.4. AI Tool: If using React/Vue, optimize component rendering.
        * 12.1.5. AI Tool: Profile JS execution.
    * **Backend PHP Optimization:**
        * 12.2.1. AI Tool: Review backend PHP code, especially database queries.
        * 12.2.2. AI Tool: Implement caching where appropriate.
    * **Asset Loading (as per 3.3):**
        * 12.3.1. AI Tool: Ensure images are lazy-loaded.
        * 12.3.2. AI Tool: Recommend/implement efficient image formats.
        * 12.3.3. AI Tool: Ensure SVGs are optimized.
        * 12.3.4. AI Tool: Ensure JS/CSS are minified and loaded conditionally/asynchronously.
    * **Core Web Vitals:**
        * 12.4.1. AI Tool: Test plugin's impact on Core Web Vitals.
* **Documentation:**
    * 12.5. AI Tool: Add section to `Readme.md` on performance considerations.
    * 12.6. AI Tool: Update `changelog.md` for performance improvements.
* **Milestones & Deliverables:**
    * Measurable improvements in loading times and responsiveness. Optimized asset delivery. Plugin meets "lightning fast" performance requirement and has minimal impact on Core Web Vitals.

**Phase 13: Quality Assurance & Testing**
* **Tasks:**
    * **Functional Testing:**
        * 13.1.1. AI Tool: Test admin functionalities: settings, asset management, creating complex Product Configurations (all placeholder types, Frontend Control Types, preview/print settings, layer tools, arrangement tools, view modes).
        * 13.1.2. AI Tool: Test frontend functionalities: personalizing with all control types (text input with char count, dropdowns, color swatches), live preview accuracy, add to cart, edit from cart.
        * 13.1.3. AI Tool: Test order processing: data saving, admin order view, print file generation accuracy (reflecting print settings).
        * 13.1.4. AI Tool: Test edge cases.
    * **Compatibility Testing:**
        * 13.2.1. AI Tool: Test across major browsers.
        * 13.2.2. AI Tool: Test with default and popular themes.
        * 13.2.3. AI Tool: Test for conflicts with common plugins.
    * **Usability Testing:**
        * 13.3.1. AI Tool: Evaluate admin ease of use.
        * 13.3.2. AI Tool: Evaluate customer ease of use with various input controls.
    * **Accessibility Audit (as per 1.7):**
        * 13.4.1. AI Tool: Conduct thorough audit against WCAG 2.1 Level AA.
        * 13.4.2. AI Tool: Remediate accessibility issues.
    * **Internationalization Testing (as per 1.7):**
        * 13.5.1. AI Tool: Verify all strings are translatable.
        * 13.5.2. AI Tool: Generate `.pot` file.
        * 13.5.3. AI Tool: Test with a sample translation.
    * **Code Review:**
        * 13.6.1. AI Tool: Review code against WordPress standards.
        * 13.6.2. AI Tool: Review for security vulnerabilities.
        * 13.6.3. AI Tool: Review for maintainability.
    * **Error Handling:**
        * 13.7.1. AI Tool: Ensure robust error handling.
        * 13.7.2. AI Tool: Ensure clear user feedback.
* **Documentation:**
    * 13.8. AI Tool: Finalize FAQ section in `Readme.md`.
    * 13.9. AI Tool: Update `changelog.md` for bug fixes and QA improvements.
* **Milestones & Deliverables:**
    * All major bugs resolved. Accessibility compliance achieved. `.pot` file generated and tested. Plugin stable, robust, secure. Code adheres to standards and is well-commented.

**Phase 14: Documentation Finalization & Release Preparation**
* **Tasks:**
    * 14.1. AI Tool: Perform a final review and update of all sections in `Readme.md`:
        * Plugin Name, Author, Description.
        * Installation Instructions.
        * Comprehensive Usage Guide for Administrators (settings, asset management, detailed product configuration including all placeholder types, Frontend Control Types, preview/print settings, layer tools, view modes).
        * Clear Usage Guide for Customers (how to use the personalizer with various input controls, preview).
        * FAQ. Changelog summary/link. Support Information. Developer notes.
    * 14.2. AI Tool: Ensure `changelog.md` is complete, correctly formatted, and accurately reflects all versions and significant changes.
    * 14.3. AI Tool: Prepare any additional user or administrator documentation if necessary.
    * 14.4. AI Tool: Ensure all code is thoroughly commented.
    * 14.5. AI Tool: Prepare plugin for packaging.
* **Documentation:**
    * **Final, comprehensive `Readme.md` and `changelog.md`.**
* **Milestones & Deliverables:**
    * All documentation complete, accurate, and user-friendly. Plugin code fully commented and adheres to all standards. Plugin packaged and ready for deployment/distribution.

## 10. Security Considerations
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
11.6. **Version Control Preparedness:**
    * The AI **must** generate code and documentation updates in a manner conducive to version control (e.g., Git).
    * Changes should be logically grouped, ideally aligning with the tasks and phases outlined in Section 9, to facilitate clear and atomic commits.
    * The AI **should** assume that all its outputs (code, `Readme.md`, `changelog.md`) will be committed to a version control repository regularly.
    * Entries in `changelog.md` **should** be suitable for use in commit messages or release notes.

## 12. Potential Challenges & AI Guidance
12.1. **Admin Designer Complexity:** Now includes managing diverse placeholder/element properties, distinct preview/print settings, visibility flags, view modes, **Frontend Control Types**, layer naming, grouping, and advanced arrangement tools.
12.2. **Vector PDF Generation:** Logic must correctly combine admin template (print settings and print-visible elements) with customer data.
12.3. **Configuration JSON Structure:** Schema for `_product_personalization_config_json` needs to robustly handle various placeholder types, their nested properties, names, group info, Frontend Control Types, choice options, and the overall preview/print settings.
12.4. **Frontend Rendering Accuracy:** Accurately rendering admin-defined preview-visible placeholders/elements and then customer inputs into them using the correct frontend controls.
12.5. **Managing distinct preview and print configurations within the admin designer UI and ensuring accurate representation for both.**
12.6. **Accessibility Implementation:** Ensuring custom controls like color swatches are fully accessible.
12.7. **Internationalization:** Ensuring all dynamically generated frontend strings (e.g., from placeholder option definitions) are translatable if needed, or clearly marked as admin-defined content.
12.8. **Preventing Edits Logic:** Ensure robust order status checks.
12.9. **Cross-Browser Compatibility:** Especially for canvas and custom form controls.
12.10. **Theme/Plugin Conflicts:** Defensive coding.
12.11. **Data Storage Efficiency:** Compact JSON.
12.12. **Font Loading/Rendering:** Efficient formats, correct rendering in canvas and PDF.

## 13. Future Enhancements (Post-MVP)
* Conditional logic for placeholders (e.g., show placeholder B if placeholder A has a certain value).
* More advanced placeholder types (e.g., date pickers, map selectors).
* **Advanced templating system (e.g., reusable "Designs" and "Base Product" definitions similar to Customily's concept, to accelerate Product Configuration creation).**
* Preview-only visual effects (e.g., texture masks, gloss effects) controllable by the admin.
* Advanced text manipulation tools (e.g., curved text).
* Basic image editing tools within the designer (e.g., filters, cropping for uploaded images).
* Ability for admin to define dynamic pricing rules directly in the designer based on placeholder choices.
