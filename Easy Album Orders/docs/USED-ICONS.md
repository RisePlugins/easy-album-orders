# Used Icons Tracking

This file tracks all Tabler Icons used in the Easy Album Orders plugin. Before final release, unused icons should be removed to reduce plugin size.

## Current Stats

- **Total icons in library**: ~5,984 SVGs (23MB)
- **Icons currently used**: 48 (~192KB estimated)
- **Potential savings**: ~22.8MB

---

## Icons In Use

Keep this list updated as you add new icons throughout development.

### Client Album Edit Page (`class-eao-client-album-meta.php`)

| Icon | Usage |
|------|-------|
| `user` | Client Information meta box header |
| `brand-loom` | Loom Video meta box header |
| `books` | Album Designs meta box header |
| `link` | Album Order Link meta box header |
| `shopping-cart` | Orders meta box header, empty orders state |
| `award` | Credit summary header |
| `gift` | Free Albums credit type indicator, credits footer header |
| `coin` | Credit Budget type indicator |
| `copy` | Copy link button |
| `external-link` | View Order Form button |
| `circle-check` | Credit applied indicator |
| `arrow-right` | View All Orders link |
| `plus` | Add Design button |
| `trash` | Remove design button |
| `photo` | Cover image placeholder |
| `refresh` | Change cover image button |
| `upload` | Upload cover image button |
| `file-plus` | Select PDF button |
| `file-type-pdf` | PDF file indicator |
| `x` | Remove image/PDF buttons |

### Album Order Edit Page (`class-eao-album-order-meta.php`)

| Icon | Usage |
|------|-------|
| `external-link` | View in Stripe link in Payment Information meta box |
| `receipt-refund` | Refund button and confirm button in refund modal |
| `x` | Close button in refund modal |
| `alert-triangle` | Warning message in refund modal |

### Album Options Page (`album-options-page.php`)

| Icon | Usage |
|------|-------|
| `palette` | Materials navigation item icon |
| `ruler-2` | Sizes navigation item icon |
| `writing` | Engraving navigation item icon |
| `mail` | Emails navigation item icon |
| `credit-card` | Payments navigation item icon |
| `settings` | General navigation item icon |
| `grip-vertical` | Drag handle for reordering materials, sizes, and engravings |

### Front-End Templates (`single-client-album.php`)

| Icon | Usage |
|------|-------|
| `plus` | Page tab "Create an Album", New address card icon, Empty orders state button |
| `clipboard-list` | Page tab "View Album Orders", Order history sidebar title, Empty orders state icon |
| `photo` | Placeholder for design images without cover |
| `palette` | Placeholder for material images |
| `x` | No engraving card, modal close buttons, proof viewer close |
| `writing` | Engraving option icon |
| `lock` | Secure payment badge in checkout modal |
| `arrow-left` | Back button in payment step |
| `file-type-pdf` | Proof viewer title |
| `presentation` | Slide view toggle in proof viewer |
| `layout-grid` | Grid view toggle in proof viewer |
| `chevron-left` | Previous page in proof viewer |
| `chevron-right` | Next page in proof viewer |
| `chevron-down` | Order history sidebar accordion toggle |
| `shopping-cart` | Cart header and empty cart icon |
| `arrow-right` | View All Order Details link in order history sidebar |
| `hash` | Order number in View Album Orders tab |
| `calendar` | Order date in View Album Orders tab |
| `book` | Album Details section in View Album Orders tab |
| `truck` | Shipping section in View Album Orders tab |
| `receipt` | Order Total section in View Album Orders tab |

### Order History Page (`single-client-album-order-history.php`)

| Icon | Usage |
|------|-------|
| `arrow-left` | Back to Order Form link |
| `clipboard-list` | Empty state icon |
| `hash` | Order number display |
| `calendar` | Order date display |
| `book` | Album Details section header |
| `truck` | Shipping section header |
| `receipt` | Order Total section header |

### Front-End Cart Item (`cart-item.php`)

| Icon | Usage |
|------|-------|
| `truck` | Ship to label in cart item |
| `circle-check` | Credit applied indicator in cart item |
| `pencil` | Edit button in cart item |
| `trash` | Remove button in cart item |

### Album Orders KPI Section (`class-eao-admin.php`)

| Icon | Usage |
|------|-------|
| `credit-card` | Total Revenue KPI icon |
| `calendar` | Orders This Month KPI icon |
| `shopping-cart` | In Cart KPI icon |
| `truck` | Ready to Ship KPI icon |

### Reports Page (`reports-page.php`)

| Icon | Usage |
|------|-------|
| `credit-card` | Total Revenue KPI icon |
| `shopping-cart` | Total Orders KPI icon |
| `receipt` | Avg. Order Value KPI icon |
| `truck` | Awaiting Shipment KPI icon |
| `chart-line` | Revenue Over Time chart header |
| `chart-pie` | Orders by Status chart header |
| `chart-bar` | Monthly Performance chart header |
| `chart-dots` | Empty state icon in Popular Choices |
| `award` | Popular Choices card header |

---

## Complete Icon List (Alphabetical)

```
alert-triangle
arrow-left
arrow-right
award
book
books
brand-loom
calendar
chart-bar
chart-dots
chart-line
chart-pie
chevron-down
chevron-left
chevron-right
circle-check
clipboard-list
coin
copy
credit-card
external-link
file-plus
file-type-pdf
gift
grip-vertical
hash
layout-grid
link
lock
mail
palette
pencil
photo
plus
presentation
receipt
receipt-refund
refresh
ruler-2
settings
shopping-cart
tag
trash
truck
upload
user
writing
x
```

**Total: 48 icons**

---

## Cleanup Script

Run this script **after development is complete** to remove unused icons and reduce plugin size.

### Step 1: Create the keep list

Save the icon list above to a file called `keep-icons.txt` (one icon per line).

### Step 2: Run cleanup script

```bash
#!/bin/bash

# Navigate to plugin directory
cd "/path/to/Easy Album Orders Plugin"

# Create backup first
cp -r assets/icons/tabler assets/icons/tabler-backup

# Read keep list
KEEP_FILE="keep-icons.txt"

# Clean outline folder
cd assets/icons/tabler/outline
for file in *.svg; do
    icon_name="${file%.svg}"
    if ! grep -qx "$icon_name" "../../../docs/$KEEP_FILE"; then
        rm "$file"
    fi
done

# Clean filled folder
cd ../filled
for file in *.svg; do
    icon_name="${file%.svg}"
    if ! grep -qx "$icon_name" "../../../docs/$KEEP_FILE"; then
        rm "$file"
    fi
done

echo "Cleanup complete!"
```

### Step 3: Verify and commit

1. Test the plugin to ensure all icons still work
2. Remove the backup folder
3. Commit the changes

---

## Adding New Icons

When you add a new icon:

1. Use `EAO_Icons::render( 'icon-name' )` or `EAO_Icons::get( 'icon-name' )`
2. Add the icon name to the appropriate section above
3. Add to the "Complete Icon List" in alphabetical order
4. Update the count

---

## Icon Reference

Browse available icons at: https://tabler.io/icons

Common categories for this plugin:
- **E-commerce**: `shopping-cart`, `credit-card`, `package`, `truck`, `receipt`
- **Albums/Photos**: `book`, `books`, `photo`, `camera`, `palette`
- **Actions**: `plus`, `minus`, `trash`, `edit`, `check`, `x`
- **Navigation**: `arrow-left`, `arrow-right`, `chevron-down`, `external-link`
- **Status**: `circle-check`, `circle-x`, `alert-circle`, `info-circle`
- **Users**: `user`, `users`, `mail`, `phone`

---

*Last updated: December 28, 2025 - Added Page Tabs (Create an Album / View Album Orders)*
