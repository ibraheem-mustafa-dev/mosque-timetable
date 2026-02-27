# MU-Plugin vs Main Plugin Integration Analysis

## Executive Summary

The mu-plugins (`mt-admin-hotfix.php` and `mt-admin-hotfix.js`) are **redundant and incompatible** with the main plugin. They should be removed.

## Architecture Comparison

### Storage Models

| Aspect | MU-Plugin | Main Plugin |
|--------|-----------|-------------|
| **Storage Type** | Post-based (post_meta) | Option-based (wp_options) |
| **ACF Context** | Post ID required | 'option' context |
| **Field Names** | `daily_timetable` or `mt_rows_YYYY_MM` | `daily_prayers_{year}_{month}` |
| **Data Structure** | Requires a post | Site-wide settings |

**Conclusion:** Completely incompatible storage architectures. Data saved by one won't be readable by the other.

---

## AJAX Handlers Comparison

| Feature | MU-Plugin Action | Main Plugin Action | Main Plugin Handler |
|---------|------------------|-------------------|---------------------|
| Get month | `mt_get_month` | `get_month_timetable` | ✅ Line 9397 |
| Save month | `mt_save_month` | `save_month_timetable` | ✅ Line 492 (method: 6570) |
| Generate month | `mt_generate_month` | `generate_month_dates` | ✅ Line 8961 |
| Generate year | `mt_generate_year` | `generate_all_dates` | ✅ Line 8928 |
| Recalc Hijri | `mt_recalc_hijri` | `recalculate_hijri_dates` | ⚠️ Line 9680 (has bug) |
| Upload PDF | `mt_upload_month_pdf` | `upload_month_pdf` | ✅ Line 9510 |
| Remove PDF | ❌ N/A | `remove_month_pdf` | ✅ Line 9539 |
| Save all | ❌ N/A | `save_all_months` | ✅ Line 8994 |
| Import CSV | ❌ N/A | `import_csv_timetable` | ✅ Line 493 |
| Import XLSX | ❌ N/A | `import_xlsx_timetable` | ✅ Line 9041 |
| Import Paste | ❌ N/A | `import_paste_data` | ✅ Line 9205 |

**Conclusion:** Main plugin has MORE functionality. MU-plugin adds nothing new.

---

## JavaScript Selectors Comparison

| Feature | MU-Plugin Selector | Main Plugin Selector | Conflict? |
|---------|-------------------|---------------------|-----------|
| Month tabs | `.mosque-month-tab` | `.mosque-month-tab` | ⚠️ BOTH |
| Year selector | `#year-selector` | `#year-selector` | ⚠️ BOTH |
| Load year | `#load-year` | `#load-year-data` | ❌ Different |
| Generate all | `#generate-all-dates` | `#generate-all-dates` | ⚠️ BOTH |
| Generate month | `#generate-month` | `.generate-month-dates` | ❌ Different |
| Save month | `#save-month` | `.save-month-btn` | ❌ Different |
| Save all | ❌ N/A | `#save-all-months` | ✅ Main only |
| Recalc Hijri | `#recalc-hijri` | `.recalc-hijri-btn` | ❌ Different |
| Upload PDF | `#upload-pdf` | `.mt-upload-pdf-btn` | ❌ Different |

**Conflicts:** Both scripts listen to the same events on shared selectors, causing race conditions.

---

## Identified Bugs in Main Plugin

### 1. **ajax_recalculate_hijri_dates() Field Name Mismatch** ⚠️ CRITICAL

**Location:** `mosque-timetable.php:6637`

**Issue:**
```php
// WRONG - uses old field name without year
$field_name = 'daily_prayers_' . $month;
```

**Should be:**
```php
// CORRECT - uses year-based field name
$field_name = "daily_prayers_{$year}_{$month}";
```

**Impact:** Hijri recalculation reads from wrong field, won't find data.

### 2. **ajax_recalculate_hijri_dates() Uses Old Data Structure** ⚠️ CRITICAL

**Location:** `mosque-timetable.php:6646-6657`

**Issue:**
- Expects old structure with `date_full` field
- Uses `update_field()` instead of `mt_save_month_rows()` helper

**Should use:**
```php
$rows = mt_get_month_rows($month, $year);
// ... recalculate hijri ...
mt_save_month_rows($month, $rows, $year);
```

---

## What the MU-Plugin Does

The mu-plugin provides:
1. ✅ Basic CRUD for month data (get/save/generate)
2. ✅ Hijri recalculation
3. ✅ PDF upload
4. ✅ Server-side table rendering

**BUT:**
- Uses post-based storage (incompatible)
- Uses different AJAX action names (breaks main plugin JS)
- Conflicts with main plugin event handlers
- Provides NO features main plugin lacks

---

## Recommendation: REMOVE MU-PLUGINS

### Why Remove:

1. **Storage Incompatibility:** Post-based vs Option-based
2. **No Added Value:** Main plugin has ALL features + more
3. **Causes Conflicts:** Overlapping event handlers
4. **Breaks Main Plugin:** Different AJAX actions prevent main plugin JS from working
5. **Not Portable:** Can't be distributed to WordPress.org

### Action Plan:

1. ✅ Fix bugs in main plugin (hijri recalculation)
2. ✅ Ensure all AJAX handlers are registered
3. ✅ Delete/disable mu-plugins
4. ✅ Test admin interface

---

## Files to Modify

### 1. mosque-timetable.php

**Fix ajax_recalculate_hijri_dates()** (lines 6618-6658):

```php
public function ajax_recalculate_hijri_dates() {
    if (!check_ajax_referer('mosque_timetable_nonce', 'nonce', false)) {
        wp_send_json_error(__('Security check failed', 'mosque-timetable'));
    }
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Insufficient permissions', 'mosque-timetable'));
    }

    $month = isset($_POST['month']) ? intval(sanitize_text_field(wp_unslash($_POST['month']))) : 0;
    $year = isset($_POST['year']) ? intval(sanitize_text_field(wp_unslash($_POST['year']))) : (int) wp_date('Y');
    $adjustment = isset($_POST['adjustment']) ? intval(sanitize_text_field(wp_unslash($_POST['adjustment']))) : 0;

    if (!$month || $month < 1 || $month > 12) {
        wp_send_json_error(__('Invalid month specified', 'mosque-timetable'));
    }

    // Use helper function to get data
    $rows = mt_get_month_rows($month, $year);

    if (empty($rows)) {
        wp_send_json_error(__('No prayer data found for this month', 'mosque-timetable'));
    }

    // Recalculate Hijri dates
    foreach ($rows as &$row) {
        if (!empty($row['date_full'])) {
            $row['hijri_date'] = $this->calculate_hijri_date($row['date_full'], $adjustment);
        }
    }

    // Save using helper function
    mt_save_month_rows($month, $rows, $year);

    wp_send_json_success(['count' => count($rows)]);
}
```

### 2. Delete MU-Plugins

```bash
rm public_html/wp-content/mu-plugins/mt-admin-hotfix.php
rm public_html/wp-content/mu-plugins/mt-admin-hotfix.js
```

---

## Testing Checklist

After removing mu-plugins and fixing bugs:

- [ ] Admin page loads without errors
- [ ] Month tabs switch correctly
- [ ] Data loads for each month
- [ ] Save month button works
- [ ] Generate dates button works
- [ ] Hijri recalculation works
- [ ] PDF upload works
- [ ] Import functions work
- [ ] No JavaScript console errors

---

## Conclusion

The mu-plugins were created as a temporary "hotfix" when the main plugin wasn't working. They:
- Use an incompatible storage model
- Duplicate existing functionality
- Cause conflicts with the main plugin
- Prevent WordPress.org distribution

**Solution:** Remove mu-plugins and fix the identified bugs in the main plugin.
