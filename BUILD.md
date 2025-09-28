# Mosque Timetable Plugin - Build Guide

This document describes the comprehensive build workflow for creating production-ready, self-contained plugin distributions.

## Build Architecture

### Dual Vendor Setup
- **Root vendor/**: Development tools only (PHPStan, PHPCS, Psalm, etc.) - Never shipped
- **Plugin vendor/**: Runtime dependencies only (minishlink/web-push, shuchkin/simplexlsx, etc.) - Shipped with plugin

### Build Process Overview
1. Install development tools at root level
2. Install optimized production dependencies in plugin folder
3. Run comprehensive QA checks (non-blocking)
4. Verify configuration files
5. Create clean, timestamped distribution ZIP

## Quick Start

### Windows (PowerShell)
```powershell
.\build-plugin.ps1
```

### Linux/macOS/WSL (Bash)
```bash
./build-plugin.sh
```

## Build Scripts

### bash version (`build-plugin.sh`)
- Cross-platform compatibility (Linux, macOS, WSL)
- Comprehensive QA integration
- Intelligent file exclusion using `zip -x`

### PowerShell version (`build-plugin.ps1`)
- Windows-optimized
- Robust error handling with `$ErrorActionPreference`
- Uses `robocopy` for efficient file copying

#### PowerShell Options
```powershell
# Standard build
.\build-plugin.ps1

# Custom build name
.\build-plugin.ps1 -BuildName "mosque-timetable-v3.0.1"

# Skip QA checks for faster builds
.\build-plugin.ps1 -SkipQA
```

## Build Steps Detail

### Step 1: Development Tools Installation
- Installs root `composer.json` dependencies
- Skips if `vendor/` already exists
- Required for QA tools (phpstan, phpcs, etc.)

### Step 2: Plugin Dependencies
- Cleans existing plugin `vendor/` directory
- Runs `composer install --no-dev --optimize-autoloader --classmap-authoritative`
- Creates optimized autoloader for production

### Step 3: Quality Assurance (Non-blocking)
- **PHP Lint**: Syntax validation
- **PHPCS**: Code standards (WordPress Coding Standards)
- **PHPStan**: Static analysis
- **ESLint**: JavaScript linting (if available)
- **Stylelint**: CSS linting (if available)

### Step 4: Configuration Verification
- Validates `.gitattributes` exists
- Validates `.gitignore` exists
- Validates `composer.json` exists

### Step 5: Distribution Creation
- Creates timestamped ZIP file
- Excludes development files via comprehensive patterns
- Optimizes for WordPress plugin installation

## File Exclusions

### Excluded from Distribution
- Development directories: `.git/`, `tests/`, `.github/`, `.claude/`, `.vscode/`, `.idea/`, `node_modules/`
- Documentation: `*.md`, `*.txt`, `README*`, `CHANGELOG*`
- Configuration: `.gitignore`, `.gitattributes`, `phpcs.xml`, `phpstan.neon`, etc.
- Temporary/backup files: `*.backup`, `*.bak`, `*debug*`, `*test*`
- OS files: `Thumbs.db`, `.DS_Store`, `*.swp`

### Included in Distribution
- Plugin PHP files
- Production `vendor/` directory with runtime dependencies
- Assets (CSS, JS, images)
- Languages directory
- WordPress plugin headers

## Output

### Build Artifacts
- **ZIP file**: `mosque-timetable-YYYYMMDD-HHMM.zip`
- **Location**: Project root directory
- **Size**: Typically 2-5MB (optimized)

### Build Report
- QA results summary
- File size and location
- Next steps for deployment

## Deployment

### WordPress Installation
1. Upload ZIP to WordPress admin (Plugins → Add New → Upload Plugin)
2. Activate plugin
3. No server-side Composer required

### Requirements
- PHP 8.0+ (as defined in plugin `composer.json`)
- WordPress 6.5+ (as defined in PHPCS configuration)

## Development Workflow

### Regular Development
```bash
# Install development tools
composer install

# Run QA checks
composer qa

# Fix code style issues
composer qa:fix

# Build for distribution
./build-plugin.sh
```

### Composer Scripts Available
- `composer qa` - Run all QA checks
- `composer qa:fix` - Fix code style issues
- `composer lint` - PHP syntax check
- `composer phpcs:plugin` - WordPress coding standards
- `composer phpstan` - Static analysis

## Troubleshooting

### Common Issues
1. **Missing vendor/**: Run `composer install` in project root
2. **Plugin vendor/ empty**: Script automatically handles this
3. **QA failures**: Check output, but build continues regardless
4. **Permission errors**: Ensure scripts are executable (`chmod +x build-plugin.sh`)

### Build Dependencies
- **Composer**: Required for dependency management
- **Node.js/npm**: Optional, for JavaScript/CSS linting
- **zip/robocopy**: Built into most systems

## File Structure

```
project-root/
├── composer.json              # Dev tools only
├── vendor/                    # Dev tools (never shipped)
├── build-plugin.sh           # Bash build script
├── build-plugin.ps1          # PowerShell build script
├── BUILD.md                   # This file
└── public_html/wp-content/plugins/mosque-timetable/
    ├── composer.json          # Runtime deps only
    ├── vendor/                # Runtime deps (shipped)
    ├── .gitattributes         # Export exclusions
    ├── .gitignore            # Local exclusions
    └── mosque-timetable.php   # Main plugin file
```

## Best Practices

1. **Always run QA before building** (unless using `-SkipQA`)
2. **Test builds on staging sites** before production
3. **Use timestamped builds** for version tracking
4. **Keep plugin vendor/ optimized** with `--no-dev`
5. **Review exclusion patterns** regularly

This build system ensures consistent, production-ready plugin distributions that work on any WordPress installation without requiring server-side Composer or development tools.