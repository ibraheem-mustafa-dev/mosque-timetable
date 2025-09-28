# Comprehensive Build Script for Mosque Timetable Plugin (PowerShell)
# Creates a production-ready, self-contained WordPress plugin distribution

param(
    [string]$BuildName = "",
    [switch]$SkipQA = $false
)

$ErrorActionPreference = "Continue"  # Changed to Continue for better QA handling

# Configuration
$PLUGIN_DIR = "public_html\wp-content\plugins\mosque-timetable"
$TIMESTAMP = Get-Date -Format "yyyyMMdd-HHmm"
$BUILD_NAME = if ($BuildName -ne "") { $BuildName } else { "mosque-timetable-$TIMESTAMP" }
$QA_FAILED = $false

Write-Host "🏗️  Building Mosque Timetable Plugin v3.0..." -ForegroundColor Green
Write-Host "⏰ Timestamp: $TIMESTAMP" -ForegroundColor Gray
Write-Host ""

# Ensure we're in the project root
if (-not (Test-Path $PLUGIN_DIR)) {
    Write-Host "❌ Plugin directory not found: $PLUGIN_DIR" -ForegroundColor Red
    Write-Host "Please run this script from the project root." -ForegroundColor Red
    exit 1
}

# Step 1: Install development tools
Write-Host "📚 Step 1: Installing development tools..." -ForegroundColor Yellow
if (-not (Test-Path "vendor")) {
    Write-Host "📦 Installing root development dependencies..." -ForegroundColor Gray
    & composer install --no-scripts
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Failed to install development dependencies" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✅ Root vendor already exists" -ForegroundColor Green
}
Write-Host ""

# Step 2: Prepare plugin dependencies
Write-Host "📁 Step 2: Preparing plugin runtime dependencies..." -ForegroundColor Yellow
Push-Location $PLUGIN_DIR

try {
    # Clean existing vendor directory
    if (Test-Path "vendor") {
        Write-Host "🧹 Cleaning existing plugin vendor directory..." -ForegroundColor Gray
        Remove-Item -Recurse -Force vendor
    }

    # Install production dependencies only
    Write-Host "📦 Installing plugin production dependencies..." -ForegroundColor Gray
    & composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts

    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Failed to install plugin dependencies" -ForegroundColor Red
        exit 1
    }
} finally {
    # Return to project root
    Pop-Location
}
Write-Host ""

# Step 3: Quality Assurance (non-blocking unless SkipQA is set)
if (-not $SkipQA) {
    Write-Host "🔍 Step 3: Running quality assurance checks..." -ForegroundColor Yellow
    Write-Host "ℹ️  Note: QA failures will not stop the build but will be reported" -ForegroundColor Gray
    Write-Host ""

    # PHP Lint
    Write-Host "🔧 Running PHP syntax check..." -ForegroundColor Gray
    & composer lint 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ PHP lint passed" -ForegroundColor Green
    } else {
        Write-Host "⚠️  PHP lint issues found" -ForegroundColor Yellow
        $QA_FAILED = $true
    }

    # PHPCS
    Write-Host "🔧 Running PHPCS..." -ForegroundColor Gray
    & composer phpcs:plugin 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ PHPCS passed" -ForegroundColor Green
    } else {
        Write-Host "⚠️  PHPCS issues found" -ForegroundColor Yellow
        $QA_FAILED = $true
    }

    # PHPStan
    Write-Host "🔧 Running PHPStan..." -ForegroundColor Gray
    & composer phpstan 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ PHPStan passed" -ForegroundColor Green
    } else {
        Write-Host "⚠️  PHPStan issues found" -ForegroundColor Yellow
        $QA_FAILED = $true
    }

    # ESLint (if available)
    if ((Get-Command npx -ErrorAction SilentlyContinue) -and (Test-Path "package.json")) {
        Write-Host "🔧 Running ESLint..." -ForegroundColor Gray
        & npm run lint:js 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ ESLint passed" -ForegroundColor Green
        } else {
            Write-Host "⚠️  ESLint issues found" -ForegroundColor Yellow
            $QA_FAILED = $true
        }
    }

    # Stylelint (if available)
    if ((Get-Command npx -ErrorAction SilentlyContinue) -and (Test-Path "package.json")) {
        Write-Host "🔧 Running Stylelint..." -ForegroundColor Gray
        & npx stylelint "public_html/wp-content/plugins/mosque-timetable/**/*.css" 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Stylelint passed" -ForegroundColor Green
        } else {
            Write-Host "⚠️  Stylelint issues found" -ForegroundColor Yellow
            $QA_FAILED = $true
        }
    }

    Write-Host ""
} else {
    Write-Host "⏭️  Step 3: Skipping QA checks (SkipQA flag set)" -ForegroundColor Gray
    Write-Host ""
}

# Step 4: Verify configuration files
Write-Host "📋 Step 4: Verifying plugin configuration..." -ForegroundColor Yellow

# Check .gitattributes
if (-not (Test-Path "$PLUGIN_DIR\.gitattributes")) {
    Write-Host "⚠️  Missing .gitattributes in plugin directory" -ForegroundColor Yellow
    $QA_FAILED = $true
} else {
    Write-Host "✅ Plugin .gitattributes exists" -ForegroundColor Green
}

# Check .gitignore
if (-not (Test-Path "$PLUGIN_DIR\.gitignore")) {
    Write-Host "⚠️  Missing .gitignore in plugin directory" -ForegroundColor Yellow
    $QA_FAILED = $true
} else {
    Write-Host "✅ Plugin .gitignore exists" -ForegroundColor Green
}

# Check composer.json
if (-not (Test-Path "$PLUGIN_DIR\composer.json")) {
    Write-Host "❌ Missing composer.json in plugin directory" -ForegroundColor Red
    exit 1
} else {
    Write-Host "✅ Plugin composer.json exists" -ForegroundColor Green
}

Write-Host ""

# Step 5: Create distribution archive
Write-Host "📦 Step 5: Creating distribution archive..." -ForegroundColor Yellow

# Ensure clean build directory
$zipPath = "$BUILD_NAME.zip"
if (Test-Path $zipPath) {
    Write-Host "🧹 Removing existing build: $BUILD_NAME.zip" -ForegroundColor Gray
    Remove-Item $zipPath -Force
}

# Use .NET compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal

# Create temporary directory for clean copy
$tempDir = Join-Path $env:TEMP "mosque-timetable-build-$TIMESTAMP"
if (Test-Path $tempDir) {
    Remove-Item -Recurse -Force $tempDir
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

try {
    Write-Host "📄 Creating clean copy with exclusions..." -ForegroundColor Gray

    # Use robocopy for efficient copying with exclusions
    $excludeDirs = @(".git", "tests", ".github", ".claude", ".vscode", ".idea", "node_modules")
    $excludeFiles = @("*.md", "*.MD", "*.txt", "*.backup", "*.bak", "*debug*", "*test*", "*Test*",
                     "vendor.zip", "composer.lock", "phpunit.xml*", "phpcs.xml*", "phpstan.neon*",
                     "psalm.xml*", ".editorconfig", "Thumbs.db", ".DS_Store", "*.swp", "*.swo",
                     "*.log", ".gitignore", ".gitattributes")

    $xdArgs = ($excludeDirs | ForEach-Object { "/XD", $_ }) -join " "
    $xfArgs = ($excludeFiles | ForEach-Object { "/XF", $_ }) -join " "

    # Build robocopy command
    $robocopyArgs = @(
        $PLUGIN_DIR
        "$tempDir\mosque-timetable"
        "/E"  # Copy subdirectories including empty ones
        "/XD" + ($excludeDirs -join " ")
        "/XF" + ($excludeFiles -join " ")
        "/NP"  # No progress
        "/NDL" # No directory list
        "/NC"  # No class
        "/NS"  # No size
        "/NJH" # No job header
        "/NJS" # No job summary
    )

    # Use Start-Process for better control
    $result = Start-Process -FilePath "robocopy" -ArgumentList $robocopyArgs -Wait -PassThru -NoNewWindow

    # Robocopy exit codes: 0-7 are success, 8+ are errors
    if ($result.ExitCode -gt 7) {
        Write-Host "⚠️  Warning: Robocopy reported issues (exit code: $($result.ExitCode))" -ForegroundColor Yellow
    }

    # Create zip
    Write-Host "📦 Compressing to ZIP archive..." -ForegroundColor Gray
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $zipPath, $compressionLevel, $false)

} finally {
    # Clean up temp directory
    if (Test-Path $tempDir) {
        Remove-Item -Recurse -Force $tempDir
    }
}

# Step 6: Build summary
Write-Host ""
$zipSize = (Get-Item $zipPath).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host "✅ Build completed successfully!" -ForegroundColor Green
Write-Host "📦 Archive: $BUILD_NAME.zip" -ForegroundColor Cyan
Write-Host "📏 Size: $zipSizeMB MB" -ForegroundColor Cyan
Write-Host "📁 Location: $(Get-Location)\$BUILD_NAME.zip" -ForegroundColor Cyan

# QA Summary
if ($QA_FAILED) {
    Write-Host ""
    Write-Host "⚠️  Quality Assurance Summary:" -ForegroundColor Yellow
    Write-Host "   Some QA checks failed, but build continued." -ForegroundColor White
    Write-Host "   Review the output above for details." -ForegroundColor White
    Write-Host "   Consider running: composer qa:fix" -ForegroundColor White
} elseif (-not $SkipQA) {
    Write-Host ""
    Write-Host "✅ Quality Assurance Summary:" -ForegroundColor Green
    Write-Host "   All QA checks passed!" -ForegroundColor White
}

Write-Host ""
Write-Host "🚀 Distribution ready for deployment!" -ForegroundColor Green
Write-Host "   • Plugin is self-contained with optimized runtime dependencies" -ForegroundColor White
Write-Host "   • No development files included" -ForegroundColor White
Write-Host "   • Ready to upload to any WordPress site" -ForegroundColor White
Write-Host "   • No server-side Composer required" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Next steps:" -ForegroundColor Cyan
Write-Host "   1. Test the plugin on a staging site" -ForegroundColor White
Write-Host "   2. Upload $BUILD_NAME.zip to WordPress admin" -ForegroundColor White
Write-Host "   3. Activate and verify functionality" -ForegroundColor White