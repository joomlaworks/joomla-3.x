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
  - [Version 3.18 - released November 5th, 2026](#version-318---released-november-5th-2026)
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

A short overview of each release. Every change is described in detail in [CHANGELOG.md](CHANGELOG.md).

## Version 3.18 - released November 5th, 2026
Summary of changes:
- **Much more of the core can be removed, cleanly:** Articles, Private Messages, Privacy, Redirect, Tags, Wrapper, the Breadcrumbs and Syndication Feeds modules and CodeMirror can be uninstalled (using K2 instead of Articles? uninstall Articles, the rest keeps working). A component takes its tables, modules, plugins, media, Smart Search entries and menu items along, and "Restore uninstalled core extensions" brings them back
- **Prothero**, a new black and white administrator template with a dark mode, the default on new sites (Isis stays, and works with the same extensions)
- **New editors:** Markdown (EasyMDE) and Blocks (Editor.js), next to TinyMCE 8, and an editor switcher in the administrator for moving between TinyMCE, TinyMCE (legacy), Markdown and Blocks without losing unsaved changes; PDF files shown on the site with a viewer & a new editor button to make attaching PDF documents with inline previews a breeze!
- **A better Media Manager:** drag and drop uploads, cropping, renaming in place, large previews of images, audio, video and PDF files, and optimised uploads (upright, WebP, no metadata)
- **Little WAF, on by default:** blocks scans, attack tools, executable uploads and object injection, sends safe security headers and cleans up the malformed addresses crawlers make up
- **Faster, sturdier caching:** campaign and social links get cached pages, a page is rendered once however many visitors ask for it, stale pages keep a busy or failing site answering, and cache times are exact across Joomla, Varnish, CDNs and browsers, and the file cache reads about 30% faster
- **Redirects** how you want them, not just for error pages: create custom redirects as you like
- **Structured data, Open Graph and X cards** for every page
- **CAPTCHA plugins:** Updated Google's reCAPTCHA to support all active versions & added new ones for Cloudflare Turnstile, hCaptcha, Akismet and IconCaptcha (fully self-hosted)
- **No jQuery, jQuery UI, MooTools or Bootstrap in the core**: one shared plain JavaScript file (`global.js`) does the work, and old names keep working for extensions. Tested extensively on many popular large extensions (K2, VirtueMart, HikaShop, JEvents, JCE, Convert Forms, AcyMailing, Community Builder and others)
- **Smart Search fixes** for large sites ([#9](https://github.com/joomlaworks/joomla-3.x/issues/9), [#21](https://github.com/joomlaworks/joomla-3.x/issues/21), [#23](https://github.com/joomlaworks/joomla-3.x/issues/23)), more PHP 8.2–8.5 fixes ([#20](https://github.com/joomlaworks/joomla-3.x/issues/20))
- **An amazing new debug console:** a button at the bottom right opens a panel with tabs (time, memory, queries, errors), light and dark

Every change is described in detail in [CHANGELOG.md](CHANGELOG.md).

**New features:**
- **Administrator:**
  - Prothero: Isis' markup with a minimal black and white look, light and dark colour schemes (automatic by default), a choice of colours for the menu bar and a matching login page; the installer asks which administrator template to use
  - Editor switcher: switch between TinyMCE, TinyMCE (legacy), Markdown and Blocks for the session, without losing unsaved changes; a Default Editor (Site) option for front-end editing
  - The debug console opens from a button at the bottom right, as a panel with tabs (time, memory, queries, errors), light and dark
  - System Information lists the bundled third-party software (version, licence, home page; outdated software marked), and a "System Information" module sits beside the Control Panel's quick icons
- **Content:**
  - "Editor - EasyMDE (Markdown)": write in Markdown, with preview, side by side and drag and drop uploads; content is still saved as HTML
  - "Editor - Blocks (Editor.js)": write content as blocks (headings, lists, images, embeds, quotes, tables, code, notes, HTML, Read more), saved as HTML which the site template's block layouts decide; templates add blocks of their own (Rookwood: Hero, Testimonials, Services); a preview at desktop, tablet and phone widths; a "Custom Blocks" module
  - "Custom Code" module (site and administrator): your own HTML, CSS, JavaScript or embeds, written in CodeMirror and output as it is
  - PDF files: a viewer on the site (`{pdf images/file.pdf}`), a PDF button in the editors, previews in TinyMCE, the Blocks editor and the Media Manager; each sample data set shows one in an article
- **Media Manager:** drag and drop uploads into the folder shown (also in the image picker), cropping (free or 21:9 to 1:1, as a new file or over the original), renaming files and folders in place, square thumbnails you can make smaller or larger, large previews of images (with the arrow keys), audio, video and PDF files. Images uploaded in the editors are turned upright, saved as WebP, stripped of their metadata (GPS included) and filed in dated folders; resizing and a copy for phones (with `srcset` written by the editors) are options, and `media:optimize` does the same for existing images
- **Security:**
  - Little WAF's generic protections (whatever extensions a site has), security headers (safe ones on by default) and URL Normalizations; an optional secret key in the administrator's address
  - Four new CAPTCHA plugins: Cloudflare Turnstile, hCaptcha, Akismet (spam filtering without a challenge) and IconCaptcha (self-hosted, no keys, enabled); one Google reCAPTCHA plugin for the checkbox, invisible and score (v3, new) types
- **Redirects:** any address can be redirected, on the site or elsewhere (301 or 302), with matching that ignores case, slashes and `index.php/`, query strings passed on, loops refused and chains warned about; errors (404 and others) collected into a list of their own (off by default) with a "Create Redirect" button; import that checks every line
- **Search engines and social networks:** "System - Structured Data & Social Tags" writes Google structured data (JSON-LD), Open Graph and X card tags for every page, with any template; the type of articles (Article, News Article, Blog Post, Review, Recipe...) per site, category or tag
- **Caching:** the page cache ignores tracking parameters (`utm_*`, `fbclid`, `gclid`... editable in Global Configuration), has its own times for the home page and other pages, serves stale pages while one request refreshes them (and instead of an error page), sends `Age`, `s-maxage`, `stale-while-revalidate`, `stale-if-error` and `X-Logged-In` for Varnish and CDNs, and the file cache reads about 30% faster
- **For developers:** behaviour from markup (`data-validate`, `data-tooltip`, `data-searchable`... without a call in the view), `<jdoc:include type="footer" />` for the CMS's scripts at the end of the page, `addPreconnect()`/`addDnsPrefetch()`/`addPreload()` also sent as `Link:` headers (Early Hints), console plugins adding command line commands ([CLI-GUIDE.md](CLI-GUIDE.md)), and PostgreSQL understanding MySQL-style backticks; see [API-GUIDE.md](API-GUIDE.md)

**Improvements:**
- Clean Cache tells you how much it removed: how many items and how much data
- The Clear Cache page shows one list on Memcached, Redis, APCu and WinCache, which keep one cache for the site and the administrator, instead of two identical tabs
- Clear Expired Cache is now a button on the Clear Cache page, not a page of its own. Memcached and Redis remove expired items by themselves, so there the page says that instead
- Memcached compression is on by default for new sites: cached pages take far less memory
- Plain JavaScript instead of jQuery, jQuery UI, MooTools and Bootstrap's scripts throughout the core: modals, tooltips, tabs, searchable lists (Chosen rewritten, same look), search tools, subforms, drag and drop reordering (with touch), date fields (the browser's date input; Jalali for Persian), colour fields (the browser's colour picker), show-on fields, the Media Manager and Joomla Update; old names and jQuery plugin calls keep working for extensions, and MooTools stays available for those that ask for it
- Bootstrap Icons replace IcoMoon (the `icon-*` classes keep working), core icons are SVGs in the text's colour, and the language flags are redrawn
- Isis and Protostar are plain CSS (no more Less; Isis' stylesheets about a third of their former size), wider form fields in the administrator, and no files for old Internet Explorer anywhere
- Hammond, Finch and Rookwood send resource hints (also as `Link:` headers for Early Hints), and every page's stylesheets and scripts are sent as preload headers
- Hammond, Finch and Rookwood: give a module the class `wide` to take it from one side of the window to the other (e.g. a slideshow on the home page)
- Updated libraries: PHPMailer 7.1.1 (was 5.2.28), CodeMirror 5.65.21 (with a dark theme), jQuery UI 1.14.2 (for extensions still requiring it), SimplePie 1.9.0 (for extensions still requiring it), Symfony Yaml 4.4, sodium_compat 1.24.2; removed: HTML5 Shiv, Google's reCAPTCHA client, unused web service libraries and polyfills for PHP below 7.1
- Media Manager: current file types uploadable by default (WebP, AVIF, MP4, WebM, MP3, Opus, FLAC, Office...; SVG deliberately not), uploads up to 128 MB, a name already taken gets the time added instead of failing
- The core system plugins run in a better order (Little WAF, Redirect, Language Filter, Remember Me and Page Cache first), so cached pages skip third-party plugins' work
- Sites updating get TinyMCE 8 as default editor (when they used TinyMCE 4) and Prothero as administrator template (when they used Isis); the "What's New" message lists what to check after updating
- PostgreSQL 9.5 is now the minimum (was 9.0); the distribution's PHP code follows PSR-12

**Security fixes:**
- TinyMCE updated to 8.10.1, which fixes a way to slip harmful code into content through its media plugin
- Hammond, Finch and Rookwood no longer show the full text of restricted articles to visitors who aren't allowed to read them
- Media Manager uploads can no longer end up in a folder outside the media folder, and dangerous file types are caught however their names are capitalised
- Extensions can no longer accidentally open the page to harmful code through the links, scripts and styles they add
- Command line: importing a database file can no longer run hidden commands, and an AI assistant given write access can no longer create administrators unless it was explicitly allowed to install code

**Bug fixes:**
- **Administrator:**
  - Lists no longer make the page scroll sideways on tablets and narrow windows: the least important columns are hidden, only as many as needed to fit
  - Modules dragged to a new place in the Modules list now stay there
  - The windows opened by the editor's buttons and by image fields fit on phones and small tablets
  - Checkboxes in lists line up with the text beside them
  - Smaller fixes in Language Overrides, menu assignments, module ordering, Smart Search, Banners, the extension installer, two-factor authentication and Install from Web
- **Site and templates:**
  - Some third-party modules no longer lose their module container (title, classes and layout) once caching is on
  - Hammond, Finch and Rookwood show an Edit link to users who may edit an article, as Joomla's own layouts do
  - Hammond, Finch and Rookwood: subcategory lists expand, Smart Search's advanced options open, search suggestions no longer cover the Search button, the image picker for front-end editing looks right, and form lists follow the dark themes
  - Rookwood's main menu and Protostar no longer make the page scroll sideways at some widths
  - Pages without a menu item of their own (login, tags, search...) no longer get "Site - Site" as their title
  - Error pages show the right error, and a site whose template was deleted shows another template instead of an error
  - Deleting a menu item no longer hides modules shown "on all pages except" that item
  - The password reset form checks the email address before sending
- **Media Manager:** common file types the settings allow (icons, OpenDocument files, older Excel and PowerPoint files) upload again, refused files get a clear message, and videos in subfolders play
- **Search and Smart Search:**
  - Rebuilding the Smart Search index no longer stops partway on MySQL and MariaDB, nor from the command line
  - Searching by date works with only a start or an end date, and the "All" results setting shows results
  - Searches find words typed without accents, also in Greek and other alphabets, and search suggestions work on SQLite
- **Caching** (bugs inherited from Joomla 3):
  - If the cache server can't be reached, runs out of memory or asks for a password, the site keeps working without the cache. Before, some of these cases showed an error on every page, including the administrator, where the setting could have been changed. Administrators now see a warning.
  - When several visitors ask for the same page at once, only one builds it on Redis and APCu too (it already worked this way on Memcached and the file cache), which matters most during traffic spikes.
  - Clear Expired Cache now really removes expired items on APCu.
  - The Clear Cache page and the command line now say when cleaning failed, instead of reporting success.
  - Memcached works with very long cache times (over 30 days) and over a socket file.
  - Clearing the cache works on large, busy sites with file or Memcached caching, Memcached no longer slows down as the cache grows, and Redis clears faster and shows true sizes
  - Clear Expired Cache also cleans the site's cache (it only cleaned the administrator's)
  - A damaged cache entry no longer shows visitors an empty page
  - Browsers can reuse cached pages they already have, and forms on cached pages work for every visitor
  - Visits from social media or newsletter links (with tracking codes) no longer change what cached pages show others
  - Clearing the cache only clears this site's cache, also from the command line
- **Databases:**
  - Lists no longer show some items twice and skip others when going from one page to the next
  - PostgreSQL: pages work with debugging or caching on, and new items save
  - SQL Server: private messages and privacy consents save, and Smart Search indexes every item
  - SQLite: searches work in every alphabet, and a few lists and options fill in correctly
  - Moving a site from MySQL 8 to MariaDB or an older MySQL works
- **Command line and MCP:** installing extensions such as VirtueMart, enabling plugins like Page Cache, editing articles (their formatting was lost) and changing settings through an AI assistant all work as expected
- **Updates and extensions:**
  - Uninstalling a core component also removes the extensions that only served it and the files it left behind, and nothing else breaks without it
  - The plugin everyone needs to log in can no longer be uninstalled by mistake
  - Updating an old site keeps the Beez 3 template if the site uses it, and updates remove files that are no longer part of Joomla
  - Installing a new site no longer starts over when a step shows a warning
  - Sample data no longer hides every module after an interrupted install
- **PHP compatibility:** warnings that filled server logs on PHP 8.1–8.5 are gone, older extensions built on FOF work on PHP 8, two-factor logins set up in Joomla 3.2–3.6 work on PHP 8, and exporting the User Actions Log works on PHP 7
- **Other:** small fixes in the code editor (CodeMirror), and missing texts that showed as codes were added

**Joomla 3 bugs fixed:**

Part of what this project does is fix what never got fixed in Joomla 3 itself. These are the ones site owners are most likely to have met, all already there in the Joomla 3.10 code this distribution started from, and also part of the list above. The [CHANGELOG](CHANGELOG.md) marks more than 70 fixes in this release as "stock Joomla 3".
- **Caching:**
  - An unreachable cache server, or a Redis server out of memory, took the whole site and its administrator down
  - With Redis or APCu, a traffic spike rebuilt the same page once per visitor instead of once
  - Cached pages could give a visitor someone else's form token
  - A damaged cache entry showed an empty page until it expired
  - Clearing the cache could fail on large, busy sites
  - Clear Expired Cache left the site's own cache untouched
  - Browser caching of cached pages never worked
- **Modules:**
  - Modules dragged to a new place in the Modules list went back on the next page load
  - Deleting a menu item could make a module disappear from every page
  - A module could lose its surrounding markup once caching was on
- **Search and Smart Search:**
  - Smart Search's full reindex stopped with "table is full" on MySQL and MariaDB with default settings
  - Search dropped results when a word was typed without the accents of a non-Latin script, such as Greek
- **Administrator:**
  - Windows opened by the editor's buttons ran off the screen on phones
  - Administrator lists scrolled sideways on tablets
  - "User - Joomla!" could be uninstalled, after which nobody could log in
- **Media Manager:** common file types the default settings allow (icons, OpenDocument files, older Excel and PowerPoint files) couldn't be uploaded

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

**New features:**
- New SQLite database driver (experimental): the whole site in one file, with no database server. In the installer (PHP 7.4+), and for existing sites through `database:convert`, which moves sites between MySQL/MariaDB, PostgreSQL and SQLite in any direction. See [SQLite Support](#sqlite-support)
- Three new frontend templates, each with its own sample data set: Hammond (news and magazines, News set), Finch (blogs, Blog set) and Rookwood (studios and companies, Studio set). Plain CSS and JavaScript without jQuery or Bootstrap, an `editor.css` so the editor shows the site's styling, print styles, lazy images, and a `css/custom.css` of your own that updates never touch. Installed but not activated on existing sites
- TinyMCE 8 editor, the default of new sites; existing sites switch in Global Configuration, and TinyMCE 4 stays as "Editor - TinyMCE (legacy)"
- "Install from Web" (the Joomla! Extensions Directory) included, as the first tab of Extensions: Install
- New command line interface, `cli/joomla.php`, with the command names of Joomla 4 and later and many more: updates, configuration, users, extensions, database, cache, Smart Search, content (articles, categories, tags, modules, menu items), templates (files, options, backups), `extension:reinstall`, `site:health` and logs. JSON output, `--dry-run`, and changes recorded in the User Actions Log. The scripts in `cli/` keep working
- Quick install from the command line: `php cli/joomla.php core:install` sets up a new SQLite site in seconds (see [Quick install from the command line](#quick-install-from-the-command-line-sqlite))
- Built-in MCP server for AI assistants, read-only by default. See [AI Assistants (MCP)](#ai-assistants-mcp)
- Clean Cache in the administrator's status bar for everyone with backend access, plus Clean Everything and Global Check-in for administrators
- PostgreSQL in the command line database tools, and PostgreSQL install scripts for Banners, Contacts and News Feeds
- New sites list 50 items per page in the managers, show the site's name after page titles, and get a taller editor
- Clearer option groups in the administrator, and lists shown with a template's layout keep it on page 2 and later

**Improvements:**
- A modernised installer: app-style design with dark mode and right-to-left support, plain HTML/CSS/JavaScript, removes its own folder when you're done, no FTP options
- Three sample data sets, News, Blog and Studio, from the installer or the Control Panel's Sample Data module, replacing Brochure, Default and Learn
- The status bar names the distribution ("Joomla 3.x UTD v3.17.0") and fits on one line; `config:set` says what it changed
- The "Joomla! Statistics" plugin is off on new and updated sites (joomla.org's statistics don't cover this distribution)

**Security fixes:**
- Installer: the ownership check for remote databases could be skipped, anyone could run the installer again while the `installation` folder remained, and setting the Super User's group could put every user into Super Users
- Crafted packages could write files outside the site's folders (Joomla Update, the extension installer, the archive code) or delete a folder outside the extension on uninstall; FOF's download class read local files
- The configuration writer could put PHP code into `configuration.php` through a crafted setting name
- Database drivers: MySQL (PDO) and the native PostgreSQL driver ran several statements given at once, connection settings could add settings of their own, and PostgreSQL allowed usernames differing only in case
- The Module Manager's ordering list could be read by anyone, and `{loadmoduleid}` ignored modules' access levels and publishing dates
- Joomla Update showed the update feed's values unescaped; Install from URL and Install from Web accepted non-`http(s)` addresses
- Little WAF also checks form data and encoded spellings; `database:convert` keeps its temporary copy in a protected folder

**Bug fixes:**
- Databases: MySQL (PDO) fixes (serialization errors with caching, numbers returned as strings like "mysqli" does, ports and sockets, lost connections), database errors handled again with "mysqli" on PHP 8.1+, the PostgreSQL driver working again on PHP 8.1+ (now tested with PostgreSQL 18), date calculations on SQLite, and clear messages when the authentication method blocks a connection
- PHP 8.1–8.5 deprecation warnings fixed throughout: non-canonical casts, Smart Search indexing (thousands of them), update checks, feeds, mail, LDAP, FTP, forms, tags, menu items and command line scripts
- Installer: no longer stopping after its first step on PHP 8.2+, sample data for SQLite, and complete translations in every installation language
- Global Configuration saves on SQLite sites and no longer requires a database user name
- Updates: download keys kept, no warning or crash with corrupted manifest caches, Install Languages no longer stuck on "The update table is not up to date", update notification emails once a day; an uninstalled Little WAF stays uninstalled
- Uninstalling Contacts or News Feeds works after Banners was uninstalled
- Database exporter/importer fixes, and no fatal error when the File cache handler can't create a folder
- Administrator: the Help buttons work again, "Cancel" in Options returns to where you were, no "Access forbidden." after saving a module, the login page always shows its gradient, and Site Information shows the operating system and web server
- Command line: older code's messages are shown, a broken command file no longer stops the others, and tags can be given by ID

## Version 3.16 - released October 3rd, 2026
Summary of changes:
- Backported 14 security fixes from the Joomla 6.1.3/5.4.8 and 6.1.4/5.4.9 security releases, each confirmed against this codebase (several were listed upstream as "Joomla 4.0 and later" but the vulnerable code is present in 3.x), plus related hardening
- Added "Little WAF", a new opt-in system plugin that filters known attack patterns against vulnerable third-party extensions
- Joomla Update now respects core extensions you've uninstalled, and can bring them back on request
- Raised the minimum database versions, and cleaned up cache, session and database drivers that can't work on PHP 7.1+
- Fixed a long list of bugs and PHP 8.x deprecation warnings, several of them in stock Joomla 3.x itself

**New features:**
- "Little WAF" system plugin (opt-in): filters known attack signatures against abandoned or historically vulnerable third-party extensions, before any component runs, with a filter per extension (Sourcerer-style `{source}` injection, Modules Anywhere-style tags)
- "Restore uninstalled core extensions" option in Joomla Update, bringing back core extensions you uninstalled, with their tables, menu items and settings
- Joomla Update checks the site's database type and version before offering an update

**Improvements:**
- Joomla Update no longer brings back removable core extensions you've uninstalled (their files used to come back on every update)
- Minimum database versions raised to MySQL 5.5.3, MariaDB 5.5 and PostgreSQL 9.0, and the update channel accepts any Joomla 3.x site
- Removed drivers that can't work on PHP 7.1+: APC, Memcache, XCache, Cache_Lite and the legacy `mysql` database driver (sites configured with it keep working through "mysqli"); Memcached is no longer "Experimental"

**Security fixes:**
- Guest account creation through the frontend profile save, even with registration disabled
- Folder deletion through a path traversal in the file cache, and traversal checks in the Template Manager's delete actions
- XSS: two filter bypasses (HTML5 entities, unterminated numeric references), `JHtml::link()`/`iframe()`, the module position column, the toolbar link button, the image layout, and contacts' schema.org markup
- Access: tagged items of restricted categories leaking through tag views and modules, content history comparisons, and batch copies of categories and fields without per-item permission checks
- SSRF through URL fields, and HTTP header injection in banner tracking downloads and vCards
- Hardening: more blocked upload extensions (incl. SHTML), CSV formula escaping, stricter internal-URL checks, a constant-time TOTP comparison

**Bug fixes:**
- Updates no longer fail on sites which uninstalled Banners, Contacts, News Feeds or Smart Search, clear the cache with every cache handler, honour database requirements in update feeds, and won't stop at 3.20.0; fewer false "the archive file is corrupt" errors for uploaded packages
- No stray "com_..._categories" menu entries after uninstalling Banners, Contacts or News Feeds
- Memcached locking could stall the site's cache for up to 30 seconds
- Restoring News Feeds and Smart Search created outdated tables, and Smart Search didn't install on PostgreSQL
- PHP 8.1–8.5 deprecation warnings fixed in 805 files, and no fatal errors on hosts disabling `php_uname()`

## Version 3.15 - released July 18th, 2026
Summary of changes:
- Backported 13 CVEs from official Joomla security advisories, independently confirmed to also affect 3.x (several labelled "affects 4.0.0+ only" upstream, but the vulnerable code is present in 3.x regardless)
- Resolved a number of functional/compatibility issues, some reported directly by the community

**Improvements:**
- A "Use custom cache time" caching option in all 26 cache-capable core modules (mod_whosonline can be cached for the first time)
- "Sort by Author" in Extensions: Manage, to tell third-party extensions from core ones

**Security fixes (CVE backports):**
- Local file inclusion via the view layout parameter (CVE-2026-40383) — **High**
- SQL injection in the tags list ordering (CVE-2026-352212) — **Moderate, High impact**
- XSS (all **Moderate**): language overrides (CVE-2026-48954), the template manager's editor, in three places where upstream fixed one (CVE-2026-48950), the image layout (CVE-2026-48953), the update list (CVE-2026-48952), Multilingual Associations (CVE-2026-25901), the content history preview (CVE-2026-30894), the feed modules (CVE-2026-25900) and "read more" links (CVE-2026-30895)
- **Low:** reset links sent over plain HTTP (CVE-2026-48902), InputFilter's instance cache key (CVE-2026-48901), restricted contacts' vCards (CVE-2026-48948)
- Already covered by earlier hardening: CVE-2026-48905 and CVE-2026-48903; not applicable to 3.x: CVE-2026-48899 and CVE-2026-48951

**Bug fixes:**
- Module caching: "Use Global" ignored Global Configuration's Cache Time (fixed automatically on upgrade)
- Updates: no more "Refresh Manifest Cache failed" warnings for removed core extensions, and Beez 3/Hathor's leftover database entries are removed ([#7](https://github.com/joomlaworks/joomla-3.x/issues/7))
- The minimum PHP check went from 5.3.10 to 7.1.0, so sites on PHP 7.1–7.3 keep receiving updates (PHP 7.4+ remains the recommended baseline)
- Literal `"_QQ_"` text instead of quotation marks in admin messages, and a PHP 8.4 `lcg_value()` deprecation ([#12](https://github.com/joomlaworks/joomla-3.x/issues/12))
- `README.txt` removed and `LICENSE.txt` renamed to `LICENSE.md`

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

The administrator's template is Prothero; add `--admin-template=isis` for Isis instead (the web installer offers the same choice on its last step).

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
| PostgreSQL | 9.5 | Tested and supported with both drivers since 3.17 (16 or newer recommended, preferably 18, see notes below) |
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

- **Versions:** PostgreSQL 9.5 is the minimum (9.0 until 3.17), but use a recent version: 16 or newer, preferably 18.
- **Installing and updating:** choose either PostgreSQL driver in the installer. Joomla Update, Extensions: Database and the "Restore uninstalled core extensions" option work as on MySQL.
- **Command line:** `php cli/joomla.php database:export` and `database:import` work on PostgreSQL, and `database:convert` moves a site between MySQL/MariaDB, PostgreSQL and SQLite, in any direction (e.g. `php cli/joomla.php database:convert --to=postgresql --user=... --password=... --database=...`). Converting translates column types and Joomla's "no date" values, which differ between MySQL and PostgreSQL, checks that every table has all its rows, and fixes the new database's structure as Extensions: Database would. The site is offline while its database is copied, and the old database is left as it is. Tables of MySQL-only extensions get the defaults MySQL would apply, so their code keeps working on PostgreSQL.
- **Core extensions:** we've gone the extra mile and added the PostgreSQL install scripts that Banners, Contacts and News Feeds never had, so these can be uninstalled and installed again on PostgreSQL too.
- **Usernames:** checks that a username or email address isn't taken ignore case, as on MySQL, but not accents: `Àdmin` and `admin` are two users on PostgreSQL (and SQL Server), one on MySQL/MariaDB.
- **Third-party extensions:** many only ship MySQL database scripts. Moving a site to PostgreSQL copies their tables and data, but check that the extensions themselves support PostgreSQL before relying on them.
- **MySQL-style queries:** since 3.18, backtick-quoted names (`` `group` ``, `` c.`itemID` ``) are turned into PostgreSQL's quoted names, the most common reason extensions' queries failed on PostgreSQL. MySQL-only functions and syntax (e.g. `GROUP_CONCAT()`, `INSERT IGNORE`) still need the extension to support PostgreSQL.


## SQLITE SUPPORT
Joomla 3 has always shipped a basic SQLite database driver, but it was never usable for a site: the installer didn't offer it, and Joomla and its extensions only ship MySQL database scripts. Version 3.17 completes it with the "SQLite (experimental)" driver, which runs Joomla's and extensions' MySQL SQL through an emulation layer (from WordPress's SQLite Database Integration project). Core and extensions work unchanged, with no SQLite-specific scripts.

- **What it's for:** the whole site's data in one portable (cloud-synchronizable) file, with no database server, for small to medium sites, development and testing. SQLite handles one write at a time (read operations never wait & write operations queue briefly), so busy sites with many simultaneous writers should stay on MySQL/MariaDB.
- **Requirements:** PHP 7.4 or newer, and the PDO SQLite extension with SQLite 3.37.0 or newer (tested on 3.37.2, the version of Ubuntu 22.04, and 3.46.1). On Linux, PHP uses the server's own SQLite: older systems (e.g. Debian 11, RHEL 9 and its clones, Ubuntu 20.04) have an older SQLite, and don't get the option. It's only offered where these are available. Most shared hosting environments should have SQLite bundled by default or activated on-demand as an option in their respective control panel. With PHP's `intl` extension, checks that a username or email address isn't taken also ignore accents and full-width letters, as MySQL does; without it, only case.
- **Getting there:** choose it in the installer, or move an existing site with `php cli/joomla.php database:convert --to=sqlite` (and back with `--to=mysqli`). The command takes the site offline while it copies the database, so nothing changes meanwhile, and puts it back online afterwards (if the conversion fails, the site comes back online with its current database).
- **Sessions:** with the "Database" session handler, every page view writes to the database file. A new SQLite site, or one moved with `database:convert`, therefore uses PHP sessions instead (Global Configuration → System → Session Handler → "PHP"), and writes only when content or settings change, or a new visitor arrives. If your server has an object cache such as Memcached, Redis or APCu enabled, prefer that for sessions: it's faster than PHP's session files and also keeps sessions out of the database. `database:convert` keeps those handlers as they are.
- **Security:** keep the database file out of the web's reach: outside the site's public folder, or in a folder of its own, which Joomla protects for Apache and IIS (with nginx, keep the file's random name too). To move it, change "Path to Database Folder" in Global Configuration → Server → Database (shown for SQLite only): Joomla copies the database there, switches to it and removes the old file.
- **Backups:** copy the database file while the site is offline (it's a complete copy of the site's data), or use `php cli/joomla.php database:export`.
- **Copies of a site** (e.g. to try `database:convert` on): if the site caches in Memcached or Redis, give the copy a new `secret` (`php cli/joomla.php config:set secret=...`, any new random value) before using it. Cache entries are named after the `secret`, so a copy sharing the cache server with the original would read and overwrite its entries.


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

The Joomla!® name and logo are trademarks of Open Source Matters, Inc. in the United States and other countries. The logo appears in this distribution only where Joomla 3 itself shows it (the installer and the administrator), kept unchanged for continuity, and in screenshots of the software. Joomla 3.x UTD does not use it to brand itself.

This distribution is free software, released under the GNU General Public License version 2 or later. See LICENSE.txt.

Original Joomla! code: Copyright &copy; 2005 - 2025 Open Source Matters, Inc.

Modifications in this distribution: Copyright &copy; 2026 JoomlaWorks Ltd. and this project's contributors
