# Mosque Timetable Plugin - Development Instructions for Claude Code

## 🚨 CRITICAL CONTEXT: What Actually Works

**IMPORTANT DISCOVERY**: Live testing on https://mosquewebdesign.com proves this plugin IS FUNCTIONAL:

✅ **CONFIRMED WORKING (Live Site)**:

- REST API endpoints return valid JSON data
- Virtual pages render correctly (/today, /prayer-times/, archives)
- PWA functionality (manifest, service worker)
- ICS calendar exports generate valid files
- Frontend shortcodes work
- Multi-year archive system functional

❌ **CRITICAL ISSUE - ADMIN INTERFACE**:

- **Timetable admin page buttons click but NOTHING saves/updates**
- This is the PRIMARY BUG to fix
- Frontend works, backend (admin) is broken

## Git

**Remote:** `github.com/ibraheem-mustafa-dev/mosque-timetable` (private). See global CLAUDE.md for workflow rules.

## Your Mission

**PRIMARY OBJECTIVE**: Fix the admin interface AJAX save/update issue

**SECONDARY**: Fix any syntax errors and ensure all features are complete

## Primary Documentation Sources

**READ ALL OF THESE BEFORE MAKING ANY CHANGES:**

1. **Feature Specification**: `public_html/wp-content/plugins/mosque-timetable/FEATURE_LIST.md`
   - 150+ features that should be implemented
   - This is the source of truth for what exists

2. **Technical Plan**: `.claude/Mosque_Timetable_Technical_Plan.md`
   - Architecture and implementation details
   - Tasks A-K implementation requirements

3. **Implementation Status**: `public_html/wp-content/plugins/mosque-timetable/CHANGELOG.md`
   - Documents Tasks A-K as completed
   - What's been built already

4. **Testing Reports**: Check these files in plugin folder:
   - `ACTUAL_FUNCTIONALITY_TEST_RESULTS.md` - Live site testing proof
   - `CRITICAL_FIXES_APPLIED.md` - Previous fixes
   - `COMPREHENSIVE_GAP_ANALYSIS.md` - Known gaps

## Critical Files to Debug

**ADMIN INTERFACE (PRIMARY FOCUS)**:

```
public_html/wp-content/plugins/mosque-timetable/
├── mosque-timetable.php                    ← Main file, contains AJAX handlers
├── assets/mosque-timetable-admin.js        ← Admin JS with click handlers
├── assets/mosque-timetable-admin.css       ← Admin styling
└── assets/
    ├── mosque-timetable.js                 ← Frontend JS
    ├── mosque-timetable.css                ← Frontend CSS
    ├── mt-modal.js                         ← Export modal (Frontend)
    └── mt-modal.css                        ← Modal styling
```

**FRONTEND (WORKING)**:

- Shortcodes: `[mosque_timetable]`, `[todays_prayers]`, `[prayer_countdown]`
- Virtual pages: `/today`, `/prayer-times/`, `/prayer-times/{year}/`
- REST endpoints: `/wp-json/mosque/v1/*`

## Known Critical Issues

### 🔴 **ISSUE #1: Admin Interface AJAX Broken** (PRIMARY)

**Symptoms**:

- Timetable admin page loads
- Buttons clickable
- NO data saves or updates
- NO visible errors to user

**Debug This Flow**:

```
1. assets/mosque-timetable-admin.js
   - Find: Button click handlers (e.g., save_month, import_csv)
   - Check: preventDefault() calls
   - Check: AJAX request formation

2. mosque-timetable.php (wp_localize_script)
   - Find: admin_enqueue_scripts hook
   - Check: ajaxurl is passed to admin JS
   - Check: nonce values are passed

3. mosque-timetable.php (AJAX handlers)
   - Find: wp_ajax_{action} hooks
   - Check: Action names match JS (e.g., 'mt_save_month')
   - Check: Nonce validation
   - Check: Capability checks
   - Check: Database operations (ACF or options)
   - Check: JSON response sent

4. Browser Console
   - Check: JavaScript errors
   - Check: Network tab for failed requests
   - Check: Response codes (200, 403, 500)
```

**Common AJAX Problems**:

- ❌ Mismatched action names (`mt_save_month` vs `save_month`)
- ❌ Missing `ajaxurl` in wp_localize_script
- ❌ Nonce verification failing
- ❌ Missing `die()` or `wp_die()` at end of handler
- ❌ Not sending JSON response (`wp_send_json_success()`)
- ❌ Database writes failing silently

### 🟡 **ISSUE #2: Possible Syntax Error**

**File**: `mosque-timetable.php`
**Line**: ~6601
**Error**: Reported "unexpected token const"

**Action**: Check lines 6595-6610 for syntax issues (misplaced const, missing semicolon, unclosed bracket)

### ✅ **Dependencies - CONFIRMED INSTALLED**

Live testing confirms these are working:

- ✅ minishlink/web-push ^9.0
- ✅ shuchkin/simplexlsx ^1.1
- ✅ shuchkin/simplexlsxgen ^1.5
- ✅ web-token/jwt-library ^3.4

Location: `public_html/wp-content/plugins/mosque-timetable/vendor/`

## Your Workflow

### Step 1: Quick Syntax Check (IF ERROR EXISTS)

```bash
cd public_html/wp-content/plugins/mosque-timetable
php -l mosque-timetable.php
```

If error at line ~6601, fix it immediately. Otherwise, continue.

### Step 2: Debug Admin AJAX (PRIMARY TASK)

**A. Identify the AJAX Flow**:

1. Open `assets/mosque-timetable-admin.js`
2. Find button click handlers (search for: `.on('click'`, `addEventListener`)
3. Note the AJAX action names used (e.g., `action: 'mt_save_month'`)
4. Check if `ajaxurl` variable is used
5. Check if nonces are sent with requests

**B. Verify wp_localize_script**:

1. Open `mosque-timetable.php`
2. Search for: `admin_enqueue_scripts` hook
3. Find the `wp_localize_script` call for admin JS
4. Verify it includes:
   ```php
   wp_localize_script('mt-admin-js', 'mtAdmin', array(
       'ajaxurl' => admin_url('admin-ajax.php'),  // MUST EXIST
       'nonce' => wp_create_nonce('mt_admin_nonce'), // MUST EXIST
       // ... other vars
   ));
   ```

**C. Check AJAX Handler Registration**:

1. In `mosque-timetable.php`, search for: `wp_ajax_`
2. For each action in admin JS, find corresponding:
   ```php
   add_action('wp_ajax_mt_save_month', array($this, 'handle_save_month'));
   ```
3. Verify action names EXACTLY match JavaScript
4. Check handlers have proper structure:

   ```php
   public function handle_save_month() {
       // 1. Check nonce
       check_ajax_referer('mt_admin_nonce', 'nonce');

       // 2. Check capability
       if (!current_user_can('manage_options')) {
           wp_send_json_error('Permission denied');
       }

       // 3. Sanitize input
       $month = intval($_POST['month']);
       $data = /* ... sanitize ... */;

       // 4. Save to database
       // ACF: update_field(...) OR
       // Options: update_option(...)

       // 5. MUST end with wp_send_json_success() or wp_die()
       wp_send_json_success(array('message' => 'Saved'));
   }
   ```

**D. Test in Browser**:

1. Open WordPress admin
2. Go to Prayer Timetable page
3. Open browser DevTools (F12)
4. Go to Console tab - look for JS errors
5. Go to Network tab
6. Click a save button
7. Check the AJAX request:
   - Status code (should be 200)
   - Response (should be JSON)
   - Request payload (should include action, nonce, data)

**E. Common Fixes**:

```php
// FIX 1: Missing ajaxurl
wp_localize_script('mt-admin-js', 'mtAdmin', array(
    'ajaxurl' => admin_url('admin-ajax.php'), // ADD THIS
    // ... rest
));

// FIX 2: Wrong action name
// In JS: action: 'mt_save_month'
// In PHP: add_action('wp_ajax_mt_save_month', ...); // MUST MATCH

// FIX 3: Missing die/exit
public function handle_save_month() {
    // ... do stuff ...
    wp_send_json_success($result); // This calls die() internally
}

// FIX 4: Silent database failure
$result = update_option('mt_prayers_2024_1', $data);
if ($result === false) {
    wp_send_json_error('Database update failed');
}
wp_send_json_success('Saved');
```

### Step 3: Feature Completion Assessment

**Known Status** (from CHANGELOG.md):

- ✅ Tasks A-K: All documented as complete
- ✅ Frontend: Confirmed working on live site
- ❌ Admin Interface: BROKEN (your task)

**Quick Verification**:

```bash
# Check if all planned AJAX handlers exist
cd public_html/wp-content/plugins/mosque-timetable
grep -n "wp_ajax_" mosque-timetable.php | wc -l
# Should show 20+ handlers

# Check if all REST endpoints registered
grep -n "register_rest_route" mosque-timetable.php | wc -l
# Should show 6+ routes
```

### Step 4: Run Quality Checks

```bash
# From repository root
composer lint       # PHP syntax check
npm run lint:js     # JavaScript linting
npm run lint:css    # CSS linting
composer phpcs      # WordPress coding standards
composer check      # PHPStan type checking (expect warnings, not errors)
```

**Important**: Focus on ERRORS, not warnings. PHPStan will show 100+ warnings about type hints - these are documentation issues, not bugs.

## Code Quality Requirements

### WordPress Standards

- Use `$wpdb->prepare()` for all database queries
- Sanitize all input with `sanitize_*()` functions
- Escape all output with `esc_*()` functions
- Use nonces for all AJAX operations
- Check capabilities before admin actions

### PHP Standards

- PHP 8.1+ syntax allowed
- Type hints required on new functions
- No suppressed errors with `@`
- Proper error handling (try/catch where needed)
- No hardcoded paths or URLs

### Security

- No SQL injection vulnerabilities
- No XSS vulnerabilities
- No CSRF vulnerabilities
- Validate file uploads (type, size, content)
- Check user permissions everywhere

### No Placeholders

- Every function must be fully implemented
- No TODO comments without implementation
- No empty switch/case statements
- No functions that return fake data

## Testing Checklist

**CRITICAL** (Must fix):

- [ ] Admin timetable page saves data successfully
- [ ] Admin import functions work (CSV, XLSX, paste)
- [ ] No syntax errors in mosque-timetable.php

**Already Working** (from live site testing):

- [x] Plugin activates without errors
- [x] REST API endpoints return valid JSON
- [x] Frontend shortcodes display correctly
- [x] PWA service worker registers
- [x] Calendar export generates ICS file
- [x] Virtual pages render (/today, /prayer-times/)

**Should Verify After Admin Fix**:

- [ ] CSV import saves to database
- [ ] Excel import saves to database
- [ ] PDF upload per month works
- [ ] Push notification subscription works

## What NOT to Do

- ❌ Don't create new placeholder functions
- ❌ Don't skip error handling
- ❌ Don't assume external dependencies exist
- ❌ Don't leave broken code "for later"
- ❌ Don't change composer.json files
- ❌ Don't modify vendor/ folder
- ❌ Don't remove features that work
- ❌ Don't add features not in the spec

## Distribution Requirements

This plugin may be:

- Submitted to WordPress.org
- Used on client sites
- Distributed publicly

Therefore:

- **Code quality matters**
- **Security is critical**
- **Documentation must be clear**
- **No debug code in production**

## When You Finish

Provide a summary report:

1. **Admin AJAX Issue**:
   - What was the root cause?
   - What files were changed?
   - How was it tested?

2. **Syntax Errors Fixed** (if any):
   - File paths and line numbers
   - What was wrong and how fixed

3. **Quality Check Results**:
   - `composer lint` output
   - `npm run lint:js` output
   - `npm run lint:css` output
   - `composer phpcs` status
   - `composer check` critical errors (ignore warnings)

4. **Manual Testing Required**:
   - What needs WordPress admin access to verify?
   - Any features that need real browser testing?

## Tech Stack Reference

- **WordPress**: 6.5+
- **PHP**: 8.1+
- **ACF Pro**: Optional (has fallback)
- **JavaScript**: Vanilla JS (no framework)
- **CSS**: Modern CSS with custom properties
- **PWA**: Service Worker + Web App Manifest
- **Dependencies**: See composer.json in plugin folder

## Getting Help

If you encounter:

- Ambiguous requirements → Check technical plan
- Missing spec details → Implement WordPress best practices
- Complex features → Break into smaller tasks
- Blocked by external factors → Document and flag

## Success Criteria

✅ Admin interface saves/updates data successfully
✅ No syntax errors in any file
✅ JavaScript linting passes
✅ CSS linting passes
✅ PHP syntax check passes
✅ No critical PHPStan errors (warnings OK)
✅ All AJAX handlers properly registered
✅ Proper error handling in admin operations

## Important Context from Documentation

### What's Been Accomplished (Per CHANGELOG.md)

**All 11 Technical Plan Tasks Completed**:

- ✅ Task A: Per-month PDF upload in admin tabs
- ✅ Task B: Visitor export modal with comprehensive options
- ✅ Task C: Subscribe button with URL override
- ✅ Task D: Friday/Jummah frontend display
- ✅ Task E: Mobile Pattern A transform & sticky prayer bar
- ✅ Task F: Terminology override system
- ✅ Task G: SEO (sitemap, llms.txt, structured data)
- ✅ Task H: PWA polish (merged with other tasks)
- ✅ Task I: Multi-year archives with intelligent year handling
- ✅ Task J: Push notifications (opt-in, privacy-safe)
- ✅ Task K: Internationalization (i18n ready)

### Live Site Proof (mosquewebdesign.com)

**Verified Working**:

- REST API: `/wp-json/mosque/v1/prayer-times/2024/9` returns valid JSON
- ICS Export: `/wp-json/mosque/v1/export-ics` generates proper calendar
- Virtual Pages: `/today`, `/prayer-times/`, `/prayer-times/2024/` all render
- PWA: manifest.json accessible and valid
- Widgets: PWA widget endpoints functional

**Real Data Configured**:

- Mosque: Stechford Mosque, Birmingham, UK
- Timezone: Europe/London
- September 2024 prayer times fully populated

### The ONE Critical Bug

**Admin Interface AJAX Not Saving** - Everything else works, but the admin panel can't persist changes to the database. This is your PRIMARY mission.

### Key Implementation Notes

**Dual Storage Modes**:

```php
// Mode 1: ACF Pro (if available)
update_field('daily_prayers_2024_1', $data);

// Mode 2: Options fallback (no ACF)
update_option('mt_daily_prayers_2024_1', $data);
```

**20+ AJAX Handlers Should Exist** for:

- Month data saving (12 months)
- CSV import
- XLSX import
- Paste import
- PDF upload
- Settings updates
- Push notification settings
- Terminology overrides

**6+ REST Endpoints** at `/wp-json/mosque/v1/`:

- `/prayer-times/{year}/{month}`
- `/today-prayers`
- `/next-prayer`
- `/export-ics`
- `/widget/prayer-times`
- `/widget/countdown`
- `/subscribe` (POST)
- `/unsubscribe` (POST)

---

**START HERE**: Fix the admin AJAX issue first. Everything else is secondary.

# PROJECT_PLAN Integration

# Added by Claude Config Manager Extension

When working on this project, always refer to and maintain the project plan located at `.claude/.plans/PROJECT_PLAN.md`.

**Instructions for Claude Code:**

1. **Read the project plan first** - Always check `.claude/.plans/PROJECT_PLAN.md` when starting work to understand the project context, architecture, and current priorities.
2. **Update the project plan regularly** - When making significant changes, discoveries, or completing major features, update the relevant sections in PROJECT_PLAN.md to keep it current.
3. **Use it for context** - Reference the project plan when making architectural decisions, understanding dependencies, or explaining code to ensure consistency with project goals.

**Plan Mode Integration:**

- **When entering plan mode**: Read the current PROJECT_PLAN.md to understand existing context and priorities
- **During plan mode**: Build upon and refine the existing project plan structure
- **When exiting plan mode**: ALWAYS update PROJECT_PLAN.md with your new plan details, replacing or enhancing the relevant sections (Architecture, TODO, Development Workflow, etc.)
- **Plan persistence**: The PROJECT_PLAN.md serves as the permanent repository for all planning work - plan mode should treat it as the single source of truth

This ensures better code quality and maintains project knowledge continuity across different Claude Code sessions and plan mode iterations.
