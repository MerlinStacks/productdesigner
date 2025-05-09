# Data Flow Diagrams

This document illustrates the key data flows between components in the WordPress/WooCommerce Product Personalization Plugin.

## 1. Admin Configuration Flow

```mermaid
sequenceDiagram
    participant Admin as Admin User
    participant AS as Admin Settings
    participant PD as Product Designer
    participant DM as Data Manager
    participant AM as Asset Manager
    
    Admin->>AS: Configure plugin settings
    AS->>DM: Save global settings
    
    Admin->>AM: Upload assets (fonts, clipart, etc.)
    AM->>DM: Store asset metadata
    
    Admin->>PD: Open product designer
    PD->>DM: Fetch product data
    DM-->>PD: Return product data
    PD->>AM: Fetch available assets
    AM-->>PD: Return asset list
    
    Admin->>PD: Define personalization areas
    Admin->>PD: Configure personalization options
    PD->>DM: Save product configuration
    DM-->>PD: Confirm save status
```

## 2. Frontend Personalization Flow

```mermaid
sequenceDiagram
    participant Customer
    participant PI as Product Interface
    participant FC as Frontend Controller
    participant CP as Customizer Panel
    participant LP as Live Preview
    participant DM as Data Manager
    participant AM as Asset Manager
    
    Customer->>PI: View product page
    PI->>DM: Check if personalization enabled
    DM-->>PI: Return personalization status
    
    alt Personalization Enabled
        PI->>PI: Display "Personalize" button
        Customer->>PI: Click "Personalize" button
        PI->>FC: Trigger customizer
        FC->>DM: Fetch product configuration
        DM-->>FC: Return configuration
        FC->>CP: Initialize customizer panel
        FC->>LP: Initialize live preview
        
        CP->>AM: Load required assets
        AM-->>CP: Return asset URLs
        
        LP->>AM: Load product image
        AM-->>LP: Return image URL
        
        Customer->>CP: Input personalization options
        CP->>LP: Update preview
        LP-->>Customer: Display updated preview
        
        Customer->>CP: Click "Done/Apply"
        CP->>FC: Finalize personalization
        FC->>PI: Return to product page
    end
```

## 3. Cart and Checkout Flow

```mermaid
sequenceDiagram
    participant Customer
    participant PI as Product Interface
    participant CI as Cart Integration
    participant LP as Live Preview
    participant DM as Data Manager
    
    Customer->>PI: Add personalized product to cart
    PI->>LP: Generate preview image
    LP-->>PI: Return preview image data
    PI->>CI: Add to cart with personalization data
    CI->>DM: Store personalization data with cart item
    
    Customer->>CI: View cart
    CI->>DM: Fetch personalization data
    DM-->>CI: Return personalization data
    CI->>CI: Display personalization summary
    
    opt Edit Personalization
        Customer->>CI: Click "Edit Personalization"
        CI->>PI: Reopen customizer with saved data
    end
    
    Customer->>CI: Proceed to checkout
```

## 4. Order Processing Flow

```mermaid
sequenceDiagram
    participant Customer
    participant CO as Checkout
    participant OI as Order Integration
    participant PG as PDF Generator
    participant DM as Data Manager
    
    Customer->>CO: Complete order
    CO->>OI: Process order with personalized items
    OI->>DM: Transfer personalization data from cart to order
    
    alt Auto-generate PDFs
        OI->>PG: Request PDF generation
        PG->>DM: Fetch personalization data
        DM-->>PG: Return personalization data
        PG->>PG: Generate print-ready PDFs
        PG->>DM: Store PDF URLs with order
    end
    
    OI-->>Customer: Display order confirmation
```

## 5. Admin Order Management Flow

```mermaid
sequenceDiagram
    participant Admin as Admin User
    participant OV as Order Viewer
    participant OI as Order Integration
    participant PG as PDF Generator
    participant DM as Data Manager
    
    Admin->>OV: View order details
    OV->>DM: Fetch order personalization data
    DM-->>OV: Return personalization data
    OV->>OV: Display personalization summary
    
    alt Manual PDF Generation
        Admin->>OV: Request PDF generation
        OV->>PG: Trigger PDF generation
        PG->>DM: Fetch personalization data
        DM-->>PG: Return personalization data
        PG->>PG: Generate print-ready PDFs
        PG->>DM: Store PDF URLs with order
        DM-->>OV: Return PDF URLs
        OV-->>Admin: Display PDF download links
    end
```

## 6. Asset Management Flow

```mermaid
sequenceDiagram
    participant Admin as Admin User
    participant AS as Admin Settings
    participant AM as Asset Manager
    participant PS as Performance Service
    participant DM as Data Manager
    
    Admin->>AS: Navigate to asset management
    AS->>DM: Fetch asset categories
    DM-->>AS: Return categories
    
    Admin->>AS: Upload new asset
    AS->>AM: Process asset upload
    AM->>PS: Optimize asset
    PS-->>AM: Return optimized asset
    AM->>DM: Store asset metadata
    DM-->>AM: Confirm storage
    AM-->>AS: Confirm upload success
    
    Admin->>AS: Browse assets
    AS->>DM: Fetch assets with filters
    DM-->>AS: Return filtered assets
    AS-->>Admin: Display asset gallery
```

## 7. Data Persistence Flow

```mermaid
flowchart TD
    subgraph "WordPress Database"
        WPO[WP Options]
        WPM[WP Post Meta]
        WCM[WooCommerce Order Meta]
        PPT[Custom Plugin Tables]
    end
    
    subgraph "File System"
        UPL[Uploads Directory]
        PDF[PDF Storage]
    end
    
    subgraph "Plugin Components"
        PC[Plugin Controller]
        DM[Data Manager]
        AM[Asset Manager]
        PG[PDF Generator]
    end
    
    PC -->|Global Settings| WPO
    DM -->|Product Config| WPM
    DM -->|Order Data| WCM
    DM -->|Asset Metadata| PPT
    
    AM -->|Asset Files| UPL
    PG -->|Generated PDFs| PDF
    
    WPO -->|Load Settings| PC
    WPM -->|Load Config| DM
    WCM -->|Load Order Data| DM
    PPT -->|Load Assets| DM
    
    UPL -->|Load Assets| AM
    PDF -->|Load PDFs| PG
```

## 8. REST API Data Flow

```mermaid
sequenceDiagram
    participant Client
    participant API as REST API
    participant Auth as Authentication
    participant DM as Data Manager
    participant AM as Asset Manager
    participant PG as PDF Generator
    
    Client->>API: Request (with nonce)
    API->>Auth: Validate request
    Auth-->>API: Authentication result
    
    alt Authentication Successful
        API->>DM: Process data request
        
        alt Get Product Config
            DM->>DM: Fetch product configuration
            DM-->>API: Return configuration
            
        else Save Personalization
            DM->>DM: Validate and store personalization
            DM-->>API: Return success status
            
        else Generate Preview
            API->>AM: Request preview generation
            AM->>AM: Generate preview image
            AM-->>API: Return preview URL
            
        else Generate PDF
            API->>PG: Request PDF generation
            PG->>PG: Generate PDF file
            PG-->>API: Return PDF URL
        end
        
        API-->>Client: Response data
        
    else Authentication Failed
        API-->>Client: Error response
    end
```

## 9. Error Handling Flow

```mermaid
flowchart TD
    subgraph "Error Sources"
        UI[User Interface]
        API[API Endpoints]
        BL[Business Logic]
        DB[Database Operations]
        FS[File System]
    end
    
    subgraph "Error Handling"
        ES[Error Service]
        LOG[Error Logging]
        DISP[Error Display]
    end
    
    subgraph "Response Types"
        USR[User-Friendly Messages]
        DEV[Developer Logs]
        ADM[Admin Notifications]
    end
    
    UI -->|UI Errors| ES
    API -->|API Errors| ES
    BL -->|Logic Errors| ES
    DB -->|Database Errors| ES
    FS -->|File System Errors| ES
    
    ES -->|Log Error| LOG
    ES -->|Display Error| DISP
    
    LOG --> DEV
    LOG --> ADM
    DISP --> USR
```

## 10. Performance Optimization Flow

```mermaid
flowchart TD
    subgraph "Asset Requests"
        AR[Asset Request]
        CR[Config Request]
        PR[Preview Request]
    end
    
    subgraph "Performance Services"
        PS[Performance Service]
        CACHE[Caching Layer]
        LAZY[Lazy Loading]
        OPT[Asset Optimization]
    end
    
    subgraph "Response"
        FAST[Optimized Response]
    end
    
    AR -->|Request Asset| PS
    CR -->|Request Config| PS
    PR -->|Generate Preview| PS
    
    PS -->|Check Cache| CACHE
    CACHE -->|Cache Hit| FAST
    CACHE -->|Cache Miss| PS
    
    PS -->|Load Assets| LAZY
    PS -->|Optimize| OPT
    
    LAZY --> FAST
    OPT --> FAST
    PS -->|Store in Cache| CACHE