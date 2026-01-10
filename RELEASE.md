# Easy Album Orders - Release Guide

This guide explains how to release new versions of the Easy Album Orders plugin.

---

## 🚀 Quick Release (Fully Automated)

### Via Cursor

Just tell Cursor:
> **"Release version 1.2.0 with message: Added new feature"**

### Via Terminal

Run the release script:
```bash
./dev/scripts/release.sh 1.2.0 "Added new feature"
```

This single command will:
1. ✅ Update version numbers in plugin file
2. ✅ Commit changes
3. ✅ Create git tag
4. ✅ Push to GitHub
5. ✅ Build production zip
6. ✅ Create GitHub release with zip attached

**Done!** Users will see the update in WordPress.

---

## Build Only (No Release)

To just create a production zip without releasing:

```bash
./dev/scripts/build.sh
```

Or specify a version:
```bash
./dev/scripts/build.sh 1.2.0
```

Output: `dist/easy-album-orders-1.2.0.zip`

---

## Build + Release (Alternative)

```bash
./dev/scripts/build.sh 1.2.0 --release "Your release notes"
```

---

## Prerequisites

### GitHub CLI (One-Time Setup)

The release scripts use GitHub CLI. Install and authenticate:

```bash
# Install
brew install gh

# Authenticate (opens browser)
gh auth login
```

---

## How Auto-Updates Work

1. Plugin checks GitHub for new releases (every 6 hours)
2. If a newer version exists, WordPress shows "Update available"
3. User clicks "Update Now"
4. WordPress downloads the zip from GitHub and installs it

### Requirements
- GitHub release must have a `.zip` file attached
- Version in zip must be higher than installed version

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

## Manual Release Process

If you prefer manual control:

### Step 1: Update Version Numbers

In `easy-album-orders/easy-album-orders.php`:
```php
* Version:           1.2.0
```
```php
define( 'EAO_VERSION', '1.2.0' );
```

### Step 2: Build

```bash
./dev/scripts/build.sh 1.2.0
```

### Step 3: Commit & Tag

```bash
git add -A
git commit -m "chore: Release version 1.2.0"
git tag -a v1.2.0 -m "Release 1.2.0"
git push origin main --tags
```

### Step 4: Create GitHub Release

```bash
gh release create v1.2.0 \
  --title "v1.2.0" \
  --notes "Release notes here" \
  ./dist/easy-album-orders-1.2.0.zip
```

---

## Checklist Before Release

- [ ] All features tested in WordPress
- [ ] No PHP errors or warnings
- [ ] JavaScript console is clean
- [ ] Mobile responsive verified
- [ ] Version numbers correct

---

## File Structure

```
Easy Album Orders Plugin/
├── easy-album-orders/              # Production plugin
├── dev/
│   ├── docs/                       # Documentation
│   └── scripts/
│       ├── build.sh                # Build production zip
│       └── release.sh              # Full automated release
├── dist/                           # Production zips (gitignored)
├── CHANGELOG.md
├── README.md
└── RELEASE.md                      # This file
```

---

## Troubleshooting

### Users Don't See Updates
1. Wait up to 6 hours (WordPress caches)
2. Clear transient: `eao_github_release` in `wp_options`
3. Verify zip is attached to GitHub release

### Release Script Fails
1. Ensure `gh auth status` shows logged in
2. Check you're in project root directory
3. Verify version doesn't already exist as tag

### "Tag already exists" Error
The version has already been released. Use a higher version number.

---

## Quick Reference

| Task | Command |
|------|---------|
| Full release | `./dev/scripts/release.sh 1.2.0 "Notes"` |
| Build only | `./dev/scripts/build.sh` |
| Build + release | `./dev/scripts/build.sh 1.2.0 --release "Notes"` |
| Check gh auth | `gh auth status` |
| View releases | `gh release list` |
