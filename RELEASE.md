# Easy Album Orders - Release Guide

This guide explains how to release new versions of the Easy Album Orders plugin.

---

## Quick Release (via Cursor)

When you're ready to push a new version to all users, just prompt:

> **"Release version X.X.X with message: [your release notes]"**

Example:
> "Release version 1.1.0 with message: Added PDF export for orders"

This will:
1. Update version numbers in all relevant files
2. Build the production zip
3. Create a git tag
4. Push to GitHub
5. Create a GitHub release with the zip attached

---

## Manual Release Process

### Step 1: Update Version Numbers

Update the version in these files:

1. **`easy-album-orders/easy-album-orders.php`** (two places):
   ```php
   * Version:           1.1.0
   ```
   ```php
   define( 'EAO_VERSION', '1.1.0' );
   ```

2. **`CHANGELOG.md`** - Add release notes under the version heading

### Step 2: Build Production Zip

Run the build script:

```bash
./dev/scripts/build.sh
```

Or specify a version:

```bash
./dev/scripts/build.sh 1.1.0
```

This creates: `dist/easy-album-orders-1.1.0.zip`

### Step 3: Commit & Tag

```bash
# Commit version changes
git add -A
git commit -m "chore: Bump version to 1.1.0"

# Create annotated tag
git tag -a v1.1.0 -m "Release 1.1.0: [brief description]"

# Push everything
git push origin main --tags
```

### Step 4: Create GitHub Release

1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Select the tag you just created (e.g., `v1.1.0`)
4. Title: `v1.1.0`
5. Description: Copy from CHANGELOG.md
6. **Attach the zip file** from `dist/easy-album-orders-1.1.0.zip`
7. Click "Publish release"

---

## GitHub Repository Setup (One-Time)

### 1. Create the GitHub Repository

If you haven't already:
```bash
# Initialize and push
cd "/path/to/Easy Album Orders Plugin"
git remote add origin https://github.com/YOUR_USERNAME/easy-album-orders.git
git push -u origin main
```

### 2. Configure Auto-Updates

In `easy-album-orders/easy-album-orders.php`, set your GitHub repo:

```php
define( 'EAO_GITHUB_REPO', 'YOUR_USERNAME/easy-album-orders' );
```

For example:
```php
define( 'EAO_GITHUB_REPO', 'ryanmoreno/easy-album-orders' );
```

### 3. For Private Repositories

If your repo is private, you'll need to add an access token:

1. Generate a token at: https://github.com/settings/tokens
2. Grant `repo` scope
3. Update `eao_init_updater()` in the main plugin file:
   ```php
   new EAO_GitHub_Updater(
       __FILE__,
       EAO_GITHUB_REPO,
       'your-access-token-here'
   );
   ```

---

## How Auto-Updates Work

Once configured:

1. WordPress checks for plugin updates periodically
2. The `EAO_GitHub_Updater` class queries your GitHub releases
3. If a newer version exists, users see "Update available"
4. Clicking "Update Now" downloads the zip from GitHub
5. WordPress installs it like any other plugin update

### Requirements for Users

- The zip must be attached to the GitHub release
- The zip must contain a folder named `easy-album-orders/`
- Version in the zip must be higher than installed version

---

## Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., 1.2.3)
  - **MAJOR**: Breaking changes
  - **MINOR**: New features (backwards compatible)
  - **PATCH**: Bug fixes

Examples:
- `1.0.0` → `1.0.1` (bug fix)
- `1.0.1` → `1.1.0` (new feature)
- `1.1.0` → `2.0.0` (breaking change)

---

## Checklist Before Release

- [ ] All features tested in WordPress
- [ ] No PHP errors or warnings
- [ ] JavaScript console is clean
- [ ] Mobile responsive verified
- [ ] CHANGELOG.md updated
- [ ] Version numbers updated
- [ ] Build script runs without errors
- [ ] Zip installs correctly on fresh WordPress

---

## Troubleshooting

### Users Don't See Updates

1. Clear WordPress transients: delete `eao_github_release` from `wp_options`
2. Check that `EAO_GITHUB_REPO` is set correctly
3. Verify the GitHub release has a zip file attached
4. Ensure version in zip is higher than installed

### Build Script Fails

1. Make sure you're in the project root
2. Check that `easy-album-orders/` folder exists
3. Verify `chmod +x dev/scripts/build.sh`

### Plugin Doesn't Update

1. Check folder name in zip matches `easy-album-orders`
2. Verify plugin file has correct version header
3. Test manually downloading and uploading zip

---

## File Structure Reference

```
Easy Album Orders Plugin/           # Project root (git repo)
├── easy-album-orders/              # Production plugin
│   ├── assets/
│   ├── includes/
│   ├── vendor/
│   ├── languages/
│   ├── easy-album-orders.php
│   └── LICENSE
├── dev/                            # Development files
│   ├── docs/
│   │   ├── PLUGIN-OVERVIEW.md
│   │   ├── DESIGN-SYSTEM.md
│   │   ├── STRIPE-INTEGRATION.md
│   │   └── USED-ICONS.md
│   └── scripts/
│       └── build.sh
├── dist/                           # Production zips (gitignored)
├── tabler-icons-full/              # Icon library (gitignored)
├── .cursorrules
├── .gitignore
├── CHANGELOG.md
├── README.md
├── RELEASE.md                      # This file
├── composer.json
└── composer.lock
```

---

## Questions?

If you need help with the release process, just ask Cursor:
- "How do I release a new version?"
- "Build and release version 1.2.0"
- "What's the current version?"
