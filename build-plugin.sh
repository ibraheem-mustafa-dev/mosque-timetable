#!/bin/bash
# Comprehensive Build Script for Mosque Timetable Plugin
# Creates a production-ready, self-contained WordPress plugin distribution

set -e

# Configuration
PLUGIN_DIR="public_html/wp-content/plugins/mosque-timetable"
TIMESTAMP=$(date +%Y%m%d-%H%M)
BUILD_NAME="mosque-timetable-${TIMESTAMP}"
QA_FAILED=false

echo "🏗️  Building Mosque Timetable Plugin v3.0..."
echo "⏰ Timestamp: ${TIMESTAMP}"
echo ""

# Ensure we're in the project root
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ Plugin directory not found: $PLUGIN_DIR"
    echo "Please run this script from the project root."
    exit 1
fi

# Step 1: Install development tools
echo "📚 Step 1: Installing development tools..."
if [ ! -d "vendor" ]; then
    echo "📦 Installing root development dependencies..."
    composer install --no-scripts
else
    echo "✅ Root vendor already exists"
fi
echo ""

# Step 2: Prepare plugin dependencies
echo "📁 Step 2: Preparing plugin runtime dependencies..."
cd "$PLUGIN_DIR"

# Clean existing vendor directory
if [ -d "vendor" ]; then
    echo "🧹 Cleaning existing plugin vendor directory..."
    rm -rf vendor/
fi

# Install production dependencies only
echo "📦 Installing plugin production dependencies..."
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts

if [ $? -ne 0 ]; then
    echo "❌ Failed to install plugin dependencies"
    exit 1
fi

# Return to project root
cd ../../..
echo ""

# Step 3: Quality Assurance (non-blocking)
echo "🔍 Step 3: Running quality assurance checks..."
echo "ℹ️  Note: QA failures will not stop the build but will be reported"
echo ""

# PHP Lint
echo "🔧 Running PHP syntax check..."
if composer lint 2>/dev/null; then
    echo "✅ PHP lint passed"
else
    echo "⚠️  PHP lint issues found"
    QA_FAILED=true
fi

# PHPCS
echo "🔧 Running PHPCS..."
if composer phpcs:plugin 2>/dev/null; then
    echo "✅ PHPCS passed"
else
    echo "⚠️  PHPCS issues found"
    QA_FAILED=true
fi

# PHPStan
echo "🔧 Running PHPStan..."
if composer phpstan 2>/dev/null; then
    echo "✅ PHPStan passed"
else
    echo "⚠️  PHPStan issues found"
    QA_FAILED=true
fi

# ESLint (if available)
if command -v npx &> /dev/null && [ -f "package.json" ]; then
    echo "🔧 Running ESLint..."
    if npm run lint:js 2>/dev/null; then
        echo "✅ ESLint passed"
    else
        echo "⚠️  ESLint issues found"
        QA_FAILED=true
    fi
fi

# Stylelint (if available)
if command -v npx &> /dev/null && [ -f "package.json" ]; then
    echo "🔧 Running Stylelint..."
    if npx stylelint "public_html/wp-content/plugins/mosque-timetable/**/*.css" 2>/dev/null; then
        echo "✅ Stylelint passed"
    else
        echo "⚠️  Stylelint issues found"
        QA_FAILED=true
    fi
fi

echo ""

# Step 4: Verify configuration files
echo "📋 Step 4: Verifying plugin configuration..."

# Check .gitattributes
if [ ! -f "$PLUGIN_DIR/.gitattributes" ]; then
    echo "⚠️  Missing .gitattributes in plugin directory"
    QA_FAILED=true
else
    echo "✅ Plugin .gitattributes exists"
fi

# Check .gitignore
if [ ! -f "$PLUGIN_DIR/.gitignore" ]; then
    echo "⚠️  Missing .gitignore in plugin directory"
    QA_FAILED=true
else
    echo "✅ Plugin .gitignore exists"
fi

# Check composer.json
if [ ! -f "$PLUGIN_DIR/composer.json" ]; then
    echo "❌ Missing composer.json in plugin directory"
    exit 1
else
    echo "✅ Plugin composer.json exists"
fi

echo ""

# Step 5: Create distribution archive
echo "📦 Step 5: Creating distribution archive..."

# Ensure clean build directory
if [ -f "${BUILD_NAME}.zip" ]; then
    echo "🧹 Removing existing build: ${BUILD_NAME}.zip"
    rm "${BUILD_NAME}.zip"
fi

# Create zip with comprehensive exclusions
cd public_html/wp-content/plugins/
echo "📄 Creating zip with exclusions..."
zip -r "../../../${BUILD_NAME}.zip" mosque-timetable \
    -x "mosque-timetable/.git/*" \
       "mosque-timetable/.gitignore" \
       "mosque-timetable/.gitattributes" \
       "mosque-timetable/tests/*" \
       "mosque-timetable/.github/*" \
       "mosque-timetable/.claude/*" \
       "mosque-timetable/.vscode/*" \
       "mosque-timetable/.idea/*" \
       "mosque-timetable/node_modules/*" \
       "mosque-timetable/*.md" \
       "mosque-timetable/*.MD" \
       "mosque-timetable/*.txt" \
       "mosque-timetable/*.backup" \
       "mosque-timetable/*.bak" \
       "mosque-timetable/*debug*" \
       "mosque-timetable/*test*" \
       "mosque-timetable/*Test*" \
       "mosque-timetable/vendor.zip" \
       "mosque-timetable/composer.lock" \
       "mosque-timetable/phpunit.xml*" \
       "mosque-timetable/phpcs.xml*" \
       "mosque-timetable/phpstan.neon*" \
       "mosque-timetable/psalm.xml*" \
       "mosque-timetable/.editorconfig" \
       "mosque-timetable/Thumbs.db" \
       "mosque-timetable/.DS_Store" \
       "mosque-timetable/*.swp" \
       "mosque-timetable/*.swo" \
       "mosque-timetable/*.log"

cd ../../..

# Step 6: Build summary
echo ""
echo "✅ Build completed successfully!"
echo "📦 Archive: ${BUILD_NAME}.zip"
echo "📏 Size: $(du -h "${BUILD_NAME}.zip" | cut -f1)"
echo "📁 Location: $(pwd)/${BUILD_NAME}.zip"

# QA Summary
if [ "$QA_FAILED" = true ]; then
    echo ""
    echo "⚠️  Quality Assurance Summary:"
    echo "   Some QA checks failed, but build continued."
    echo "   Review the output above for details."
    echo "   Consider running: composer qa:fix"
else
    echo ""
    echo "✅ Quality Assurance Summary:"
    echo "   All QA checks passed!"
fi

echo ""
echo "🚀 Distribution ready for deployment!"
echo "   • Plugin is self-contained with optimized runtime dependencies"
echo "   • No development files included"
echo "   • Ready to upload to any WordPress site"
echo "   • No server-side Composer required"
echo ""
echo "🔧 Next steps:"
echo "   1. Test the plugin on a staging site"
echo "   2. Upload ${BUILD_NAME}.zip to WordPress admin"
echo "   3. Activate and verify functionality"