# Mosque Timetable Plugin – Technical Plan for Implementation

This document is the single source of truth for ongoing development of the Mosque Timetable plugin. It contains a clear snapshot of what is currently implemented and a structured plan of what to build next. When used as a prompt for Claude Code, follow the instructions exactly and do not introduce new patterns or libraries unless explicitly specified.

## 0. Ground rules for Claude

**Coding style:** Follow WordPress coding standards. Escape output, validate and sanitise input, and always wrap admin and AJAX actions with proper nonce and capability checks. Use `mt_` prefixes for all PHP functions, `mt-` for CSS class names, and `MT_` or `mt` prefixes for JavaScript globals to avoid collisions.

**Storage modes:** Two modes are supported. When ACF Pro is present, field groups are registered programmatically. When ACF is absent, data falls back to WordPress options. Do not break this fallback.

Do not register Gutenberg blocks or custom post types. We expose data via shortcodes and REST APIs.

Push notifications: Implement web push with optimal UX. Do not auto-prompt on page load. Show a pre-permission panel first, then call the native permission prompt only after explicit user action. Use VAPID keys; store subscriptions securely; provide a clear unsubscribe. Voice integrations remain out of scope.

Avoid copying third‑party function names. This includes the "Super Salah" modals – we only aim to match their level of polish, not their naming conventions.

## 1. Snapshot of reality today (Current)

Use the facts below when deciding what to refactor versus what to keep. The plugin distributed with this repository implements these features now:

### Data model

**ACF structure:** There are twelve separate ACF field groups—`daily_prayers_1` through `daily_prayers_12`—one for each month. Each field group contains repeater rows with columns for Gregorian date, Hijri date, Fajr start / Jamāʿah, Sunrise, Zuhr start / Jamāʿah, Asr start / Jamāʿah, Maghrib start / Jamāʿah, Isha start / Jamāʿah, and two Jummah fields (1st and 2nd). A fallback uses a nested option array for the same data when ACF Pro is not installed. The plugin automatically generates the Gregorian date, the day name (e.g. Monday), and the Hijri date when populating each month's data—admins only need to fill the prayer times.

**Per‑month PDF field:** There is an upload print‑ready PDFs per month button.

### Admin interface

**Timetable page:** An admin page with twelve tabs (January–December). Each tab shows a repeater table for the corresponding month. Admins can edit prayer times and save via AJAX.

**Import/export page:** Supports CSV and XLSX imports using the SimpleXLSX library. There is a basic export to CSV that dumps the entire year.

**Settings / Appearance:** Additional pages allow specifying the mosque name, address and some styling options.

**Nonces and permissions:** More than twenty AJAX actions exist, each protected by nonces and capability checks (verified by grep and code inspection).

**Subscribe button:** On the front‑end timetable, there is a "Subscribe to Our Prayer Calendar" link that points to `/prayer‑times/calendar.ics`. This feed is generated server‑side and can be subscribed to by calendar clients. There is no option to override this URL or provide additional subscribe feeds.

### Import/export

**CSV/XLSX/Paste import:** Admins can import a month's data from CSV or XLSX or pasting a format equivalent via a modal. Basic validation is present.

**ICS export:** An export button triggers an AJAX call to `export_ics_calendar`. The server generates an `.ics` file for the selected month or year. Options are limited to including start times, Jamāʿah times, or both (via `prayer_types`) and a single reminder offset in minutes (via `reminder`). Zuhr is automatically replaced by Jummah on Fridays in the `.ics` export.

**CSV export:** There is an AJAX endpoint to export the entire year as a CSV.

**No advanced export modal:** Visitors cannot choose custom date ranges, multiple reminders, or Jamāʿah options. The export button uses a hard‑coded set of parameters (both start and Jamāʿah, 15‑minute reminders, both Jummah services).

### Front‑end display

**Shortcodes:** Three shortcodes are registered: `[mosque_timetable]` (month view), `[todays_prayers]` (today's prayers with a countdown), and `[prayer_countdown]` (only the countdown).

**Responsive table:** The timetable renders as a table on desktop. It highlights the current day and uses JavaScript to detect and mark the next prayer. On mobile, the table is narrow but still a table; there is no stacked card view or sticky next prayer bar.

**Export & Subscribe controls:** Each timetable page includes a month selector, an "Export Calendar" button that downloads an `.ics` file, and a "Subscribe" button that links to `/prayer‑times/calendar.ics`. There is no visitor‑side modal for picking options.

### PWA and SEO

**PWA:** A `manifest.json` and `sw.js` are included. The manifest defines shortcuts (Today's Prayers, Countdown, Timetable) and experimental "widgets" entries for certain platforms. The service worker caches API responses for offline use and includes a push notification handler; however, there is no code in the frontend that subscribes users to push or provides VAPID keys.

**Virtual pages:** The plugin registers rewrite rules for `/prayer‑times/<year>/<month>` and `/prayer‑times/calendar.ics`. The latter serves the `.ics` feed; the former is stubbed for future virtual pages.

**Structured data:** The `add_structured_data()` method outputs a Place schema for the mosque, optional PrayerEvent schemas for today's prayers, and a FAQPage schema. It does not generate Event nodes for every prayer.

**robots/sitemap:** There is no custom prayer‑times sitemap or llms.txt. robots.txt is not modified by the plugin.

## 2. Target feature set (Planned)

Implement these features in future revisions. Each subsection is independent; complete them one by one and test thoroughly. If something is unclear, choose the simplest interpretation that satisfies the acceptance criteria.

### A. Per‑month PDF upload inside month tabs

**Goal:** Allow admins to upload a print‑ready PDF for each month on the Timetable page (not in Settings). When a month tab is selected, a small uploader appears for that month only. On the front‑end timetable, show a "Print / Download Timetable" button beneath the heading. If a PDF exists, the button links to it. Otherwise, the button calls `window.print()`.

**Storage:**
- With ACF: add a `pdf_url` subfield to each month's repeater row.
- Without ACF: save a separate option `mt_pdf_{YYYY}_{MM}`. Implement `mt_get_pdf_for_month()` to fetch the correct URL.

**Acceptance:** Uploading a PDF on the January tab displays the print/download button on the January front‑end view. Switching months changes the link. Works with or without ACF.

### B. Visitor export modal with options and export endpoint

**Goal:** Replace the simple export button with a modal that lets visitors customise their calendar export. Use vanilla JS and CSS; theme colours should inherit from CSS variables (e.g. `--wp--preset--color--primary`) and be overrideable. The modal should include:

**Date Range**
- Full Year (default).
- Selected Month (default to the month currently displayed).
- Custom Range (optional date pickers; implement only if trivial).

**Include Jamāʿah times?**
- Start times are always included and cannot be deselected.
- A checkbox to include Jamāʿah times.

**Notifications (VALARM)**
- Checkboxes for Start, 5m, 10m, 20m and 30m reminders. Create a VALARM block in the `.ics` file for each selected offset.

**Jummah selection**
- Both (default), 1st, 2nd. Determines which Jummah events appear and ensures Zuhr is not exported on Fridays.

**Sunrise (end of Fajr) warning**
- Optional alarm 15m / 30m / 45m / 1h before sunrise. You may model this as a separate event called "End of Fajr" or attach a VALARM to sunrise.

**Outputs**
- Download .ics – triggers the export endpoint to download the file.
- Add to Google Calendar – opens Google Calendar's import page with instructions. (Google does not support multi‑event creation via URL parameters.)

**Backend:** Add a GET `/wp-json/mosque/v1/export-ics` endpoint that accepts query parameters for year, month, range_start, range_end, include_jamah, alarms[], jummah, sunrise_alarm, and subscribe. The endpoint should generate the `.ics` file with the selected options. For subscribe mode (`subscribe=1`) omit the Content-Disposition header so calendar clients can subscribe.

**Acceptance:**
- The modal opens when visitors click "Add to Calendar", validates inputs and produces an `.ics` file that imports cleanly.
- Friday exports never contain a Zuhr event; they contain selected Jummah events.
- Alarms appear in the resulting calendar exactly as selected.
- The modal falls back gracefully on unsupported browsers.

### C. Subscribe button with optional override

**Goal:** Provide a "Subscribe to Calendar" button separate from the export modal. Link it to a stable feed (the existing `/prayer‑times/calendar.ics`). Introduce a future‑proof setting that allows overriding this feed URL. When the override is set, the subscribe button uses the custom URL.

**Acceptance:** Calendar apps (Google, Apple, Outlook) can subscribe to the feed. If a custom URL is later saved, the button uses that instead.

### D. Friday/Jummah frontend behaviour

**Goal:** On Fridays, the timetable should not display Zuhr start and Jamāʿah times. Instead, the "Zuhr/Jummah" column shows both Jummah times separated by "/". (E.g. "1:15 / 2:30"). The table header should still read "Zuhr / Jummah".

**ICS rules:** Ensure that `generate_ics_content()` excludes Zuhr on Fridays and includes Jummah 1 and/or 2 based on the visitor's modal selection.

**Acceptance:** Fridays render as described. The exported `.ics` matches the display.

### E. Mobile Pattern A transform and sticky Next Prayer bar

**Goal:** Improve readability on phones and add a persistent "Next Prayer" bar.

**Pattern A:** On screens ≤ 480 px, convert each table row into a card. Stack Jamāʿah times under start times with smaller text. Highlight the current day and the next prayer with clear styling.

**Sticky bar:** Add a bar at the top of the timetable page that shows the Gregorian and Hijri date and a row of chips for each prayer. On mobile, chips are swipeable and auto‑centre on the next prayer. On desktop, the bar shows all prayers inline.

**Acceptance:**
- No horizontal scrolling or overflow on mobile.
- The next prayer chip is visible and centred on load.
- ARIA roles (tablist, aria-selected) are used appropriately for keyboard accessibility.

### F. Terminology overrides

**Goal:** Allow admins to override labels used in the UI (e.g. "Mosque" → "Masjid", "Salah" → "Namaz", "Dhuhr" ↔ "Zuhr", "Maghrib" ↔ "Maghreb", "Isha" ↔ "ʿIshā'"). Provide a simple key/value map in the Settings page. Apply replacements only to labels, not to internal keys or field names.

**Acceptance:** Changing "Salah" to "Namaz" updates all front‑end and admin labels. The replacement is case‑sensitive and does not affect the underlying data.

### G. SEO discovery and structured data

**Goal:** Improve discoverability via search engines and AI tools.

**Prayer times sitemap:** Register a rewrite and template to serve `/prayer‑times‑sitemap.xml` containing a `<url>` entry for each month (e.g. `/prayer‑times/2025/09`).

**robots.txt filter:** Append `Sitemap: https://example.com/prayer‑times‑sitemap.xml` and `Allow: /llms.txt` to robots.txt (in addition to the site's main sitemap if present).

**llms.txt:** Serve a plain‑text file at `/llms.txt` with metadata: source of truth, REST endpoint base, license, and contact email.

**Structured data:** Embed JSON‑LD on timetable pages including Organization (mosque), Place (address/geo), WebSite (with a SearchAction), and a Dataset for the timetable and its `.ics` distribution. Do not emit an Event schema for every daily prayer; restrict events to Jummah if requested.

**Acceptance:** The prayer sitemap is valid XML. robots.txt includes the new entries without overwriting existing ones. llms.txt is reachable and clearly formatted. Page source passes Google's Structured Data testing tool.

### H. PWA polish and /today page

**Goal:** Polish the Progressive Web App.

**Shortcuts:** Ensure the manifest includes shortcuts for Today's Prayers, Next Prayer, and Timetable with proper icons.

**/today page:** Create a minimal `/today` template or endpoint that displays the next prayer bar and today's prayer list. Make sure it loads offline via the service worker.

**Remove push scaffolding:** Remove any notifications UI in the front‑end. Do not display permission prompts. Leave the service worker's push handler in place but unused.

**Acceptance:** The site can be installed as a PWA. Shortcuts launch the appropriate views. No push permission prompt appears.

### I. Multi‑year archive pages and default year handling

**Goal:** Support multiple timetables—one for each year—and expose them on public archive pages. Visitors should be able to view past or future years via a URL such as `/prayer-times/2023`. The main timetable page should automatically show the current year (based on the server's date) unless overridden on the settings page.

**Admin UI:** Extend the "Year Archive Browser" in the admin page to actually create and switch between year‑specific timetables. Each year should maintain its own set of monthly data (rather than sharing the same `daily_prayers_*` options). Provide a way to create a new year (copying the structure from the current year) and delete existing years if needed.

**Storage:** Either namespace the `daily_prayers_*` options by year (e.g. `daily_prayers_{year}_1`) or adopt a nested options array keyed by year to avoid collisions.

**Rewrite rules:** Activate the existing rewrite rule for `/prayer-times/<year>/<month>` so the front‑end can render archived timetables. Ensure that `/prayer-times/<year>` defaults to the first month or a summary page. Continue to generate `/prayer-times/calendar.ics` for the current year.

**Frontend behaviour:** Add a year selector on the public timetable page similar to the admin browser. When a visitor selects a different year, update the table and the export/subscription links to use that year. The main timetable page (`/prayer-times` without a year) should always display the current year (or the year set on the configure settings page).

**Acceptance:**
- Navigating to `/prayer-times/2023` displays the timetable for 2023. Navigating to `/prayer-times/2024/05` displays the timetable for May 2024. The URLs are generated via rewrite rules and do not expose query variables.
- The settings page allows specifying a "current" or default year which overrides the server's current year. The front‑end uses this year when visitors load `/prayer-times` without specifying a year.
- Each year's data is stored independently and editing one year does not affect others.

### J. Web push notifications (opt-in, privacy-safe)

Goal: Allow visitors to opt-in to reminders for upcoming prayers (and optional sunrise warnings) using Web Push, following best-practice permission UX.

**Frontend UX:**

Add a “Prayer Reminders” button near the sticky prayer bar (and on /today).

Clicking opens a pre-permission panel explaining benefits (e.g., “Get alerted 10–30 min before prayer”).

On confirm, request permission via Notification.requestPermission() and subscribe with pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: <VAPID_PUBLIC_KEY> }).

Provide “Turn off reminders” to unsubscribe.

**Admin settings (Options page):**

VAPID Public/Private keys (validated).

Default reminder offsets (e.g., 5/10/15/20/30 min) and sunrise warning toggle.

Privacy note text (editable).

**Backend (REST + storage):**

POST /wp-json/mosque/v1/subscribe stores subscription JSON (nonce + capability checks).

POST /wp-json/mosque/v1/unsubscribe removes subscription.

Use Minishlink/WebPush (Composer) to send.

Add a WP-Cron event (1-min cadence): for each subscription, compute upcoming prayers using existing logic; enqueue notifications for selected offsets; send with VAPID.

**Rules & constraints:**

Never prompt on page load; only after user click.

Respect Friday rules (no Zuhr; Jummah 1/2 as configured).

iOS/macOS Safari limitations are tolerated; feature degrades gracefully if push unsupported.

Clear error handling: show useful messages for denied/blocked permission.

Security/privacy: sanitise inputs, nonce all endpoints, allow full unsubscribe, and document data retention.

**Acceptance:**

Users can opt-in, receive timely pushes at chosen offsets, and unsubscribe completely.

No native prompt appears without user action.

Cron reliably schedules and sends notifications; logs failures.

Works without breaking ACF/Options fallback.

**File & hook map additions:**

assets/mosque-timetable.js (UI + subscription flow), assets/mosque-timetable.css (minor styles).

mosque-timetable.php (REST endpoints, settings, cron).

Composer: ensure minishlink/web-push present.

Hooks: rest_api_init, admin_init (settings), init (cron schedule), shutdown or custom action to queue sends.

**CHANGELOG:** Add entries for settings, endpoints, UI, cron, and SW integration.

### K. Translation and internationalisation
**Goal:** Make the plugin fully translation‑ready so it can be localised into any language. All user‑facing strings (front‑end and admin) must be passed through WordPress translation functions and a text domain must be loaded. A languages directory should contain a .pot template file for translators.
Tasks:

**Load text domain** – Call load_plugin_textdomain( 'mosque-timetable', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); inside the plugin initialization so WordPress can locate translation files.

**Wrap all strings** – Ensure every UI label, message, heading, button text, tooltip, error and success message is wrapped in __() or _e() with the text domain mosque-timetable. This includes admin screens, front‑end shortcodes, modal labels, prayer names, and the export modal.

**Generate a .pot file** – Add a languages folder containing mosque-timetable.pot (a gettext template). Use a tool like makepot.php or Poedit to extract translatable strings. This file will be used by translators to create .po/.mo files.

**Ensure date localisation** – Use WordPress functions like date_i18n() for month/day names so they respect the site’s locale. Where Hijri dates are displayed, continue using the existing Hijri calculation but allow the labels (“Rabiʿ al-Awwal”, etc.) to be translated via the text domain.

**Test RTL and non‑Latin scripts** – Verify that the timetable renders correctly in right‑to‑left languages and that exported files (PDF/ICS) contain translated text where appropriate.

## 3. File and hook map

Place new code in these files or adjust existing ones. Append new functions rather than overwriting large sections to preserve existing behaviour.

```
mosque-timetable.php
assets/mosque-timetable.js
assets/mosque-timetable.css
assets/mosque-timetable-admin.js
assets/mosque-timetable-admin.css
assets/mt-modal.js       (new)
assets/mt-modal.css      (new)
templates/today.php      (new)
```

Use the following WordPress hooks:

- `admin_menu`, `admin_enqueue_scripts`, `wp_enqueue_scripts`
- `rest_api_init` for the new export endpoint
- `robots_txt` filter for custom lines
- `init` for rewrite rules and serving the sitemap and llms.txt
- `template_redirect` for serving `/today` and other virtual pages

## 4. Tasks with acceptance criteria

Claude, implement tasks A through K in order. After each task, test using the built‑in WordPress admin and a calendar client to ensure correct behaviour. Use the acceptance criteria listed above to decide whether the task is complete. Keep a CHANGELOG.md updated summarising each change.