# Easy Album Orders - WordPress Plugin Overview

## Project Description

Easy Album Orders is a WordPress plugin designed for professional photographers to streamline the process of selling custom albums directly through their website. The plugin provides a complete order management system with customizable album options and an intuitive front-end ordering experience for clients.

## Core Concept

Photographers create customized order forms for their clients, which are accessible via unique front-end pages. When clients submit orders, they're stored in a centralized order management system where photographers can track, edit, and fulfill orders.

---

## Key Features

### For Photographers (Admin)
- **Client Album Management**: Create and manage individual client album order forms with unique URLs
- **Album Design Upload**: Upload PDF album designs with base pricing
- **Material Configuration**: Define materials with sophisticated options:
  - Material images for visual display
  - Per-material engraving permissions
  - Size restrictions per material
  - Color options (solid colors OR texture swatches from images)
  - Upcharge pricing per material
- **Size Management**: Define available sizes with upcharge pricing
- **Engraving Methods Management**: Configure multiple engraving methods (Foil Stamp, Debossing, Laser, etc.)
  - Individual upcharge pricing per method
  - Character limits per method
  - Font options per method
- **Album Credits System**: Offer dollar credits or quantity credits per design
- **Pricing Control**: Base price + upcharges (material, size, engraving) - credits
- **Order Management Dashboard**: View all album orders with status filters
- **Order Status Tracking**: Track albums as Submitted, Ordered, or Shipped
- **Full Order Editing**: Modify any aspect of an album order (design, material, size, engraving, customer info)
- **Fix Client Mistakes**: Edit orders after submission to correct errors
- **Loom Video Integration**: Add personalized video messages for clients

### For Clients (Front-End)
- **Personalized Order Page**: Unique URL (`/album/client-name/`) with optional welcome video
- **Single-Page Interface**: All functionality on one page (no separate cart page)
- **Album Design Selection**: Choose from photographer's uploaded designs with pricing
- **Visual Material Selection**: See material images and realistic color/texture options
- **Dynamic Options**: Size availability based on selected material
- **Multiple Engraving Methods**: Choose from different methods (Foil Stamp, Debossing, Laser) with different pricing
- **Conditional Engraving**: Only available for materials that support it
- **Real-Time Pricing**: See price calculations with base price, upcharges, and credits
- **Shopping Cart Widget**: View submitted albums on same page
- **Edit Before Checkout**: Edit or remove submitted albums before finalizing
- **Address Management**: Enter shipping address once for all albums
- **Checkout Process**: Lock in all albums with one click
- **No Post-Checkout Editing**: Once ordered, must contact photographer for changes

---

## Architecture Overview

### Custom Post Types

#### 1. Client Album (`client_album`)
- **Purpose**: Creates individual order forms for specific clients
- **Visibility**: Generates front-end pages accessible by clients (URL: `/album/client-name/`)
- **Meta Data**:
  - **Client Information**:
    - Client name
    - Client email (optional)
    - Loom Video URL (optional personalized video message)
  - **Album Designs** (Repeater field):
    - Design name
    - Uploaded PDF file
    - Design preview/thumbnail
    - Base price
    - Page count
    - Album credits:
      - Credit type (none, dollar, or quantity)
      - Credit amount (if dollar type)
  - Form expiration date (optional)
  - Order form slug/URL (auto-generated from client name)
- **Uses Global Settings**: Materials, sizes, colors, and engraving options all come from global settings
- **Admin View**: List of all client albums with management options

#### 2. Album Orders (`album_order`)
- **Purpose**: Each individual album order is a separate post
- **Visibility**: Admin-only (back-end), but clients can edit on front-end before checkout
- **Status Workflow**:
  1. **Submitted**: Album added to cart, client can still edit on front-end
  2. **Ordered**: Client clicked checkout, locked from front-end editing
  3. **Shipped**: Photographer fulfilled and shipped the order
- **Meta Data**:
  - Associated client album ID (links to the client album form used)
  - **Album Details**:
    - Album name (customer-provided)
    - Selected album design (design name, PDF reference, base price)
    - Selected material (name, image, upcharge)
    - Selected material color (name, color/texture reference)
    - Selected size (dimensions, upcharge)
    - Engraving details (method name, text, font, upcharge) - if applicable
    - Applied credits (dollar amount or quantity)
    - Calculated price (base + upcharges - credits)
  - **Customer Information**:
    - Name
    - Email
    - Phone
    - Shipping address
  - Order status (submitted, ordered, shipped)
  - Submission date (when added to cart)
  - Order date (when checkout was clicked)
  - Shipped date (when marked as shipped)
  - Notes from client and photographer
- **Admin View**: List of all album orders with status filters and full edit capability
- **Front-End View**: Client can view and edit "submitted" albums before checkout

### Settings System

#### Global Album Settings
**Location**: WordPress Admin > Client Albums > Album Options

This is where photographers configure their entire album catalog once, which applies to all clients.

#### Materials (Repeater Field)
Each material includes:
- **Material Image**: Visual representation for front-end display
- **Material Name**: Display name (e.g., "Italian Leather", "Linen", "Silk")
- **Upcharge Price**: Additional cost for this material (can be $0)
- **Allow Engraving**: Toggle to enable/disable engraving for this specific material
- **Restrict Available Sizes**: Limit which sizes are available for this material
- **Available Colors** (Sub-repeater):
  - Color name (e.g., "Brown", "Distressed Brown", "White")
  - Color type: Solid or Texture
  - Solid color value (hex/color picker)
  - OR Texture image + selected region coordinates
  - Example: Distressed leather shows actual distress marks

#### Sizes (Repeater Field)
Each size includes:
- **Size Name**: Display name (e.g., "8x10", "10x10", "12x12")
- **Dimensions**: Width x Height for display
- **Upcharge Price**: Additional cost for this size (can be $0)

#### Engraving Options (Repeater Field)
Each engraving method includes:
- **Engraving Name**: Display name (e.g., "Foil Stamp", "Debossing", "Laser Engraving")
- **Upcharge Price**: Additional cost for this engraving method
- **Character Limit**: Maximum characters allowed for this method (optional)
- **Available Fonts**: List of font options for this method (optional)
- **Description**: Brief explanation of the engraving method for customers

**Note**: Only materials with "Allow Engraving" enabled will show engraving options on the front-end.

#### Other Settings
- Email notification settings
- Currency settings
- General plugin settings

---

### Per-Client Album Configuration
**Location**: Client Albums > Add New / Edit

When creating a client album, photographers only configure:

#### Client Information
- **Client Name**: Display name for the client
- **Client Email**: For notifications (optional)
- **Loom Video URL**: Personalized welcome message (optional)

#### Album Designs (Repeater Field)
Each album design includes:
- **Design Name**: Display name (e.g., "30-Page Wedding Album", "5-Page Mini Album")
- **Album Design PDF**: Upload PDF showing the album layout/design
- **Design Preview**: Thumbnail (auto-generated or uploaded)
- **Base Price**: Starting price for this design (e.g., $500)
- **Page Count**: Number of pages in this design
- **Album Credits** (Two options):
  1. **Dollar Credit**: Fixed dollar amount off (e.g., $250 credit)
  2. **Quantity Credit**: Free album at base price (upcharges still apply)

**Note**: All materials, sizes, colors, and engraving options come from the global settings. The photographer doesn't need to reconfigure these for each client.

---

## User Flow

### Photographer Workflow

1. **Setup** (One-time)
   - Install and activate plugin
   - Navigate to **Client Albums > Album Options**
   - **Configure Global Materials**:
     - Add materials (name, image, upcharge)
     - For each material:
       - Enable/disable engraving
       - Restrict available sizes
       - Add color options (solid or texture swatches)
   - **Configure Global Sizes**:
     - Add sizes (name, dimensions, upcharge)
   - **Configure Engraving Methods**:
     - Add engraving methods (e.g., Foil Stamp, Debossing, Laser)
     - Set upcharge price for each method
     - Configure character limits per method
     - Set available fonts per method (optional)
   - Set up email notifications and other settings

2. **Create Client Album** (For each client)
   - Navigate to **Client Albums > Add New**
   - **Enter Client Information**:
     - Client name (required)
     - Client email (optional)
     - Loom Video URL (optional personalized message)
   - **Add Album Designs**:
     - Upload PDF files of album designs
     - Set design name and page count
     - Set base price for each design
     - Configure album credits per design:
       - None, Dollar amount ($250 off), or Quantity (free base price)
   - Publish to generate front-end order form
   - Share unique URL with client: `/album/client-name/`
   
   **Note**: All materials, sizes, colors, and options come from global settings automatically

3. **Manage Orders**
   - Navigate to **Album Orders**
   - View all individual album orders
   - Filter by status (Submitted, Ordered, Shipped)
   - Filter by client album
   - **Edit Album Orders**:
     - Click to edit any album order
     - Modify any field (design, material, color, size, engraving, customer info)
     - Useful when clients call with changes/corrections
     - Update status (Submitted → Ordered → Shipped)
   - Add photographer notes
   - Mark as shipped when fulfilled

### Client Workflow

1. **Access Order Form**
   - Receive unique URL from photographer: `/album/client-name/`
   - Visit personalized order form page
   - View welcome video (Loom embed) if provided

2. **Configure Album** (Can repeat for multiple albums)
   - Enter album name (custom title for this album)
   - **Select Album Design**: Choose from photographer's uploaded designs
     - View design PDF preview
     - See base price and page count
     - See any applied credits for this design
   - **Select Material**: Choose from available materials (from photographer's global settings)
     - View material image
     - See material name and upcharge
   - **Select Material Color**: Choose from colors available for selected material
     - View solid color swatch OR material texture preview
   - **Select Size**: Choose from available sizes (filtered by material restrictions)
     - See size dimensions and upcharge
   - **Add Engraving** (if material allows):
     - Select engraving method (e.g., Foil Stamp, Debossing, Laser)
     - Enter engraving text (respecting character limit for selected method)
     - Select font (from available options for selected method)
     - See engraving method upcharge
   - View calculated price for this album (base + upcharges - credits)
   - **Add to Cart**
     - Album is saved with status "Submitted"
     - Album appears in cart area on same page

3. **Manage Cart** (Same page)
   - View all "submitted" albums in cart widget
   - See total price for all albums
   - **Edit albums**: Click to edit any submitted album
   - **Remove albums**: Delete submitted albums
   - Enter shipping address (saved and reused for all albums)
   - Add special requests/notes

4. **Checkout**
   - Click "Checkout" button
   - All "submitted" albums change status to "ordered"
   - Albums are now locked from client editing
   - Client receives confirmation

5. **Post-Checkout**
   - Client can no longer edit ordered albums
   - Photographer can edit everything on back-end if needed

---

## Order Status Workflow

### Three Status States

1. **Submitted** (Yellow/Pending)
   - Album added to cart on front-end
   - Client can still edit or remove
   - Not yet confirmed by client
   - Visible in cart widget

2. **Ordered** (Blue/Active)
   - Client clicked "Checkout" button
   - All submitted albums transition to ordered
   - Locked from client editing
   - Photographer notified (email)
   - Photographer can edit on back-end

3. **Shipped** (Green/Complete)
   - Photographer fulfilled and shipped the album
   - Final state
   - Can include tracking information

### Status Transitions

```
[Add to Cart] → SUBMITTED
               ↓ (client can edit/remove)
               ↓
[Checkout] → ORDERED
               ↓ (photographer fulfills)
               ↓
[Mark Shipped] → SHIPPED
```

### Important Notes
- Each album is an individual `album_order` post
- "Cart" is a collection of albums with "submitted" status for a specific client
- Once "ordered", only photographer can modify on back-end
- Photographer can fix mistakes (wrong design, spelling errors, etc.)

---

## Admin Menu Structure

The plugin creates a streamlined admin interface with two main menu items:

### 1. Client Albums (Parent Menu)
- **Icon**: Camera or photo album icon
- **Capabilities**: `manage_options` or custom capability

**Submenus**:
- **All Client Albums** (default): List view of all client album order forms
- **Add New**: Create a new client album order form
- **Album Options**: Global settings for materials, sizes, engraving, and plugin configuration

### 2. Album Orders (Standalone Menu)
- **Icon**: Shopping cart or orders icon
- **Capabilities**: `manage_options` or custom capability
- **View**: List view of all album orders (each album is a separate post)
- **Filters**: By status (Submitted, Ordered, Shipped), by client album
- **Single View**: Edit individual album with all configuration options
  - Full edit capability (design, material, color, size, engraving)
  - Customer information
  - Status management
  - Notes and dates

---

## Technical Stack

### WordPress Components
- **Custom Post Types**: `client_album`, `album_order`
- **Custom Meta Boxes**: For order details and settings
- **Custom Admin Menus**: 
  - Client Albums (parent) with Add New and Album Options submenus
  - Album Orders (standalone)
- **Custom Taxonomies** (optional): For categorizing materials, sizes
- **Shortcodes**: For embedding order forms
- **REST API** (optional): For AJAX form submissions

### Front-End
- **HTML5 Forms**: Accessible and responsive
- **CSS**: Modern, mobile-friendly styling
- **JavaScript**: Form validation and enhanced UX
- **jQuery** (WordPress default): For AJAX submissions

### Back-End
- **PHP 7.4+**: Core plugin logic
- **WordPress Coding Standards**: Following best practices
- **Object-Oriented Architecture**: Maintainable and extensible code

---

## Proposed File Structure

```
easy-album-orders/
├── easy-album-orders.php           # Main plugin file
├── PLUGIN-OVERVIEW.md              # This file
├── README.md                       # Plugin readme
├── LICENSE                         # License file
│
├── includes/                       # Core plugin functionality
│   ├── class-eao-plugin.php       # Main plugin class
│   ├── class-eao-activator.php    # Activation hooks
│   ├── class-eao-deactivator.php  # Deactivation hooks
│   ├── class-eao-loader.php       # Hooks loader
│   │
│   ├── post-types/                # Custom post type definitions
│   │   ├── class-client-album.php
│   │   └── class-album-order.php
│   │
│   ├── admin/                     # Admin-specific functionality
│   │   ├── class-eao-admin.php
│   │   ├── class-eao-admin-menus.php         # Admin menu registration
│   │   ├── class-eao-album-options.php       # Album Options page handler
│   │   ├── class-eao-meta-boxes.php
│   │   ├── class-eao-material-manager.php    # Material configuration
│   │   ├── class-eao-design-manager.php      # Album design uploads
│   │   └── views/                            # Admin templates
│   │       ├── meta-box-client-album.php
│   │       ├── meta-box-album-order.php
│   │       ├── album-options-page.php        # Album Options settings page
│   │       ├── section-materials.php         # Materials repeater UI
│   │       ├── section-sizes.php             # Sizes repeater UI
│   │       ├── section-engraving.php         # Engraving methods repeater UI
│   │       └── section-general.php           # General settings UI
│   │
│   ├── public/                    # Front-end functionality
│   │   ├── class-eao-public.php
│   │   ├── class-eao-form-handler.php
│   │   ├── class-eao-cart.php                # Cart widget (submitted albums)
│   │   ├── class-eao-pricing.php             # Price calculations
│   │   ├── class-eao-checkout.php            # Checkout handler
│   │   └── templates/                        # Front-end templates
│   │       ├── order-form.php                # Main order page (single page)
│   │       ├── material-selector.php         # Material selection UI
│   │       ├── color-selector.php            # Color/texture selection
│   │       ├── design-selector.php           # Album design selection
│   │       ├── cart-widget.php               # Cart display (submitted albums)
│   │       ├── album-editor.php              # Edit submitted album
│   │       └── checkout-confirmation.php     # Confirmation after checkout
│   │
│   └── core/                      # Core utilities
│       ├── class-eao-database.php
│       ├── class-eao-email.php
│       ├── class-eao-helpers.php
│       └── class-eao-color-picker.php        # Color/texture selection tool
│
├── assets/                        # Static assets
│   ├── css/
│   │   ├── admin.css
│   │   ├── admin-repeater.css               # Repeater field styling
│   │   ├── color-picker.css                 # Color/texture picker
│   │   └── public.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── admin-repeater.js                # Repeater field logic
│   │   ├── color-picker.js                  # Color/texture picker
│   │   ├── cart.js                          # Cart functionality
│   │   ├── pricing-calculator.js            # Real-time price updates
│   │   └── public.js
│   └── images/
│       └── placeholder-material.png         # Default material image
│
├── uploads/                       # Plugin-specific uploads (ignored by git)
│   ├── materials/                 # Material images
│   ├── designs/                   # Album design PDFs
│   └── textures/                  # Color texture images
│
└── languages/                     # Translation files
    └── easy-album-orders.pot
```

---

## Development Phases

### Phase 1: Foundation ✅ COMPLETE
- [x] Set up plugin structure
- [x] Create main plugin file with headers
- [x] Implement activation/deactivation hooks
- [x] Create custom post types (Client Album, Album Orders)
- [x] Basic admin menu integration
- [x] Database tables for global settings (using WordPress options API)

### Phase 2: Admin Interface - Menus & Global Settings ✅ COMPLETE
- [x] Register admin menus:
  - [x] Client Albums (parent with icon)
  - [x] Add New submenu
  - [x] Album Options submenu
  - [x] Album Orders (standalone with icon)
- [x] Album Options page structure (tabbed interface)
- [x] Materials management (repeater fields):
  - [x] Material name, image, upcharge
  - [x] Engraving toggle per material
  - [x] Size restrictions per material
  - [x] Color options (sub-repeater with solid/texture modes)
  - [x] Color/texture picker tool for texture swatches
- [x] Sizes management (repeater fields)
- [x] Engraving methods management (repeater fields):
  - [x] Method name, upcharge
  - [x] Character limits per method
  - [x] Font options per method
- [x] Email and general settings

### Phase 3: Admin Interface - Client Albums & Orders ✅ COMPLETE
- [x] Client Album meta boxes:
  - [x] Client information fields
  - [x] Album designs repeater (upload PDF, pricing, credits)
- [x] Album Order meta boxes:
  - [x] All album configuration fields (editable)
  - [x] Customer information (editable)
  - [x] Status selector (Submitted, Ordered, Shipped)
  - [x] Date fields (submission, order, shipped)
  - [x] Notes (client and photographer)
- [x] Order management dashboard with status filters
- [x] Individual order edit page

### Phase 4: Front-End ✅ COMPLETE
- [x] Single-page order form (`/album/client-name/`)
- [x] Loom video embed
- [x] Fetch and display global materials, sizes, colors, engraving methods
- [x] Album design selector with PDF preview (from client album designs)
- [x] Material selector with images (from global settings)
- [x] Color/texture selector (solid colors and texture swatches)
- [x] Dynamic size filtering based on material restrictions
- [x] Conditional engraving fields:
  - [x] Show engraving options only if material allows
  - [x] Engraving method selector
  - [x] Dynamic character limits and fonts based on selected method
- [x] Real-time price calculator (base + upcharges - credits)
- [x] "Add to Cart" functionality:
  - [x] Creates album_order post with "submitted" status
  - [x] Stores all configuration and customer info
- [x] Cart widget on same page:
  - [x] Display submitted albums
  - [x] Edit submitted albums
  - [x] Remove submitted albums
  - [x] Show total price
- [x] Checkout button:
  - [x] Changes all submitted albums to "ordered" status
  - [x] Locks albums from client editing
- [x] Form validation (client-side and server-side)
- [x] Checkout confirmation
- [x] Responsive styling

### Phase 5: Advanced Features 🔄 IN PROGRESS
- [ ] Email notifications (order confirmation, status updates)
- [ ] PDF generation for orders
- [ ] Export orders to CSV with line-item details
- [ ] Design preview modal/lightbox
- [x] Order notes (client and photographer notes implemented)
- [ ] Communication system (threaded messages)
- [x] Order history section (client can view past orders on front-end)

### Phase 6: Polish 🔄 IN PROGRESS
- [x] Security hardening (nonces, sanitization, validation)
- [ ] Performance optimization
- [x] Internationalization (i18n) - text domain used throughout
- [ ] Documentation (user guide, developer docs)
- [ ] Testing (unit tests, integration tests)

---

## Security Considerations

- **Nonce Verification**: All form submissions
- **Capability Checks**: Restrict admin functions
- **Data Sanitization**: All input data
- **Data Validation**: Ensure data integrity
- **SQL Injection Prevention**: Use WordPress database methods
- **XSS Prevention**: Escape output data

---

## Pricing Calculation Logic

### Formula
```
Album Total = (Base Price + Material Upcharge + Size Upcharge + Engraving Upcharge) - Credits
```

### Example Scenarios

**Scenario 1: No Credits**
- Base Price: $500
- Material (Linen): $0
- Size (10x10): $0
- No Engraving: $0
- **Total: $500**

**Scenario 2: With Upcharges**
- Base Price: $500
- Material (Italian Leather): +$100
- Size (12x12): +$50
- Engraving (Foil Stamp): +$99
- **Total: $749**

**Scenario 3: Dollar Credit**
- Base Price: $500
- Material (Italian Leather): +$100
- Size (12x12): +$50
- Engraving (Debossing): +$149
- Subtotal: $799
- Dollar Credit: -$250
- **Total: $549**

**Scenario 4: Quantity Credit (Free Album)**
- Base Price: $500 → Free ($0)
- Material (Italian Leather): +$100 (still charged)
- Size (10x10): +$0
- No Engraving: $0
- **Total: $100** (customer only pays upcharges)

---

## Future Enhancements

- Payment integration (Stripe, PayPal, Square)
- Advanced discount codes
- Multi-page order forms wizard
- Client login system with order history
- Invoice generation and printing
- Integration with shipping providers (ShipStation, EasyPost)
- Analytics dashboard (popular materials, average order value)
- Client feedback/approval workflow
- Photo gallery integration (auto-populate albums from galleries)
- Bulk ordering for multiple clients
- Tax calculation integration
- International currency support

---

## Plugin Meta Information

- **Plugin Name**: Easy Album Orders
- **Version**: 1.0.0
- **Requires WordPress**: 5.8+
- **Requires PHP**: 7.4+
- **License**: GPL v2 or later
- **Text Domain**: easy-album-orders

---

## Next Steps

1. Review and approve this overview
2. Set up the basic plugin structure
3. Begin Phase 1 development
4. Iterate and test each phase
5. Deploy for production use

---

*This overview is a living document and will be updated as the project evolves.*

