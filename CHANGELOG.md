# Changelog

All notable changes to Easy Album Orders will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

_No unreleased changes._

## [1.0.0] - 2026-01-09

### 🎉 Initial Release

Easy Album Orders is a WordPress plugin designed for professional photographers to streamline the process of selling custom albums directly through their website.

### Added

#### Stripe Payment Integration
- Integrated Stripe Payment Intents API for secure, PCI-compliant payment processing
- `EAO_Stripe` class for Stripe API interactions (Payment Intents, webhooks, test/live mode)
- Stripe settings tab in Album Options with API key configuration
- Two-step checkout flow: customer info → card payment
- Webhook handler for payment events (`payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`)
- Payment Information meta box with status badges, amounts, and Stripe Dashboard links
- Admin refund functionality with full/partial refund options
- Free order flow for $0 totals or when credits cover full amount
- Support for 3D Secure / Strong Customer Authentication (SCA)

#### Core Plugin Foundation
- Main plugin file with version checks (PHP 7.4+, WordPress 5.8+)
- Activation/deactivation hooks with default options setup
- Core class architecture (`EAO_Plugin`, `EAO_Loader`, `EAO_Activator`, `EAO_Deactivator`, `EAO_Helpers`)
- Custom Post Types: `client_album` and `album_order`
- GitHub-based auto-updater for automatic plugin updates

#### Admin Interface
- Client Albums parent menu with gallery icon
- Album Options settings page with tabbed interface
- Materials management (repeater with colors, engraving toggle, size restrictions)
- Sizes management (repeater with dimensions and upcharges)
- Engraving methods management (repeater with fonts and character limits)
- General settings (currency, email notifications)
- Client Album meta boxes (client info, Loom video, album credits, designs)
- Album Order meta boxes (status, summary, configuration, customer info, notes)
- Custom columns and filters for both list tables
- Admin UI/UX with modular card layout and design tokens

#### Front-End Order Form
- Single-page client order form (`/album/client-name/`)
- Loom video embed integration
- Album design selector with cover images, pricing, and PDF proofs
- Material selector with images and dynamic color swatches
- Size selector with material-based availability filtering
- Conditional engraving section with method/font selection
- Real-time price calculator with upcharges and credits
- Cart sidebar widget for submitted albums
- Add to Cart, Edit, and Remove functionality via AJAX
- Checkout process that locks orders

#### Credits System
- Per-design album credits (Free Album Credits or Credit Budget)
- Automatic credit pool tracking and depletion
- Credits badge showing remaining available credit
- Price calculator dynamically applies remaining credit

#### Iconography
- Tabler Icons integration for consistent UI
- `EAO_Icons` helper class for rendering inline SVGs
- Outline and filled icon variants

---

For detailed architecture information, see the [documentation](dev/docs/PLUGIN-OVERVIEW.md).
