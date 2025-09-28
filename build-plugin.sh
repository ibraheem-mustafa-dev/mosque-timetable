#!/bin/bash
# Build script for Mosque Timetable Plugin
# Creates a clean, production-ready plugin distribution

set -e

PLUGIN_DIR="public_html/wp-content/plugins/mosque-timetable"
TIMESTAMP=$(date +%Y%m%d-%H%M)
BUILD_NAME="mosque-timetable-${TIMESTAMP}"

echo "🏗️  Building Mosque Timetable Plugin..."

# Ensure we're in the project root
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ Plugin directory not found: $PLUGIN_DIR"
    echo "Please run this script from the project root."
    exit 1
fi

echo "📁 Preparing plugin dependencies..."

# Navigate to plugin directory and install runtime dependencies only
cd "$PLUGIN_DIR"

# Clean existing vendor directory
if [ -d "vendor" ]; then
    echo "🧹 Cleaning existing vendor directory..."
    rm -rf vendor/
fi

# Install production dependencies
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts

# Return to project root
cd ../../..

echo "📦 Creating distribution archive..."

# Create zip excluding development files (uses .gitattributes export-ignore)
cd public_html/wp-content/plugins/
zip -r "../../../${BUILD_NAME}.zip" mosque-timetable \
    -x "mosque-timetable/.git/*" \
       "mosque-timetable/.gitignore" \
       "mosque-timetable/.gitattributes" \
       "mosque-timetable/tests/*" \
       "mosque-timetable/.github/*" \
       "mosque-timetable/.claude/*" \
       "mosque-timetable/node_modules/*" \
       "mosque-timetable/*.md" \
       "mosque-timetable/*.backup" \
       "mosque-timetable/*debug*" \
       "mosque-timetable/*test*" \
       "mosque-timetable/vendor.zip"

cd ../../..

echo "✅ Plugin built successfully!"
echo "📦 Archive: ${BUILD_NAME}.zip"
echo "📏 Size: $(du -h "${BUILD_NAME}.zip" | cut -f1)"

echo ""
echo "🚀 Distribution ready for deployment!"
echo "   The plugin is now self-contained with runtime dependencies."
echo "   Upload ${BUILD_NAME}.zip to any WordPress site."