# Admin Page ACF Removal - Migration Guide

This document outlines the changes needed to remove ACF formatting from the timetable admin page.

## ✅ Completed

1. **New Modern CSS**: `assets/mosque-timetable-admin.css` has been completely rewritten with:
   - ACF-like clean aesthetic
   - Modern design system (CSS custom properties)
   - Card-based layouts
   - Fully responsive (mobile-first)
   - Smooth animations
   - Professional color scheme

## 🔧 Required PHP Changes

### Change 1: Update `render_timetables_admin_page()` function (Line 659-877)

**Replace ACF calls:**
```php
// OLD (Line 660-661):
$available_months = get_field( 'available_months', 'option' ) ?: range( 1, 12 );
$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );

// NEW:
$available_months = get_option( 'mt_available_months', range( 1, 12 ) );
$default_year     = (int) get_option( 'mt_default_year', wp_date( 'Y' ) );
```

**Update month names for i18n (Line 663-676):**
```php
// OLD:
$months = array(
    1  => 'January',
    2  => 'February',
    // ... etc
);

// NEW:
$months = array(
    1  => __( 'January', 'mosque-timetable' ),
    2  => __( 'February', 'mosque-timetable' ),
    // ... etc (wrap all month names with __() )
);
```

**Update HTML structure (Line 678+):**

The new HTML uses modern classes. Key changes:

1. **Wrapper structure:**
   ```php
   <div class="wrap mosque-timetable-admin">
       <div class="mosque-admin-container">
           <!-- All content -->
       </div>
   </div>
   ```

2. **Header section** - Replace with:
   ```php
   <div class="mosque-admin-header">
       <div class="mosque-admin-title-wrapper">
           <h1 class="mosque-admin-title">
               <span class="dashicons dashicons-calendar-alt"></span>
               <?php esc_html_e( 'Prayer Timetables', 'mosque-timetable' ); ?>
           </h1>
           <p class="mosque-admin-subtitle">
               <?php printf( esc_html__( 'Manage prayer times for %s', 'mosque-timetable' ), esc_html( (string) $default_year ) ); ?>
           </p>
       </div>
   </div>
   ```

3. **Control cards** - Replace year/import sections with:
   ```php
   <div class="mt-control-cards">
       <div class="mt-control-card mt-year-card">
           <div class="mt-card-header">
               <h2>📅 <?php esc_html_e( 'Year Selection', 'mosque-timetable' ); ?></h2>
           </div>
           <div class="mt-card-body">
               <!-- Year controls here -->
           </div>
       </div>

       <div class="mt-control-card mt-import-card">
           <div class="mt-card-header">
               <h2>📥 <?php esc_html_e( 'Import Tools', 'mosque-timetable' ); ?></h2>
           </div>
           <div class="mt-card-body">
               <!-- Import buttons here -->
           </div>
       </div>
   </div>
   ```

4. **Month tabs** - Change from `<a href="#"` to `<button type="button"`:
   ```php
   <button type="button"
       class="mosque-month-tab <?php echo $first ? 'active' : ''; ?>"
       data-month="<?php echo esc_attr( (string) $m ); ?>">
       <?php echo esc_html( $months[ $m ] ); ?>
   </button>
   ```

5. **Month panels** - Use new classes:
   ```php
   <div class="mosque-month-panel <?php echo $first ? 'active' : ''; ?>">
       <div class="mosque-month-header">
           <h3 class="mosque-month-title">...</h3>
           <div class="mosque-month-actions">...</div>
       </div>

       <div class="mt-hijri-controls">...</div>
       <div class="mt-pdf-upload-section">...</div>
       <div class="mosque-admin-table-wrapper">...</div>
   </div>
   ```

6. **Loading placeholder**:
   ```php
   <div class="mt-loading-placeholder">
       <div class="mt-spinner"></div>
       <p><?php echo esc_html( sprintf( __( 'Loading %s...', 'mosque-timetable' ), $months[ $m ] ) ); ?></p>
   </div>
   ```

### Change 2: Update `enqueue_admin_assets()` function (Line 915-923)

**Replace ACF call in wp_localize_script:**
```php
// OLD (Line 923):
'currentYear'  => (int) ( get_field( 'default_year', 'option' ) ?: wp_date( 'Y' ) ),

// NEW:
'currentYear'  => (int) get_option( 'mt_default_year', wp_date( 'Y' ) ),
```

## 🔄 Migration Strategy

### Option 1: Manual Update (Recommended if comfortable with PHP)
1. Open `mosque-timetable.php`
2. Make the changes listed above
3. Test the admin page
4. Save and commit

### Option 2: Search & Replace
Use these safe search/replace operations:

```
Search:  get_field( 'available_months', 'option' )
Replace: get_option( 'mt_available_months', range( 1, 12 ) )

Search:  get_field( 'default_year', 'option' )
Replace: get_option( 'mt_default_year', wp_date( 'Y' ) )
```

Then manually update the HTML structure.

### Option 3: Use the Complete Rewrite
A complete rewritten `render_timetables_admin_page()` function is available upon request.

## ✨ Benefits of New Design

1. **No ACF Dependency** - Works without ACF Pro
2. **Modern & Clean** - Professional card-based design
3. **Fully Responsive** - Mobile-optimized with media queries
4. **Better UX** - Improved spacing, typography, and interactions
5. **Accessibility** - Proper semantic HTML and ARIA labels
6. **i18n Ready** - All strings wrapped in translation functions
7. **Consistent** - Uses design tokens (CSS custom properties)
8. **Fast** - Optimized animations and transitions

## 🎨 New CSS Features

- **Design System**: CSS custom properties for colors, spacing, shadows
- **Card Layouts**: Clean card-based sections
- **Gradient Buttons**: Modern gradient primary buttons
- **Smooth Animations**: Fade-in, slide-up, hover effects
- **Loading States**: Professional spinner and loading indicators
- **Validation States**: Color-coded input validation
- **Mobile Responsive**: Breakpoints at 1024px, 768px, 480px
- **Print Friendly**: Optimized print styles

## 📱 Responsive Breakpoints

- **Desktop**: 1400px max-width container
- **Tablet** (≤ 1024px): Single column cards, smaller tabs
- **Mobile** (≤ 768px): Stacked layout, full-width buttons
- **Small Mobile** (≤ 480px): 2-column month tabs, compact spacing

## 🧪 Testing Checklist

After making changes:

- [ ] Admin page loads without errors
- [ ] Month tabs switch correctly
- [ ] Year selector works
- [ ] Import modal opens and functions
- [ ] All buttons are styled correctly
- [ ] Responsive design works on mobile
- [ ] PDF upload/remove works
- [ ] Hijri recalculation works
- [ ] Save functions work
- [ ] No console errors

## 🐛 Troubleshooting

**Issue**: Styles not loading
- **Fix**: Clear browser cache and WordPress cache

**Issue**: Buttons not clickable
- **Fix**: Check that JavaScript is loading (check browser console)

**Issue**: Layout broken
- **Fix**: Ensure all HTML class names match the new CSS

**Issue**: PHP errors
- **Fix**: Check that all ACF calls are replaced with get_option()

## 📝 Notes

- All new options use the `mt_` prefix (e.g., `mt_default_year`)
- The CSS uses BEM-like naming (e.g., `mt-control-card`, `mosque-month-tab`)
- Design tokens are defined in `:root` for easy customization
- All strings are translation-ready with `__()` and `esc_html__()`

---

**Status**: CSS Complete ✅ | PHP Changes Required ⏳
