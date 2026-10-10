Joomla 3.x UTD (up-to-date)
===========================

According to [market share estimates](https://w3techs.com/technologies/details/cm-joomla) (as of May 2026), Joomla 3.x is currently used on more than 50% of all installed Joomla sites worldwide.

However, official support for Joomla 3.x ended in February 2025 (counting the eLTS program).

So we're actively developing Joomla 3.x UTD as an up-to-date distribution of the Joomla 3.x content management system, built to ensure code security, support modern PHP & MySQL/MariaDB versions & fix any broken behaviour that never got sorted before the release of newer major versions of Joomla.

If you are a Joomla extension developer still supporting Joomla 3.x and you are reading this, please do your users a favour and make sure your extension update XML files don't stop at Joomla 3.10.x.

**New in 3.17: work with your site through AI assistants and the command line.** Joomla 3.x UTD has a built-in MCP server and a command line with 100 commands. See the guide, **[AI Assistants & the Command Line](AI-GUIDE.md)**, to connect Claude, ChatGPT/Codex, Gemini, Copilot, Cursor, Devin Desktop and other assistants, and to use the command line for pretty much anything.

---

## CONTENTS
- [Changelog](#changelog)
  - [Version 3.17 - released October 10th, 2026](#version-317---released-october-10th-2026)
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
- [PostgreSQL Support](#postgresql-support)
- [SQLite Support](#sqlite-support)
- [AI Assistants (MCP)](#ai-assistants-mcp)
  - [Full guide: AI Assistants & the Command Line](AI-GUIDE.md)
- [Notes on Operating System Support](#notes-on-operating-system-support)
- [Contribute](#contribute)
- [Discuss](#discuss)
- [Chat with the Codebase (AI assisted)](#chat-with-the-codebase-ai-assisted)
- [Longterm Plan (as a different project)](#longterm-plan-as-a-different-project)

## CHANGELOG

## Version 3.17 - released October 10th, 2026
Summary of changes:
- Full support for all major database vendors: MySQL/MariaDB (native & PDO), Postgres (native & PDO), SQLite, SQL Server & Azure SQL, spanning versions that were released even 15 or so years ago (excluding SQLite, which supports versions from late 2021 on, 3.37.0 and newer, for now). Our work made all databases simply work as they should with Joomla. And Joomla 3.x UTD works natively with the default user authentication method that ships with MySQL 8.0, 8.4 and 9.x (caching SHA-2) and it does not require switching MySQL to "native" authentication (the old way up to MySQL 5.7).
- For SQLite specifically, we're using WordPress' emulation layer so that any SQL statement built for MySQL will work with SQLite as well. And we use WAL mode to queue write operations in SQLite, while switching session management to "PHP" to reduce write volume to what would otherwise end up in the sessions table in the database. SQLite is excellent for spinning up Joomla 3.x UTD dev environments in seconds, or even putting entire sites in Dropbox, OneDrive etc. that you can resume working on when switching between devices. But it can also be used for small sites, that don't require lots of concurrent writes. We currently flag the SQLite driver as 'experimental,' having tested it primarily against engine releases from the past five years. Compatibility will continue to improve as we test older releases and gather real-world feedback from third-party extensions.
- A brand new installation process that supports all database drivers.
- 3 brand new frontend templates that truly highlight what a good content management system should be: **Finch** (for blogging), **Hammond** (for news portals & magazines) and **Rookwood** (for portfolio/presentation business sites). All using 100% core Joomla extensions, built with plain CSS and JavaScript, providing editor.css files for ensuring your WYSIWYG editor sees the template's CSS styling, including awesome "print CSS" styling for article views and scoring excellent on Google's Core Web Vitals. Not only that, we revamped all sample data sets to adapt on each template. And you can switch between sample data sets even after you've installed Joomla 3.x UTD for the first time, as if you had installed the new sample data set at first. The 3 new templates are a great starting point for any new project and have been professionally crafted using our nearly 2 decades of experience in building high traffic & heavy content websites with Joomla & K2.
- Protostar has been fully upgraded for PHP 8 and security, but will not be actively used in the future - it's there for backwards compatibility only.
- We've added TinyMCE 8 (the latest version) as a new default editor plugin for Joomla; TinyMCE 4 stays as "Editor - TinyMCE (legacy)", and existing sites keep it until they switch in Global Configuration, to ensure maximum backwards compatibility.
- New command line interface (CLI - at cli/joomla.php) using the command names of newer Joomla releases (after version 4), but enhancing with many more and using JSON output so that scripts and AI agents can programmatically interact with your site. We're talking full content management (articles, categories, modules, menus), full template management, dry runs, health checks, converting between databases, log viewers and more! This work will bring Joomla 3.x UTD closer to becoming a truly agentic CMS.
- The new CLI also exposes a brand new MCP server: All major AI assistants (e.g. Claude, OpenAI, Gemini, Qwen, Kimi, GLM etc.) can now work with your site through this built-in MCP server and perform operations as you instruct them in both the content and structure of the site.
- It's even possible to install a new Joomla 3.x UTD site using just the CLI in mere seconds, which makes rapid prototyping and testing a joy! [See our video on X here](https://x.com/joomlaworks/status/2107490029568164119?s=20).
- The backend template has a new responsive login page, inline with Isis' colors, but modernized and more practical.
- We've added a new one-click **Clean Cache** control in the administrator's status bar for everyone with backend access; administrators also get **Clean Everything** (the main site cache, anything inside the /cache folder and temporary folders as defined in configuration.php) and **Global Check-in** from the same spot.
- Post-installation Messages now show what's new in every release of this distribution, starting from v3.11, instead of stock Joomla's outdated messages from the 3.2–3.10 era.
- Fixed the administrator's Help buttons, which showed "not found" since v3.11.
- Fixed a ton of long-standing bugs in the API and ironed out any PHP deprecations we could detect.

**Security fixes:**
- Installer: the ownership check for remote databases could be skipped by going straight to the step which writes the configuration (stock Joomla 3)
- Installer: anyone could run the installer again on an installed site while the `installation` folder remained (stock Joomla 3); now only the browser which installed the site can go on
- Installer: setting the Super User's group could put every user into the Super Users group when the mapping already existed
- Module Manager: the ordering list of the module edit form could be read by anyone, listing the titles of the modules in any position
- `{loadmoduleid}` showed any module to anyone, whatever its access level or publishing dates (since 3.12)
- A crafted package could write files outside the site's folders, through Joomla Update's extraction, the extension installer or the archive code, and uninstalling one could delete a folder outside the extension (stock Joomla 3); such packages are now refused as a whole
- FOF's download class read local files (`file://`) and could write outside the temporary folder (stock Joomla 3)
- MySQL (PDO) driver: it ran several statements given at once (stock Joomla 3); now one, like the "mysqli" driver
- PostgreSQL: the native driver ran several statements given at once, its connection settings weren't quoted, and usernames differing only in case could be registered (stock Joomla 3)
- The configuration writer could put PHP code into `configuration.php` through a crafted setting name (stock Joomla 3)
- Joomla Update showed the update feed's values unescaped; Install from URL and Install from Web accepted download addresses other than `http`/`https` (stock Joomla 3)
- Little WAF now also checks form data and encoded spellings of the tags, and works on servers without `REQUEST_URI`; each filter only acts while its extension is installed
- `database:convert` wrote its temporary copy of the database to an unprotected folder of `tmp/`; it now uses the protected backup folder, as `database:export` does when run inside the site
- The PDO database drivers refuse connection settings which could add settings of their own (stock Joomla 3)

**Bug fixes:**
- Fixed intermittent "Serialization of 'PDOStatement' is not allowed" errors with the PDO database drivers when caching is enabled (any cache handler), typically right after the site wrote something to the database
- Fixed the "MySQL (PDO)" database driver returning numbers as numeric types on PHP 8.1+, while the default "MySQL (mysqli)" driver returns them as strings. Code comparing database values strictly could behave differently depending on the driver.
- Fixed a PHP warning when checking for extension updates while an installed extension's cached manifest data is empty or corrupted, and a potential crash at the start of a Joomla update in the same situation
- Fixed the "Little WAF" plugin showing no version, date or author in Extensions: Manage on sites upgraded to 3.16 (fresh installs were fine)
- Fixed Extensions: Install Languages permanently showing "The update table is not up to date" (with no languages listed) on sites missing the English language pack's database record, which some older upgrade paths never created; it's now restored automatically on update
- Fixed PHP 8.5 deprecation warnings from non-canonical casts (e.g. `(boolean)`, `(integer)`, `(double)`) still present in parts of the code
- Fixed thousands of PHP 8.5 deprecation warnings while Smart Search indexes content
- Fixed a PHP warning in command line scripts given an empty argument
- Fixed Global Configuration not saving on SQLite sites ("Invalid field: Host")
- Fixed PHP 8.4/8.5 deprecation warnings when checking for updates, reading RSS feeds, sending mail with NTLM authentication, connecting to LDAP and using FTP
- Fixed extension updates losing their download key when the package URL already had a query string
- Update notification emails for a new Joomla version are now sent once a day, at a time you choose in the plugin's options (10:00 by default); they used to go out every 6 hours until the site was updated
- Fixed a fatal error on PHP 8 when the "File" cache handler can't create a cache folder
- Fixed Global Configuration requiring a database user name
- Fixed exporting and importing database tables with Joomla's database exporter/importer (invalid XML for some column defaults, and a fatal error importing new tables with the "MySQL (PDO)" driver)
- Fixed database errors on PHP 8.1+ with the default "MySQL (mysqli)" driver turning into fatal errors where Joomla and extensions expected to handle them, and lost database connections no longer being reconnected
- Fixed the same with the "MySQL (PDO)" driver on every PHP version, plus an abrupt "Recursion trying to check if connected" stop when its connection was lost
- Fixed the "MySQL (PDO)" driver ignoring a port or socket in the database host (e.g. `localhost:/path/to/mysql.sock`); the installer now also accepts a local socket without asking to verify the site's ownership
- Fixed the "PostgreSQL" database driver not working at all on PHP 8.1+, System Information failing on PostgreSQL 16+, creating users failing with a duplicate key error on PostgreSQL after many users, and lost PostgreSQL connections never being reconnected. PostgreSQL support is now tested (PostgreSQL 18, both drivers)
- Fixed the installer stopping after its first step on PHP 8.2+ when PHP is set to display errors
- Fixed uninstalling Contacts or News Feeds failing (and leaving them half removed) on sites installed with sample data, after Banners had been uninstalled
- Clear error messages when the database user's authentication method is the reason a connection fails (e.g. `mysql_native_password` on MySQL 8.4/9), saying what to change
- Fixed several installer bugs: no sample data offered for SQLite, SQLite not hiding the server fields in the browser, unescaped values on the overview page, a path check on the chosen sample-data file, missing, empty, wrong and malformed translations (every installation language now has every string, including those of this version), and PHP 8.5 deprecation notices (one of them in every select list built from arrays, site-wide)
- Fixed PHP 8.2+ deprecation warnings when editing tagged items and in forms with date, colour, captcha or module ordering fields, and PHP 8.1+/8.5 warnings with menu items linking to external URLs and on pages without a menu item
- Fixed warnings when saving Login/Logout menu items created by code, and when saving Global Configuration from the command line on new sites
- An uninstalled "Little WAF" plugin no longer comes back on Joomla updates
- "Cancel" in a component's Options goes back to the page you came from, like "Save & Close" does
- The Site Information/Statistics modules show the operating system's name (e.g. "Ubuntu 26.04.1 LTS", or "Linux" on the frontend) instead of a cut-off "Linux" plus the start of the server's host name, and the web server (e.g. "Apache 2.4.58"; only the name on the frontend)
- Fixed "Access forbidden." showing in the Module Manager after saving a module, for users who may edit modules but not publish them (the module was saved)
- The command line now shows the messages of older code (e.g. why a category can't be deleted), and a broken command file no longer stops the other commands
- Fixed tags given by ID from the command line being created as new tags named after the number
- Fixed date calculations with negative intervals failing on the SQLite database driver
- The administrator login page always shows its gradient: upgraded sites got a flat blue panel from Isis' old "Login Background Colour" option, which is removed

**Improvements:**
- The "Joomla! Statistics" plugin and its request to send statistics are off on new and updated sites (joomla.org's statistics don't cover this distribution)
- A modernised installer for new sites: new app-style design with a sidebar of steps (with dark mode and right-to-left support), well-formed XHTML-style HTML5, plain HTML/CSS/JavaScript without Bootstrap, jQuery or any other file from outside the `installation` folder, and the same steps as before
- The installer removes the `installation` folder by itself when you continue to your site or its administrator (only after installing, and only its own folder), and no longer has FTP options
- The installer offers three sample data sets, News, Blog and Studio; Brochure, Default and Learn (and the Learn set's images) are removed
- The "Sample Data" plugin (formerly "Sample Data - Blog") and Control Panel module install either set, News first, with a button per set
- The administrator's status bar names the distribution ("Joomla 3.x UTD v3.17.0"), and shows Visitors and Messages as icons and "Admins", so it stays on one line
- `config:set` says which options it set, with their new and previous values (secrets and long values by name only)

**New features:**
- TinyMCE 8 editor ("Editor - TinyMCE"), next to the TinyMCE 4 one, now "Editor - TinyMCE (legacy)": the editor buttons below the editor as before, image uploads by dropping, pasting or the image dialog, templates, a toolbar builder per user group, 61 languages. New sites use it; existing sites switch in Global Configuration (and can switch back)
- "Install from Web" (the Joomla! Extensions Directory browser) is included and enabled, as the first tab of Extensions: Install; it can't be installed from the JED any more, whose feed stops at Joomla 3.10
- Quick install from the command line: `php cli/joomla.php core:install` sets up a new site on SQLite from just the site's name and the administrator's email address and username, with a generated password and, optionally, sample data (see [Quick install from the command line](#quick-install-from-the-command-line-sqlite))
- Content management from the command line: list, show, create, change, publish, trash and delete articles, categories, tags, modules and menu items, saved as the administrator saves them, acting as an account whose permissions apply (`--as`); `--dry-run` on every command which changes something; `database:optimize`; `extension:reinstall` (overwrites an extension's files with its original package, from its update site or a given file, and lists or removes files the package doesn't have, e.g. on a hacked site); `site:health`; `log:list`/`log:tail` and `actionlog:list`. Changes made from the command line are recorded in the User Actions Log
- Template management from the command line: a template's positions, options and design tokens (`template:info`), its options, and its files (list, read, write, delete), with every change undoable, syntax checks for PHP, and full template backups and restores (`template:backup`, `template:restore`)
- Hammond, Finch and Rookwood load `css/custom.css` when it exists: the place for a site's own CSS, never touched by updates
- Clearer option groups in the administrator: spacer headings are bands, spacer lines are visible rules
- A taller editor by default (800px; the "HTML Height" option of the TinyMCE plugins sets it)
- Hammond, Finch and Rookwood each have an `editor.css`: the editor shows articles with the site's fonts, colours and layout (Finch's and Rookwood's in light or dark, as the device is set)
- New sites list 50 items per page in the administrator's managers (Global Configuration's Default List Limit) instead of 20
- New sites show the site's name after page titles ("Home - My Site" instead of "Home")
- Clean Cache in the administrator's status bar for everyone with backend access, plus Clean Everything (cache, cache and temporary folders) and Global Check-in for administrators
- Hammond, Finch and Rookwood print articles on A4 with just the logo and the article, without splitting paragraphs between pages
- Hammond, Finch and Rookwood: lean `index.php` files (the setup is in each template's helper), their own offline page and lazy images below the top of each page; Hammond and Finch also show their social links out of the box (`#` until set)
- Built-in MCP server (`php cli/joomla.php mcp:serve`) for AI assistants, read-only by default. See [AI Assistants (MCP)](#ai-assistants-mcp)
- New SQLite database driver (experimental): the whole site in one file, with no database server, for small to medium sites, development and testing. Core and extensions work unchanged, as it runs their MySQL SQL. Available on PHP 7.4+ in the installer, and for existing sites through the new `database:convert` command line command (which also moves a site back to MySQL). SQLite sites use PHP sessions, so browsing doesn't write to the database. See [SQLite Support](#sqlite-support)
- PostgreSQL in the command line database tools: `database:export` and `database:import` work on PostgreSQL, and `database:convert` moves a site between MySQL/MariaDB, PostgreSQL and SQLite, in any direction. See [PostgreSQL Support](#postgresql-support)
- PostgreSQL install scripts for Banners, Contacts and News Feeds, so they can be reinstalled after an uninstall (also through "Restore uninstalled core extensions")
- New command line interface, `cli/joomla.php`, using the same command names and options as Joomla 4 and later to update Joomla and manage the configuration, users, extensions, database, cache, sessions and Smart Search (e.g. `php cli/joomla.php core:update`, `database:export`, `user:add`, `extension:install`, `config:set`, `site:down`). Every command can return JSON (`--format=json`), so scripts and AI agents can work with the site directly. Extensions can add their own commands. The existing scripts in `cli/` still work as before (cron jobs need no changes), but now run the new commands.
- Hammond, a news and magazine template: a 12-column frontpage of modules (main story, latest news, sections, opinion, most read, ad slots), a sticky header with a combined menu and search panel, list, article and info page layouts, share popups, system fonts and SVG icons, sized for readability and Core Web Vitals, without jQuery or Bootstrap. The default template of new sites without sample data or with the News set; installed but not activated on existing sites
- News sample data: 227 articles in ten sections with tags, menus and modules, and images, videos and posts matching each section. From the installer, `core:install --sample-data=news` or the Control Panel's Sample Data module (Super Users only; it also makes a Hammond style the site's default template, and replaces the sample data set installed before)
- Finch, a colourful blog template: every topic in its own colour (chosen with a menu item's Page Class), a greeting set large on the home page, posts one after another with the newest on a band of colour, posts with a coloured header and a calm reading column, light, dark or as the device is set, system fonts and SVG icons, without jQuery or Bootstrap. The default template of new sites with the Blog set; installed but not activated on existing sites
- Blog sample data: 20 essays by one author in four topics, with tags, pages and the sidebar's modules, using the News set's images. From the installer, `core:install --sample-data=blog` or the Control Panel's Sample Data module (Super Users only; it also makes a Finch style the site's default template, and replaces the sample data set installed before)
- Rookwood, a template for studios, agencies and companies: full-width sections, a dark and a light theme with a switch for visitors, a search panel in the header, large type, bold colours, Showcase and Mosaic category layouts, case study and page layouts, system fonts and SVG icons, without jQuery or Bootstrap. The default template of new sites with the Studio set; installed but not activated on existing sites
- Studio sample data: 9 case studies and 16 journal posts, studio and contact pages, and a home page of sections, with ten CC0 images of its own. From the installer, `core:install --sample-data=studio` or the Control Panel's Sample Data module (it also makes a Rookwood style the site's default template)
- Hammond, Finch and Rookwood link "Joomla 3.x UTD" in their footer text to the distribution's site; the sample sets name Joomla 3.x UTD as what they demonstrate
- Lists shown with a template's layout keep it on page 2 and later (stock Joomla 3 dropped it from the page links)

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

### Quick install from the command line (SQLite)
A new site on SQLite takes one command with a few flags: give it the site's name, the administrator's email address and username (or leave them out, and it asks for them), and it installs Joomla, generates the administrator's password and shows it at the end (or you can set a custom password with the optional `--admin-password` flag), then removes the `installation` folder. Execute the command in an empty folder (that will host the site), as the user the web server runs PHP (so there are no permission issues) as:

```bash
wget -q https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip && unzip -q joomla-latest.zip && rm joomla-latest.zip && php cli/joomla.php core:install --site-name="My Site" --admin-email=me@example.com --admin-username=admin
```

Add `--sample-data=news`, `--sample-data=blog` or `--sample-data=studio` to start with one of the installer's sample data sets; the news set comes with the Hammond template as the site's template, the blog set with Finch, the studio set with Rookwood, 3 brand new, modern and Core Web Vitals friendly templates, (derived from years of experience building content-heavy sites by the project maintainers).

Installing sites like this needs PHP 7.4 or newer with the `pdo_sqlite` extension. If you also add the flag `--format=json`, you'll get installation details in JSON format (including the password), which is ideal for scripts (using jq) and LLMs.


## PHP COMPATIBILITY
This distribution targets at least PHP 7.4. This is the baseline version we use for broader compatibility with hosts and the Joomla 3.x ecosystem (e.g. other extensions and templates that are actively maintained). Using at least PHP 7.4 we can also guarantee compatibility with all supported databases (as of v3.17): MySQL/MariaDB, Postgres & SQLite.

Sites on PHP 7.1 through 7.3 will still be offered updates to this distribution through the Joomla Update component - 7.4 is our recommended baseline, not a hard cutoff - so these sites can keep receiving security patches even before upgrading their PHP version. PHP 7.0 and below is not supported; the update won't be offered and installing manually isn't recommended.

**For end users:**
If your site's server/webspace is configured with PHP 7.0 to 7.3, upgrading to PHP 7.4 is typically a safe switch. The same applies to sites on PHP 5.6, just make sure your extensions and templates are not holding you back.

**For professionals/hosting companies:**
If you are hosting sites for others, consider letting them know they can safely upgrade to this Joomla distribution, both for security as well as newer PHP compatibility/features/performance.

Switching to this distribution will also allow you (or take you closer) to upgrade your server(s). E.g. a server hosting Joomla sites using PHP prior to version 7.2 may be stuck in CentOS 7/cPanel, which is no longer supported by either the OS vendor or cPanel, with whatever that entails primarily for security.


## DATABASE SUPPORT
| Database | Minimum version | Status |
|---|---|---|
| MySQL | 5.5.3 | Tested and actively supported (5.7 or newer recommended, see notes below for 8.x & 9.x) |
| MariaDB | 5.5 | Tested and actively supported |
| PostgreSQL | 9.0 | Tested and supported with both drivers since 3.17 (16 or newer recommended, preferably 18, see notes below) |
| Microsoft SQL Server / Azure SQL | 2008 R2 (10.50.1600.1) | Inherited from stock Joomla 3.x; tested since 3.17 on SQL Server 2022 with both drivers (core only: few extensions support it) |
| SQLite (experimental) | 3.37.0, with PHP 7.4+ | New in 3.17: runs MySQL SQL through an emulation layer, so core and extensions work unchanged; tested on SQLite 3.37.2 (Ubuntu 22.04's) and 3.46.1, with core and common extensions |

For PostgreSQL and SQLite, see [PostgreSQL Support](#postgresql-support) and [SQLite Support](#sqlite-support).

Database support in Joomla 3.x was always centered on MySQL/MariaDB. The core works with all of the databases above, but many third-party extensions only ship MySQL/MariaDB database scripts, so expect rough edges with those on PostgreSQL and SQL Server. The installer enforces these minimum versions. Once a site runs 3.16 or newer, it won't be offered further updates while its database is below these versions, and sees a notice in Joomla Update instead.

**SQL Server / Azure SQL:** sites connect through Microsoft's PHP drivers (`sqlsrv`) and ODBC driver. ODBC Driver 18 encrypts the connection by default; the server's certificate is accepted without being verified (as earlier ODBC drivers did, which didn't encrypt), since most self-hosted servers have a self-signed one. The command line's database tools (`database:export`, `database:import`, `database:convert`, `database:optimize`) don't support SQL Server, and Smart Search indexes made before 3.17 should be rebuilt (Clear Index, then Index).


## NOTES ON MYSQL & MARIADB
Joomla 3.x UTD works with MySQL 8.0, 8.4 and 9.x on their default settings: no `my.cnf` changes are needed for authentication. MySQL's default authentication method since 8.0 (`caching_sha2_password`) is handled by PHP itself, from PHP 7.4 (or at least 7.1.16/7.2.4).

If you previously enabled `mysql_native_password` (e.g. `default_authentication_plugin` or `mysql_native_password = ON` with `authentication_policy`), it keeps working on MySQL 8.0 and 8.4, but MySQL 9.0 removed it. Before upgrading to MySQL 9, or to drop these settings, switch your sites' database users to the default method (users created while the my.cnf had `mysql_native_password` enabled):
```
ALTER USER 'username'@'host' IDENTIFIED WITH caching_sha2_password BY 'password';
```
If a site can't connect because of the authentication method, its error message says so and what to change.

MariaDB still uses `mysql_native_password` by default, which works as before. PHP can't use MariaDB's optional `ed25519` and `PARSEC` authentication methods, so keep the database users of your sites on `mysql_native_password`.

We also recommend the following setting for maximum compatibility in both MySQL and MariaDB:
```
sql_mode = ""
```


## POSTGRESQL SUPPORT
Joomla 3 has always had PostgreSQL drivers, but they fell behind: the main one stopped working on PHP 8.1, and parts of Joomla broke on recent PostgreSQL versions. Since 3.17, PostgreSQL is fully supported again and tested against PostgreSQL 18, with both drivers: "PostgreSQL" (PHP's `pgsql` extension) and "PostgreSQL (PDO)" (`pdo_pgsql`).

- **Versions:** PostgreSQL 9.0 is the minimum, but use a recent version: 16 or newer, preferably 18.
- **Installing and updating:** choose either PostgreSQL driver in the installer. Joomla Update, Extensions: Database and the "Restore uninstalled core extensions" option work as on MySQL.
- **Command line:** `php cli/joomla.php database:export` and `database:import` work on PostgreSQL, and `database:convert` moves a site between MySQL/MariaDB, PostgreSQL and SQLite, in any direction (e.g. `php cli/joomla.php database:convert --to=postgresql --user=... --password=... --database=...`). Converting translates column types and Joomla's "no date" values, which differ between MySQL and PostgreSQL, checks that every table has all its rows, and fixes the new database's structure as Extensions: Database would. The site is offline while its database is copied, and the old database is left as it is. Tables of MySQL-only extensions get the defaults MySQL would apply, so their code keeps working on PostgreSQL.
- **Core extensions:** we've gone the extra mile and added the PostgreSQL install scripts that Banners, Contacts and News Feeds never had, so these can be uninstalled and installed again on PostgreSQL too.
- **Usernames:** checks that a username or email address isn't taken ignore case, as on MySQL, but not accents: `Àdmin` and `admin` are two users on PostgreSQL (and SQL Server), one on MySQL/MariaDB.
- **Third-party extensions:** many only ship MySQL database scripts. Moving a site to PostgreSQL copies their tables and data, but check that the extensions themselves support PostgreSQL before relying on them.


## SQLITE SUPPORT
Joomla 3 has always shipped a basic SQLite database driver, but it was never usable for a site: the installer didn't offer it, and Joomla and its extensions only ship MySQL database scripts. Version 3.17 completes it with the "SQLite (experimental)" driver, which runs Joomla's and extensions' MySQL SQL through an emulation layer (from WordPress's SQLite Database Integration project). Core and extensions work unchanged, with no SQLite-specific scripts.

- **What it's for:** the whole site's data in one portable (cloud-synchronizable) file, with no database server, for small to medium sites, development and testing. SQLite handles one write at a time (read operations never wait & write operations queue briefly), so busy sites with many simultaneous writers should stay on MySQL/MariaDB.
- **Requirements:** PHP 7.4 or newer, and the PDO SQLite extension with SQLite 3.37.0 or newer (tested on 3.37.2, the version of Ubuntu 22.04, and 3.46.1). On Linux, PHP uses the server's own SQLite: older systems (e.g. Debian 11, RHEL 9 and its clones, Ubuntu 20.04) have an older SQLite, and don't get the option. It's only offered where these are available. Most shared hosting environments should have SQLite bundled by default or activated on-demand as an option in their respective control panel. With PHP's `intl` extension, checks that a username or email address isn't taken also ignore accents and full-width letters, as MySQL does; without it, only case.
- **Getting there:** choose it in the installer, or move an existing site with `php cli/joomla.php database:convert --to=sqlite` (and back with `--to=mysqli`). The command takes the site offline while it copies the database, so nothing changes meanwhile, and puts it back online afterwards (if the conversion fails, the site comes back online with its current database).
- **Sessions:** with the "Database" session handler, every page view writes to the database file. A new SQLite site, or one moved with `database:convert`, therefore uses PHP sessions instead (Global Configuration → System → Session Handler → "PHP"), and writes only when content or settings change, or a new visitor arrives. If your server has an object cache such as Memcached, Redis or APCu enabled, prefer that for sessions: it's faster than PHP's session files and also keeps sessions out of the database. `database:convert` keeps those handlers as they are.
- **Security:** keep the database file out of the web's reach: outside the site's public folder, or in a folder of its own, which Joomla protects for Apache and IIS (with nginx, keep the file's random name too). To move it, change "Path to Database Folder" in Global Configuration → Server → Database (shown for SQLite only): Joomla copies the database there, switches to it and removes the old file.
- **Backups:** copy the database file while the site is offline (it's a complete copy of the site's data), or use `php cli/joomla.php database:export`.


## AI ASSISTANTS (MCP)
The command line includes an MCP (Model Context Protocol) server, so AI assistants such as Claude Code or Claude Desktop can work with a site directly: check its health, read its logs, and list, write and change content, using the command line's commands as tools.

**The full guide, [AI Assistants & the Command Line](AI-GUIDE.md), covers setting up Claude, ChatGPT/Codex, Gemini, Copilot, Cursor and other clients, choosing what an assistant may do, and using the command line yourself, with recipes for every area of a site.**

- **Read-only by default:** the assistant can use every command which only reads, and dry runs of the others (which report what they would change), but can't change anything. Add `--allow-write` to let it make changes.
- **Templates:** with `--allow-write`, an assistant can change a template's CSS, JavaScript and images (e.g. "make the headings darker" goes into `css/custom.css`) and its options. Anything which puts code on the server also needs `--allow-code`: writing a template's code (PHP, XML, `.htaccess`), restoring a template backup, installing or updating extensions, updating Joomla, and changing `configuration.php` (`config:set`, `database:convert`). Ask it to run `template:backup` first.
- **Narrow it down:** `--allow` and `--deny` choose the commands, with wildcards (e.g. `--allow="article:*,category:*,site:*"`), and `--as=username` makes content changes as that account, whose permissions apply (e.g. an Editor account can write and edit articles but not publish them).
- **Traceable:** every change is recorded in the User Actions Log, as made through MCP. Secret values (passwords, keys) are left out of the log and aren't shown to the assistant, and options which would read or write files on the server aren't offered (database exports go to the site's protected backup folder).
- **Claude Code:** `claude mcp add joomla -- php /path/to/site/cli/joomla.php mcp:serve` (add `--allow-write` and the other options at the end). For a site on another server, run it over SSH: `claude mcp add joomla -- ssh user@example.com php /path/to/site/cli/joomla.php mcp:serve`.
- **Claude Desktop and other clients:** add a server with the command `php` and the arguments `/path/to/site/cli/joomla.php` and `mcp:serve` to the client's MCP configuration (e.g. `"mcpServers": {"joomla": {"command": "php", "args": ["/path/to/site/cli/joomla.php", "mcp:serve"]}}`).

Run the server as the same system user as the web server (or one with the same file permissions), as for any command. `php cli/joomla.php help mcp:serve` lists its options.


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
- First-class support for MySQL, MariaDB, PostgreSQL and SQLite. The SQL Server and Azure SQL drivers will be dropped.
- The focus shifts to using K2 for content. This means that com_content (and anything related) is removed entirely. This way important content features are decoupled from the CMS base, which aims to be a solid platform for building sites, while maintaining true backwards compatibility with past releases (of the fork).
- Admin refresh.
- Gradual jQuery/Mootools removal - switch to modern JS only.
- Gradual codebase modernization to support future PHP & MySQL/MariaDB versions without much effort.
- A truly agentic CMS: AI agents working alongside editors, developers and site owners. The command line and MCP server of Joomla 3.x UTD are the seed for the integrations to come.

## LEGAL
Joomla 3.x UTD is an independent community project. It is not affiliated with, endorsed by, or supported by Open Source Matters, Inc. or The Joomla! Project™.

The Joomla!® name and logo are trademarks of Open Source Matters, Inc. in the United States and other countries. This project does not use the Joomla! logo.

This distribution is free software, released under the GNU General Public License version 2 or later. See LICENSE.md.

Original Joomla! code: Copyright &copy; 2005 - 2025 Open Source Matters, Inc.

Modifications in this distribution: Copyright &copy; 2026 JoomlaWorks Ltd. and this project's contributors
