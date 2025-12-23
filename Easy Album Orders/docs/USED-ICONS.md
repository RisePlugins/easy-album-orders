# Used Icons Tracking

This file tracks all Tabler Icons used in the Easy Album Orders plugin. Before final release, unused icons should be removed to reduce plugin size.

## Current Stats

- **Total icons in library**: ~5,984 SVGs (23MB)
- **Icons currently used**: 31 (~124KB estimated)
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
| `award` | Credit summary header, credits section header |
| `copy` | Copy link button |
| `external-link` | View Order Form button |
| `circle-check` | Credit applied indicator |
| `arrow-right` | View All Orders link |
| `plus` | Add Design button |
| `book` | Design card header |
| `trash` | Remove design button |
| `file-type-pdf` | PDF preview |

### Album Order Edit Page (`class-eao-album-order-meta.php`)

| Icon | Usage |
|------|-------|
| `external-link` | View in Stripe link in Payment Information meta box |
| `receipt-refund` | Refund button and confirm button in refund modal |
| `x` | Close button in refund modal |
| `alert-triangle` | Warning message in refund modal |

### Album Options Page (`album-options-page.php`)

*To be updated when icons are added*

### Front-End Templates (`single-client-album.php`)

| Icon | Usage |
|------|-------|
| `photo` | Placeholder for design images without cover |
| `palette` | Placeholder for material images |
| `x` | No engraving card, modal close buttons, proof viewer close |
| `writing` | Engraving option icon |
| `plus` | New address card icon |
| `lock` | Secure payment badge in checkout modal |
| `arrow-left` | Back button in payment step |
| `file-type-pdf` | Proof viewer title |
| `presentation` | Slide view toggle in proof viewer |
| `layout-grid` | Grid view toggle in proof viewer |
| `chevron-left` | Previous page in proof viewer |
| `chevron-right` | Next page in proof viewer |
| `chevron-down` | Order history sidebar accordion toggle |
| `clipboard-list` | Order history sidebar title |
| `shopping-cart` | Cart header and empty cart icon |

### Front-End Cart Item (`cart-item.php`)

| Icon | Usage |
|------|-------|
| `truck` | Ship to label in cart item |
| `circle-check` | Credit applied indicator in cart item |
| `pencil` | Edit button in cart item |
| `trash` | Remove button in cart item |

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
chevron-down
chevron-left
chevron-right
circle-check
clipboard-list
copy
external-link
file-type-pdf
layout-grid
link
lock
palette
pencil
photo
plus
presentation
receipt-refund
shopping-cart
tag
trash
truck
user
writing
x
```

**Total: 31 icons**

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

*Last updated: December 23, 2025*
