# Easy Album Orders

A WordPress plugin designed for professional photographers to streamline the process of selling custom albums directly through their website.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

## Overview

Easy Album Orders provides a complete order management system for photographers to create customized album order forms for their clients. Clients can configure albums with various materials, sizes, colors, and engraving options, all from a personalized front-end interface.

## Features

### For Photographers (Admin)

- **Client Album Management**: Create unique order forms for each client with personalized URLs
- **Global Album Options**: Configure materials, sizes, and engraving methods once, use for all clients
- **Material Configuration**:
  - Upload material images
  - Set upcharge pricing
  - Define color options (solid colors or texture swatches)
  - Control engraving availability per material
  - Restrict available sizes per material
- **Multiple Engraving Methods**: Configure different engraving types (Foil Stamp, Debossing, Laser) with individual pricing
- **Album Design Upload**: Upload PDF designs with base pricing and credits
- **Album Credits System**: Offer dollar credits or quantity credits to honor packages
- **Order Management**: View, edit, and track all album orders
- **Status Tracking**: Monitor orders through Submitted → Ordered → Shipped
- **Full Edit Capability**: Modify any order detail to fix client mistakes
- **Loom Video Integration**: Add personalized welcome videos for clients

### For Clients (Front-End)

- **Personalized Order Page**: Unique URL with optional welcome video
- **Single-Page Interface**: Configure albums, view cart, and checkout on one page
- **Visual Selection**: See material images and realistic texture previews
- **Real-Time Pricing**: Dynamic price calculation with upcharges and credits
- **Shopping Cart**: Add multiple albums before checkout
- **Edit Before Checkout**: Modify or remove submitted albums
- **Simple Checkout**: Lock in all albums with one click

## Order Workflow

```
Configure Album → Add to Cart (SUBMITTED)
                        ↓
              Client can edit/remove
                        ↓
                  Checkout (ORDERED)
                        ↓
             Photographer fulfills
                        ↓
                Mark as Shipped (SHIPPED)
```

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/easy-album-orders/`
3. Activate the plugin through WordPress admin
4. Navigate to **Client Albums > Album Options** to configure global settings

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Modern web browser for optimal front-end experience

## Setup Guide

### Initial Configuration

1. **Configure Album Options** (Client Albums > Album Options):
   - Add materials with images, colors, and pricing
   - Add available sizes with dimensions and pricing
   - Configure engraving methods (Foil Stamp, Debossing, Laser, etc.)
   - Set up email notifications

2. **Create Client Album** (Client Albums > Add New):
   - Enter client name and email
   - Add Loom video URL (optional)
   - Upload album designs (PDFs) with pricing
   - Configure album credits per design
   - Publish to generate unique client URL

3. **Share with Client**:
   - Send client their unique URL: `/album/client-name/`
   - Client configures and orders albums
   - View orders in **Album Orders** menu

## Menu Structure

### Client Albums
- **All Client Albums**: Manage all client order forms
- **Add New**: Create new client album form
- **Album Options**: Global settings (materials, sizes, engraving)

### Album Orders
- View all individual album orders
- Filter by status (Submitted, Ordered, Shipped)
- Edit any order to fix mistakes

## Pricing System

Album pricing is calculated as:

```
Total = (Base Price + Material Upcharge + Size Upcharge + Engraving Upcharge) - Credits
```

### Credit Types

- **Dollar Credit**: Fixed discount amount (e.g., $250 off)
- **Quantity Credit**: Free base price, customer pays only upcharges

## Development Status

🚧 **Currently in active development** 

See [PLUGIN-OVERVIEW.md](PLUGIN-OVERVIEW.md) for detailed architecture and development roadmap.

### Development Phases

- [ ] Phase 1: Foundation (Plugin structure, custom post types)
- [ ] Phase 2: Admin Interface - Menus & Global Settings
- [ ] Phase 3: Admin Interface - Client Albums & Orders
- [ ] Phase 4: Front-End (Order form, cart, checkout)
- [ ] Phase 5: Advanced Features (Email, PDF, exports)
- [ ] Phase 6: Polish (Security, performance, i18n)

## Contributing

This is a private plugin project. For questions or feature requests, please contact the development team.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please contact the RisePlugins team.

## Changelog

### Version 1.0.0 (In Development)
- Initial development
- Core architecture planning
- Custom post types design
- Admin interface design
- Front-end workflow design

## Credits

Developed by [RisePlugins](https://github.com/RisePlugins)

---

**Note**: This plugin is currently under active development. Features and functionality are subject to change.

