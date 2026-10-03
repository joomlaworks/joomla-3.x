Joomla 3.x UTD (up-to-date)
===========================

According to [market share estimates](https://w3techs.com/technologies/details/cm-joomla) (as of May 2026), Joomla 3.x is currently used on more than 50% of all installed Joomla sites worldwide.

However, official support for Joomla 3.x ended in February 2025 (counting the eLTS program).

So we're actively developing Joomla 3.x UTD as an up-to-date distribution of the Joomla 3.x content management system, built to ensure code security, support modern PHP & MySQL/MariaDB versions & fix any broken behaviour that never got sorted before the release of newer major versions of Joomla.

If you are a Joomla extension developer reading this, ensure your extension update XML files don't stop at Joomla 3.10.x. Do your users a favour ;)

---

## CONTENTS
- [Changelog](#changelog)
  - [Version 3.16 - released October 3rd, 2026](#version-316---released-october-3rd-2026)
  - [Version 3.15 - released July 18th, 2026](#version-315---released-july-18th-2026)
  - [Version 3.14 - released July 4th, 2026](#version-314---released-july-4th-2026)
  - [Version 3.13 - released May 31st, 2026](#version-313---released-may-31st-2026)
  - [Version 3.12 - released May 21st, 2026](#version-312---released-may-21st-2026)
  - [Version 3.11 - released April 20th, 2026](#version-311---released-april-20th-2026)
- [How to Upgrade for Existing Joomla 3.x Sites](#how-to-upgrade-for-existing-joomla-3x-sites)
- [How to Install (for new sites)](#how-to-install-for-new-sites)
- [PHP Compatibility](#php-compatibility)
- [Database Support](#database-support)
- [Notes on MySQL & MariaDB](#notes-on-mysql--mariadb)
- [Notes on Operating System Support](#notes-on-operating-system-support)
- [Contribute](#contribute)
- [Discuss](#discuss)
- [Chat with the Codebase (AI assisted)](#chat-with-the-codebase-ai-assisted)
- [Longterm Plan (as a different project)](#longterm-plan-as-a-different-project)

## CHANGELOG

## Version 3.16 - released October 3rd, 2026
Summary of changes:
- Backported 14 security fixes from the Joomla 6.1.3/5.4.8 and 6.1.4/5.4.9 security releases, each confirmed against this codebase (several were listed upstream as "Joomla 4.0 and later" but the vulnerable code is present in 3.x), plus related hardening
- Added "Little WAF", a new opt-in system plugin that filters known attack patterns against vulnerable third-party extensions
- Joomla Update now respects core extensions you've uninstalled, and can bring them back on request
- Raised the minimum database versions, and cleaned up cache, session and database drivers that can't work on PHP 7.1+
- Fixed a long list of bugs and PHP 8.x deprecation warnings, several of them in stock Joomla 3.x itself

**Security fixes:**
- Guest account creation via the frontend profile-save action, even with user registration disabled
- Arbitrary directory deletion through a path traversal in the file cache's group handling
- Two XSS-filter bypasses using HTML5 entities and unterminated numeric references (both confirmed against the real filter before fixing)
- Tagged items in access-restricted categories leaking through tag views and the "Tags - Popular"/"Tags - Similar" modules
- A missing access check on the second record of a content history comparison
- XSS gaps in `JHtml::link()`/`JHtml::iframe()`, the module manager's position column, the toolbar link button and the generic image layout's attribute names
- SSRF: feed, SEF-domain, user-profile website and custom update URL fields are now restricted to web URL schemes
- HTTP header injection via an unescaped filename in the banner-tracking download and the contact vCard export
- Missing per-item edit-permission checks in the category and custom-field batch-copy actions
- A missing SHTML file extension in the Template Manager's upload blacklist
- Stored XSS: contact name/position/category echoed unescaped into public-facing schema.org markup
- Smaller hardening: traversal checks in the Template Manager's file/folder delete, CSV formula escaping in the banner tracks export, stricter internal-URL checks, a constant-time TOTP comparison in the bundled FOF library, and a few more blocked executable file extensions

**New features & additions:**
- "Little WAF" system plugin: filters known attack signatures against abandoned or historically vulnerable third-party extensions before any component runs. Ships with two filters, each named after and toggled per extension: Sourcerer-style `{source}...{/source}` URL injection, and, as a precaution, Modules Anywhere-style `{module}`/`{modulepos}` tags. The plugin is disabled by default; once enabled, its filters are on by default. More filters will be added over time as similar issues are identified.
- "Restore uninstalled core extensions" option on Joomla Update's "Reinstall Joomla core files" and "Upload & Update" screens, to bring back core extensions you uninstalled earlier, with their database tables, menu items and default settings
- Joomla Update now checks the site's database type and version before offering an update (from 3.16 onwards)
- New "Database support" section in this README

**Improvements to existing features:**
- Joomla Update no longer brings back removable core extensions you've uninstalled (Banners, Contacts, News Feeds, Search, Smart Search, Content History, Multilingual Associations and Fields with their modules and plugins, as well as any other removable core module, plugin or template). Previously their files were restored on every update, showed up under Extensions > Discover, and could cause a "Refresh Manifest Cache failed" warning on every later update.
- Raised the minimum database versions to MySQL 5.5.3, MariaDB 5.5 and PostgreSQL 9.0 (SQL Server stays at 2008 R2), enforced by the installer and, for sites already on 3.16 or newer, by Joomla Update
- The update channel now accepts any Joomla 3.x installation, since database migrations go back to Joomla 2.5.0 and support a direct jump to the current version regardless of starting point
- Removed cache/session drivers that can't work on PHP 7.1+: APC (not APCu), Memcache (not Memcached), XCache and Cache_Lite. WinCache was kept, since it still has genuine PHP 7.x support. Leftover files are cleaned up automatically on upgrade.
- Removed the legacy `mysql` database driver, which hasn't worked since PHP 7; sites still configured with it keep working as before through the `mysqli` driver
- The Memcached cache/session driver is no longer labelled "Experimental", after fixing its locking bugs (see below)

**Bug fixes:**
- Fixed updates failing with "Table '…_banners' doesn't exist" on sites that had uninstalled Banners, Contacts, News Feeds or Smart Search; the same false errors are gone from Extensions > Manage > Database
- Uninstalling Banners, Contacts or News Feeds no longer leaves a stray "com_..._categories" entry under Components in the backend menu; existing leftovers are cleaned up on update
- Fixed updates leaving the cache stale on sites using the Redis, Memcached, APCu or WinCache cache handlers (the update's cache cleanup only worked with the File handler); the update now warns if the cache can't be cleared
- Fixed the update system ignoring database requirements declared in an update feed (it would have refused every update instead)
- Fixed a dormant bug in the update feed that would have silently stopped this distribution from offering updates once a site reached version 3.20.0
- Fixed two bugs in the update-extraction engine behind "the archive file is corrupt" false positives when manually uploading an update package. A separate issue remains open for zips built with streaming tools (e.g. GNOME Files' "Compress" action, or Windows 11 24H2+'s native "Compress to ZIP"); a standard `zip`-created archive is unaffected.
- Fixed the Memcached cache driver's internal locking, which could stall the entire site's cache for up to 30 seconds
- Fixed restoring News Feeds and Smart Search creating outdated database tables, and Smart Search not installing at all on PostgreSQL
- Fixed fatal errors on PHP 8 hosts that disable `php_uname()`
- Silenced a large batch of PHP 8.1–8.5 deprecation warnings, from real production logs plus a curated, individually verified automated sweep of 805 files. Every fix keeps working on PHP 7.1 and up. Cosmetic fixes only, no behaviour change.

## Version 3.15 - released July 18th, 2026
Summary of changes:
- Backported 13 CVEs from official Joomla security advisories, independently confirmed to also affect 3.x (several labelled "affects 4.0.0+ only" upstream, but the vulnerable code is present in 3.x regardless)
- Resolved a number of functional/compatibility issues, some reported directly by the community

**Security fixes (CVE backports), most to least severe:**
- Local file inclusion (LFI) via the view layout parameter (CVE-2026-40383) — **High**
- SQL injection in the com_tags "all tags" list ordering (CVE-2026-352212) — **Moderate, High impact**
- Privilege-escalation XSS via language overrides, where a non-Super-User with delegated translation access could inject quote characters that break out of the many places Joomla renders language strings unescaped (CVE-2026-48954) — **Moderate**
- XSS in the template manager's file/image/font editor, where a crafted (but filter-legal) request parameter could decode to a `<script>` tag on display — affecting three code paths in this codebase where the official fix only covered one (CVE-2026-48950) — **Moderate**
- Unescaped attribute output in the generic image layout used by extensions (CVE-2026-48953) — **Moderate**
- Reflected XSS in the com_installer update list view (CVE-2026-48952) — **Moderate**
- Additional XSS gaps in com_associations left over from an earlier fix (CVE-2026-25901) — **Moderate**
- Stored XSS in the content history preview screen (CVE-2026-30894) — **Moderate**
- XSS in the feed modules, front and back end (CVE-2026-25900) — **Moderate**
- XSS in article "read more" links via unescaped titles (CVE-2026-30895) — **Moderate**
- Password/username reset links being sent over plain HTTP even on HTTPS-only sites (CVE-2026-48902) — **Low**
- An instance-cache key bug in InputFilter that could serve a wrongly-configured filter instance (CVE-2026-48901) — **Low**
- An access-control bypass allowing anyone to download restricted contacts' vCards in com_contact (CVE-2026-48948) — **Low**
- Two further CVEs confirmed already covered by earlier hardening in this project, no new gap: CVE-2026-48905 and CVE-2026-48903 (both related to the core HTML attribute filter)
- Two further CVEs confirmed not applicable, as the vulnerable code/feature doesn't exist in this version of Joomla: CVE-2026-48899 (sample data plugin permissions) and CVE-2026-48951 (a "modal return" screen XSS)

**Functional & compatibility fixes:**
- Fixed modules set to "Use Global" caching (mod_menu and 25 other core modules) silently ignoring Global Configuration's Cache Time in favour of their own separate Cache Time field — which, due to a hardcoded/reverting default, almost always meant a fixed 15-minute refresh regardless of what Global Configuration was actually set to; affected sites are fixed automatically on upgrade, no manual database changes needed
- Added an explicit "Use custom cache time" caching option to all 26 cache-capable core modules (including mod_whosonline, which now also supports Global/custom caching for the first time), so overriding a single module's cache TTL is now a clear, self-documenting choice instead of an unlabelled number field
- Fixed modules set to "Use custom cache time" never actually being cached on real page loads — the site's per-module page renderer only attempted caching for "Use Global", so the new option silently fell through to always rendering live
- Added "sort by Author" to "Extensions > Manage", so third-party extensions can be easily distinguished from Joomla core ones when auditing a large site for vulnerable or old/unmaintained extensions
- Fixed a misleading "Refresh Manifest Cache failed: X Extension is not currently installed" warning shown on every Joomla Update run, for any stock core extension (e.g. the protostar template, or com_banners/mod_banners) a site admin has deliberately removed
- Fixed the removed beez3/hathor templates not actually being deleted from the database on upgrade from an earlier Joomla 3.x release, which left stale entries in Template Manager that threw a PHP error when opened (see [issue #7](https://github.com/joomlaworks/joomla-3.x/issues/7)) — affected sites will self-heal automatically on their next update, no manual fix needed
- Fixed a PHP 8.4 deprecation warning from the session garbage-collection plugin's use of `lcg_value()` (see [issue #12](https://github.com/joomlaworks/joomla-3.x/issues/12))
- Fixed literal `"_QQ_"` text appearing instead of quotation marks in various admin messages (e.g. the "Add Install from Web tab" notice) — a legacy language-file placeholder that was never being converted back to a real quote mark; fixed across all 80 affected language files
- Raised the hardcoded minimum-PHP-version check from a stale `5.3.10` to `7.1.0` (the actual floor this codebase can run on), and lowered the update feed's own PHP requirement to match — sites on PHP 7.1–7.3 now keep receiving updates and security patches instead of being silently skipped or hitting an unhandled error page. PHP 7.4+ remains our recommended production baseline, both for broader hosting support (e.g. AlmaLinux 8 with cPanel, or Ubuntu 22.04+ with Ondřej's PHP repos) and for the best experience overall
- Housekeeping: removed the stock, unedited `README.txt` (superseded by this file) and renamed `LICENSE.txt` to `LICENSE.md`; sites upgrading from an older release get the old files cleaned up automatically

## Version 3.14 - released July 4th, 2026
Summary of changes:
- Fixed two PHP 8.5 deprecations reported via PR #13: null array offsets in `HtmlDocument::getBuffer()`/`setBuffer()`, and the now-deprecated `imagedestroy()` call in `Image::destroy()` and `Backgroundfill::execute()` (supplemental to PR #13).
- Updated Joomla language file versioning, in-line with the main version (this would also "trip" some scanners)

## Version 3.13 - released May 31st, 2026
Summary of changes:
- The Isis administrator (backend) template now uses CSS view transitions
- All obsolete CSS has been removed from the Isis template
- Further PHP 8.x compatibility fixes, extending coverage to newly reported files
- Fixed: Database update version (OLD-VERSION) does not match CMS version (CURRENT-VERSION) - under Extensions > Manage > Database

## Version 3.12 - released May 21st, 2026
Summary of changes:
- Built-in update server: sites running 3.12 or newer can now receive updates directly via the Joomla backend updater
- Removed legacy bundled items: `eos310` & `phpversioncheck` quickicon plugins, `beez3` frontend template, `hathor` backend template
- Further PHP 8.x compatibility fixes, extending coverage to previously missed files
- Additional security patches backported from Joomla 4/5/6
- Fixed the getModuleById method in JModuleHelper to correctly return a module's data using its ID.

Please note that if you had `beez3` or `hathor` as one of your frontend or backend (respectively) default templates, upgrading to this version will set `protostar` and `isis` as your new defaults (respectively). If you use another frontend template, it will (of course) not be updated...

## Version 3.11 - released April 20th, 2026
Summary of changes:
- Joomla 3.x is now compatible with PHP up to version 8.5
- Includes security patches for CVEs reported after Joomla 3.10.20 eLTS was released
- Includes additional security patches & some quality-of-life improvements
- Works better with MySQL 8.x

For detailed changelog, please visit: https://github.com/joomlaworks/joomla-3.x/blob/main/CHANGELOG.md

---

## HOW TO UPGRADE FOR EXISTING JOOMLA 3.X SITES

### Using the Joomla Update component in the backend (Web UI method)
In the Joomla backend, adjust the options for the Joomla Update component (either from the component's "Options" or through Joomla's "Global Configuration") and use the following update URL in the "Custom URL" field, along with these settings:

- Update Channel: Custom URL
- Minimum Stability: Stable
- Custom URL: `https://joomlaworks.github.io/joomla-3.x/list.xml`

Refresh and you should see the latest release available to upgrade.

Future updates will also show up there and you can also be notified (as a super admin) about them (if you have these notifications enabled).

Happy updating!


### Using a terminal (CLI method)
Using your server's terminal, extract the latest rolling release https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip on top of an existing Joomla 3.x installation.

Remember to remove the `/installation` folder afterwards.

In a typical Linux based server, you can easily do the upgrade using the following one-liner command (after you "cd" into your Joomla site's folder):
```
wget -qO- https://github.com/joomlaworks/joomla-3.x/archive/refs/heads/main.tar.gz | tar -xz --strip-components=1 && rm -rf installation .github .gitignore *.md
```


## HOW TO INSTALL (FOR NEW SITES)
To install, just extract the latest rolling release https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip where you want the site to be and then follow the normal Joomla installation process.


## PHP COMPATIBILITY
This distribution targets at least PHP 7.4. This is the baseline version we use for broader compatibility with hosts and the Joomla 3.x ecosystem (e.g. other extensions and templates that are actively maintained).

Sites on PHP 7.1 through 7.3 will still be offered updates to this distribution through the Joomla Update component - 7.4 is our recommended baseline, not a hard cutoff - so these sites can keep receiving security patches even before upgrading their PHP version. PHP 7.0 and below is not supported; the update won't be offered and installing manually isn't recommended.

**For end users:**
If your site's server/webspace is configured with PHP 7.0 to 7.3, upgrading to PHP 7.4 is typically a safe switch. The same applies to sites on PHP 5.6, just make sure your extensions and templates are not holding you back.

**For professionals/hosting companies:**
If you are hosting sites for others, consider letting them know they can safely upgrade to this Joomla distribution, both for security as well as newer PHP compatibility/features/performance.

Switching to this distribution will also allow you (or take you closer) to upgrade your server(s). E.g. a server hosting Joomla sites using PHP prior to version 7.2 may be stuck in CentOS 7/cPanel, which is no longer supported by either the OS vendor or cPanel, with whatever that entails primarily for security.


## DATABASE SUPPORT
| Database | Minimum version | Status |
|---|---|---|
| MySQL | 5.5.3 | Tested and actively supported (5.7 or newer recommended, see notes below for 8.x) |
| MariaDB | 5.5 | Tested and actively supported |
| PostgreSQL | 9.0 | Inherited from stock Joomla 3.x, not tested by this project |
| Microsoft SQL Server / Azure SQL | 2008 R2 (10.50.1600.1) | Inherited from stock Joomla 3.x, not tested by this project |

Database support in Joomla 3.x was always centred on MySQL/MariaDB. PostgreSQL and SQL Server work with the core, but several core and third-party extensions only ship MySQL/MariaDB database scripts, so expect rough edges there. The installer enforces these minimum versions. Once a site runs 3.16 or newer, it won't be offered further updates while its database is below these versions, and sees a notice in Joomla Update instead.


## NOTES ON MYSQL & MARIADB
For Joomla 3.x to work flawlessly with MySQL versions 8.0 or newer, you need to have this setting enabled in your my.cnf configuration:
```
# For MySQL 8.0 only
default_authentication_plugin = mysql_native_password

# For MySQL 8.4+
mysql_native_password         = ON
authentication_policy         = mysql_native_password
```
Use one or the other, not both. The above settings do not apply to MariaDB.

We also recommend the following setting for maximum compatibility in both MySQL and MariaDB:
```
sql_mode = ""
```

## NOTES ON OPERATING SYSTEM SUPPORT
This distribution is built solely for Linux/BSD based systems, cause let's be honest, you'll be hosting this on some Linux/BSD flavour, not Windows or macOS. As such, we don't test on Windows or macOS. Things ***should*** work just fine if you use something like XAMPP or MAMP respectively, but just know that we don't test against these two operating systems.


## CONTRIBUTE
If you'd like to contribute meaningful upgrades to existing functionality or fix bugs, feel free to open an issue in this project.


## DISCUSS
The discussion forum is now open: https://github.com/joomlaworks/joomla-3.x/discussions

Use it to report bugs with this distribution of Joomla 3.x only - this includes functional bugs for any existing feature in Joomla 3.x itself.


## CHAT WITH THE CODEBASE (AI assisted)
Ask/search/chat with the project's codebase using one of the options below:

- [![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/joomlaworks/joomla-3.x) - Powered by Devin
- [![zread](https://img.shields.io/badge/Ask_Zread-_.svg?style=flat&color=00b0aa&labelColor=000000&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQuOTYxNTYgMS42MDAxSDIuMjQxNTZDMS44ODgxIDEuNjAwMSAxLjYwMTU2IDEuODg2NjQgMS42MDE1NiAyLjI0MDFWNC45NjAxQzEuNjAxNTYgNS4zMTM1NiAxLjg4ODEgNS42MDAxIDIuMjQxNTYgNS42MDAxSDQuOTYxNTZDNS4zMTUwMiA1LjYwMDEgNS42MDE1NiA1LjMxMzU2IDUuNjAxNTYgNC45NjAxVjIuMjQwMUM1LjYwMTU2IDEuODg2NjQgNS4zMTUwMiAxLjYwMDEgNC45NjE1NiAxLjYwMDFaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00Ljk2MTU2IDEwLjM5OTlIMi4yNDE1NkMxLjg4ODEgMTAuMzk5OSAxLjYwMTU2IDEwLjY4NjQgMS42MDE1NiAxMS4wMzk5VjEzLjc1OTlDMS42MDE1NiAxNC4xMTM0IDEuODg4MSAxNC4zOTk5IDIuMjQxNTYgMTQuMzk5OUg0Ljk2MTU2QzUuMzE1MDIgMTQuMzk5OSA1LjYwMTU2IDE0LjExMzQgNS42MDE1NiAxMy43NTk5VjExLjAzOTlDNS42MDE1NiAxMC42ODY0IDUuMzE1MDIgMTAuMzk5OSA0Ljk2MTU2IDEwLjM5OTlaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik0xMy43NTg0IDEuNjAwMUgxMS4wMzg0QzEwLjY4NSAxLjYwMDEgMTAuMzk4NCAxLjg4NjY0IDEwLjM5ODQgMi4yNDAxVjQuOTYwMUMxMC4zOTg0IDUuMzEzNTYgMTAuNjg1IDUuNjAwMSAxMS4wMzg0IDUuNjAwMUgxMy43NTg0QzE0LjExMTkgNS42MDAxIDE0LjM5ODQgNS4zMTM1NiAxNC4zOTg0IDQuOTYwMVYyLjI0MDFDMTQuMzk4NCAxLjg4NjY0IDE0LjExMTkgMS42MDAxIDEzLjc1ODQgMS42MDAxWiIgZmlsbD0iI2ZmZiIvPgo8cGF0aCBkPSJNNCAxMkwxMiA0TDQgMTJaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00IDEyTDEyIDQiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K&logoColor=ffffff)](https://zread.ai/joomlaworks/joomla-3.x) - Powered by Z.ai/GLM


## LONGTERM PLAN (as a different project)
A new fork is on the way, based on Joomla 3.x. This fork is WIP (but very, very active) and when released it will feature:
- A stripped down version of Joomla 3.x with all non-essential extensions removed.
- Fully compatible with PHP versions from 7.4 to 8.x and so on.
- Fully compatible with the latest versions of MySQL & MariaDB.
- The focus shifts to using K2 for content. This means that com_content (and anything related) is removed entirely. This way important content features are decoupled from the CMS base, which aims to be a solid platform for building sites, while maintaining true backwards compatibility with past releases (of the fork).
- Admin refresh.
- Gradual jQuery/Mootools removal - switch to modern JS only.
- Gradual codebase modernization to support future PHP & MySQL/MariaDB versions without much effort.

## LEGAL
Joomla 3.x UTD is an independent community project. It is not affiliated with, endorsed by, or supported by Open Source Matters, Inc. or The Joomla! Project™.

The Joomla!® name and logo are trademarks of Open Source Matters, Inc. in the United States and other countries. This project does not use the Joomla! logo.

This distribution is free software, released under the GNU General Public License version 2 or later. See LICENSE.md.

Original Joomla! code: Copyright &copy; 2005 - 2025 Open Source Matters, Inc.

Modifications in this distribution: Copyright &copy; 2026 JoomlaWorks Ltd. and this project's contributors
