# CHANGELOG

## Version 3.16 - released October 3rd, 2026
### Security fixes
- Cross-referenced the Joomla 6.1.4/5.4.9 security release (September 2026, 16 items) against this codebase using the real upstream diffs, and backported 10 confirmed-applicable fixes. Several were listed upstream as "Joomla 4.0 and later" but the vulnerable code is present in 3.x:
  - **Unauthorized account creation via `profile.save`:** the frontend profile controller never checked that the caller was logged in. For a guest, the model saved a brand-new, unblocked `JUser`, even with user registration disabled. Guests now get a 403.
  - **Arbitrary directory deletion via cache purge:** Cache Management's delete action accepted group names such as `../../images`, and the file cache only checked that the target path *started with* the cache folder without resolving `..`. Folder deletion now resolves the real path and refuses anything outside the cache folder, and cache file paths reject traversal in the group name. Reproduced first: the old code really deleted a folder outside the cache.
  - **Two XSS filter bypasses in `InputFilter`:** `java&NewLine;script:` (an HTML5-only entity) and the unterminated `java&#10script:` both got through the attribute filter untouched (confirmed by running the real filter). Values are now decoded the way a browser does (HTML5 entity table, before lowercasing, including unterminated numeric references), `&newline;` is a blocked character, and tabs are stripped from attribute values. Done without `mb_chr()` to stay compatible with PHP 7.1. The related upstream "tab in a `data:text/html` URI" bypass was already blocked by this distribution's broader `data:` URI rule.
  - **Tagged items leaking from restricted categories:** tag views, "Tags - Popular" and "Tags - Similar" only checked each item's own access level, not its category's. All three now require an accessible category ("Tags - Similar" also requires a published one).
  - **Content history comparison ACL:** only the first of the two compared versions was access-checked, so a version of an item the user can't edit could be compared against one they can. Both are now checked.
  - **XSS in `JHtml::link()` / `JHtml::iframe()`:** URLs, iframe names and attribute arrays are now escaped (without double-encoding values that are already escaped), and invalid attribute names are dropped.
  - **XSS in the module manager list:** module positions were echoed unescaped in two places.
  - **XSS in the toolbar link button:** in 3.x the URL is written into an inline `onclick` JavaScript string, a worse context than upstream's `href`. It is now JSON-encoded and then attribute-escaped.
  - **Generic image layout attribute names:** the earlier CVE-2026-48953 fix escaped values but still allowed any attribute *name*. Invalid names are now dropped, matching upstream.
  - **SSRF through URL fields:** the news feed URL, both feed modules' URL, the SEF plugin's site domain and the user profile website now only accept `http`/`https`. The Joomla Update custom URL, previously unvalidated, now only accepts `http`/`https`/`ftp`/`ftps`.
- Smaller hardening from the Joomla 6.1.4/5.4.9 release: the Template Manager's file/folder delete actions reject `..` in the target path; the banner tracks CSV export guards against spreadsheet formula injection; `JUri::isInternal()` rejects URLs containing quotes, spaces or angle brackets; the bundled FOF library's TOTP check now compares in constant time (codes passed as integers with lost leading zeros still match, as before); and `php6`/`php9`/`pht` were added to the upload executable-extension lists where missing.
- Cross-referenced the Joomla 6.1.3/5.4.8 security release (August 2026) against this codebase and backported 4 confirmed-applicable fixes: HTTP response header injection via an unescaped filename in the com_banners click-tracking download and com_contact vCard export (a `"` in a banner's filename or a contact's name could break out of the `Content-Disposition` header); missing per-item `core.edit` checks in the com_categories and com_fields batch-copy operations, letting a user with copy access but no edit rights on a specific source item duplicate it anyway, plus a matching component-context validation gap in com_fields' own edit-permission check; `.shtml`/`.shtm`/`.sht`/`.stm` missing from the Template Manager's executable-extension upload blacklist (low practical risk today, since a separate whitelist already excludes them by default, but now matches upstream). Also fixed an unrelated stored-XSS gap found in the same pass: com_contact's default view echoed the contact name, job title, and category title into public-facing schema.org microdata completely unescaped.
- Confirmed not applicable: access-level and edit-task webservice ACLs, and workflow stage changes (3.x has no API application or com_workflow); HTML mail templates (3.x has no mail template system and sends plain-text mail); the MFA bypass via "Remember Me" cookies (in 3.x the two-factor code is checked during login itself, before the cookie can be issued).

### New features & additions
- Added a new system plugin, "Little WAF" (`plg_system_littlewaf`), that filters known attack signatures against abandoned or historically vulnerable third-party extensions at the earliest possible point in the request lifecycle — before routing or any component/plugin runs. Added after confirming real-world active exploitation of an unpatched Sourcerer install (CVE-2026-74253, fixed upstream in Sourcerer 14.0.0). Ships with two filters, each named after and individually toggled for the extension it targets: blocking Sourcerer-style `{source}...{/source}` URL-based PHP injection attempts (verified against a real observed attack pattern), and, as a precautionary measure, Modules Anywhere-style `{module ...}`/`{modulepos ...}` tags (Regular Labs patched a different vulnerability class — SSRF/XSS/restricted-content exposure — in this extension the same week as the Sourcerer fix). More filters will be added over time as similar patterns are identified. The plugin itself is off by default; once enabled, every filter is on by default, since enabling the plugin is itself the explicit opt-in. Its ordering is set far ahead of every other system plugin so an enabled filter always runs first, before page caching or anything else could short-circuit the request. Existing sites get the plugin added (disabled) automatically on update, via the same idempotent DML-migration mechanism used for prior data-only migrations.
- To bring uninstalled core extensions back, Joomla Update's "Reinstall Joomla core files" and "Upload & Update" screens have a new "Restore uninstalled core extensions" option. It reinstalls them through Joomla's own installer, so their database tables, administrator menu items and permissions are recreated. It also restores their default enabled/disabled state, and puts back the default placement of administrator modules (such as the administrator menu) that have none. Regular updates and reinstalls keep uninstalled core extensions removed.
- Joomla Update now checks the site's database type and version before offering an update, using the `<supported_databases>` tag in a new update details file (`docs/extension-db.xml`) that only sites on 3.16 or newer read. Sites below the minimum versions (see "Improvements" below) get a notice instead of the update. Sites on older versions are still offered 3.16 exactly as before, and the requirements apply from their next update onwards.
- Documented database support honestly in the README: MySQL and MariaDB are tested; PostgreSQL and SQL Server are inherited from stock Joomla 3.x and not tested by this project.

### Improvements to existing features
- Joomla Update no longer brings back core extensions the site admin has uninstalled. The update package always contains every core extension, so after uninstalling e.g. Banners, the next update put all its files back. They weren't installed again, but Extensions > Discover offered them for installation, and once Discover had run, every later update showed a "Refresh Manifest Cache failed: ... is not currently installed" warning for them. The update now deletes the re-extracted files of any uninstalled removable core extension, and any leftover "discovered" placeholder for it. This covers every core extension the Extensions Manager lets you uninstall: Banners, Contacts, News Feeds, Search, Smart Search, Content History, Multilingual Associations and Fields (with all their modules and plugins), plus the standalone site and administrator modules, plugins and the Protostar/Isis templates. Each is checked on its own, so partially removed sets are handled correctly. Weblinks was already handled. Extensions that are new to the version being updated from are still added as usual, because their database records are created by the update itself before this check runs. Only what an uninstall itself removes is deleted again, so leftover media folders that an uninstall keeps are left alone.
- Raised the minimum database versions to MySQL 5.5.3 (the first MySQL 5.5 release with `utf8mb4`; every production 5.5 release qualifies), MariaDB 5.5 and PostgreSQL 9.0, all from around 2010. SQL Server stays at 2008 R2, which is from the same period. The installer enforces them, and its messages were updated in all installation languages. Two Serbian translations wrongly said "MySQL" in the PostgreSQL message; corrected.
- Broadened the update channel to match any Joomla 3.x installation, not just this distribution's own 3.10+ lineage — schema migrations go back to Joomla 2.5.0, so earlier stock 3.x sites can safely jump straight to the current version, the same as stock Joomla's own supported upgrade paths.
- Removed the APC (not APCu), Memcache (not Memcached), XCache, and Cache_Lite cache/session drivers. None of the PHP extensions behind APC/Memcache/XCache are installable on PHP 7.1+ (this distribution's minimum supported version); Cache_Lite is an unbundled PEAR package Joomla itself had already marked deprecated with no replacement, whose last release predates any realistic PHP 7 testing. Selecting any of these was already guaranteed non-functional. Sites upgrading from an older release get the leftover files cleaned up automatically. WinCache was considered but kept, since it still has genuine PHP 7.x support (Microsoft continues to maintain it for IIS-hosted PHP). eAccelerator support was already removed by upstream Joomla years ago; only two unused, already-orphaned language entries for it remained, which were also cleaned up.
- Removed the legacy `mysql` database driver (plus its query, iterator, exporter and importer classes, the deprecated `JDatabaseMysql` alias and FOF's equivalents). It relied on PHP's `mysql_*` functions, which were removed in PHP 7, so it couldn't run on any PHP version this distribution supports. Sites still configured with `$dbtype = 'mysql'` are unaffected: Joomla already switches them to the `mysqli` driver automatically. Upgrading sites get the old files removed.
- Added a plain-text copy of the license (`LICENSE.txt`, identical to `LICENSE.md`). It's no longer deleted during updates, and a misspelled `LISENSE.txt` copy that briefly appeared in the repository is removed from upgraded sites.
- The Memcached cache and session driver is no longer labelled "(Experimental)", after fixing the locking bugs that were the only concrete defects found in it (see "Bug fixes" below).

### Bug fixes
- Fixed updates failing with "Table '…_banners' doesn't exist" (MySQL error 1146) on sites that had uninstalled Banners, Contacts, News Feeds or Smart Search, which drop their database tables when uninstalled. The update's database check re-applied old database changes to those deleted tables and crashed. Database changes for the tables of an uninstalled core component are now skipped, both during updates and on Extensions > Manage > Database (which listed the same errors, and whose Fix button failed the same way). Core changes that only mention those tables in their data still run.
- Brought the News Feeds and Smart Search install SQL in line with the current database structure, so restoring them creates up-to-date tables. Smart Search's PostgreSQL install SQL was rebuilt from the main PostgreSQL install script: it was years out of date and contained MySQL-only index syntax, so installing Smart Search from it on PostgreSQL had never worked.
- Uninstalling Banners, Contacts or News Feeds no longer leaves a stray "com_..._categories" entry under Components in the backend menu. The stock Categories sub-entries of these three components were registered to the Categories component instead of their own, so uninstalling the component left them behind. They're now registered to their own component on new installs and corrected on existing sites during the update. Entries already left behind by an earlier uninstall are removed.
- Fixed the update's final cache cleanup doing nothing on sites using the Redis, Memcached, APCu or WinCache cache handlers (it only worked with the File handler). Those sites were left with stale cached data after every update, including the list of installed components, which could hide newly installed or restored components from the administrator menu. Every cache group is now cleared individually, the same way System: Clear Cache's "Delete All" does. If the cache can't be cleared, for example because the Redis or Memcached server can't be reached, the update now shows a warning suggesting to clear it manually instead of failing silently.
- Fixed Joomla Update's handling of the `<supported_databases>` tag in update feeds. Database types were looked up in lower case, while the XML parser reports attribute names in upper case, so any feed using the tag would have stopped every site from being offered the update. (Stock Joomla 3.x bug; it never surfaced because no update feed used the tag.)
- Fixed a dormant versioning bug in the update-site manifest (`docs/extension.xml`) that would have silently stopped this distribution from offering any further updates once a site reached version 3.20.0, with no error or symptom until someone noticed updates had stopped.
- Fixed two real bugs in the update-extraction engine behind "the archive file is corrupt" false positives when manually uploading an update package: it read large chunks of the archive in a single call without verifying it actually got the full amount back (PHP doesn't guarantee that on every storage backend), and it tested the wrong bitflag bit when deciding whether a ZIP entry uses a trailing data descriptor, which could wrongly desync header parsing on perfectly ordinary zips depending on compression settings. Both verified against real multi-megabyte/crafted packages: reproduced the exact failures on the old code and confirmed each fix resolves it without regressing standard zip files. A separate, larger issue remains open: zips built with tools that stream file data (e.g. GNOME Files' "Compress" action, or Windows 11 24H2+'s native "Compress to ZIP") use placeholder sizes in the local file header that this parser doesn't yet resolve via the central directory — manual testing with a standard `zip`-created archive is unaffected.
- Fixed real bugs in the Memcached cache driver's internal locking, found while investigating why it's labelled "Experimental": `flush()` never released its internal lock, and `store()`/`remove()`/`clean()` could leave it stuck if interrupted mid-operation. Both were capable of stalling the entire site's cache for up to 30 seconds. The driver now recovers correctly from these situations.
- Fixed fatal errors on PHP 8 hosts that disable `php_uname()` (common on shared hosting) in System Information, both Statistics modules, the system Statistics plugin and the bundled PHPMailer's hostname fallback.
- Silenced a large batch of PHP 8.1/8.2/8.5 deprecation warnings that could clutter logs on newer PHP versions, found via static review plus a real production error log from a live PHP 8.5 site: old-style `Serializable` implementations missing their modern counterpart methods, dynamic property assignments across `com_config`, the legacy component routers, the admin menu/toolbar/document classes, and the bundled FOF library, `null` values being passed where a string was expected in several core functions (including the core translation function and the browser-detection helper), and a now-redundant `ReflectionProperty::setAccessible()` call. All confirmed to be cosmetic (log-noise) fixes with no behavior change, each verified individually against the actual code path that triggered it — several driven end-to-end against the real patched files, not just reasoned about statically.
- Swept the codebase for PHP 7.4–8.5 deprecation warnings using a carefully curated set of automated fixes (805 files), on top of several fixes found individually from real production logs (`com_config`'s Global Configuration/Component Options views, the WYSIWYG editor save/display logic, and the menu manager). Every automated fix was individually verified to keep working on PHP 7.1 and up — not just to silence the warning on newer PHP — and the full set was checked against the real codebase before being applied, not just trusted at face value; one rule that looked like a match for earlier work was deliberately excluded because it would have broken reflection-based access to private/protected members on PHP 8.0 and below. All cosmetic (log-noise) fixes with no behavior change; confirmed with a clean lint pass across every changed file.

---

## Version 3.15 - released July 18th, 2026
Summary of changes:
- Backported 13 confirmed-applicable CVEs from the official Joomla security advisories feed (32 advisories reviewed in total, across May 26 and July 7, 2026). Three were labelled "affects 4.0.0+ only" (or similar) upstream but independently confirmed to affect 3.x by inspecting the actual vulnerable code, rather than trusting the advisory's stated range.
- Confirmed 2 further CVEs (CVE-2026-48905, CVE-2026-48903) are already fully covered by this project's own prior hardening — no new gap.
- Confirmed 4 further CVEs are not applicable, as the vulnerable plugins/UI screens/code paths they target don't exist in this version of Joomla.
- Also resolved a number of functional and compatibility issues, several reported directly by the community — see "Functional & Bug Fixes" below.

---

## Security Patches

In detail, from most to least severe:

**CVE-2026-40383 — Local file inclusion via view layout parameter (High)**
- `libraries/src/MVC/Controller/BaseController::display()` reads the `layout` request parameter through the permissive `string` filter (not `cmd`), and `HtmlView::setLayout()` splits it on `:` and stores the pre-colon segment as the template-override directory with no sanitization. `loadTemplate()` then substitutes that unsanitized string directly into the template search path before `include`-ing whatever `JPath::find()` resolves — allowing path traversal into local file inclusion.
- Fixed by restricting the template-override segment in `HtmlView::setLayout()` to `[A-Za-z0-9_-]` before it's stored, closing the traversal at the source.

**CVE-2026-352212 — SQL injection in com_tags (Moderate, High impact)**
- `components/com_tags/models/tags.php`'s `getListQuery()` concatenated the "all tags" list's order-direction component parameter directly into `$query->order(...)` with no validation. The sibling single-tag model already whitelists the equivalent value against `ASC`/`DESC` — this second model was missed.
- Fixed by adding the same whitelist check before the value reaches the query.

**CVE-2026-48954 — Stored XSS via language overrides (Moderate)**
- Overridden language strings are rendered via `JText::_()` **unescaped** almost everywhere in Joomla core and third-party extensions (by long-standing convention), including inside hardcoded HTML attributes such as `title="<?php echo JText::_('KEY'); ?>"` (a real example: `com_tags`'s admin tag list). A quote character in an override value breaks out of that attribute. `InputFilter` doesn't stop this, since it only sanitizes HTML *tags* found inside the override text — a bare `x" onmouseover="..."` payload has no `<`/`>` for its tag parser to notice. This is a low-effort privilege-escalation path for any account with delegated `com_languages` edit rights short of Super User (e.g. a "Translator" ACL role): plant the payload once, and it fires for any admin who later visits an ordinary screen rendering that string.
- Fixed with a new 3.x-native form rule: `administrator/components/com_languages/models/rules/languageoverridequotes.php` (`JFormRuleLanguageoverridequotes`, following the existing `com_users` `loginuniquefield` rule pattern), wired via `addrulepath`/`validate`/`message` on `administrator/components/com_languages/models/forms/override.xml`'s `override` field, plus a new language string. Rejects `"`/`'` in override text unless the submitting user has `core.admin`.

**CVE-2026-48950 — XSS in com_templates file manager (Moderate) — fixed, broader in 3.x than upstream**
- The `file` GET parameter is base64-encoded and decoded server-side to resolve a template source/image/font file path. `cmd`-filtering the outer base64 string does **not** constrain what the decoded bytes contain — a `cmd`-safe input can still decode to a full `<script>` tag (verified: `base64_decode('PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg')` → `<script>alert(1)</script>`).
- `administrator/components/com_templates/models/template.php` has this exact bug in **three** places — `getSource()`, `getImage()`, and `getFont()` each independently `base64_decode()` the request parameter with zero subsequent filtering — while upstream's own fix only addressed the "source" case. Fixed all 6 corresponding echo sites (file/image/font × 2 each) in `views/template/tmpl/default.php` with `$this->escape()`.

**CVE-2026-48953 — XSS in the generic image output layout (Moderate)**
- `layouts/joomla/html/image.php` only escaped the `src` and `alt` attributes before handing the full attribute array to `ArrayHelper::toString()`, which concatenates every other key (`title`, `class`, `data-*`, etc.) into `key="value"` with no escaping. Not triggered by core's own templates today, but it's a public layout API third-party extensions call directly with arbitrary attributes.
- Fixed by escaping every scalar attribute value in the array before rendering, not just `src`/`alt`.
- Note: advisory listed this as "4.0.0+ only" — the vulnerable file and pattern are identical in 3.x; backported anyway.

**CVE-2026-48952 — XSS in com_installer update list view (Moderate)**
- `administrator/components/com_installer/views/update/tmpl/default.php` echoed `client_translated`, `type_translated`, `current_version`, `version`, `folder_translated`, `install_type`, and `detailsurl` raw — all sourced from third-party extension update XML feeds, which can be malicious or MITM'd. The `infourl` link's `href` attribute was also unescaped (only the link text was).
- Fixed by wrapping all of the above in `$this->escape()`.

**CVE-2026-25901 — XSS in com_associations (Moderate) — additional gap beyond the earlier 3.12 fix**
- `administrator/components/com_associations/views/association/tmpl/edit.php`: a prior session's CVE-2026-21631 fix only escaped 3 of the 8+ dynamic `data-*` attributes on this template's two iframes (`referenceTitle`, `referenceTitleValue`, `targetTitle`). This is a *different* CVE covering the rest: `data-associatedview`, both `data-item` (`typeName`), both `data-id`, both `data-language`, and `data-action` on the target iframe.
- Fixed by escaping all of the above.

**CVE-2026-30894 — XSS in com_contenthistory preview (Moderate)**
- `administrator/components/com_contenthistory/views/preview/tmpl/preview.php` echoed the content-history version note and field values raw.
- Fixed by escaping all three.

**CVE-2026-25900 — XSS in feed modules (Moderate)**
- `modules/mod_feed/tmpl/default.php` and `administrator/modules/mod_feed/tmpl/default.php` echoed feed title, image `src`/`alt`, and item title/link raw or under-escaped (content sourced from external, untrusted RSS feeds). The admin variant's link was worse than upstream's own pre-fix state — it only escaped `&`, not `<`/`>`/`"`.
- Fixed by properly escaping all feed-sourced values (`ENT_QUOTES`) in both files. Feed descriptions deliberately left unescaped, matching upstream — they're treated as trusted HTML by design.

**CVE-2026-30895 — XSS in readmore links (Moderate)**
- `layouts/joomla/content/readmore.php` and `modules/mod_articles_category/tmpl/default.php`: `JHtml::_('string.truncate', $item->title, ...)` defaulted to `$allowHtml = true`, preserving (not stripping) any embedded HTML/script in an article title before echoing the truncated readmore link text.
- Fixed by passing `$allowHtml = false` at all 7 call sites across both files (found via a codebase-wide grep for the vulnerable pattern, since upstream's own fix only covered one of our two affected files under a different template name).

**CVE-2026-48902 — Password/username reset links downgraded to plain HTTP (Low)**
- `components/com_users/models/{reset,remind}.php` built the confirmation link with `$mode = force_ssl == 2 ? 1 : (-1)` — `-1` legacy-maps to `Route::TLS_DISABLE`, forcing `http://` unless "Force SSL: Entire Site" was explicitly enabled, even on sites served entirely over HTTPS via a reverse proxy/CDN. This sent the reset token in cleartext.
- Fixed by changing the fallback from `-1` (force HTTP) to `0` (`Route::TLS_IGNORE` — use the current request's scheme), only forcing HTTPS explicitly when Force SSL is on.

**CVE-2026-48901 — Incorrect cache key construction for InputFilter instances (Low)**
- `libraries/src/Filter/InputFilter::getInstance()` built its instance-cache signature from `($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)`, omitting the security-sensitive `$stripUSC` parameter. A caller requesting a filter with `$stripUSC` disabled could silently receive a cached instance created by an earlier caller with it enabled (or vice versa).
- Fixed by adding `$stripUSC` to the cache-key signature.
- Note: also listed as "4.0.0+ only" upstream; the exact same code (with the same bug) is present in 3.x — backported anyway.

**CVE-2026-48948 — Access control bypass in com_contact vCard export (Low)**
- `components/com_contact/views/contact/view.vcf.php` (the `&format=vcf` export) never performed the access-level check that `view.html.php` does, allowing anyone to download the vCard of a contact restricted to a non-Public access level (name, email, phone, address) via a direct URL.
- Fixed by adding the same `$item->access`/`$item->category_access` vs. `getAuthorisedViewLevels()` check used in the HTML view, returning a 403 before building the vCard.

**Already covered by earlier hardening — no new gap:**
- **CVE-2026-48905** (XSS via `data:text/html` URIs in the attribute filter) — blocked via the existing **S-1 (CVE-2025-63082)** hardening in `checkAttribute()` (`libraries/vendor/joomla/filter/src/InputFilter.php`), which rejects *all* `data:` URIs except safe base64 images — a superset that also covers `data:text/html`. Verified against the official upstream test case. (Also hardened `checkAttribute()`/`cleanAttributes()` to fully resolve nested/double-encoded HTML entities before pattern-matching, closing a related bypass class as defense-in-depth.)
- **CVE-2026-48903** (checkAttribute filter code) — the official fix adds `\r` to the whitespace-stripping list in `checkAttribute()`. This codebase already strips `\r` — plus `\v` and `\f`, which the official fix doesn't cover — via the **H-1** hardening pass from a prior release.

**Confirmed not applicable, contrary to surface-level pattern matches:**
- **CVE-2026-48899** (sample data plugins ACL) — 3.x has only the `blog` sampledata plugin; all 3 of its steps already combine `isEnabled()` + `authorise('core.create', ...)` checks, predating this CVE. The other two vulnerable plugins (`multilang`, `testing`) don't exist in 3.x.
- **CVE-2026-48951** (modalreturn layouts XSS) — the vulnerable `modalreturn.php` files (a Joomla 4/5-only post-modal-save confirmation page) don't exist anywhere in 3.x, confirmed by a repo-wide filename search.
- **CVE-2026-35221** (com_finder SQLi) — 3.x has the same-*looking* array key/value pattern the upstream fix changes, but tracing every consumer of the affected data structure confirms 3.x's actual query-building code only ever uses the array's values (already safely cast to int), never the keys — the vulnerable path doesn't exist here. (Advisory's own range starts at 5.4.0 and doesn't claim to affect 3.x either.)
- **CVE-2026-35220** (CSRF in admin activation endpoint) — advisory range (6.0.0–6.1.0 only) confirmed accurate; the affected dispatcher architecture doesn't exist in 3.x.

**Not backported from this review round:** 13 further advisories confirmed not applicable — they depend on the Joomla 4.0+ webservices/API layer, com_scheduler, com_workflow, or the 4.2+ MFA redesign, none of which exist in 3.x.

---

## Functional & Bug Fixes

**Modules set to "Use Global" caching ignored Global Configuration's Cache Time**
- Any module (mod_menu and 25 other core modules) with its Caching option set to "Use Global" actually used whatever value sat in its own, separate "Cache Time" field instead of Global Configuration's Cache Time — and that field ships with (and, due to a related quirk, keeps reverting back to whenever cleared) a default of 900 seconds/15 minutes, so in practice "Use Global" almost always meant a fixed 15-minute refresh, regardless of what Global Configuration was actually set to (e.g. 3 minutes).
- Root cause: every core module's "Caching" field is a simple Use Global/No Caching choice, but the underlying `cache_time` parameter ships with a default value that gets permanently saved onto the module the first time it's saved through the Module Manager (or on fresh install, for default sample modules like the Main Menu) — silently overriding the intended fallback to Global Configuration.
- Fixed centrally in `ModuleHelper::moduleCache()` so "Use Global" now always honors Global Configuration's Cache Time outright, for every core (and any similarly-structured third-party) module — regardless of whatever value happens to be stored in the module's own `cache_time` field. Affected sites are fixed automatically on upgrade — no manual database changes needed.
- Added an explicit "Use custom cache time" option to the Caching field on all 26 core modules that support caching (including mod_whosonline, which now also supports Global/custom caching for the first time), so overriding a single module's cache TTL is now a clear, self-documenting choice rather than an unlabelled number field with no explanation of when it takes effect. The Cache Time field only appears once this option is selected.
- Fixed a follow-up bug from the change above: modules set to "Use custom cache time" were never actually cached at all, on any real page load. The module-caching logic itself was correct, but the site's actual per-module page renderer only ever attempted caching for modules explicitly set to "Use Global", so the new custom option silently fell through to always rendering live. Fixed by correcting that check so any enabled caching mode (not just "Use Global") is honored.

**Added "sort by Author" to Extensions > Manage**
- The Author column was already shown on Extensions > Manage but couldn't be sorted, making it hard to group third-party extensions apart from Joomla core ones (e.g. when auditing a large site for stale/abandoned extensions).
- The column header is now clickable like the others, and "Author" is available in the sort-by dropdown, both ascending and descending.

**Misleading "Refresh Manifest Cache failed" warning on every update**
- Sites that have deliberately removed a stock core extension (e.g. the `protostar` template, or `com_banners`/`mod_banners`) saw a "Refresh Manifest Cache failed: X Extension is not currently installed" warning for each one, on every single future Joomla Update run — even though nothing was actually wrong.
- `administrator/components/com_admin/script.php`'s `updateManifestCaches()` now skips extensions already flagged with `state = -1` before attempting to refresh their manifest cache, instead of re-triggering the same known, non-actionable warning every time.

**beez3/hathor never actually removed from the database on upgrade (GitHub issue #7)**
- On some sites upgrading from an earlier Joomla 3.x release, the removed `beez3` (frontend) and `hathor` (backend) templates remained listed in Extensions → Templates, and clicking either threw a PHP error (`uksort(): Argument #1 ($array) must be of type array, false given`).
- Root cause: the SQL migration that removes their database rows is pure data-cleanup (`UPDATE`/`DELETE`), and Joomla's schema-migration runner only knows how to detect and apply structural (`ALTER TABLE`/`CREATE TABLE`) changes — it silently treats anything else as "nothing to do" while still marking the migration as applied. On an affected site, the leftover row's `manifest_cache` can end up set to the literal string `"false"` on a subsequent update, once the template's files are no longer on disk to read — which is what the PHP error above stems from.
- Fixed by executing this distribution's own data-cleanup migration files directly, before the manifest-cache refresh step runs, on every update — bypassing the schema-migration runner for these files entirely, since it was never designed to run this kind of statement. Written to be safely re-run every time, so sites already affected by this (like the reporter's) will self-heal automatically on their next update — no manual database cleanup needed.

**`lcg_value()` deprecated in PHP 8.4 (GitHub issue #12)**
- `plugins/system/sessiongc/sessiongc.php` used the now-deprecated `lcg_value()` (twice) to implement its "1 in 100 chance" garbage-collection trigger, throwing a deprecation notice on PHP 8.4+.
- Fixed by replacing it with an equivalent-probability check using `random_int()` instead — available since PHP 7.0, not deprecated on any supported version. (The deprecation notice's own suggested replacement, `\Random\Randomizer::getFloat()`, needs PHP 8.2+, which is newer than this distribution's recommended PHP baseline, so it wasn't an option here.)

**Literal `"_QQ_"` text appearing instead of quote marks, throughout the admin UI**
- `_QQ_` is a legacy placeholder Joomla language files used in place of a literal double quote, dating back to a PHP 5.2 `parse_ini_file()` bug — long irrelevant given this distribution's modern PHP floor. It was officially deprecated in Joomla 3.9.0 in favour of a real escaped quote (`\"`), but the fallback that was supposed to convert old `_QQ_` usages back into a real quote at load time only runs in a code path that's essentially never used in practice — so on a normal install, `_QQ_` was rendered completely literally, unconverted. Reported symptom: the "Add Install from Web tab" notice on Extensions > Install (shown whenever the web-installer plugin is missing) displayed literal `"_QQ_"` sequences instead of quotation marks, among other places.
- Found via a repo-wide scan: 80 language files, 1436 occurrences, both in the core English UI and in several bundled non-English installation-language packs. Fixed all of them, converting to the proper `\"` escaped-quote form. Verified every `.ini` file in the repository (477 total, not just the 80 changed) still parses correctly.

**Minimum PHP version compatibility gate was wildly out of date**
- `index.php`, `administrator/index.php`, and `installation/index.php` each define `JOOMLA_MINIMUM_PHP` and use it to show a friendly "Your host needs to use PHP X or higher" message before anything else runs. It was still set to the original stock-Joomla value, `5.3.10`, from over a decade ago — far below what this codebase can actually run on. This distribution's own PHP 8.1-compatibility work added nullable type declarations (`?Type $param`) throughout the codebase, which require PHP 7.1 at minimum — a site on PHP 5.6 or 7.0 wouldn't get the friendly error message at all, since the outdated check let it through, and it would hit a raw, unhandled PHP parse error instead.
- Fixed by raising `JOOMLA_MINIMUM_PHP` to `7.1.0` — the codebase's actual, verified floor — in all three entry points, so unsupported hosts now get a clear, actionable message instead of a blank/broken page.
- Also lowered the update feed's own `<php_minimum>` requirement (which separately controls whether "Update Now" even offers this release) from `7.4.0` to `7.1.0` to match. **PHP 7.4+ remains this distribution's recommended production baseline** — it's broadly available across modern hosting (e.g. AlmaLinux 8 with cPanel, or Ubuntu 22.04+ with Ondřej's PHP repositories) and is where we focus testing — but sites on PHP 7.1–7.3 shouldn't be denied security patches just because they haven't upgraded PHP yet. They'll now see and can install updates same as anyone else.

**Housekeeping: removed `README.txt`, renamed `LICENSE.txt` to `LICENSE.md`**
- `README.txt` was unedited stock upstream Joomla boilerplate, fully superseded by this project's own `README.md`; removed outright.
- `LICENSE.txt` renamed to `LICENSE.md` for consistency with the rest of the project's documentation files.
- Both changes are reflected in the core file manifest, and sites upgrading from an older release will have the old files cleaned up automatically — no manual action needed.

---

## Version 3.14 - released July 4th, 2026
Summary of changes:
- Fixed two PHP 8.5 deprecations reported via [PR #13](https://github.com/joomlaworks/joomla-3.x/pull/13) (contributed by [@raramuridesign](https://github.com/raramuridesign))
- Updated the en-GB language file version metadata to match the actual running version, fixing false-positive "outdated Joomla" flags from vulnerability scanners that read these files

In detail:
- Fixed PHP 8.5 deprecation "Using null as an array offset" in `HtmlDocument::getBuffer()` / `setBuffer()` — `$name`/`$title` are frequently `null` (e.g. modules rendered without a title) and were used directly as array keys into the internal render buffer; `getBuffer()` now normalizes them to `''` before indexing, and `setBuffer()` normalizes `$options['type']`/`['name']`/`['title']` with `?? ''` before storing
- Fixed PHP 8.5 deprecation of `imagedestroy()` in `Image::destroy()` and `Backgroundfill::execute()` — the function has had no effect since PHP 8.0 (GD switched from resources to refcounted `GdImage` objects) and PHP 8.5 now deprecates calling it; `Image::destroy()` was changed to `$this->handle = null` (preserves `isLoaded()` behavior), and the redundant call on a local temp handle in `Backgroundfill` was simply removed
- Fixed a stray `(boolean)` → `(bool)` cast in `libraries/vendor/joomla/image/src/Image.php`, missed by the broader PHP 8.5 cast sweep in 3.12
- `administrator/language/en-GB/{en-GB.xml,install.xml}`, `language/en-GB/{en-GB.xml,install.xml}`, and `administrator/manifests/packages/pkg_en-GB.xml` still declared `<version>3.10.20</version>` (unchanged since the original eLTS base). Some scanners fingerprint the Joomla version from these files rather than `JVERSION`; bumped all five to `3.14` (`pkg_en-GB.xml` version simplified from `3.10.20.1` to `3.14`) with an updated `<creationDate>`

---

## Version 3.13 - released May 31st, 2026
Summary of changes:
- The Isis administrator (backend) template now uses CSS view transitions
- All obsolete CSS has been removed from the Isis template
- Further PHP 8.x compatibility fixes, extending coverage to newly reported files
- Fixed: "Database update version does not match CMS version" in Extensions → Manage → Database — SQL schema migrations now run automatically on upgrade
- Fixed: Joomla Update "complete" screen showed the old version number after an upgrade (PHP-FPM OPcache multi-worker issue)

In detail:

**PHP 8.2 — Dynamic property deprecations (community-reported, via GitHub Discussions)**
- Declared `public $registeredurlparams = null` on `CMSApplication` — set dynamically by `BaseController` and the FOF controller in both frontend and backend contexts; triggered PHP 8.2 dynamic property deprecation on every cacheable page request
- Declared `public $itemTags = array()` on `TagsHelper` — assigned in `getItemTags()` without a declaration; triggered PHP 8.2 dynamic property deprecation whenever tags are loaded for an item
- Declared `public $empty = false` and `public $dates` on `FinderIndexerQuery` — both assigned in `__construct()` without a class-level declaration (PHP 8.2)

**PHP 8.1 — null-to-scalar deprecations (community-reported, via GitHub Discussions)**
- Added null guard in `HtmlView::escape()`: returns `''` immediately when `$var === null`, before calling `htmlspecialchars()` — `htmlspecialchars(null, ...)` was deprecated in PHP 8.1 and fires on every null field rendered through any view's `escape()` method
- Added `(string)` cast to `$str` in `utf8_ltrim()`, `utf8_rtrim()`, and `utf8_trim()` in `libraries/vendor/joomla/string/src/phputf8/trim.php` — these receive `null` from upstream callers; PHP 8.1 deprecated passing `null` to the native trim functions
- Added `(string)` cast in `Registry/Format/Json::stringToObject()` before `trim($data)` — `$data` can be `null`; PHP 8.1 deprecated `null` to `trim()`
- Added `(string)` cast in both `strtoupper($value)` calls in `ListModel::populateState()` — `$value` comes from `getUserStateFromRequest()` which returns `null` when the key is absent; PHP 8.1 deprecated `null` to `strtoupper()`
- Added `$date ?? 'now'` guard in `Date::__construct()` before `parent::__construct()` — `DateTime::__construct(null)` is deprecated in PHP 8.1; third-party extensions that call `new JDate(null)` triggered this on every affected page

**PHP 8.0+ — CLI warning: undefined `HTTP_HOST`**
- Added `$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'` guard in `Uri::getInstance()` before building the server URI from `$_SERVER` — `HTTP_HOST` is absent in CLI context; the previously bare `$_SERVER['HTTP_HOST']` access emitted an undefined-key warning that poisoned stdout and cascaded into the fatal "Failed to start the session because headers have already been sent" error for any CLI script that bootstrapped the CMS application

**Update & schema infrastructure fixes**
- Added `fixSchemas()` method to `administrator/components/com_admin/script.php`, called automatically from `update()` on every upgrade: (1) runs `JSchemaChangeset::fix()` to apply all pending SQL migration files from `sql/updates/`; (2) updates `#__schemas` to the latest file version; (3) syncs `manifest_cache.version` for extension_id 700 to `JVERSION` — eliminates the "Database update version does not match CMS version" banner that previously required a manual "Fix" click in Extensions → Manage → Database
- Updated `administrator/manifests/files/joomla.xml`: `<version>` was never updated from its original `3.10.20-elts` value, causing `updateManifestCaches()` to write the wrong version to `manifest_cache` on every upgrade; also updated `<updateservers>` to `https://joomlaworks.github.io/joomla-3.x/list.xml` — prevents upgrades from silently reverting the `#__update_sites` entry back to `update.joomla.org`
- Added 3.12 filesystem cleanup entries to `deleteUnexistingFiles()` in `script.php`: `/templates/beez3`, `/administrator/templates/hathor`, `/plugins/quickicon/eos310`, `/plugins/quickicon/phpversioncheck`, `/media/plg_quickicon_eos310` and their associated language files are now removed from disk on upgrade, closing the gap where the 3.12 migration SQL correctly removed DB records but left the actual files on disk
- Fixed `com_joomlaupdate` post-upgrade "complete" screen showing the old version instead of the new one: on PHP-FPM with OPcache, `opcache_reset()` (called during `finalise()`) only resets the current worker process — the `cleanup()` and `complete` requests can land on different workers that still have the old `Version.php` bytecode cached, leaving `JVERSION` at the pre-upgrade value; `cleanup()` now reads the version from `administrator/manifests/files/joomla.xml` on disk (XML files are never bytecode-cached by OPcache), stores it in `com_joomlaupdate.newversion` session state, and `complete.php` reads from the session instead of `JVERSION`

---

## Version 3.12 - released May 21st, 2026
Summary of changes:
- Built-in update server: sites running 3.12 or newer can now receive updates directly via the Joomla backend updater
- Removed legacy/unused bundled items: `eos310` and `phpversioncheck` quickicon plugins, `beez3` frontend template, `hathor` backend template
- Further PHP 8.x compatibility fixes, extending coverage to previously missed files
- Additional security patches backported from Joomla 4/5/6, partly informed by the [TLWebdesign/Joomla-3-EOL-Security-Fixes](https://github.com/TLWebdesign/Joomla-3-EOL-Security-Fixes) project
- Fixed the getModuleById method in JModuleHelper to correctly return a module's data using its ID.

In detail:
- Added `#[\AllowDynamicProperties]` to `Table` (abstract base), `CMSObject`, and `idna_convert` — suppresses PHP 8.2 dynamic property deprecation across all Table subclasses and all JObject descendants, which intentionally use dynamic properties by design (PHP 8.2; also PHP 9 safe)
- Changed remaining implicitly nullable typed parameters (`Type $p = null` → `?Type $p = null`) in `libraries/fof/` (form header, AES encrypt class and its mcrypt/openssl/interface adapters, database query and factory), `libraries/vendor/joomla/session/`, `libraries/vendor/joomla/data/` (DataObject, DataSet, DumpableInterface), `libraries/vendor/joomla/di/`, `libraries/vendor/google/recaptcha/`, `libraries/vendor/symfony/yaml/`, `libraries/vendor/joomla/filesystem/`, and all six `plugins/privacy/` plugins — these were missed by the automated P-10 pass (PHP 8.1/8.5 `E_DEPRECATED`)
- Blocked malicious `data:` URIs in HTML attribute filtering: `InputFilter::checkAttribute()` now rejects any `data:` URI that is not a safe image base64 (`data:image/(png|gif|jpeg|webp);base64,`), preventing XSS via crafted data URLs in `href`/`src` attributes (CVE-2025-63082)
- Escaped module chrome attributes in `ModuleHelper::renderModule()` with `htmlspecialchars()` before they are passed to `modChrome_*` template functions, preventing XSS via maliciously crafted module style/attribute parameters (CVE-2024-40747)
- Rejected database identifiers containing null bytes or backslashes in `JDatabaseDriver::quoteNameStr()` — both can be used to break out of identifier quoting and inject arbitrary SQL (CVE-2025-25226)
- Escaped `data-title` and `data-title-value` attributes in the com_associations side-by-side editor template with `$this->escape()`, preventing stored XSS via item titles in the multilingual association comparison view (CVE-2026-21631)
- Added `docs/list.xml` update feed hosted via GitHub Pages (`https://joomlaworks.github.io/joomla-3.x/list.xml`); `com_joomlaupdate` and all three installation SQL files now point to this feed instead of `update.joomla.org`
- Added `.github/workflows/rolling-release.yml`: on every push to `main`, a clean zip is built via `git archive` and published to a fixed `rolling` GitHub Release (`joomla-latest.zip`) — no tagged releases required; updating the version in `list.xml` is sufficient to trigger the update notification on live sites
- Removed `plugins/quickicon/eos310`, `plugins/quickicon/phpversioncheck`, `templates/beez3`, and `administrator/templates/hathor` along with their language files and `media/plg_quickicon_eos310` — none are relevant to this distribution
- Removed all four items from the installation SQL files (MySQL, PostgreSQL, SQL Azure) so fresh installs do not register them
- Added migration SQL (`administrator/components/com_admin/sql/updates/*/3.12.0-2026-05-21.sql`) that runs automatically on upgrade: reassigns any site using beez3/hathor as their global default template to protostar/isis, then deletes all related `#__extensions`, `#__template_styles`, and `#__postinstall_messages` records

Post-release corrections (May 22, 2026):
- Fixed `Version::MINOR_VERSION` constant which was incorrectly left at `11` instead of `12`, causing `JVERSION` to evaluate to `3.11.0` on 3.12 sites and the update notification to reappear after a successful upgrade
- Fixed migration SQL error 1054: `#__postinstall_messages` DELETE used wrong column name `language_key` (does not exist); corrected to `title_key` in all three SQL variants (MySQL, PostgreSQL, SQL Azure)
- Fixed update feed `docs/list.xml`: was in `<updates>` format but the core update site has `type='collection'`, so Joomla's `CollectionAdapter` silently ignored it entirely; rewrote as `<extensionset>` collection and added `docs/extension.xml` as the separate details file (mirrors `update.joomla.org` architecture)
- Fixed PHP 8.5 deprecation in `Uri::getInstance()`: passing `null` as the `$uri` argument (from `$this->get('uri.request')` returning null in CLI context) used null as an array key — guarded with a `null → 'SERVER'` coercion
- Fixed PHP 8.5 deprecation: replaced all 17 non-canonical `(boolean)` casts with `(bool)` across `libraries/src/` and `libraries/joomla/` (Date, Table, TagsHelper, ContentHistory, FormattedtextLogger, Text, Associations, BaseLayout, Microdata, Rule, database iterator/exporter/importer)

---

## Version 3.11 - released April 20th, 2026
Summary of changes:
- Joomla 3.x is now compatible with PHP up to version 8.5
- Includes security patches for CVEs reported after Joomla 3.10.20 eLTS was released
- Includes additional security patches & some quality-of-life improvements
- Works better with MySQL 8.x

In detail:
- Patched the MySQLi driver to work better under PHP 8.3
- Patched 3 CVEs after Joomla 3.10.20 was released (CVE-2025-54476, CVE-2025-63083, CVE-2026-21629)
- For CVE-2025-54476: extended whitespace stripping in InputFilter to include `\r`, `\v`, `\f`
- Added `core.admin` authorisation to `finalise()`, `cleanup()`, and `purge()` in com_joomlaupdate controller
- Replaced `===` with `hash_equals()` for all TOTP code comparisons (timing-safe)
- Added Yubico API client ID/secret params to the YubiKey 2FA plugin; added HMAC-SHA1 request signing and response verification
- Gated `AKFactory::unserialize()` in restore.php behind password validation to prevent unauthenticated object injection
- Escaped RSS feed output (description, image, item titles/links) in com_newsfeeds default template
- Changed password-reset activation token comparison to `hash_equals()` (timing-safe)
- Replaced `eval()` in `HtmlDocument::countModules()` with a safe switch-based expression evaluator
- Replaced removed `utf8_encode()`/`utf8_decode()` with `mb_convert_encoding()` in InputFilter, fr stemmer, com_users/com_admin profile models, and fof string utils (PHP 8.2)
- Replaced removed `FILTER_SANITIZE_STRING` constant with `strip_tags()` in `DaemonApplication` (PHP 8.1)
- Fixed `null` passed as `$flags` to `htmlentities()` in fof string utils (PHP 8.1 `TypeError`)
- Added `__serialize()`/`__unserialize()` magic methods to `Joomla\Input\Input`, `Joomla\CMS\Input\Input`, and `Joomla\CMS\Input\Cli` to silence `Serializable` interface deprecation and prevent cascading session fatal error (PHP 8.1)
- Added `int` return type to `Joomla\Input\Input::count()` to satisfy `Countable` signature (PHP 8.1)
- Cast `uri.request` / `uri.base.full` registry values to `string` in `WebApplication` before passing to `stripos()` / `substr_replace()` / `strlen()` (PHP 8.1 `TypeError`)
- Guarded `session_name()` and `session_cache_limiter()` in the native session handler with `!headers_sent()` checks (PHP 8.1)
- Changed all implicitly nullable typed parameters (`Type $p = null` → `?Type $p = null`) across the entire Application class hierarchy: `BaseApplication`, `CliApplication`, `DaemonApplication`, `CMSApplication`, `WebApplication`, `SiteApplication`, `AdministratorApplication` (PHP 8.1 `E_DEPRECATED`; also silences CLI stdout pollution in finder_indexer and deletefiles)
- Removed spurious `$file` argument from `posix_getuid()` and `posix_getgid()` calls in `DaemonApplication::changeIdentity()` — these functions take no arguments; passing one produced an unsuppressed `E_WARNING` on PHP 8.0+ and the comparison was logically incorrect
- Added `php3`, `php4`, `php5`, `php7`, `php8`, `phps`, `phar`, `shtml` to the executable extension blocklist in `JHelperMedia::canUpload()` — all missing entries that PHP or Apache will execute
- Fixed XSS content-sniffing offset bug in `JHelperMedia::canUpload()`: `file_get_contents(..., -1, 256)` → `(..., 0, 256)` — negative offset read only the last byte, making the check fully bypassable
- Changed all implicitly nullable typed parameters (`Type $p = null` → `?Type $p = null`) across 118 files in `libraries/src/`, `libraries/joomla/`, and Composer vendor classes (PHP 8.1 `E_DEPRECATED`; eliminates stdout pollution in CLI scripts)
- Converted `JSessionStorage` to implement `SessionHandlerInterface` and call `session_set_save_handler($this, true)`; added `#[\ReturnTypeWillChange]` to all 6 interface methods in base class and 4 subclasses (PHP 8.1)
- Declared `User::$aid` and `CacheStorage::$_threshold` as explicit class properties to eliminate dynamic property deprecation warnings (PHP 8.2)
- Fixed `${var}` string interpolation to `{$var}` in `lessc.inc.php` — fatal parse error on PHP 8.3+
- Replaced removed `mhash()` with `hash($algo, $data, true)` for raw-binary output in `UserHelper.php` legacy password schemes (PHP 8.1)
- Replaced deprecated `strftime()` with `date()` via new `HTMLHelper::strftimeToDateFormat()` helper in calendar field rendering (PHP 8.1)
- Replaced remaining `utf8_encode()` calls in `twitter/statuses.php` with `mb_convert_encoding()` (PHP 8.2)
