# Mosque Timetable - Simplified SFTP Workflow

This document describes your streamlined development workflow using SFTP direct deployment to Hostinger.

## Overview

Since you're using the SFTP extension with direct file sync to your live server, your workflow is much simpler than typical git-based deployments. You get the benefits of modern development tools without the complexity of git workflows.

## Your Current Setup

### ✅ **What You Have**
- **SFTP Auto-sync**: Files sync directly to `mosquewebdesign.com` via VSCode extension
- **Dual Vendor Setup**: Clean separation of dev tools vs runtime dependencies
- **Quality Assurance**: PHPStan, PHPCS, ESLint, Stylelint
- **Build Automation**: Scripts for creating distribution-ready plugin zips

### ❌ **What You Don't Need**
- Complex git workflows or deployment pipelines
- `.gitattributes` export-ignore patterns
- Remote repository management
- CI/CD complexity

## Daily Development Workflow

### 1. **Local Development**
```bash
# Start developing
code .

# Install/update dev tools (as needed)
composer install

# Your SFTP extension automatically syncs changes to live server
# uploadOnSave: true means every save goes live immediately
```

### 2. **Quality Assurance**
```bash
# Run all QA checks
composer qa

# Fix code style issues
composer qa:fix

# Individual checks
composer lint          # PHP syntax
composer phpcs:plugin   # WordPress coding standards
composer phpstan        # Static analysis
```

### 3. **Testing**
- **Live Testing**: Since SFTP syncs to production, test directly on your live site
- **Local Testing**: Use Local by Flywheel for safe testing before going live

## When You Need Distribution Zips

### **For Plugin Releases/Sharing**
```bash
# Create production-ready plugin zip
./build-plugin.sh
# or
.\build-plugin.ps1

# Output: mosque-timetable-YYYYMMDD-HHMM.zip
```

This creates a clean, self-contained plugin that:
- ✅ Works on any WordPress site
- ✅ Includes only runtime dependencies
- ✅ Excludes dev files, tests, docs
- ✅ Has optimized autoloader
- ✅ No server Composer required

## File Management

### **SFTP Sync (Auto-handled)**
Your `sftp.json` already excludes the right files:
```json
"ignore": [
  ".vscode", ".git", "node_modules", "*.log",
  "vendor", ".DS_Store", "**/.cache/**"
]
```

### **Local Workspace (.gitignore)**
Keeps your local directory clean:
- `vendor/` (dev dependencies)
- `node_modules/`
- `*.log`, `*.backup`
- IDE files, OS files

## Directory Structure

```
mosque-timetable/
├── composer.json              # Dev tools (phpstan, phpcs, etc.)
├── vendor/                    # Dev tools (never synced)
├── build-plugin.sh/.ps1       # Build scripts
├── sftp.json                  # SFTP sync config
└── public_html/wp-content/plugins/mosque-timetable/
    ├── composer.json          # Runtime deps only
    ├── vendor/                # Runtime deps (synced)
    ├── .gitignore            # Local workspace cleanliness
    └── mosque-timetable.php   # Main plugin file
```

## Benefits of Your Setup

### **Fast Development**
- ✅ **Immediate feedback**: Save → Live server in seconds
- ✅ **No deployment delays**: No build/deploy/wait cycles
- ✅ **Real environment testing**: Test on actual hosting environment

### **Quality Maintained**
- ✅ **Code standards**: PHPStan, PHPCS catch issues
- ✅ **Best practices**: Dual vendor setup, optimized autoloader
- ✅ **Distribution ready**: Can create plugin zips anytime

### **Simple Mental Model**
- 📝 **Edit files** → SFTP syncs automatically
- 🔍 **Run QA** → Fix any issues
- 📦 **Build zip** → Only when distributing plugin

## Troubleshooting

### **SFTP Issues**
- Check your `sftp.json` credentials
- Verify SSH key at `C:/Users/Bean/.ssh/id_ed25519`
- Check Hostinger firewall/permissions

### **Build Issues**
- Ensure `composer install` ran successfully at root
- Verify plugin `composer.json` exists
- Check build script permissions (`chmod +x build-plugin.sh`)

### **QA Failures**
- Review specific tool output
- Use `composer qa:fix` for automatic fixes
- Build continues even with QA issues

## Quick Commands

```bash
# Quality checks
composer qa                    # All checks
composer qa:fix               # Fix code style

# Build plugin zip
./build-plugin.sh             # Standard build
./build-plugin.sh --help      # See options

# SFTP sync status
# Check VSCode SFTP extension status bar
```

## Best Practices

1. **Test locally first** when making significant changes
2. **Run QA regularly** to catch issues early
3. **Use build script** when sharing plugin with others
4. **Keep git simple** - just for local backup/history
5. **Monitor live site** after auto-sync deployments

Your setup gives you the best of both worlds: modern development tools with simple, direct deployment!