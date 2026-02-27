=== Mosque Timetable - Prayer Times for Mosques ===
Contributors: ibraheemmustafa
Tags: mosque, prayer times, islamic, salah, masjid, muslim, pwa, timetable, ramadan, qibla
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 3.1.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The most complete prayer times plugin for mosques. Auto-calculation, PWA, push notifications, offline mode, REST API, ICS calendar, and 150+ features.

== Description ==

**Mosque Timetable** is a comprehensive WordPress plugin built specifically for mosques and Islamic centres. It handles everything from prayer time management to Progressive Web App functionality, giving your congregation a modern, reliable way to access prayer times — even without an internet connection.

= Why Mosque Timetable? =

Most prayer time plugins require you to manually upload a CSV each month. This plugin auto-calculates prayer times from your mosque's coordinates using 15 different calculation methods, populates your full timetable in seconds, and serves it to your congregation through a beautiful, mobile-first interface.

= Key Features =

**Prayer Time Management**
* Automatic prayer time calculation using Aladhan API (15 methods: MWL, ISNA, Egyptian, Karachi, and more)
* Location-based calculation using your mosque's latitude and longitude
* Automatic Jamāʿah time calculation with configurable offsets per prayer
* Manual entry with CSV, Excel (.xlsx), and copy/paste import options
* Full multi-year archive management

**Frontend Display**
* `[mosque_timetable]` — Full monthly prayer timetable
* `[todays_prayers]` — Today's prayers with next prayer highlighting
* `[prayer_countdown]` — Live countdown to next prayer (updates every second)
* Responsive mobile-first design with card layout on small screens
* Sticky prayer bar with swipeable prayer chips on mobile
* Friday/Jummah dual-service display
* Terminology override system (Mosque → Masjid, Zuhr → Dhuhr, etc.)

**Progressive Web App (PWA)**
* Full offline functionality — prayer times work without internet
* Install to home screen on iOS and Android
* Push notifications for prayer time reminders (opt-in)
* Dedicated `/today` page optimised for quick access
* Background sync when connection restores

**Export & Calendar Integration**
* ICS calendar export — subscribe in Google Calendar, Apple Calendar, or Outlook
* Customisable export: date range, Jamāʿah toggle, notification offsets, Jummah service selection
* Per-month PDF timetable upload and download
* CSV export of full yearly data
* REST API with 9 endpoints for external integrations

**SEO & Discoverability**
* Auto-generated XML sitemap for prayer time pages
* Schema.org structured data (Organisation, Dataset, WebSite)
* Open Graph tags for social sharing
* LLMs.txt for AI tool integration
* robots.txt integration

**Security**
* Nonce validation on all AJAX operations
* Capability checking on admin functions
* Input sanitisation throughout (400+ sanitisation calls)
* SQL injection prevention with prepared statements
* XSS protection with proper output escaping

= Who Is This For? =

* Mosques and Masjids of any size
* Islamic centres and prayer halls
* Muslim community organisations
* Anyone running a WordPress site that needs to display accurate prayer times

= Shortcodes =

`[mosque_timetable]` — Monthly prayer timetable table with full Islamic date display

`[todays_prayers]` — Today's prayer times with automatic next prayer highlighting and countdown

`[prayer_countdown]` — Live countdown timer to the next prayer, handles tomorrow's Fajr after Isha

= REST API =

Access prayer data programmatically for apps and integrations:

* `/wp-json/mosque/v1/prayer-times/{year}/{month}` — Monthly data
* `/wp-json/mosque/v1/today-prayers` — Current day
* `/wp-json/mosque/v1/next-prayer` — Next prayer calculation
* `/wp-json/mosque/v1/export-ics` — Calendar export
* `/wp-json/mosque/v1/widget/prayer-times` — PWA widget data
* `/wp-json/mosque/v1/widget/countdown` — Countdown widget

= Built for the Ummah =

This plugin is developed with Islamic values in mind. It is free, open source, and built to serve mosques and Muslim communities worldwide. We hope it is a sadaqah jariyah for everyone who uses and contributes to it.

== Installation ==

1. Download the plugin zip file.
2. Go to **Plugins → Add New → Upload Plugin** in your WordPress admin.
3. Upload the zip file and click **Install Now**.
4. Click **Activate Plugin**.
5. Go to **Mosque Settings** in the admin menu and enter your mosque name and coordinates.
6. Go to **Prayer Timetable**, select a month tab, and click **Generate Dates** to auto-populate prayer times.
7. Add `[mosque_timetable]` to any page to display the timetable.

**Tip:** For best results, enable **Automatic Prayer Time Calculation** in Mosque Settings and enter your mosque's latitude and longitude. The plugin will fetch accurate times for your location.

== Frequently Asked Questions ==

= Does this plugin require ACF Pro? =

No. ACF Pro is supported and enhances the admin interface, but the plugin works fully without it using WordPress's built-in options table as a fallback.

= How does automatic prayer time calculation work? =

Enter your mosque's latitude, longitude, and preferred calculation method in Mosque Settings. When you click "Generate Dates" in the Prayer Timetable admin, the plugin fetches prayer times from the Aladhan API and saves them to your database. Visitors see the stored times — no API calls on the frontend.

= Which prayer time calculation methods are supported? =

15 methods including: Muslim World League (MWL), ISNA, Egyptian General Authority, Umm al-Qura (Makkah), University of Islamic Sciences (Karachi), Tehran, Jafari, and more.

= Can I import my existing timetable? =

Yes. You can import via CSV upload, Excel (.xlsx) upload, or copy and paste directly from a spreadsheet into the admin interface.

= How does the ICS calendar export work? =

Visitors can download a .ics file or subscribe to a calendar link that imports all prayer times into Google Calendar, Apple Calendar, or Outlook with optional notifications. You can customise the export date range, notification timings (5m, 10m, 20m, 30m before), and Jummah service selection.

= Does the PWA work on iPhones? =

Yes. The plugin generates a standard Web App Manifest and Service Worker. On iOS Safari, users can add the site to their home screen and access prayer times offline. Push notifications on iOS require iOS 16.4+ with the site added to the home screen.

= Can I customise the terminology? =

Yes. The Terminology Override system lets you replace any label site-wide — for example, "Mosque" to "Masjid", "Zuhr" to "Dhuhr", "Prayer Time" to "Salah Time". Toggle each override individually in Mosque Settings.

= What PHP version is required? =

PHP 8.1 or higher. PHP 8.2 is recommended.

= Does it work with Multisite? =

Basic multisite compatibility is present. Each site in the network has its own prayer times and settings.

= Is there a digital screen / TV display mode? =

Yes. A full-screen digital display mode is included for showing prayer times on monitors in the mosque. Large fonts, automatic time advancement, and a live clock are included.

== Screenshots ==

1. Monthly prayer timetable with full Islamic date display — desktop view
2. Today's prayers widget with live countdown and next prayer highlighting
3. Mobile card layout with swipeable prayer chips and sticky prayer bar
4. Export modal with ICS calendar customisation options
5. Admin timetable interface with month tabs and auto-generate button

== Changelog ==

= 3.1.0 =
* Added automatic prayer time calculation via Aladhan API (15 methods)
* Added PWA with full offline functionality and home screen install
* Added push notification support for prayer reminders (opt-in)
* Added digital screen display mode for mosque monitors
* Added Ramadan mode with Suhoor/Iftar time support
* Added REST API with 9 endpoints for external integrations
* Added multi-year archive system (/prayer-times/{year}/)
* Added ICS calendar export with full customisation options
* Added LLMs.txt for AI tool discoverability
* Added Schema.org structured data (Organisation, Dataset, WebSite)
* Added XML sitemap for prayer time pages
* Improved mobile responsive design with card layout transformation
* Added sticky prayer bar with swipeable chips on mobile
* Added terminology override system for multilingual customisation
* Added Friday/Jummah dual-service display
* Added per-month PDF upload and download

= 3.0.0 =
* Complete rewrite with modern object-oriented architecture
* Added ACF Pro support with options table fallback
* Added AJAX-powered admin interface with month-by-month tabs
* Added CSV and Excel import functionality
* Added copy/paste import from spreadsheets
* Added ICS calendar generation
* Added shortcodes: mosque_timetable, todays_prayers, prayer_countdown

== Upgrade Notice ==

= 3.1.0 =
Major update adding PWA, push notifications, automatic prayer calculation, and REST API. Please back up your site before upgrading. No data migration required.
