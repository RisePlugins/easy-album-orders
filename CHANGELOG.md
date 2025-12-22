# Changelog

All notable changes to Easy Album Orders will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added (Phase 5: Stripe Payment Integration)
- Integrated Stripe Payment Intents API for secure, PCI-compliant payment processing
- New `EAO_Stripe` class (`includes/core/class-eao-stripe.php`) for Stripe API interactions:
  - Payment Intent creation and retrieval
  - Webhook signature verification
  - Test/Live mode switching
  - Statement descriptor configuration
- Stripe settings tab in Album Options page:
  - Enable/Disable toggle for payment requirement
  - Test/Live mode selector
  - API key fields (publishable and secret keys for both modes)
  - Webhook secret configuration
  - Statement descriptor customization
  - Webhook URL display with copy-to-clipboard
  - Setup guide with step-by-step instructions
- Two-step checkout flow in frontend:
  - Step 1: Customer information collection
  - Step 2: Stripe Elements card input
  - Real-time card validation
  - Secure payment badge
  - Back button for step navigation
- New AJAX endpoints for payment processing:
  - `eao_create_payment_intent` - Creates Stripe Payment Intent
  - `eao_confirm_payment` - Confirms payment and completes checkout
- Webhook handler (`includes/public/class-eao-stripe-webhook.php`):
  - REST endpoint at `/wp-json/eao/v1/stripe-webhook`
  - Handles `payment_intent.succeeded` events
  - Handles `payment_intent.payment_failed` events
  - Handles `charge.refunded` events
  - Idempotency checks to prevent duplicate processing
  - Fallback customer info saving from metadata
  - Debug logging when WP_DEBUG is enabled
- Payment Information meta box on Album Order edit screen:
  - Payment status badge (Paid/Failed/Refunded/Pending/Free)
  - Amount paid and refund amounts
  - Direct link to Stripe Dashboard (test/live mode aware)
  - Payment date display
  - Error message display for failed payments
- Payment column in Album Orders list table
- Payment badge CSS styles for all payment states
- Free order flow (no payment required for $0 totals or when credits cover full amount)
- Stripe.js integration for frontend card collection (never touches server)
- Support for 3D Secure / Strong Customer Authentication (SCA)

### Added (Phase 1: Foundation)
- Main plugin file (`easy-album-orders.php`) with plugin headers and version checks
- Activation hooks with default options setup
- Deactivation hooks with cleanup routines
- Core class architecture:
  - `EAO_Plugin` - Main orchestration class
  - `EAO_Loader` - WordPress hooks management
  - `EAO_Activator` - Plugin activation routines
  - `EAO_Deactivator` - Plugin deactivation cleanup
  - `EAO_Helpers` - Utility functions
- Custom Post Types:
  - `client_album` - Client album order forms with front-end pages (`/album/client-name/`)
  - `album_order` - Individual album orders with status tracking
- Admin menu structure:
  - Client Albums parent menu with gallery icon
  - Album Options submenu page
  - Album Orders standalone menu with cart icon
- Album Options settings page with tabbed interface:
  - Materials management (repeater with colors, engraving toggle, size restrictions)
  - Sizes management (repeater with dimensions and upcharges)
  - Engraving methods management (repeater with fonts and character limits)
  - General settings (currency, email notifications)
- Admin assets (CSS and JavaScript) for repeater fields and media uploads
- Public assets (CSS and JavaScript) for front-end order forms
- Helper functions for price calculation and formatting

### Added (Phase 3: Admin Interface - Client Albums & Orders)
- Client Album meta boxes:
  - Client Information (name, email, phone)
  - Loom Video URL with embed preview
  - Album Credits for client discounts
  - Album Designs repeater (name, cover image, PDF proof, base price)
  - Album Order Link with copy-to-clipboard functionality
- Album Order meta boxes:
  - Order Status with date tracking (submitted, ordered, shipped)
  - Order Summary with price breakdown
  - Album Configuration (design, material, color, size, engraving)
  - Customer Information (name, email, phone, shipping address)
  - Order Notes (client notes, internal photographer notes)
  - Related Client Album link
- Custom columns for Client Albums list:
  - Client name and email
  - Number of designs
  - Album credits
  - Order count with link to filtered orders
  - View order form link
- Custom columns for Album Orders list:
  - Order number (EAO-000001 format)
  - Album name
  - Related Client Album
  - Customer info
  - Configuration summary
  - Total price
  - Status badge with color coding
- Album Order filters:
  - Filter by status (Submitted, Ordered, Shipped)
  - Filter by Client Album
- Sortable columns for both list tables
- PDF upload support in media library

### Changed (Admin UI/UX Redesign)
- Complete redesign of Album Options page with modular card layout
- Materials now display as full-width cards (no accordion)
- Colors now display as circular swatches with visual preview
- Added color edit modal for adding/editing colors
- Size cards with inline editing
- Engraving cards with card-based layout
- General settings organized into settings cards
- Improved responsive design for all admin components
- New design tokens and CSS custom properties
- Toggle switches for boolean settings
- Consistent form styling across all admin pages

### Changed (Credits System Redesign)
- Album credits are now per-design instead of per-client-album
- Each design can have:
  - Free Album Credits: Number of free albums (base price covered, upgrades still apply)
  - Credit Budget: A pool of dollar credit that depletes as orders are placed
- Credit pools are automatically tracked:
  - Free album credits decrement by 1 per order
  - Dollar credit pools reduce by the amount applied per order
- Example: $200 credit budget on a $250 design = first album costs $50, second costs $250
- Credits badge shows remaining available credit
- Price calculator dynamically applies remaining credit (up to album total)
- Cart items show which credit type was applied
- Admin order view shows credit type (Free Album Credit vs Album Credit)

### Added (Phase 4: Front-End Order Form)
- Single-page client order form template (`/album/client-name/`)
- Loom video embed at top of order form
- Album design selector with:
  - Cover image display
  - Base price display
  - PDF proof link
- Material selector with:
  - Material images
  - Upcharge display
  - Dynamic color swatches based on material
- Color/texture selector with visual swatches and tooltips
- Size selector with:
  - Dimensions display
  - Upcharge display
  - Material-based availability filtering
- Conditional engraving section:
  - Only shows when material allows engraving
  - Method selection with upcharge display
  - Dynamic character limit per method
  - Font selection based on method
- Real-time price calculator showing:
  - Base price
  - Material upcharge
  - Size upcharge
  - Engraving upcharge
  - Album credits applied
  - Running total
- Cart sidebar widget:
  - Display all submitted (in-cart) albums
  - Edit submitted albums
  - Remove albums from cart
  - Running cart total
- Add to Cart functionality via AJAX
- Edit existing cart items
- Checkout process that locks orders
- Order confirmation message
- Template loader with theme override support
- AJAX handlers for all cart operations

### Added (Iconography System)
- Integrated Tabler Icons Pro library for consistent iconography
- Created `EAO_Icons` helper class for rendering inline SVGs
- Icon files stored in `assets/icons/tabler/` (outline and filled variants)
- Added icon CSS styles to both admin and public stylesheets
- Updated DESIGN-SYSTEM.md with comprehensive iconography documentation

### Planning
- Plugin architecture design
- Custom post types (Client Albums, Album Orders)
- Admin menu structure
- Global album options system
- Front-end order form design
- Status workflow (Submitted → Ordered → Shipped)

## [1.0.0] - TBD

### Planned Features

#### Phase 1: Foundation
- Main plugin file and headers
- Activation/deactivation hooks
- Custom post types registration
- Database setup
- Basic admin menu integration

#### Phase 2: Admin Interface - Global Settings
- Client Albums parent menu
- Album Options submenu page
- Materials management (repeater fields)
- Sizes management (repeater fields)
- Engraving methods management (repeater fields)
- Color/texture picker tool
- Email and general settings

#### Phase 3: Admin Interface - Client Albums & Orders
- Client Album meta boxes
- Album designs upload and configuration
- Album credits system
- Album Orders meta boxes
- Individual order edit capability
- Status management
- Order dashboard with filters

#### Phase 4: Front-End
- Single-page order form (`/album/client-name/`)
- Loom video integration
- Material selector with visual display
- Color/texture selector
- Dynamic size filtering
- Conditional engraving options
- Real-time price calculator
- Cart widget (submitted albums)
- Album editing (before checkout)
- Checkout process
- Status transitions

#### Phase 5: Advanced Features
- Email notifications
- PDF generation
- Order export to CSV
- Design preview modal
- Order notes and communication

#### Phase 6: Polish
- Security hardening
- Performance optimization
- Internationalization (i18n)
- Documentation
- Testing

---

## Version Notes

**[Unreleased]** - Currently in planning and initial development phase.

For detailed architecture information, see [PLUGIN-OVERVIEW.md](PLUGIN-OVERVIEW.md)

