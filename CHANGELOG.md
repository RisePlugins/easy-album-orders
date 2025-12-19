# Changelog

All notable changes to Easy Album Orders will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

