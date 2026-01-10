#!/bin/bash
#
# Easy Album Orders - Full Release Script
#
# Handles the complete release process:
# 1. Updates version numbers in plugin file
# 2. Commits changes
# 3. Creates git tag
# 4. Pushes to GitHub
# 5. Builds production zip
# 6. Creates GitHub release with zip attached
#
# Usage: ./dev/scripts/release.sh 1.2.0 "Release notes here"
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
PLUGIN_FILE="$PROJECT_ROOT/easy-album-orders/easy-album-orders.php"

# Function to print colored messages
print_status() {
    echo -e "${GREEN}[RELEASE]${NC} $1"
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

# Check arguments
if [ -z "$1" ]; then
    print_error "Version number required!"
    echo ""
    echo "Usage: ./dev/scripts/release.sh VERSION [RELEASE_NOTES]"
    echo ""
    echo "Examples:"
    echo "  ./dev/scripts/release.sh 1.2.0"
    echo "  ./dev/scripts/release.sh 1.2.0 \"Added new feature XYZ\""
    exit 1
fi

VERSION="$1"
RELEASE_NOTES="${2:-Release v$VERSION}"

# Validate version format
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_error "Invalid version format. Use semantic versioning (e.g., 1.2.0)"
    exit 1
fi

print_status "Starting release process for v${VERSION}"
echo ""

# Check prerequisites
print_info "Checking prerequisites..."

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

# Check for uncommitted changes
if [[ -n $(git status --porcelain) ]]; then
    print_warning "You have uncommitted changes. They will be included in the release commit."
fi

# Check if tag already exists
if git tag | grep -q "^v${VERSION}$"; then
    print_error "Tag v${VERSION} already exists locally!"
    exit 1
fi

if git ls-remote --tags origin | grep -q "refs/tags/v${VERSION}"; then
    print_error "Tag v${VERSION} already exists on remote!"
    exit 1
fi

echo ""
print_status "Step 1/6: Updating version numbers..."

# Get current version
CURRENT_VERSION=$(grep -m 1 "Version:" "$PLUGIN_FILE" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')
print_info "Current version: $CURRENT_VERSION → $VERSION"

# Update version in plugin header
sed -i '' "s/\* Version:.*/* Version:           $VERSION/" "$PLUGIN_FILE"

# Update version constant
sed -i '' "s/define( 'EAO_VERSION', '.*' );/define( 'EAO_VERSION', '$VERSION' );/" "$PLUGIN_FILE"

# Verify updates
NEW_HEADER_VERSION=$(grep -m 1 "Version:" "$PLUGIN_FILE" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')
NEW_CONST_VERSION=$(grep "EAO_VERSION" "$PLUGIN_FILE" | sed "s/.*'\([0-9]*\.[0-9]*\.[0-9]*\)'.*/\1/")

if [ "$NEW_HEADER_VERSION" != "$VERSION" ]; then
    print_error "Failed to update plugin header version!"
    exit 1
fi

print_status "✅ Version numbers updated"

echo ""
print_status "Step 2/6: Committing changes..."

cd "$PROJECT_ROOT"
git add -A
git commit -m "chore: Release version $VERSION

$RELEASE_NOTES"

print_status "✅ Changes committed"

echo ""
print_status "Step 3/6: Creating git tag..."

git tag -a "v${VERSION}" -m "Release $VERSION

$RELEASE_NOTES"

print_status "✅ Tag v${VERSION} created"

echo ""
print_status "Step 4/6: Pushing to GitHub..."

git push origin main --tags

print_status "✅ Pushed to GitHub"

echo ""
print_status "Step 5/6: Building production zip..."

"$SCRIPT_DIR/build.sh" "$VERSION"

ZIP_PATH="$PROJECT_ROOT/dist/${PLUGIN_SLUG:-easy-album-orders}-${VERSION}.zip"

print_status "✅ Production zip built"

echo ""
print_status "Step 6/6: Creating GitHub release..."

gh release create "v${VERSION}" \
    --title "v${VERSION}" \
    --notes "$RELEASE_NOTES" \
    "$PROJECT_ROOT/dist/easy-album-orders-${VERSION}.zip"

print_status "✅ GitHub release created"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
print_status "🎉 Release v${VERSION} complete!"
echo ""
echo "  📦 Zip: $PROJECT_ROOT/dist/easy-album-orders-${VERSION}.zip"
echo "  🔗 Release: https://github.com/RisePlugins/easy-album-orders/releases/tag/v${VERSION}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
