# Build script for Mosque Timetable Plugin (PowerShell)
# Creates a clean, production-ready plugin distribution

param(
    [string]$BuildName = ""
)

$ErrorActionPreference = "Stop"

$PLUGIN_DIR = "public_html\wp-content\plugins\mosque-timetable"
$TIMESTAMP = Get-Date -Format "yyyyMMdd-HHmm"
if ($BuildName -eq "") {
    $BUILD_NAME = "mosque-timetable-$TIMESTAMP"
} else {
    $BUILD_NAME = $BuildName
}

Write-Host "🏗️  Building Mosque Timetable Plugin..." -ForegroundColor Green

# Ensure we're in the project root
if (-not (Test-Path $PLUGIN_DIR)) {
    Write-Host "❌ Plugin directory not found: $PLUGIN_DIR" -ForegroundColor Red
    Write-Host "Please run this script from the project root." -ForegroundColor Red
    exit 1
}

Write-Host "📁 Preparing plugin dependencies..." -ForegroundColor Yellow

# Navigate to plugin directory and install runtime dependencies only
Push-Location $PLUGIN_DIR

try {
    # Clean existing vendor directory
    if (Test-Path "vendor") {
        Write-Host "🧹 Cleaning existing vendor directory..." -ForegroundColor Yellow
        Remove-Item -Recurse -Force vendor
    }

    # Install production dependencies
    Write-Host "📦 Installing production dependencies..." -ForegroundColor Yellow
    & composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts

    if ($LASTEXITCODE -ne 0) {
        throw "Composer install failed"
    }
} finally {
    # Return to project root
    Pop-Location
}

Write-Host "📦 Creating distribution archive..." -ForegroundColor Yellow

# Create zip excluding development files
$excludePatterns = @(
    "*\.git\*",
    "*\.gitignore",
    "*\.gitattributes",
    "*tests\*",
    "*\.github\*",
    "*\.claude\*",
    "*node_modules\*",
    "*.md",
    "*.backup",
    "*debug*",
    "*test*",
    "vendor.zip"
)

$sourcePath = "$PLUGIN_DIR\*"
$zipPath = "$BUILD_NAME.zip"

# Use .NET compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal

# Create temporary directory for clean copy
$tempDir = Join-Path $env:TEMP "mosque-timetable-build"
if (Test-Path $tempDir) {
    Remove-Item -Recurse -Force $tempDir
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

try {
    # Copy plugin files excluding patterns
    robocopy $PLUGIN_DIR "$tempDir\mosque-timetable" /E /XD .git tests .github .claude node_modules /XF *.md *.backup *debug* *test* vendor.zip .gitignore .gitattributes

    # Create zip
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $zipPath, $compressionLevel, $false)

} finally {
    # Clean up temp directory
    if (Test-Path $tempDir) {
        Remove-Item -Recurse -Force $tempDir
    }
}

$zipSize = (Get-Item $zipPath).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host "✅ Plugin built successfully!" -ForegroundColor Green
Write-Host "📦 Archive: $BUILD_NAME.zip" -ForegroundColor Cyan
Write-Host "📏 Size: $zipSizeMB MB" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Distribution ready for deployment!" -ForegroundColor Green
Write-Host "   The plugin is now self-contained with runtime dependencies." -ForegroundColor White
Write-Host "   Upload $BUILD_NAME.zip to any WordPress site." -ForegroundColor White