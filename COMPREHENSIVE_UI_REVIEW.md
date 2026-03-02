# Mosque Timetable Plugin - Comprehensive UI/UX Review

**Date:** 1 March 2026
**Reviewer:** Blub (UI/UX Pro Max methodology)
**Version reviewed:** 3.1.0 (live on mosquewebdesign.com)

---

## Current Grade: C+

Not B-. After a full visual, interactive, and architectural review, this is honest. The code quality work we did (PHPCS, file split) pushed the *code* to B-, but the *product experience* is C+. Here's why.

---

## 1. VISUAL DESIGN REVIEW

### Frontend - Prayer Times Page (Desktop)

**Problems identified:**

| Issue | Severity | Detail |
|-------|----------|--------|
| Generic gradient header | HIGH | Purple-to-blue gradient (#667eea to #764ba2) screams "AI-generated template". No Islamic identity whatsoever. Could be a gym app. |
| No Islamic design language | CRITICAL | Zero geometric patterns, no calligraphy references, no crescent motifs. A mosque plugin should *feel* like a mosque. |
| Double month selector | HIGH | Two separate month selectors visible (one at top, one below the header). Confusing, redundant. |
| "February 2026" heading repeated | MEDIUM | Month name appears in gradient banner AND as text AND in dropdown. Three times. |
| PWA install banner covers table | CRITICAL | Purple banner overlays the actual prayer data. Users can't see the timetable until they dismiss it. Defeats the purpose. |
| Empty cells with no data state | HIGH | When no data: blank cells with no message. Should say "No times set" or show Aladhan auto-import option. |
| Button soup | MEDIUM | "Download Timetable", "Subscribe to Calendar", "Add to Calendar", "Export Calendar", "Print Timetable", "Subscribe to Our Prayer Calendar" - SIX action buttons. Overwhelming. |
| Emoji as icons | MEDIUM | 🏗 in the mosque logo area, 🕌 emoji used in places. Should be proper SVG icons. |
| Colour clashes | MEDIUM | Green "Export Calendar", grey "Print Timetable", pink "Subscribe" buttons next to each other. No visual hierarchy. |

### Frontend - Mobile Card View

**What works:**
- Card layout is actually decent on mobile
- Prayer times are legible
- Hijri date displayed

**Problems:**
- PWA banner covers 30% of the screen on mobile - UNACCEPTABLE
- Cards don't show Fajr separately (it's labelled "SUNRISE" at 06:15 but that's clearly the Fajr time)
- No "next prayer" highlight on mobile cards
- Mosque name/address takes up precious screen real estate
- "Jama'ah" times are small and grey - should be equally prominent (that's what people actually come for)

### Frontend - Today's Prayers Widget (Homepage)

**Problems:**
- Shows "Next Prayer" floating bar with gradient background
- Error message "Unable to calculate next prayer time" displayed prominently
- Floats over page header content, covering the mosque logo
- On the homepage, it's just a floating bar - not a proper widget section

### Admin Panel - Timetable Editor

**What works:**
- Month tabs are clear
- Year selector functions
- Import tools (CSV, XLSX) are accessible
- "Ask Kodee" AI helper button (nice touch)

**Problems:**
- Design Canvas header uses the same generic purple gradient
- 31 rows of tiny input fields - data entry is painful
- No inline validation (can enter "99:99" as a time)
- No visual preview of what the frontend will look like
- Hijri dates column shows blank for most rows
- Clock icon buttons (the ⏱ circles) purpose unclear without hovering
- No bulk operations (e.g., "copy Fajr Jamaat from yesterday")
- Scrolls forever on smaller screens

### Admin Panel - Appearance Settings

**What works:**
- Colour pickers for customisation
- Widget size controls
- PWA toggle

**Problems:**
- Bare minimum customisation (7 colour pickers, that's it)
- No font selection
- No layout/template selection
- No live preview
- No theme presets (e.g., "Classic Islamic", "Modern Minimal", "Dark Mode")
- "Widget Width: 200px" default is absurdly small

---

## 2. INTERACTION DESIGN REVIEW

| Aspect | Grade | Notes |
|--------|-------|-------|
| First-run experience | F | No setup wizard. Plugin activated = nothing works until you manually add times |
| Data entry | D | Tiny inputs, no shortcuts, no bulk ops, no auto-import on first run |
| Navigation | C | Admin subpages are fine but frontend nav ("View Archive / Today's Prayers / Home") is an afterthought |
| Error handling | D | "Unable to calculate next prayer time" with no actionable guidance |
| Responsiveness | C+ | Mobile cards work, but table is hidden/broken on mobile. PWA banner wrecks everything. |
| Feedback/loading | D | No loading indicators for save, import, or generate operations |
| Accessibility | D | No ARIA labels on time inputs, no keyboard shortcuts, colour-only differentiation |

---

## 3. SEO REVIEW

**What works:**
- Structured data (JSON-LD) for prayer times
- Clean URL structure (/prayer-times/2026/february/)
- Hijri date in schema

**Problems:**
- "This page doesn't exist" 404 on /todays-prayers/ - BROKEN SEO page
- No meta descriptions set by the plugin
- No Open Graph tags for sharing
- No schema.org Mosque entity markup
- Rewrite rules not flushed (causing the 404)
- No sitemap integration
- Page title just says "Stechford Mosque - Prayer Times" - should include month/year for long-tail SEO

---

## 4. FEATURE REVIEW (vs. Market)

| Feature | Status | S-Tier Requirement |
|---------|--------|--------------------|
| Monthly timetable display | ✅ Works | Needs design overhaul |
| Today's prayers | ❌ 404 | Must fix immediately |
| Aladhan API import | ✅ Works | Needs one-click setup wizard |
| CSV/XLSX import | ✅ Works | Good |
| PDF upload | ✅ Exists | Good |
| ICS calendar export | ✅ Works | Good |
| PWA support | ⚠️ Intrusive | Banner needs redesign |
| Push notifications | ✅ Code exists | Untested |
| Web push (VAPID) | ✅ Code exists | Untested |
| Hijri dates | ✅ Works | Good |
| Print timetable | ✅ Works | Good |
| Dark mode | ❌ Missing | Required for S-tier |
| Setup wizard | ❌ Missing | CRITICAL for WP.org adoption |
| Gutenberg block | ❌ Missing | Required for modern WP |
| Ramadan mode | ❌ Missing | Key differentiator |
| Jummah schedule | ⚠️ Partial | Field exists but no dedicated UI |
| Digital screen mode | ❌ Missing | S-tier feature |
| WhatsApp share | ❌ Missing | Viral growth engine |
| Multi-language | ❌ Missing | Arabic RTL essential |
| Shortcode builder | ❌ Missing | Power user feature |

---

## 5. WHAT S-TIER LOOKS LIKE (Design Vision)

### Design Language: "Sacred Geometry meets Modern UI"

The plugin should immediately communicate "this is for mosques" through:

1. **Geometric Islamic patterns** as subtle backgrounds (not overpowering)
2. **A colour system rooted in Islamic architecture** - deep teals, warm golds, cream whites, midnight blues
3. **Arabic-inspired typography** - use Google Fonts like Amiri, Scheherazade New, or El Messiri for headings paired with Inter/DM Sans for body
4. **Crescent/minaret iconography** - custom SVG icon set, not emoji
5. **RTL-ready layout** from day one

### Recommended Colour Palette

```
Primary:     #0D7377 (Deep Teal - mosque domes)
Secondary:   #C5A55A (Warm Gold - Islamic calligraphy)
Accent:      #1A3A5C (Midnight Blue - night sky)
Background:  #F5F1EB (Warm Cream - old paper)
Surface:     #FFFFFF
Text:        #1A2332
Text Muted:  #5A6978
Success:     #2D8B4E (Iftar green)
Friday:      #C5A55A20 (Gold tint)
Today:       #0D737710 (Teal tint)
```

### Font Pairing
- **Headings:** El Messiri (600/700) - Arabic-inspired geometric sans
- **Body:** DM Sans (400/500) - Clean, modern, excellent readability
- **Prayer times:** JetBrains Mono or Space Mono - monospaced for alignment
- **Arabic text:** Amiri - beautiful Naskh-style for Quranic references

### Frontend Redesign Priorities

1. **Today's Prayer Card** (most viewed)
   - Full-width hero card with current/next prayer prominent
   - Live countdown to next prayer
   - Animated prayer indicator (subtle pulse on current prayer)
   - Single tap to see jamaat time
   - "Share on WhatsApp" button

2. **Monthly Timetable**
   - Clean table with proper hierarchy
   - Today highlighted, Fridays gold-tinted
   - Sticky header on scroll
   - Responsive: table on desktop, cards on mobile (already partially done)
   - Print button that generates clean A4 layout

3. **Admin Panel**
   - Dashboard-style overview (today's times, quick stats, data status)
   - Inline editing with time picker, not raw text inputs
   - Bulk operations (copy row, fill from API, shift all times)
   - Live preview panel
   - Setup wizard on first activation

---

## 6. WHEN TO BUILD THE WEBSITE TEMPLATE

**Not yet.** Here's the order:

1. **Phase 1 (NOW):** Fix the 404, fix the PWA banner, fix the data rendering
2. **Phase 2 (Next 2 weeks):** Full frontend CSS redesign using the Islamic design system above
3. **Phase 3 (Week 3-4):** Admin panel UX overhaul + Setup Wizard
4. **Phase 4 (Week 5-6):** Submit to WP.org with proper screenshots
5. **Phase 5 (After 10+ reviews on WP.org):** Build a dedicated demo website template

The website template should come AFTER the plugin is polished and has real user feedback. Building a template now would be premature optimisation - you'd be designing around a UI that hasn't been validated.

**When it IS time:** Build a starter theme (child theme of Astra or standalone) that's purpose-built for mosques. Include the timetable plugin pre-configured, a homepage layout, events section, donation page, and contact. Sell it as a bundle: "Mosque Website Kit" = Theme + Plugin Pro for £149/yr.

---

## 7. PRIORITY ACTION LIST

### Immediate (This Week)
1. Fix /todays-prayers/ 404 (flush rewrite rules or create the page)
2. Fix PWA install banner - make it a dismissible toast, not a blocking overlay
3. Remove duplicate month selectors on prayer times page
4. Fix "Unable to calculate next prayer time" error

### Short-term (Phase 2 - CSS Redesign)
5. Replace generic purple gradient with Islamic design system colours
6. Add geometric Islamic pattern SVG backgrounds
7. Implement the teal/gold/cream colour palette
8. Add El Messiri + DM Sans font pairing
9. Redesign Today's Prayer card as hero component
10. Add dark mode support
11. Fix mobile card view (Fajr labelling, jamaat prominence)
12. Add proper SVG icons replacing all emoji

### Medium-term (Phase 3 - Admin UX)
13. Build setup wizard (mosque name, location, Aladhan import)
14. Add time picker inputs instead of raw text
15. Add admin dashboard overview page
16. Add live preview in appearance settings
17. Add theme presets
18. Build Gutenberg block

### Pre-WP.org Submission
19. Take proper screenshots after redesign
20. Write compelling plugin description
21. Create demo video
22. Submit

---

## Summary

The plugin has solid **functionality** underneath - prayer data management, multiple import methods, export options, PWA support, push notifications, Hijri dates. The engineering is there. But the **presentation** is generic AI-template quality that doesn't communicate "mosque" or "Islamic" in any way. The interaction design has critical UX issues (PWA banner blocking content, 404 pages, duplicate controls). 

**Current: C+** (functional but visually generic and UX-broken)
**After Phase 2 redesign: B+** (distinctive design, fixed UX)
**After Phase 3 + WP.org: A** (polished product experience)
**After S-tier features (Ramadan mode, WhatsApp, API): S** (market leader)

The gap from C+ to S is mostly design + UX, not code. The code is already B- quality. That's actually good news - it means the hardest part (making it work) is done. Now it needs to *look* and *feel* like it works.
