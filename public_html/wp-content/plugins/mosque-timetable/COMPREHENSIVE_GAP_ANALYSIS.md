# Mosque Timetable Plugin - Comprehensive Gap Analysis

**Analysis Date**: September 27, 2025
**Based on**: Mosque_Timetable_Technical_Plan.md vs Current Implementation
**Plugin Location**: `public_html\wp-content\plugins\mosque-timetable`

## Executive Summary

This analysis compares the current plugin implementation against the technical plan requirements. According to the CHANGELOG.md, **all 11 planned features (Tasks A-K) appear to be implemented**, but code analysis reveals significant gaps between what's documented and what's actually functioning in the WordPress environment.

## Current Implementation Status

### ✅ **IMPLEMENTED & WORKING**

Based on code analysis, these features are fully implemented:

#### 1. **Core Data Model** (Plan Section 1)
- **ACF Structure**: 12 field groups (`daily_prayers_1` through `daily_prayers_12`) ✅
- **Fallback Storage**: WordPress options fallback when ACF unavailable ✅
- **Year-aware Storage**: Implemented with `daily_prayers_{year}_{month}` pattern ✅
- **Per-month PDF Upload**: ACF subfields and file handling ✅

#### 2. **Admin Interface** (Plan Section 1)
- **Timetable Page**: 12 month tabs with AJAX editing ✅
- **Import/Export Page**: CSV/XLSX/Paste import with validation ✅
- **Settings Pages**: Mosque configuration and appearance ✅
- **AJAX Security**: 20+ nonce-protected endpoints ✅

#### 3. **Frontend Shortcodes** (Plan Section 1)
- **`[mosque_timetable]`**: Month view table ✅
- **`[todays_prayers]`**: Today's prayers with countdown ✅
- **`[prayer_countdown]`**: Countdown only ✅

#### 4. **PWA Infrastructure** (Plan Section 1)
- **Manifest**: `manifest.json` with shortcuts and widgets ✅
- **Service Worker**: `sw.js` with caching and push handlers ✅
- **Icons**: 192px and 512px PWA icons ✅

#### 5. **Import/Export System** (Plan Section 1)
- **CSV/XLSX Import**: Via modal with SimpleXLSX library ✅
- **ICS Export**: Server-side `.ics` generation ✅
- **CSV Export**: Full year export ✅

### ⚠️ **DOCUMENTED BUT NEEDS VERIFICATION**

These features are mentioned in CHANGELOG.md but require testing in live WordPress environment:

#### 6. **Task A: Per-month PDF Upload**
- **Status**: Code exists, needs testing
- **Implementation**: PDF upload form in month tabs, storage in ACF subfields

#### 7. **Task B: Visitor Export Modal**
- **Status**: `mt-modal.js` exists, REST endpoint coded
- **Implementation**: Comprehensive export options modal

#### 8. **Task C: Subscribe Button Override**
- **Status**: Settings exist, helper functions coded
- **Implementation**: Custom URL override in settings

#### 9. **Task D: Friday/Jummah Behavior**
- **Status**: Frontend logic coded
- **Implementation**: Zuhr replacement with "Jummah 1 / Jummah 2" format

#### 10. **Task E: Mobile Optimization**
- **Status**: CSS and JS exist for mobile cards
- **Implementation**: Pattern A transformation, sticky prayer bar

#### 11. **Task F: Terminology Overrides**
- **Status**: Admin interface and replacement logic coded
- **Implementation**: Customizable label mappings

#### 12. **Task G: SEO & Structured Data**
- **Status**: Sitemap, robots.txt, llms.txt handlers coded
- **Implementation**: XML sitemap, Schema.org markup

#### 13. **Task H: PWA Polish**
- **Status**: Manifest updated, `/today` endpoint coded
- **Implementation**: Enhanced shortcuts, dedicated today page

#### 14. **Task I: Multi-year Archives**
- **Status**: Archive pages and year browser coded
- **Implementation**: `/prayer-times/{year}` rewrite rules

#### 15. **Task J: Push Notifications**
- **Status**: Complete system coded with minishlink/web-push
- **Implementation**: VAPID keys, cron jobs, subscription management

#### 16. **Task K: Internationalization**
- **Status**: Translation functions wrapped, .pot file exists
- **Implementation**: Text domain loading, RTL support

## ❌ **MAJOR GAPS IDENTIFIED**

### 1. **Missing Template Files**
**Problem**: Plan specifies `templates/today.php` but no templates directory exists.
```
Expected: public_html/wp-content/plugins/mosque-timetable/templates/today.php
Found: None
```
**Impact**: `/today` virtual page may not render properly.

### 2. **SimpleXLSX Library Missing**
**Problem**: XLSX import mentions SimpleXLSX library but it's not in composer.json.
```
Expected: shuchkin/simplexlsx in composer dependencies
Found: Only minishlink/web-push dependency
```
**Impact**: XLSX imports will fail.

### 3. **REST Endpoint Registration Issues**
**Problem**: Code references REST endpoints but registration may be incomplete.
```php
// Found in code but needs verification:
- /wp-json/mosque/v1/export-ics
- /wp-json/mosque/v1/subscribe
- /wp-json/mosque/v1/unsubscribe
- /wp-json/mosque/v1/today-prayers
- /wp-json/mosque/v1/prayer-times/{year}/{month}
```
**Impact**: Frontend functionality dependent on REST API may fail.

### 4. **Virtual Page Template Resolution**
**Problem**: Rewrite rules exist but template handling unclear.
```php
// Found rewrite rules for:
- /prayer-times/{year}/{month}
- /prayer-times/calendar.ics
- /today
- /prayer-times-sitemap.xml
- /llms.txt
```
**Impact**: Virtual pages may return 404 errors.

### 5. **Asset Enqueuing Context**
**Problem**: Advanced assets (mt-modal.js, mobile styles) may not be properly enqueued.
```
Files exist:
- assets/mt-modal.js ✅
- assets/mt-modal.css ✅

Enqueuing verification needed for:
- Frontend modal functionality
- Mobile-specific styles
- PWA manifest integration
```

### 6. **Database Migration for Year-Aware Storage**
**Problem**: Legacy `daily_prayers_{month}` fields need migration to `daily_prayers_{year}_{month}`.
```php
// Old format: daily_prayers_1, daily_prayers_2, etc.
// New format: daily_prayers_2025_1, daily_prayers_2025_2, etc.
```
**Impact**: Existing data may be inaccessible after year-aware changes.

## 🔧 **DETAILED IMPLEMENTATION PROMPTS**

### Priority 1: Critical Infrastructure Fixes

#### **Fix 1: Add Missing Template Structure**
```
Create the missing template infrastructure for virtual pages:

1. Create directory: public_html/wp-content/plugins/mosque-timetable/templates/
2. Create templates/today.php with:
   - Header with mosque name and current date
   - Today's prayer times display
   - Next prayer countdown
   - Subscribe and export buttons
   - Mobile-optimized layout
3. Update template_redirect handler to properly load templates
4. Ensure template inherits theme styles while maintaining plugin styling

Template should integrate with existing shortcode functionality and respect terminology overrides.
```

#### **Fix 2: Add SimpleXLSX Dependency**
```
Add the missing XLSX processing library:

1. Update composer.json to include "shuchkin/simplexlsx": "^1.0"
2. Run composer update to install dependency
3. Update XLSX import handler to use SimpleXLSX instead of current placeholder
4. Add proper error handling for malformed XLSX files
5. Ensure XLSX parsing respects same column order as CSV import
6. Test with sample XLSX files containing prayer time data

Import should handle both .xlsx and .xls formats with graceful degradation to CSV for unsupported formats.
```

#### **Fix 3: Complete REST Endpoint Registration**
```
Audit and complete REST API endpoint registration:

1. Verify all endpoints in register_rest_endpoints() are properly registered
2. Add missing endpoint handlers for:
   - /wp-json/mosque/v1/subscribe (POST)
   - /wp-json/mosque/v1/unsubscribe (POST)
   - /wp-json/mosque/v1/widget/prayer-times (GET)
   - /wp-json/mosque/v1/widget/countdown (GET)
3. Implement proper permission callbacks for each endpoint
4. Add comprehensive input validation and sanitization
5. Ensure all endpoints return consistent JSON structure
6. Add CORS headers for PWA functionality
7. Test endpoints with various authentication states

Each endpoint should have proper error handling and follow WordPress REST API standards.
```

### Priority 2: Virtual Page Template System

#### **Fix 4: Implement Virtual Page Rendering**
```
Complete the virtual page system referenced in rewrite rules:

1. Create template handler in template_redirect hook
2. Implement rendering for:
   - /today -> templates/today.php
   - /prayer-times/{year} -> yearly archive
   - /prayer-times/{year}/{month} -> monthly timetable
   - /prayer-times-sitemap.xml -> XML sitemap generation
   - /llms.txt -> metadata file output
3. Add proper HTTP headers for each page type
4. Implement 404 handling for invalid years/months
5. Ensure SEO meta tags are included
6. Add canonical URLs and breadcrumb support
7. Integrate with WordPress theme system

Virtual pages should respect theme layout while maintaining plugin-specific content styling.
```

#### **Fix 5: Implement Archive Page Navigation**
```
Create comprehensive archive navigation system:

1. Build /prayer-times/ main archive page showing available years
2. Create /prayer-times/{year}/ year page showing 12 months with data indicators
3. Add navigation breadcrumbs (Home > Prayer Times > 2024 > January)
4. Implement year/month navigation widgets
5. Add "Current Year" highlighting
6. Include data availability indicators (empty months, partial data)
7. Add quick navigation to current month/year
8. Implement responsive design for mobile

Archive pages should provide clear navigation between years and easy access to current data.
```

### Priority 3: Frontend Integration Verification

#### **Fix 6: Test and Fix Modal Integration**
```
Verify and fix the visitor export modal system:

1. Test mt-modal.js integration with frontend
2. Verify modal opens from "Export Calendar" buttons
3. Test all modal form options (date range, jamāʿah, notifications)
4. Verify REST endpoint connectivity from modal
5. Test ICS file generation with various option combinations
6. Fix any CSS conflicts with theme styles
7. Ensure modal is accessible (keyboard navigation, screen readers)
8. Test Google Calendar integration instructions

Modal should work seamlessly across different themes and devices.
```

#### **Fix 7: Verify Push Notification System**
```
Test and fix the complete push notification workflow:

1. Verify VAPID key generation and storage in admin
2. Test subscription flow from frontend "Prayer Reminders" button
3. Verify service worker registration and push message handling
4. Test notification permission pre-prompt modal
5. Verify WP-Cron job execution for sending notifications
6. Test unsubscribe functionality
7. Verify notification content includes prayer name and time
8. Test notification click handling (opens relevant page)

System should handle browser compatibility and provide clear error messages for unsupported browsers.
```

### Priority 4: Data Migration and Compatibility

#### **Fix 8: Implement Year-Aware Data Migration**
```
Create migration system for year-aware storage:

1. Add migration routine in plugin activation hook
2. Migrate existing daily_prayers_{month} to daily_prayers_{currentYear}_{month}
3. Create backup of original data before migration
4. Add admin notice showing migration status
5. Implement rollback functionality if migration fails
6. Add data integrity checks post-migration
7. Update all data access functions to use year-aware keys
8. Test both ACF and options fallback modes

Migration should be non-destructive and provide clear feedback to administrators.
```

#### **Fix 9: Verify Mobile Responsiveness**
```
Test and fix mobile Pattern A transformation:

1. Verify table-to-card transformation on screens ≤ 480px
2. Test sticky prayer bar functionality and swipe navigation
3. Verify auto-centering on next prayer
4. Test keyboard navigation (arrow keys, tab order)
5. Verify ARIA labels and accessibility features
6. Test touch interactions and gestures
7. Ensure no horizontal scrolling on any mobile device
8. Test prayer chip responsiveness

Mobile experience should be touch-friendly and accessible without compromising functionality.
```

### Priority 5: SEO and Discovery Features

#### **Fix 10: Implement Complete SEO System**
```
Complete the SEO discovery implementation:

1. Test XML sitemap generation at /prayer-times-sitemap.xml
2. Verify robots.txt integration without overwriting existing entries
3. Test llms.txt file serving with proper metadata
4. Verify Schema.org structured data output
5. Test search engine indexing of prayer time pages
6. Add OpenGraph and Twitter Card meta tags
7. Implement JSON-LD for prayer events
8. Test with Google's Structured Data Testing Tool

SEO features should enhance discoverability without interfering with existing site SEO.
```

## 🚨 **IMPOSSIBLE/IMPRACTICAL FEATURES**

### **Voice Integration**
**Status**: Explicitly out of scope in plan
**Reason**: Plan states "Voice integrations remain out of scope"

### **Advanced Calendar Subscriptions**
**Status**: Limited by calendar client capabilities
**Reason**: Plan notes "Google does not support multi-event creation via URL parameters"

### **Real-time Prayer Time Calculations**
**Status**: Not specified in plan
**Reason**: Would require complex astronomical calculations or external APIs

## 📊 **IMPLEMENTATION PRIORITY MATRIX**

| Priority | Feature | Effort | Impact | Dependencies |
|----------|---------|--------|--------|--------------|
| 🔥 **P0** | REST Endpoints | High | Critical | None |
| 🔥 **P0** | Template System | High | Critical | None |
| 🟡 **P1** | SimpleXLSX Library | Low | High | Composer |
| 🟡 **P1** | Data Migration | Medium | High | Database |
| 🟢 **P2** | Modal Integration | Medium | Medium | REST API |
| 🟢 **P2** | Push Notifications | High | Medium | Service Worker |
| 🟢 **P3** | Mobile Responsiveness | Medium | Medium | CSS/JS |
| 🟢 **P3** | SEO Features | Medium | Low | Virtual Pages |

## 🎯 **NEXT STEPS RECOMMENDATION**

1. **Immediate (This Week)**:
   - Fix REST endpoint registration
   - Add missing templates directory and today.php
   - Test core functionality in live WordPress environment

2. **Short Term (Next 2 Weeks)**:
   - Add SimpleXLSX dependency
   - Implement data migration system
   - Test and fix modal integration

3. **Medium Term (Next Month)**:
   - Complete push notification testing
   - Verify mobile responsiveness
   - Implement complete SEO features

4. **Long Term (Ongoing)**:
   - Performance optimization
   - Advanced feature enhancements
   - Community feedback integration

## 📝 **TESTING CHECKLIST**

- [ ] All 16 planned features load without PHP errors
- [ ] REST endpoints return expected JSON responses
- [ ] Virtual pages render correctly (not 404)
- [ ] Modal opens and submits successfully
- [ ] Push notifications can be subscribed to and received
- [ ] Mobile cards display properly on small screens
- [ ] Year navigation works between different years
- [ ] Import/export functions handle files correctly
- [ ] SEO features don't conflict with existing site SEO
- [ ] Translation system works with different languages

This analysis provides the foundation for completing the plugin implementation and ensuring all planned features work correctly in a live WordPress environment.