Joomla 3.x
=============

## CHANGELOG

### Version 3.11 - released April 20.04.2026
Summary of changes:
- Joomla 3.x is now compatible with PHP up to version 8.5
- Includes security patches for issues reported in newer versions of Joomla that also apply to Joomla 3.x
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


## TO DO
- Create endpoints to easily update Joomla 3.x after version 3.11, from within the Joomla backend
- Maintain modern PHP compatibility and apply security patches when necessary


## HOW TO USE / UPGRADE
To upgrade: Grab the project zip (for now) and using the terminal, extract on top of an existing Joomla 3.10.12 or newer version. On a production site, remove the "/installation" folder and you're done.

To install from scratch: just extract and follow the installation process as you always did.


## CONTRIBUTE
If you'd like to contribute meaningful upgrades to existing functionality or fix bugs, feel free to open an issue in this project.


## LONGTERM PLAN AS A DIFFERENT PROJECT
A new fork is on the way, based on Joomla 3.x. This fork is WIP (but very, very active) and it will feature:
- A stripped down version of Joomla 3.x with all non-essential extensions removed
- The focus shifts to K2 and com_content (and anything related) is removed entirely - this way important content features are decoupled from the CMS base which aims to be a solid platform for building sites, maintaining true backwards compatibility for future releases
- Complete jQuery/Mootools removal - switch to modern JS.
- Codebase gradually modernized to support future PHP & MySQL/MariaDB versions
