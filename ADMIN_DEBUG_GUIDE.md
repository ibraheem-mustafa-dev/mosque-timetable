# Admin Interface Debug Guide

## 🐛 Issue: "Generate" Button Doesn't Populate Data

### Quick Diagnosis Steps

1. **Open Browser Console** (F12 → Console tab)
2. **Click "Generate All Dates"** button
3. **Check for errors**

---

## Common Issues & Fixes

### Issue 1: AJAX Request Failing

**Symptoms:**
- Button clicks but nothing happens
- No data appears in table
- Console shows `404` or `500` error

**Debug in Console:**
```javascript
// Check if ajaxurl is defined
console.log(mosqueTimetableAdmin.ajaxUrl);

// Check nonce
console.log(mosqueTimetableAdmin.nonce);
```

**Expected Output:**
```
https://mosquewebdesign.com/wp-admin/admin-ajax.php
[some hash value]
```

**If undefined:** wp_localize_script is not working

---

### Issue 2: ACF Pro Field Name Mismatch

**Symptoms:**
- AJAX succeeds (200 OK)
- Success message shows
- Data still doesn't appear in table

**Cause:**
Field names in ACF don't match code expectations.

**Expected ACF Field Names:**
```
daily_prayers_2024_1   (January 2024)
daily_prayers_2024_2   (February 2024)
...
daily_prayers_2024_12  (December 2024)
```

**Check in WordPress:**
1. Go to: Custom Fields → Field Groups
2. Find: "Prayer Timetable Data"
3. Verify field names match pattern: `daily_prayers_{year}_{month}`

---

### Issue 3: Database Storage Not Working

**Check via PHP:**
Add this temporarily to mosque-timetable.php after line 8710:

```php
error_log('MT Generate: Saving month ' . $month . ' for year ' . $year);
error_log('MT Generate: Data count: ' . count($month_data));
$save_result = mt_save_month_rows( $month, $month_data, $year );
error_log('MT Generate: Save result: ' . ($save_result ? 'SUCCESS' : 'FAILED'));
return $save_result;
```

Then check error log at:
```
/home/u945238940/domains/mosquewebdesign.com/logs/error_log
```

---

### Issue 4: JavaScript Not Loading Latest Version

**Symptoms:**
- Old behavior persists after upload
- Console shows old action names

**Fix:**
Clear caches:
1. WordPress admin cache
2. Browser cache (Ctrl+Shift+Delete)
3. Server cache (if any CDN/caching plugin)

**Force reload:**
```
Ctrl+F5 (Windows)
Cmd+Shift+R (Mac)
```

---

## Quick Fix Attempts

### Fix 1: Verify AJAX Handler

Add console.log to mosque-timetable-admin.js line 519:

```javascript
generateAllDates() {
  console.log('Calling generate_all_dates with year:', this.config.currentYear);
  $('#generate-all-dates').addClass('mosque-btn-loading');
  $.post(this.config.ajaxUrl, {
    action: 'generate_all_dates',
    nonce: this.config.nonce,
    year: this.config.currentYear
  })
  .done((res) => {
    console.log('Generate response:', res);
    // ... rest
  })
  .fail((xhr) => {
    console.error('Generate failed:', xhr.responseText);
  });
}
```

### Fix 2: Check Network Tab

1. Open DevTools → Network tab
2. Click "Generate All Dates"
3. Find request to `admin-ajax.php`
4. Check:
   - **Request Payload:** Should include `action`, `nonce`, `year`
   - **Response:** Should be JSON with `success: true`
   - **Status:** Should be `200`

---

## Expected Flow

```
1. User clicks "Generate All Dates"
   ↓
2. JavaScript calls AJAX with action: 'generate_all_dates'
   ↓
3. PHP receives request at wp_ajax_generate_all_dates hook
   ↓
4. Validates nonce & permissions
   ↓
5. Calls generate_month_structure() for each month (1-12)
   ↓
6. Each month:
   - Fetches prayer times from Aladhan API (if enabled)
   - Calculates Hijri dates
   - Builds data structure
   - Calls mt_save_month_rows()
   ↓
7. mt_save_month_rows() saves to:
   - ACF field: daily_prayers_{year}_{month}
   - OR Options: mosque_timetable_rows[year][month]
   ↓
8. Returns success JSON to JavaScript
   ↓
9. JavaScript calls loadMonthData() to refresh table
   ↓
10. Table shows populated data
```

---

## Manual Data Population Test

If generate isn't working, test saving manually:

### Via WP-CLI:
```bash
wp acf update-field daily_prayers_2024_10 '[{"day_number":1,"fajr_start":"05:30"}]' --option=option
```

### Via PHP (add temporarily):
```php
// Test save function
add_action('admin_init', function() {
  if (isset($_GET['test_mt_save'])) {
    $test_data = [
      ['day_number' => 1, 'fajr_start' => '05:30', 'fajr_jamaat' => '05:45']
    ];
    $result = mt_save_month_rows(10, $test_data, 2024);
    die('Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
  }
});
```

Then visit: `https://mosquewebdesign.com/wp-admin/?test_mt_save=1`

---

## If Still Not Working

**Possible causes:**
1. ACF Pro not active
2. Database permissions issue
3. PHP version too old (needs 8.1+)
4. WordPress auto-save conflict
5. Security plugin blocking AJAX

**Ultimate debug:**
Enable WordPress debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check: `/wp-content/debug.log`
