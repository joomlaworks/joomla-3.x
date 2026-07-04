# CHANGELOG

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
