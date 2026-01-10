#!/bin/bash
#
# Easy Album Orders - Production Build Script
#
# Creates a clean production-ready zip file for distribution.
# Usage: 
#   ./dev/scripts/build.sh              # Build only
#   ./dev/scripts/build.sh 1.2.0        # Build version 1.2.0
#   ./dev/scripts/build.sh 1.2.0 --release "Release notes here"  # Build + GitHub release
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory and project root
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/../.." && pwd )"
PLUGIN_DIR="$PROJECT_ROOT/easy-album-orders"
DIST_DIR="$PROJECT_ROOT/dist"
BUILD_DIR="$PROJECT_ROOT/build"

# Plugin name (folder name in zip)
PLUGIN_SLUG="easy-album-orders"

# Parse arguments
VERSION=""
RELEASE_NOTES=""
DO_RELEASE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --release)
            DO_RELEASE=true
            if [[ -n "$2" && ! "$2" =~ ^-- ]]; then
                RELEASE_NOTES="$2"
                shift
            fi
            shift
            ;;
        *)
            if [[ -z "$VERSION" ]]; then
                VERSION="$1"
            fi
            shift
            ;;
    esac
done

# Function to get version from main plugin file
get_plugin_version() {
    grep -m 1 "Version:" "$PLUGIN_DIR/easy-album-orders.php" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r'
}

# Function to print colored messages
print_status() {
    echo -e "${GREEN}[BUILD]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Get version if not provided
if [ -z "$VERSION" ]; then
    VERSION=$(get_plugin_version)
fi

if [ -z "$VERSION" ]; then
    print_error "Could not determine version. Please specify as argument or check plugin file."
    exit 1
fi

print_status "Building Easy Album Orders v${VERSION}"
print_status "Project root: $PROJECT_ROOT"

# Create dist directory if it doesn't exist
mkdir -p "$DIST_DIR"

# Clean up any previous build
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

print_status "Copying plugin files..."

# Copy production files only
cp -R "$PLUGIN_DIR/assets" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$PLUGIN_DIR/includes" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$PLUGIN_DIR/vendor" "$BUILD_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/easy-album-orders.php" "$BUILD_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/LICENSE" "$BUILD_DIR/$PLUGIN_SLUG/"

# Create languages directory (for translations)
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG/languages"

print_status "Cleaning up development files..."

# Remove any development files that might have snuck in
find "$BUILD_DIR" -name ".DS_Store" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "*.map" -delete 2>/dev/null || true
find "$BUILD_DIR" -name ".gitignore" -delete 2>/dev/null || true
find "$BUILD_DIR" -name ".gitkeep" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "*.md" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "composer.json" -delete 2>/dev/null || true
find "$BUILD_DIR" -name "composer.lock" -delete 2>/dev/null || true

# Remove tests and documentation from vendor (if any)
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/vendor/*/test" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/vendor/*/tests" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/vendor/*/doc" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/vendor/*/docs" 2>/dev/null || true

# Set output zip filename
ZIP_FILENAME="${PLUGIN_SLUG}-${VERSION}.zip"
ZIP_PATH="$DIST_DIR/$ZIP_FILENAME"

# Remove old zip if exists
rm -f "$ZIP_PATH"

print_status "Creating zip archive..."

# Create the zip
cd "$BUILD_DIR"
zip -r "$ZIP_PATH" "$PLUGIN_SLUG" -x "*.DS_Store"

# Clean up build directory
rm -rf "$BUILD_DIR"

# Calculate file size
if [[ "$OSTYPE" == "darwin"* ]]; then
    FILE_SIZE=$(ls -lh "$ZIP_PATH" | awk '{print $5}')
else
    FILE_SIZE=$(ls -lh "$ZIP_PATH" | awk '{print $5}')
fi

print_status "✅ Build complete!"
echo ""
echo "  📦 Output: $ZIP_PATH"
echo "  📊 Size: $FILE_SIZE"
echo ""

# Handle release if requested
if [ "$DO_RELEASE" = true ]; then
    print_status "Creating GitHub release..."
    
    # Check if gh is installed
    if ! command -v gh &> /dev/null; then
        print_error "GitHub CLI (gh) is not installed. Install with: brew install gh"
        exit 1
    fi
    
    # Check if authenticated
    if ! gh auth status &> /dev/null; then
        print_error "Not authenticated with GitHub. Run: gh auth login"
        exit 1
    fi
    
    # Default release notes if not provided
    if [ -z "$RELEASE_NOTES" ]; then
        RELEASE_NOTES="Release v${VERSION}"
    fi
    
    # Check if tag already exists on remote
    if git ls-remote --tags origin | grep -q "refs/tags/v${VERSION}"; then
        print_info "Tag v${VERSION} already exists. Creating release from existing tag..."
        gh release create "v${VERSION}" \
            --title "v${VERSION}" \
            --notes "$RELEASE_NOTES" \
            "$ZIP_PATH" 2>/dev/null || \
        gh release upload "v${VERSION}" "$ZIP_PATH" --clobber
    else
        print_info "Creating new tag and release..."
        gh release create "v${VERSION}" \
            --title "v${VERSION}" \
            --notes "$RELEASE_NOTES" \
            "$ZIP_PATH"
    fi
    
    print_status "✅ GitHub release created!"
    echo ""
    echo "  🔗 https://github.com/RisePlugins/easy-album-orders/releases/tag/v${VERSION}"
    echo ""
else
    print_info "To create a GitHub release, run:"
    echo "  ./dev/scripts/build.sh ${VERSION} --release \"Your release notes\""
    echo ""
    print_info "Or manually:"
    echo "  1. git tag -a v${VERSION} -m 'Release ${VERSION}'"
    echo "  2. git push origin main --tags"
    echo "  3. gh release create v${VERSION} --title 'v${VERSION}' --notes 'Release notes' '$ZIP_PATH'"
    echo ""
fi
