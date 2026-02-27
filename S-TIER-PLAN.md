# Mosque Timetable: S-Tier Master Plan

*Created: 2026-02-24*
*Status: DRAFT for Bean's review*

---

## Current Grade: B-
## This Plan's Grade: S-

**What separates each tier:**
- **B:** Good plugin, broken admin, no distribution. "Nice project."
- **A:** Best prayer times WP plugin. Polished, on WP.org, growing installs. "Best in class."
- **S:** The thing mosques didn't know they needed. Changes how they operate online. Creates a network effect. Becomes the default. "How did we manage without this?"

**The difference between A and S is this:** A-tier is the best tool. S-tier is the best *ecosystem*. S-tier means every mosque that installs it makes the plugin more valuable for every other mosque.

---

## PART 1: THE PRODUCT PLAN

### Phase 0: Ship It (Week 1-2)
**Goal:** Working plugin on WordPress.org

1. Fix admin AJAX (2 hours, literally 2 characters + handler registration)
2. Split monolithic 376KB file into class files (4-6 hours)
3. Run `composer phpcbf` to auto-fix PHPCS violations (2 hours)
4. Create WP.org readme.txt with proper headers (1 hour)
5. Take 5 screenshots of frontend (today page, monthly table, mobile cards, export modal, admin) (1 hour)
6. Submit to WordPress.org (review takes 1-5 days)
7. Deploy to mosquewebdesign.com as live demo

**Deliverable:** Listed on WordPress.org, live demo site working.

### Phase 1: A-Tier Features (Week 3-6)
**Goal:** Best prayer times plugin, no contest

1. **Setup Wizard** - 3-step onboarding: mosque name/location > calculation method > generate first month. Nobody reads docs. This must be idiot-proof.
2. **Ramadan Mode** - Suhoor/Iftar times, Taraweeh schedule, Qiyam ul-Layl. Auto-activates based on Hijri date. This is the #1 missing feature for the target audience.
3. **Digital Screen Mode** - Full-screen prayer times display for mosque TVs/monitors. Auto-advances, large fonts, clock, next prayer countdown. This is what Masjidal charges hardware fees for. You give it free with a Raspberry Pi guide.
4. **Gutenberg Block** - Modern WP editing. Not just shortcodes.
5. **One-Click Aladhan Import** - Select mosque location on a map, auto-populate entire year. No spreadsheets needed.
6. **WhatsApp Share Button** - "Share today's prayer times" generates a pre-formatted WhatsApp message. This is how your audience actually communicates.
7. **Jummah Khutbah Schedule** - Speaker name, topic, time for each Friday. Mosques get asked this constantly.

### Phase 2: S-Tier - The Network (Week 7-14)
**Goal:** Create something no competitor has - a connected mosque network

#### 2a. The Mosque Directory API
Every mosque that installs the plugin automatically joins a public API directory (opt-in). This means:
- **MosqueFinder:** Anyone can search "prayer times near me" and get live data from real mosques, not calculated estimates. No app like Muslim Pro can do this because they use calculated times, not actual Jamaa'ah times set by the imam.
- **Embeddable Widget:** Other websites can embed any participating mosque's live prayer times.
- **Cross-Mosque Search:** "Find a mosque with Taraweeh tonight" or "Which mosque has the earliest Fajr Jamaa'ah?"

**This is the network effect.** Each new mosque makes the directory more valuable. 10 mosques = interesting. 100 = useful. 1,000 = essential.

#### 2b. The Community Layer
- **Announcement System:** Mosque admins can post announcements (Eid confirmed, imam visiting, class cancelled) that push to subscribers via web notification + email digest.
- **Event Calendar:** Mosque events with RSVP. Integrated with the prayer timetable.
- **Multi-Mosque Dashboard:** For Islamic councils/umbrella orgs managing 5-50 mosques. One admin panel, all mosques. This is the enterprise upsell.

#### 2c. The Data Moat
- **Prayer Times Analytics:** "Your mosque gets 340 views on Fridays, 89% from mobile." No mosque has this data. It's invaluable for committees proving digital investment to trustees.
- **Historical Archive:** Over time, you build the most comprehensive database of actual mosque prayer times globally. This data has research, API, and commercial value.

### Phase 3: Revenue Engine (Month 4+)

#### Free Tier (WordPress.org)
- Core prayer times management
- Monthly timetable display
- Today's prayers widget + countdown
- ICS calendar export
- Basic mobile responsive design
- Aladhan API auto-calculation
- Setup wizard
- Community directory listing (opt-in)

#### Pro Tier - $49/year per mosque
- PWA with offline mode
- Push notifications
- Digital screen mode
- Ramadan mode (Suhoor/Iftar/Taraweeh)
- Multi-year archive
- Announcement system
- Event calendar
- WhatsApp share
- Jummah khutbah schedule
- PDF timetable generation
- REST API access
- Priority email support

#### Organisation Tier - $199/year
- Everything in Pro
- Multi-mosque dashboard (up to 10 mosques)
- Centralised announcement broadcasting
- Custom branding/white-label
- Analytics dashboard
- Dedicated onboarding call
- $15/year per additional mosque

#### API Tier - $29/month
- For app developers wanting live mosque prayer times
- Rate-limited API access to the directory
- Webhook notifications for prayer time changes
- Bulk data export

### Revenue Projections (Realistic)

| Metric | Month 6 | Year 1 | Year 2 | Year 3 |
|--------|---------|--------|--------|--------|
| WP.org Installs | 500 | 2,000 | 8,000 | 20,000 |
| Pro Conversions (3%) | 15 | 60 | 240 | 600 |
| Pro Revenue | £735 | £2,940 | £11,760 | £29,400 |
| Org Tier (0.5%) | 2 | 10 | 40 | 100 |
| Org Revenue | £398 | £1,990 | £7,960 | £19,900 |
| API Tier | 0 | 2 | 10 | 25 |
| API Revenue | £0 | £696 | £3,480 | £8,700 |
| **Total ARR** | **£1,133** | **£5,626** | **£23,200** | **£58,000** |

Conservative estimates. The real upside is if the directory network effect kicks in, which could 3-5x these numbers.

---

## PART 2: MARKETING & OUTREACH PLAN

### Understanding the Personas

#### Persona 1: "The Volunteer Admin" (60% of target)
- **Who:** 30-55, male, committee member or volunteer running the mosque website
- **Tech level:** Can install WordPress plugins, maybe basic Elementor. Not a developer.
- **Pain:** Updates prayer times manually every month. Uses a PDF or image. Gets asked "what time is Isha?" constantly on WhatsApp.
- **Language:** Often bilingual but comfortable in English for tech. Urdu/Arabic/Bengali/Somali/Turkish at home.
- **Where they are:** WhatsApp groups, Facebook groups, mosque committee meetings, Friday prayers
- **What convinces them:** Seeing it work on another mosque's site. Word of mouth from another admin.

#### Persona 2: "The Young Tech Brother" (25% of target)
- **Who:** 18-35, male, technically competent, often the one who "does the website" for the mosque
- **Tech level:** Comfortable with WordPress, maybe knows some code. Follows tech influencers.
- **Pain:** Frustrated by outdated mosque websites. Wants modern tools but committee won't invest.
- **Where they are:** Twitter/X, Reddit (r/islam, r/muslimtechnet), YouTube, Discord, GitHub
- **What convinces them:** Good README, clean code, modern features (PWA, API). Open source ethos.

#### Persona 3: "The Imam/Committee Chair" (10% of target)
- **Who:** 40-65, decision maker, controls budget
- **Tech level:** Low. Uses WhatsApp and maybe Facebook.
- **Pain:** Doesn't see why the mosque needs a better website. Budget is tight.
- **Where they are:** In-person meetings, WhatsApp, email
- **What convinces them:** "Other mosques are using it." Numbers. "It's free." A volunteer who sets it up for them.

#### Persona 4: "The Islamic Org / Council" (5% of target)
- **Who:** Regional Islamic councils, umbrella bodies (MCB, local mosque associations)
- **Tech level:** Have staff. Some have IT teams.
- **Pain:** Managing consistency across multiple mosques. No centralised tooling.
- **Where they are:** Conferences, email, LinkedIn
- **What convinces them:** Case studies, ROI data, org tier pricing, white-label option.

---

### The Outreach Strategy

**Core principle:** Mosques trust other mosques. Not ads. Not cold emails. Your #1 growth channel is mosque-to-mosque word of mouth, accelerated by digital.

#### Channel 1: Direct Mosque Outreach (Weeks 1-8)
**Target:** UK mosques with WordPress websites
**Method:** Scalable, minimal spoken language

**Step 1: Build the list**
- ByteScraper data shows 1,488 UK mosques with websites
- Scrape/crawl to identify which ones run WordPress (check /wp-login.php, /wp-content/ in source)
- Filter to mosques that currently display prayer times as images/PDFs (easy to spot, means they need this plugin)
- Expected: 300-500 WordPress mosques with manual prayer time displays

**Step 2: The outreach email**
One email. Not a sequence. Not a drip campaign. Mosques get enough spam.

```
Subject: Free prayer times plugin for [Mosque Name]

Assalamu Alaikum,

I noticed [Mosque Name] displays prayer times as [an image/a PDF] on your website.

I've built a free WordPress plugin that:
- Auto-calculates prayer times from your location (15 methods including [their likely method])
- Shows a live countdown to the next prayer
- Lets your community subscribe to a calendar so times update automatically
- Works offline on phones (PWA)

It's free, open source, and already used by [X] mosques.

Here's what it looks like: [link to demo on mosquewebdesign.com]

If you'd like, I can set it up for you remotely in under 15 minutes. No charge, fi sabilillah.

Ibraheem
[mosquewebdesign.com]
```

**Why this works:**
- Personalised (mosque name, what they currently do)
- Shows the problem they have
- Free offer with no catch
- "fi sabilillah" signals shared values
- Offer to set it up FOR them removes friction
- No phone call needed. Email + remote setup via admin access.

**Step 3: The free setup**
When they respond, ask for WP admin access (or install via plugin upload). Set it up in 15 min. Generate their prayer times. Send a screenshot of their site with it live.

This is your marketing. Every mosque you set up becomes a reference. "Check [Mosque Name]'s website, they're using it."

**Target: 20 mosques in first 4 weeks.** That's 20 live references and 20 potential WP.org reviews.

#### Channel 2: WhatsApp Network Effect (Ongoing)
**This is your superpower.** Mosque committees live on WhatsApp.

**Step 1:** Add "Share on WhatsApp" to the plugin frontend. One tap sends: "Today's prayer times at [Mosque Name]: Fajr 5:52am, Zuhr 12:15pm..." Pre-formatted, clean, with a link back to the mosque website.

**Step 2:** Every time a congregant shares prayer times, the mosque website link goes with it. Free distribution.

**Step 3:** When someone clicks the link and sees the nice PWA experience, they think "why doesn't my mosque have this?" They ask their mosque admin. The admin Googles "mosque prayer times wordpress plugin." You're the top result (because WP.org + SEO).

**This is viral growth for free.** The WhatsApp share button is not a nice-to-have, it IS the marketing strategy.

#### Channel 3: Islamic Facebook Groups (Weeks 3-8)
**Where:** Facebook groups for mosque admins, Islamic web design, Muslim tech
- UK mosque committees groups (several with 1,000-5,000 members)
- "Muslim Web Developers" type groups
- Local city Muslim groups (Birmingham, London, Manchester, Bradford, etc.)

**What to post:**
Not "check out my plugin." That gets deleted.

Post VALUE:
- "How to set up automatic prayer times on your mosque website (free)" - tutorial with screenshots
- "5 things your mosque website needs in 2026" - article where the plugin is one of five
- Before/after screenshots of a mosque site you set up
- "We just helped [Mosque Name] modernise their prayer times display" - case study

**Why Facebook:** Persona 1 (the volunteer admin) lives here. Not Twitter. Not Reddit. Facebook groups.

#### Channel 4: Islamic Tech Twitter/X (Weeks 3+)
**Target:** Persona 2 (the young tech brother)

Build in public:
- "Building an open-source prayer times plugin for mosques. Here's what I learned about PWAs for Islamic content."
- "Just shipped Ramadan mode. Auto-detects Hijri dates and switches to Suhoor/Iftar layout."
- "150+ features, 9 REST API endpoints, fully offline PWA. And it's free. For the Ummah."
- Share the GitHub repo. Let people contribute.

**Why this works:** Muslim tech Twitter is tight-knit and supportive. Authentic projects get amplified. "For the Ummah" resonates. People will share it to their mosque committees.

#### Channel 5: YouTube & SEO Content (Month 2+)
**Target:** People searching for solutions

Create 5 key videos/articles:
1. "How to Display Prayer Times on Your WordPress Website (Free Plugin)" - pure SEO play
2. "Turn Your Mosque Website into a PWA (Works Offline)" - tech angle
3. "How to Set Up a Digital Prayer Times Display for Your Mosque" - Raspberry Pi + screen mode
4. "Mosque Website Checklist 2026" - broader, plugin is featured
5. "Building the Mosque Directory API" - developer/tech community

**Written versions** on mosquewebdesign.com blog for SEO. Target keywords:
- "mosque prayer times wordpress" (low competition, high intent)
- "islamic prayer times plugin" (medium competition)
- "mosque website design" (medium competition, you own the domain)
- "digital prayer times display mosque" (zero competition)

#### Channel 6: Conferences & Events (Quarterly)
**Target:** Personas 3 and 4

- **ISNA Convention** (US, largest Islamic convention)
- **GPU (Global Peace & Unity)** (UK)
- **MCB events** (UK, Muslim Council of Britain)
- **Local mosque open days / interfaith events**

You don't need a booth. You need:
- A tablet showing the live demo
- A one-page flyer (QR code to WP.org listing)
- To talk to 10 mosque admins per event

**Alternatively:** Sponsor/speak at Islamic tech meetups. "How technology can serve the Ummah" talk, featuring the plugin as a case study.

#### Channel 7: WordPress.org Ecosystem (Ongoing)
**Target:** People already looking

- Optimise WP.org listing (screenshots, FAQ, description with keywords)
- Respond to every review within 24 hours (Blub can draft, you approve)
- Respond to every support thread (builds trust + SEO)
- Cross-promote with Islamic WordPress themes (Peace theme, Flavor theme, Flavor theme)
- Guest post on WP blogs: "Building a PWA WordPress Plugin for Mosques"

#### Channel 8: The Mosque Directory as Marketing (Phase 2+)
**The S-tier move.** Once the directory has 50+ mosques:

- Launch **mosquefinder.com** or similar - a public site where anyone can search "prayer times near me" and get live data from real mosques
- This site drives traffic back to each mosque's website
- Each mosque's listing says "Powered by Mosque Timetable Plugin"
- Every visitor who discovers their mosque through the directory reinforces the plugin's value
- Mosque admins WANT to be listed because it drives traffic to them

**This is the flywheel:** More mosques install > directory gets better > more people use directory > more mosques want to be listed > repeat.

---

### Growth Targets

| Milestone | Target | Timeline |
|-----------|--------|----------|
| WP.org listing live | - | Week 2 |
| First 20 mosques installed | Direct outreach | Week 6 |
| 10+ WP.org reviews (4.5+ stars) | From setup mosques | Week 8 |
| Product Hunt launch | After reviews | Week 10 |
| 100 installs | Organic + outreach | Month 3 |
| 500 installs | WhatsApp viral + SEO | Month 6 |
| 50 mosques in directory | Opt-in from installs | Month 6 |
| First Pro sale | Pro tier launch | Month 4 |
| 2,000 installs | Network effect | Year 1 |
| Mosque directory as standalone value | mosquefinder.com | Year 1 |

---

## PART 3: WHY THIS IS S-TIER

### What A-tier looks like:
Best prayer times plugin on WordPress. Great features. Growing installs. Nice reviews. Makes money from Pro licences. Congratulations, you built a good product.

### What S-tier looks like:
1. **Network effect nobody else has.** The mosque directory API means every install makes the whole ecosystem more valuable. MOHID doesn't have this. Masjidal doesn't have this. Daily Prayer Time definitely doesn't have this.

2. **WhatsApp as a growth engine.** You're not fighting for attention on Instagram or Google Ads. You're embedding yourself in the communication channel your audience already uses every single day. Every shared prayer time is a free ad.

3. **The "fi sabilillah" moat.** Competitors charge $30-199/month. You offer a genuinely generous free tier. When a mosque admin compares "free, open source, built for the Ummah" vs "$199/month corporate SaaS", the choice is obvious. This isn't undercutting, it's a values-based competitive advantage.

4. **Data moat.** Over 2-3 years, you build the most comprehensive database of actual mosque Jamaa'ah times globally. Not calculated estimates like Muslim Pro. Actual imam-set times. This data is uniquely valuable for apps, research, and community tools. Nobody else is collecting this.

5. **Trojan horse for mosque digitalisation.** Plugin starts as prayer times. Phase 2 adds announcements and events. Phase 3 could add donation integration, membership management, madrasah scheduling. You're building the operating system for mosques, starting with the thing every single one needs: prayer times.

6. **Scalable without sales calls.** Everything above works with email + self-service + remote setup. No phone calls. No demos. No sales team. A volunteer in Indonesia can install this and join the directory without ever speaking to you. That's global scale.

### What could make it S+ (stretch goals):
- **Mobile app** that aggregates all directory mosques (like Muslim Pro but with real times)
- **Alexa/Google Home skill:** "What time is Isha at my mosque?"
- **Apple Watch complication** showing next prayer
- **Open data initiative:** Partner with Islamic universities to make the prayer times database available for research
- **Mosque-to-mosque messaging:** Let mosque admins coordinate (e.g., "Our Taraweeh is full, redirect to [nearby mosque]")

---

## PART 4: EXECUTION TIMELINE

### Month 1: Foundation
- [ ] Fix admin AJAX
- [ ] Split monolithic file
- [ ] Fix PHPCS violations
- [ ] Submit to WordPress.org
- [ ] Set up mosquewebdesign.com as demo
- [ ] Build UK mosque WordPress list (300-500 targets)
- [ ] Start direct email outreach (50 mosques)
- [ ] Begin building setup wizard

### Month 2: First Users
- [ ] Continue outreach (target: 20 installs)
- [ ] Ship setup wizard
- [ ] Ship Ramadan mode (timely if near Ramadan)
- [ ] Ship WhatsApp share button
- [ ] Ship digital screen mode
- [ ] Ask installed mosques for WP.org reviews
- [ ] First Facebook group posts
- [ ] First Twitter/X build-in-public posts

### Month 3: Growth
- [ ] Product Hunt launch (after 10+ reviews)
- [ ] Ship Gutenberg block
- [ ] Ship Jummah khutbah schedule
- [ ] First YouTube tutorial
- [ ] First blog post on mosquewebdesign.com
- [ ] Begin building directory API backend

### Month 4: Monetise
- [ ] Launch Pro tier
- [ ] Ship announcement system
- [ ] Ship event calendar
- [ ] Ship analytics dashboard
- [ ] Begin directory beta (opt-in for installed mosques)
- [ ] Second round of mosque outreach (100 more)

### Month 5-6: Scale
- [ ] Launch Organisation tier
- [ ] Ship multi-mosque dashboard
- [ ] Directory live with 50+ mosques
- [ ] Launch mosquefinder.com (or similar)
- [ ] API tier for developers
- [ ] First conference/event appearance

---

## GRADE: S

**Why S and not S+:**
- S+ requires the mobile app and voice assistant integration, which are 6-12 month projects
- S+ requires 1,000+ mosques in the directory (network effect fully realised)
- This plan gets to S with current resources (you + Blub + existing infrastructure)

**What makes it S:**
- Network effect through the directory (no competitor has this)
- Viral growth through WhatsApp (built into the product)
- Values-based moat (fi sabilillah vs corporate SaaS)
- Data moat (actual Jamaa'ah times vs calculated)
- Global scale without sales calls
- Trojan horse for full mosque OS

**Risk factors:**
- Execution speed (ADHD tax, other client work)
- WordPress.org review could take time or require revisions
- Directory value depends on critical mass (need 50+ to be useful)
- Pro conversion rate could be lower than 3% for non-profit audience

---

*This plan lives at: `C:\Users\Bean\Projects\mosque-timetable\S-TIER-PLAN.md`*
*Review, challenge, approve. Then we execute.*
