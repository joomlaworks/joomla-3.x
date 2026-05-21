# CHANGELOG

## Version 3.12 - released May 21st, 2026
Summary of changes:
- Further PHP 8.x compatibility fixes, extending coverage to previously missed files
- Additional security patches backported from Joomla 4/5/6, partly informed by the [TLWebdesign/Joomla-3-EOL-Security-Fixes](https://github.com/TLWebdesign/Joomla-3-EOL-Security-Fixes) project
- Built-in update server: sites running 3.12 or newer can now receive updates directly via the Joomla backend updater
- Removed legacy/unused bundled items: `eos310` and `phpversioncheck` quickicon plugins, `beez3` frontend template, `hathor` backend template

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
