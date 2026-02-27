# Mosque Timetable - WordPress Prayer Times Plugin

![Version](https://img.shields.io/badge/version-3.1.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

**A comprehensive WordPress plugin for managing and displaying Islamic prayer times with PWA capabilities, automatic calculation, and offline functionality.**

---

## 🌟 Key Features

### 📅 Prayer Time Management
- **Automatic Prayer Calculation** - Fetch prayer times automatically using Aladhan API
- **15 Calculation Methods** - MWL, ISNA, Egyptian, Karachi, and more
- **Location-Based Times** - Uses mosque coordinates for accurate calculations
- **Jamāʿah Time Automation** - Configurable offsets for congregation times
- **Manual Entry Option** - Full control with CSV/Excel import or manual input
- **Multi-Year Support** - Manage historical and future prayer times

### 🕌 Display Options
- **Monthly Timetables** - Beautiful responsive tables via `[mosque_timetable]` shortcode
- **Today's Prayers** - Current day display with `[todays_prayers]` widget
- **Live Countdown** - Real-time countdown to next prayer with `[prayer_countdown]`
- **Mobile Optimized** - Card-based layout with swipeable prayer chips
- **Sticky Prayer Bar** - Always-visible prayer times on mobile
- **Next Prayer Highlighting** - Automatic time tracking and visual cues

### 📱 Progressive Web App (PWA)
- **Offline Functionality** - Works without internet connection
- **Install on Home Screen** - Add to iOS/Android home screens
- **Push Notifications** - Prayer time reminders (opt-in)
- **Dedicated `/today` Page** - Fast access to current prayers
- **Background Sync** - Automatic updates when online
- **App Shortcuts** - Quick access to key features

### 📤 Export & Integration
- **ICS Calendar Export** - Import into Google Calendar, Apple Calendar, Outlook
- **Customizable Exports** - Choose date range, notifications, Jummah services
- **Subscribe to Calendar** - One-click subscription to auto-updating calendar
- **REST API** - 9 endpoints for external integrations
- **PDF Timetables** - Downloadable monthly PDFs
- **CSV/Excel Import** - Bulk data management

### 🎨 Customization
- **Terminology Override** - Customize labels (Mosque→Masjid, Zuhr→Dhuhr)
- **Multilingual Ready** - Translation-ready with text domain
- **Responsive Design** - Mobile-first with breakpoints for all devices
- **Accessibility** - WCAG 2.1 AA compliance with ARIA labels
- **Custom Branding** - Upload mosque logo and details

### 🔍 SEO & Discoverability
- **XML Sitemap** - Auto-generated prayer times sitemap
- **Structured Data** - Schema.org markup for search engines
- **Open Graph Tags** - Social media sharing optimization
- **LLMs.txt** - AI tool integration for ChatGPT, Claude, etc.
- **robots.txt Integration** - Proper crawling directives

---

## 🚀 Quick Start

### Installation

1. **Upload Plugin**
   - Upload `mosque-timetable` folder to `/wp-content/plugins/`
   - Or install via WordPress admin (Plugins → Add New → Upload)

2. **Activate Plugin**
   - Go to Plugins page in WordPress admin
   - Click "Activate" under Mosque Timetable

3. **Configure Settings** (Optional: ACF Pro recommended but not required)
   - Go to **Mosque Settings** in admin menu
   - Enter mosque name, address, contact details
   - Enable automatic prayer times (optional)

4. **Set Up Automatic Prayer Times** (Optional)
   - Navigate to "Automatic Prayer Time Calculation" tab
   - Toggle "Enable Automatic Prayer Times"
   - Enter mosque latitude and longitude
   - Select calculation method (MWL, ISNA, etc.)
   - Configure Jamāʿah offsets (minutes after start time)

5. **Generate Prayer Times**
   - Go to **Prayer Timetable** in admin menu
   - Click month tab (e.g., "January 2025")
   - Click "Generate Dates" button
   - If auto-times enabled: Prayer times auto-populate
   - If disabled: Enter times manually or import CSV/Excel

6. **Display on Frontend**
   - Add shortcode to any page/post:
     - `[mosque_timetable]` - Monthly table
     - `[todays_prayers]` - Today's prayers
     - `[prayer_countdown]` - Live countdown

---

## 📋 Shortcodes

### Monthly Timetable
```
[mosque_timetable]
```
Displays full monthly prayer timetable with:
- All prayer times (Fajr, Sunrise, Zuhr, Asr, Maghrib, Isha)
- Jamāʿah congregation times
- Friday Jummah services
- Islamic dates
- Responsive mobile cards

### Today's Prayers
```
[todays_prayers]
```
Shows current day prayer times with:
- Next prayer highlighting
- Countdown timer
- Mobile-optimized layout

### Prayer Countdown
```
[prayer_countdown]
```
Live countdown to next prayer:
- Real-time updates (every second)
- Auto-advances to next prayer
- Handles tomorrow's Fajr after Isha

---

## 🔌 REST API Endpoints

Access prayer data programmatically:

| Endpoint | Description |
|----------|-------------|
| `/wp-json/mosque/v1/prayer-times/{year}/{month}` | Monthly prayer data |
| `/wp-json/mosque/v1/today-prayers` | Current day prayers |
| `/wp-json/mosque/v1/next-prayer` | Next prayer calculation |
| `/wp-json/mosque/v1/export-ics` | Calendar export with options |
| `/wp-json/mosque/v1/widget/prayer-times` | PWA widget data |
| `/wp-json/mosque/v1/widget/countdown` | Countdown widget data |

**Example Request**:
```bash
curl https://yoursite.com/wp-json/mosque/v1/prayer-times/2025/1
```

---

## 🎯 Automatic Prayer Time Calculation

### How It Works

1. **Admin Configuration**:
   - Enable in Mosque Settings → Automatic Prayer Time Calculation
   - Enter mosque coordinates (latitude/longitude)
   - Select calculation method (15 options available)
   - Set Jamāʿah offsets per prayer (e.g., Fajr +10 min)

2. **Generate Dates**:
   - Go to Prayer Timetable admin
   - Select month tab
   - Click "Generate Dates"
   - System fetches times from Aladhan API
   - Calculates Jamāʿah times automatically
   - Auto-populates Friday Jummah from Zuhr Jamāʿah
   - Saves all times to database

3. **Offline Access**:
   - Times stored in database (not fetched on-demand)
   - Frontend users access stored data
   - PWA works completely offline
   - No API calls required for visitors

### Supported Calculation Methods

| Method | Description |
|--------|-------------|
| MWL | Muslim World League |
| ISNA | Islamic Society of North America |
| Egyptian | Egyptian General Authority |
| Makkah | Umm al-Qura, Makkah |
| Karachi | University of Islamic Sciences, Karachi |
| Tehran | Institute of Geophysics, University of Tehran |
| Jafari | Shia Ithna-Ashari (Ja'fari) |
| And 8 more... | See settings for full list |

---

## 📦 Import/Export

### Import Options
- **CSV Import** - Upload comma-separated values
- **Excel Import** - Upload .xlsx files (SimpleXLSX library)
- **Copy/Paste** - Paste data from spreadsheets
- **Manual Entry** - Direct input in admin tables

### Export Options
- **ICS Calendar** - Download/subscribe to calendar
- **PDF Timetables** - Per-month PDF files
- **CSV Export** - Full yearly data export
- **Excel Export** - Formatted .xlsx download

### Calendar Export Customization
- Date range selection (full year or specific month)
- Include/exclude Jamāʿah times
- Multiple notification options (5m, 10m, 20m, 30m before)
- Jummah service selection (both, 1st, or 2nd only)
- Sunrise warning alarms

---

## 🌐 Virtual Pages

Plugin creates these custom pages:

| URL | Description |
|-----|-------------|
| `/today` | Dedicated today's prayers page with countdown |
| `/prayer-times/` | Archive of all available years |
| `/prayer-times/{year}/` | Year-specific monthly breakdown |
| `/prayer-times/{year}/{month}/` | Individual month timetable |
| `/prayer-times-sitemap.xml` | XML sitemap for SEO |
| `/llms.txt` | AI tool integration file |
| `/prayer-times/calendar.ics` | ICS calendar file |

---

## 🔐 Security Features

- ✅ **Nonce Validation** - All AJAX operations secured
- ✅ **Capability Checking** - Admin functions properly gated
- ✅ **Input Sanitization** - 315+ sanitization calls
- ✅ **Output Escaping** - 315+ escape calls
- ✅ **SQL Injection Prevention** - Prepared statements throughout
- ✅ **XSS Protection** - Proper output escaping
- ✅ **CSRF Protection** - WordPress nonce system
- ✅ **File Upload Validation** - Type, size, content checks

---

## ⚙️ Requirements

### Minimum Requirements
- **WordPress**: 6.5 or higher
- **PHP**: 8.1 or higher
- **MySQL**: 5.7 or higher
- **Server**: Apache/Nginx with mod_rewrite

### Recommended
- **ACF Pro**: 6.0+ (optional, has fallback to options table)
- **HTTPS**: Required for PWA and push notifications
- **PHP Memory**: 256MB+ for large imports
- **Max Upload Size**: 10MB+ for Excel/PDF files

### Browser Support
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## 🛠️ Configuration

### Basic Settings (Mosque Settings Page)
- Mosque name, address, phone, email
- Default year for timetables
- PWA enable/disable toggle
- Terminology overrides
- Custom subscribe URL

### Advanced Settings (Prayer Calculation Tab)
- Enable automatic prayer times
- Mosque coordinates (latitude/longitude)
- Calculation method selection
- Jamāʿah time offsets (per prayer)

### Admin Interface (Prayer Timetable Page)
- Month-by-month tabs
- Generate dates button
- Import tools (CSV, Excel, paste)
- PDF upload per month
- Export options

---

## 📱 PWA Features

### Installation
- Automatic install prompt on supported browsers
- "Add to Home Screen" capability
- Custom app icon and splash screen
- Standalone app mode

### Offline Functionality
- Service worker with intelligent caching
- Cached prayer times accessible offline
- Custom offline page with mosque branding
- Background sync when online

### Push Notifications (Opt-in)
- Prayer time reminders
- Customizable notification timing
- Sunrise warning alarms
- Privacy-safe (user must subscribe)

### App Shortcuts
- Today's prayers
- Full timetable
- Calendar export

---

## 🎨 Customization

### Terminology Override System
Customize any label in the plugin:
- Mosque → Masjid
- Zuhr → Dhuhr
- Prayer Time → Salah Time
- And more...

**How to Use**:
1. Go to Mosque Settings
2. Scroll to "Terminology Overrides"
3. Add original term and replacement
4. Toggle to enable/disable
5. Changes apply site-wide

### Styling
- Uses CSS custom properties for theming
- Professional gradient backgrounds
- Smooth animations with reduced motion support
- Mobile-first responsive design
- High contrast mode compatible

---

## 🌍 Internationalization

- **Translation Ready** - Full text domain support
- **RTL Compatible** - Right-to-left language preparation
- **Date Localization** - WordPress timezone integration
- **Number Formatting** - Locale-aware numbers
- **Regional Variations** - Terminology customization

---

## 📊 Multi-Year Archive System

### Features
- Store unlimited years of prayer times
- Historical data access
- Future year planning
- Automatic year advancement
- Intelligent validation

### Archive Pages
- Main archive lists all available years
- Year pages show monthly breakdowns
- Current year highlighted
- Availability indicators (✅ Available / ⏳ No data)
- Responsive grid layouts

---

## 🔧 Developer Resources

### Hooks & Filters
```php
// Filter prayer times before display
apply_filters('mt_prayer_times', $times, $year, $month);

// Customize export data
apply_filters('mt_export_data', $data, $options);

// Modify calculation method
apply_filters('mt_calculation_method', $method, $coordinates);
```

### Actions
```php
// After saving month data
do_action('mt_month_saved', $month, $year, $data);

// After generating dates
do_action('mt_dates_generated', $month, $year, $auto_times);

// After API fetch
do_action('mt_api_fetched', $api_data, $month, $year);
```

---

## 📞 Support & Documentation

### Getting Help
- **Documentation**: See internal guide files
- **Feature List**: Check FEATURE_LIST.md
- **Changelog**: See CHANGELOG.md
- **Technical Plan**: Review technical implementation docs

### Reporting Issues
- Check syntax with `php -l mosque-timetable.php`
- Review browser console for JavaScript errors
- Verify AJAX requests in network tab
- Check WordPress debug log

---

## 📝 License

This plugin is licensed under the GPL-2.0+ license.

**You are free to**:
- Use commercially
- Modify and distribute
- Use privately
- Include in larger works

**Under the conditions**:
- Disclose source
- Include license and copyright notice
- State changes made to the code
- Use same license for derivatives

---

## 🎉 Credits

### Dependencies
- **Aladhan API** - Prayer time calculations
- **SimpleXLSX** - Excel import functionality
- **SimpleXLSXGen** - Excel export functionality
- **Web-Push** - Push notification support
- **JWT Library** - Secure authentication

### Built With
- WordPress best practices
- Modern JavaScript (ES6+)
- Progressive Web App standards
- Schema.org structured data
- WCAG 2.1 accessibility guidelines

---

## 📈 Statistics

- **150+ Features** implemented
- **9 REST API Endpoints** for integration
- **3 Shortcodes** for easy display
- **14+ AJAX Handlers** for admin operations
- **4 Virtual Pages** for archives
- **60+ ACF Fields** for configuration
- **15 Calculation Methods** for prayer times
- **53 Security Checks** implemented
- **9,000+ Lines** of production code

---

## 🚀 What's Next?

### Recommended Setup Flow
1. ✅ Install and activate plugin
2. ✅ Configure mosque details
3. ✅ Enable automatic prayer times
4. ✅ Generate dates for current month
5. ✅ Add shortcode to pages
6. ✅ Test PWA functionality
7. ✅ Enable push notifications (optional)
8. ✅ Share calendar export with community

---

**Mosque Timetable Plugin** - Complete prayer time management for WordPress mosques worldwide 🕌
