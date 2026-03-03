# Mosque Timetable — WordPress Plugin

## Always Do First
- Read `wordpress-pro` SKILL.md before any WordPress/PHP work
- Prayer calculation accuracy is critical — verify against Muslim Pro + local mosque before any changes to calculation methods
- Check WP.org plugin guidelines before any UI changes (aiming for directory listing)

## Project Context
- **Type:** WordPress plugin (public, WP.org submission target)
- **Goal:** Best prayer times plugin + mosque directory API
- **Current state:** 10,699-line monolith being modularised
- **Target audience:** Mosque webmasters, Muslim community sites
- **PHP minimum:** 7.4

## Prayer Calculation — Critical
- **Default method:** Custom 12.5° Fajr angle (method=99, methodSettings=12.5,null,null)
- This matches Bean's mosque + Muslim Pro app — do NOT change defaults without explicit instruction
- Always test Fajr time against known-correct value before committing
- PHPUnit tests REQUIRED for any changes to calculation functions

## WordPress.org Standards
- No external HTTP calls on every page load (cache aggressively)
- All options prefixed: `mosque_timetable_`
- Uninstall hook must clean up all options + tables
- i18n: all strings wrapped in `__()` or `_e()` with text domain `mosque-timetable`
- No bundled copies of jQuery or other WP-included scripts

## Community Trust Rules
- No breaking changes to existing shortcodes without deprecation warning
- Backward compatibility for at least 2 major versions
- Admin UI must be non-technical friendly ("Fajr angle" needs a tooltip explaining what it is)

## Hard Rules
- No placeholders, no TODOs in committed code
- UK English in all strings
- Changelog entry required for every commit to main
