# Joomla 3.11.0 — Security Patch Log

**Date:** April 20, 2026
**Base version:** Joomla 3.10.20 eLTS
**Patched version:** Joomla 3.11.0

---

## Summary

This document records the security patches backported from Joomla 5.x/6.x into the Joomla 3.10.20 eLTS codebase, and the version bump to 3.11.0. All CVEs listed below were confirmed to affect the 3.x branch post-3.10.20 release. CVEs affecting only Joomla 4.0.0+ were reviewed and determined not applicable.

---

## CVEs Patched

### CVE-2025-54476 — XSS via input filter attribute bypass
- **Severity:** High
- **Affected:** Joomla 3.0.0–3.10.20-elts
- **Fixed upstream in:** Joomla 4.4.14 / 5.3.4 (September 30, 2025); joomla-framework/filter v2.0.6 (PR #84)
- **File patched:** `libraries/vendor/joomla/filter/src/InputFilter.php`
- **Change:** Added whitespace/null-byte stripping inside `checkAttribute()` before the XSS pattern match. Prevents bypass vectors such as `java\tscript:alert()` or `java&#x09;script:alert()` from evading the existing regex check.

```php
// Before (vulnerable):
$attrSubSet[1] = html_entity_decode(strtolower($attrSubSet[1]), $quoteStyle, 'UTF-8');
return (strpos($attrSubSet[1], 'expression') !== false && $attrSubSet[0] === 'style')
    || preg_match('/(?:(?:java|vb|live)script|behaviour|mocha)(?::|&colon;|&column;)/', $attrSubSet[1]) !== 0;

// After (patched):
$attrSubSet[1] = html_entity_decode(strtolower($attrSubSet[1]), $quoteStyle, 'UTF-8');

// Remove common XSS-evasion characters (CVE-2025-54476)
$attrSubSet[1] = str_replace(["\t", "\n", " ", "\0"], '', $attrSubSet[1]);

return (strpos($attrSubSet[1], 'expression') !== false && $attrSubSet[0] === 'style')
    || preg_match('/(?:(?:java|vb|live)script|behaviour|mocha)(?::|&colon;|&column;)/', $attrSubSet[1]) !== 0;
```

---

### CVE-2025-63083 — XSS in pagebreak plugin table-of-contents output
- **Severity:** Medium
- **Affected:** Joomla 3.9.0–5.4.1
- **Fixed upstream in:** Joomla 5.4.2 / 6.0.2 (January 6, 2026)
- **File patched:** `plugins/content/pagebreak/tmpl/toc.php`
- **Change:** Wrapped `$listItem->title` in `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` to prevent injection of arbitrary HTML/JS via crafted article page-break titles in the table-of-contents navigation block.

```php
// Before (vulnerable):
<?php echo $listItem->title; ?>

// After (patched):
<?php echo htmlspecialchars($listItem->title, ENT_QUOTES, 'UTF-8'); ?>
```

---

### CVE-2026-21629 — Improper ACL check in administrator com_ajax
- **Severity:** Medium
- **Affected:** Joomla 3.0.0–5.4.3
- **Fixed upstream in:** Joomla 5.4.4 / 6.0.4 (March 31, 2026)
- **File patched:** `administrator/components/com_ajax/ajax.php`
- **Change:** Added a guest-user check at the top of the administrator-side `com_ajax` entry point. Unauthenticated requests now receive a 403 before any AJAX dispatch logic runs. Without this patch, a guest user could invoke AJAX handler methods (`getAjax()`, `postAjax()`) in admin-area modules and plugins that assumed login-wall protection.

```php
// Added after defined('_JEXEC') or die:
if (JFactory::getUser()->guest)
{
    throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}
```

> **Note:** This CVE alone is not sufficient for remote code execution or webshell upload on a stock Joomla installation. Exploitation would require chaining with a secondary vulnerable extension that exposes a dangerous AJAX method.

---

---

## Proactive Security Hardening — April 20, 2026

The following issues were identified by static analysis of the codebase and fixed. None carry a CVE number but each represents a real exploitable condition or weakens defence-in-depth.

---

### H-1 — CVE-2025-54476 patch incomplete: `\r` / `\v` / `\f` bypass
- **Severity:** High
- **File:** `libraries/vendor/joomla/filter/src/InputFilter.php`
- **Issue:** The original patch stripped `\t`, `\n`, space, and `\0` but omitted carriage return (`\r`, 0x0D), vertical tab (`\v`, 0x0B), and form feed (`\f`, 0x0C). The WHATWG HTML5 URL parser strips all ASCII whitespace before scheme evaluation, so `java\rscript:alert(1)` bypassed the regex and executed in Firefox/Chrome.
- **Fix:** Extended the `str_replace` character list to include `"\r"`, `"\v"`, `"\f"`.

```php
// Before:
$attrSubSet[1] = str_replace(["\t", "\n", " ", "\0"], '', $attrSubSet[1]);

// After:
$attrSubSet[1] = str_replace(["\t", "\n", "\r", "\v", "\f", " ", "\0"], '', $attrSubSet[1]);
```

---

### H-2 — Missing `core.admin` check in com_joomlaupdate controller
- **Severity:** Medium
- **File:** `administrator/components/com_joomlaupdate/controllers/update.php`
- **Issue:** `finalise()` (line 138), `cleanup()` (line 182), and `purge()` (line 235) only validated the CSRF token. Any backend user (Manager role) could invoke them. The sibling methods `upload()`, `confirm()`, etc. already check `core.admin`.
- **Fix:** Added `JFactory::getUser()->authorise('core.admin')` guard to each of the three methods immediately after the token check.

---

### H-3 — Non-constant-time TOTP comparison
- **Severity:** Medium
- **File:** `plugins/twofactorauth/totp/totp.php`
- **Issue:** Six `===` comparisons of 6-digit OTP codes leaked timing information, reducing the effective brute-force search space.
- **Fix:** All six sites changed to `hash_equals((string) $code, (string) $input)`.

---

### H-4 — Yubikey plugin: no HMAC verification, hardcoded demo client ID
- **Severity:** Medium/High
- **Files:** `plugins/twofactorauth/yubikey/yubikey.php`, `yubikey.xml`, `en-GB.plg_twofactorauth_yubikey.ini`
- **Issue:** The Yubico API response `h=` HMAC field was never verified, allowing a MITM to forge `status=OK` and bypass 2FA. The plugin also used the hardcoded public demo `id=1`, which has no associated secret key, making HMAC verification structurally impossible.
- **Fix:**
  - Added `clientid` (integer) and `clientsecret` (password) plugin parameters.
  - Requests are now HMAC-SHA1-signed when a secret is configured.
  - Response `h=` signature is verified with `hash_equals()` before `status=OK` is trusted.
  - OTP/nonce response fields compared with `hash_equals()`.
  - Plugin returns `false` immediately if no client ID is configured.
- **Action required:** Obtain a free API key at `https://upgrade.yubico.com/getapikey/` and enter the Client ID and Secret in the plugin's configuration.

---

### H-5 — Unauthenticated `AKFactory::unserialize()` in restore.php
- **Severity:** High (conditional on restoration.php existing)
- **File:** `administrator/components/com_joomlaupdate/restore.php`
- **Issue:** The `factory` request parameter path called `AKFactory::unserialize($_REQUEST['factory'])` without the AES password check that guards the sibling `json` path. During an active update window (while `restoration.php` exists) an attacker could trigger PHP object injection via POP gadgets in Composer dependencies.
- **Fix:** Added a guard that terminates with `Invalid login` if the `json` path password check was not satisfied before `factory` is processed.

---

### H-6 — Unescaped RSS feed output in com_newsfeeds
- **Severity:** Low/Medium
- **File:** `components/com_newsfeeds/views/newsfeed/tmpl/default.php`
- **Issue:** Feed description, image URI, image title, item titles, and item links were echoed without escaping. A compromised or hostile upstream RSS feed could inject HTML/JS.
- **Fix:** All feed-sourced values wrapped with `$this->escape()` or `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

---

### H-7 — Non-constant-time password-reset token comparison
- **Severity:** Low
- **File:** `components/com_users/models/reset.php`
- **Issue:** `$user->activation !== $token` used `!==` (short-circuit) instead of a timing-safe comparison.
- **Fix:** Changed to `!hash_equals((string) $user->activation, (string) $token)`.

---

### H-8 — `eval()` in HtmlDocument::countModules
- **Severity:** Low
- **File:** `libraries/src/Document/HtmlDocument.php`
- **Issue:** A module-count expression built from whitelist-split tokens was evaluated with `eval()`. Not currently exploitable via user input, but any future caller forwarding user data would yield RCE.
- **Fix:** Replaced `eval()` with an explicit `switch`-based integer expression evaluator covering all operators the method supports (`+`, `-`, `*`, `/`, `==`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `and`, `or`, `xor`).

---

## PHP 8.x Compatibility Fixes — April 20, 2026

The following changes were made to ensure the codebase runs cleanly on PHP 8.0–8.3 without deprecation notices, `TypeError` exceptions, or fatal errors. All changes are backward-compatible with PHP 7.4.

---

### P-1 — `utf8_encode()` / `utf8_decode()` removed in PHP 8.2
- **Severity:** Fatal (function not found on PHP 8.2+)
- **Files patched:**
  - `libraries/vendor/joomla/filter/src/InputFilter.php` (×3 `utf8_encode()` calls)
  - `administrator/components/com_finder/helpers/indexer/stemmer/fr.php` (×6 calls)
  - `components/com_users/models/profile.php`
  - `administrator/components/com_admin/models/profile.php`
  - `libraries/fof/string/utils.php`
- **Fix:** All calls replaced with `mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1')` / `mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8')` as appropriate.

---

### P-2 — `FILTER_SANITIZE_STRING` removed in PHP 8.1
- **Severity:** Fatal (`E_ERROR: Undefined constant`)
- **File:** `libraries/src/Application/DaemonApplication.php`
- **Fix:** Replaced `filter_var($value, FILTER_SANITIZE_STRING)` with `strip_tags($value)`.

---

### P-3 — `null` passed as `$flags` to `htmlentities()` deprecated in PHP 8.1
- **Severity:** `TypeError` on PHP 8.1+
- **File:** `libraries/fof/string/utils.php`
- **Fix:** Replaced `null` flag argument with explicit `ENT_COMPAT`.

---

### P-4 — `Serializable` interface deprecated in PHP 8.1
- **Severity:** `E_DEPRECATED` notice at class-load time; cascades to fatal session startup failure when `display_errors` routes output before `session_name()` is called
- **Files patched:**
  - `libraries/vendor/joomla/input/src/Input.php` (base framework class)
  - `libraries/src/Input/Input.php` (CMS subclass)
  - `libraries/src/Input/Cli.php` (CLI subclass)
  - `libraries/src/Input/Cookie.php` — no change required; inherits new magic methods from CMS `Input` parent
- **Fix:** Added `__serialize(): array` and `__unserialize(array $data): void` magic methods alongside the existing `serialize()`/`unserialize()` methods. On PHP 8.1+ the magic methods take precedence and the deprecation is suppressed. On PHP 7.4 the magic methods are simply unused extra methods — no conflict. Old `Serializable` methods retained for PHP 7.x compatibility.

---

### P-5 — `count()` missing `int` return type (PHP 8.1 `Countable` signature)
- **Severity:** `E_DEPRECATED` notice
- **File:** `libraries/vendor/joomla/input/src/Input.php` line ~170
- **Fix:** Changed `public function count()` to `public function count(): int`. The `: int` return type declaration is valid PHP 7.0+ syntax — no compatibility shim required.

---

### P-6 — `stripos()` / `substr_replace()` / `strlen()` called with possible `null` argument (PHP 8.1)
- **Severity:** `TypeError` on PHP 8.1+
- **File:** `libraries/src/Application/WebApplication.php` line ~1305
- **Issue:** `$this->get('uri.request')` and `$this->get('uri.base.full')` can return `null` when the registry key is absent. PHP 8.1 no longer silently coerces `null` to `''` for string functions.
- **Fix:** Cast both values to `(string)` at the call sites for `stripos()`, `substr_replace()`, and `strlen()`.

---

### P-7 — `session_name()` / `session_cache_limiter()` called after headers sent
- **Severity:** `E_WARNING`; can prevent session startup
- **File:** `libraries/joomla/session/handler/native.php` lines ~128 and ~235
- **Root cause:** Any earlier deprecation notice output (e.g. from P-4 above) marks headers as sent before the session handler runs, causing PHP to emit warnings and potentially abort session start.
- **Fix:** Wrapped both calls in `if (!headers_sent()) { ... }` guards. The session name / cache limiter settings are silently skipped rather than triggering warnings if headers have already been sent.

---

### P-8 — Implicitly nullable typed parameters across Application class hierarchy (PHP 8.1)
- **Severity:** `E_DEPRECATED` notice on every bootstrap request/CLI invocation
- **Files patched:**
  - `libraries/src/Application/BaseApplication.php` — `__construct()`, `loadDispatcher()`, `loadIdentity()`
  - `libraries/src/Application/CliApplication.php` — `__construct()`
  - `libraries/src/Application/DaemonApplication.php` — `__construct()`
  - `libraries/src/Application/CMSApplication.php` — `__construct()`, `loadSession()`
  - `libraries/src/Application/WebApplication.php` — `__construct()`, `loadDocument()`, `loadLanguage()`, `loadSession()`
  - `libraries/src/Application/SiteApplication.php` — `__construct()`
  - `libraries/src/Application/AdministratorApplication.php` — `__construct()`
- **Issue:** All 11 signatures used the pattern `TypeName $param = null` without `?`. PHP 8.1 deprecated this implicit nullable form. With `error_reporting(E_ALL)` and `display_errors=1` set by `finder_indexer.php` and `deletefiles.php`, the notices print to stdout at bootstrap, polluting CLI output and re-triggering the session "headers already sent" cascade (see P-7).
- **Fix:** All affected parameters changed to explicitly nullable `?TypeName $param = null`. Valid PHP 7.1+ syntax — fully backward-compatible with PHP 7.4.

---

### P-9 — `posix_getuid()` / `posix_getgid()` called with spurious argument in DaemonApplication
- **Severity:** `E_WARNING` on every `changeIdentity()` call (unsuppressed)
- **File:** `libraries/src/Application/DaemonApplication.php` lines ~468 and ~476
- **Issue:** `posix_getuid()` and `posix_getgid()` take **zero** arguments. Both calls incorrectly passed `$file` (the PID file path), copied from the adjacent `fileowner()`/`filegroup()` calls. PHP 8.0+ emits an unsuppressed `E_WARNING` for unexpected arguments to these functions. The logic was also subtly wrong — the intent is to compare the *current process* UID/GID against the target, which is what the no-argument forms return.
- **Fix:** Removed the spurious `$file` argument from both calls. Correct on all PHP versions.

---

## Upload Security Hardening — April 20, 2026

The following vulnerabilities were identified by auditing all file upload code paths. None carry a CVE number but each represents a real exploitable condition.

---

### U-1 — Incomplete executable extension blocklist in `JHelperMedia::canUpload()`
- **Severity:** High
- **File:** `libraries/src/Helper/MediaHelper.php`
- **Issue:** The `$executable` array — which blocks dangerous extensions in ALL dot-separated segments of a filename (e.g. `shell.php.jpg` is caught because `php` appears in a non-final segment) — was missing several extensions that PHP or Apache will execute:

  | Missing | Why dangerous |
  |---|---|
  | `phar` | PHP executes `.phar` files natively as PHP code on all modern PHP versions |
  | `php3`, `php4`, `php5`, `php7`, `php8` | Commonly mapped to PHP by Apache; `php5` and `php7` particularly common on shared hosts |
  | `phps` | PHP source display; some hosts execute it, all hosts leak code |
  | `shtml` | Apache SSI execution — allows `<!--#exec cmd="..." -->` |

- **Fix:** Added `php3`, `php4`, `php5`, `php7`, `php8`, `phps`, `phar`, and `shtml` to the blocklist.

---

### U-2 — XSS content-sniffing check reads last 1 byte instead of first 256
- **Severity:** Medium
- **File:** `libraries/src/Helper/MediaHelper.php`
- **Issue:** The check that looks for embedded HTML/script tags in uploaded file content used `file_get_contents($file['tmp_name'], false, null, -1, 256)`. On PHP 7.1+, a negative offset counts from the *end* of the file, so this read only the final ~1 character. The check was completely bypassable by placing any HTML payload (`<script>`, `<?php`, etc.) anywhere except the very last byte of the file.
- **Fix:** Changed offset from `-1` to `0` so the first 256 bytes are checked as originally intended.

---

### U-3 — No server-level PHP execution guard in `images/` upload directory
- **Severity:** Medium (defence in depth)
- **File:** `images/.htaccess` *(created)*
- **Issue:** No `.htaccess` existed in the primary media upload destination. If any file with an executable extension reached disk (misconfigured allowlist, future bypass, direct FTP placement), Apache would execute it as PHP or a CGI script.
- **Fix:** Created `images/.htaccess` that:
  - Denies HTTP requests for files matching PHP/script extensions (`php`, `php[0-9]`, `phtml`, `phar`, `shtml`, `cgi`, `pl`, `py`, `asp`, `aspx`, `exe`, `sh`, `bash`) using both Apache 2.4 and 2.2 syntax.
  - Disables `php_flag engine` for mod_php 5, 7, and 8.
  - Removes `ExecCGI` and directory indexing (`-Indexes`).

---

## CVEs Reviewed but Not Applicable to 3.x

| CVE | Reason not patched |
|-----|--------------------|
| CVE-2025-63082 | XSS for data URLs in img tags — affects 4.0.0+ only |
| CVE-2026-21630 | SQL injection in com_content webservice — 4.0.0+ only (no webservice API in 3.x) |
| CVE-2026-21631 | XSS in com_associations comparison view — 4.0.0+ only |
| CVE-2026-21632 | XSS in article title outputs — 4.0.0+ only |
| CVE-2026-23898 | Arbitrary file deletion in com_joomlaupdate — 4.0.0+ only |
| CVE-2026-23899 | Improper access check in webservice endpoints — 4.0.0+ only |

---

## Version Bump

**File:** `libraries/src/Version.php`

| Constant | Before | After |
|----------|--------|-------|
| `MINOR_VERSION` | `10` | `11` |
| `PATCH_VERSION` | `20` | `0` |
| `EXTRA_VERSION` | `'elts'` | `''` |
| `RELEASE` (deprecated) | `'3.10'` | `'3.11'` |
| `DEV_LEVEL` (deprecated) | `'20-elts'` | `'0'` |

The installation now reports itself as **Joomla! 3.11.0**.

---

## PHP 8.x Compatibility Fixes — Session 2 — April 20, 2026

The following changes were identified in a second-pass audit targeting PHP 8.1–8.5 compatibility. All fixes are backward-compatible with PHP 7.4.

---

### P-10 — Implicitly nullable typed parameters across the full codebase (PHP 8.1)
- **Severity:** `E_DEPRECATED` on every bootstrap/CLI invocation; cascades to stdout pollution, "headers already sent" fatal errors, and session startup failures
- **Scope:** 118 files changed
- **Files patched (representative list):**
  - `libraries/src/` — all classes in Application, Cache, Database, Document, Event, Extension, Factory, Filter, Form, HTML, Http, Image, Installer, Language, Log, Mail, Menu, MVC, Plugin, Profiler, Router, Schema, Session, Table, Uri, User, Utility, Version
  - `libraries/joomla/` — legacy session, form, database, application, archive classes
  - `libraries/vendor/typo3/phar-stream-wrapper/src/` — Manager, PharStreamWrapper, Resolver classes
  - `libraries/vendor/joomla/application/src/` — AbstractApplication and related classes
- **Issue:** The pattern `TypeName $param = null` without a leading `?` was used throughout. PHP 8.1 deprecated this implicit nullable form and emits `E_DEPRECATED` for every such signature that is invoked. At scale, hundreds of notices per request made stdout unusable for CLI scripts.
- **Fix:** All affected parameters changed to `?TypeName $param = null` using an automated Python state-machine script. The script extracted function parameter lists and applied the transformation only within those lists — property declarations of the form `public $foo = null` were intentionally left unchanged (they are already valid on all PHP versions and do not accept the `?` prefix). The `?` prefix is valid PHP 7.1+ syntax — fully backward-compatible with PHP 7.4.

---

### P-11 — `session_set_save_handler()` individual-callback form deprecated (PHP 8.1)
- **Severity:** `E_DEPRECATED` on every session start
- **Files patched:**
  - `libraries/joomla/session/storage.php` (base class)
  - `libraries/joomla/session/storage/apc.php`
  - `libraries/joomla/session/storage/apcu.php`
  - `libraries/joomla/session/storage/database.php`
  - `libraries/joomla/session/storage/xcache.php`
- **Issue:** `session_set_save_handler()` was called with 6 individual callbacks (the pre-PHP-5.4 style). PHP 8.1 deprecated this form. The recommended replacement is to pass an object that implements `SessionHandlerInterface`.
- **Fix:**
  - `JSessionStorage` declared as `abstract class JSessionStorage implements \SessionHandlerInterface`.
  - `register()` updated to call `session_set_save_handler($this, true)`.
  - All 6 interface methods (`open`, `close`, `read`, `write`, `destroy`, `gc`) annotated with `#[\ReturnTypeWillChange]` in the base class and each subclass to suppress PHP 8.1 return-type mismatch deprecations. On PHP 7.4 the `#` starts a comment, making the attribute a no-op — fully backward-compatible.
  - `read()` bare `return;` statements fixed to `return ''` in the base class and `xcache.php` to satisfy the `string` return type contract.

---

### P-12 — Dynamic property creation deprecated (PHP 8.2)
- **Severity:** `E_DEPRECATED` on first assignment to undeclared property; becomes `E_ERROR` in PHP 9
- **Files patched:**
  - `libraries/src/User/User.php`
  - `libraries/src/Cache/CacheStorage.php`
- **Issues and fixes:**
  - `User::$aid` — assigned by `bind()` and legacy code but never declared in the class body. Added `public $aid = 0;` after the existing `$requireReset` declaration.
  - `CacheStorage::$_threshold` — assigned in the base-class constructor but not declared. Added `public $_threshold;` after the existing `$_hash` declaration. The notice appeared on concrete subclasses (e.g. `MemcachedStorage`) because PHP reports the class being instantiated, not the assigning class.

---

### P-13 — `${var}` string interpolation removed (PHP 8.3)
- **Severity:** Fatal parse error on PHP 8.3+
- **File:** `libraries/vendor/leafo/lessphp/lessc.inc.php`
- **Issue:** PHP 8.2 deprecated the `"${varName}"` and `"${expr}"` string interpolation forms; PHP 8.3 made them a fatal parse error. Two occurrences were present:
  - Line 1366: `"${name}expecting..."` (simple variable form)
  - Line 1748: `"op_${ltype}_${rtype}"` (complex expression form)
- **Fix:** Both changed to the `"{$var}"` curly-brace-first form, which has been valid since PHP 4 and remains the correct form on all PHP versions.

```php
// Before (fatal on PHP 8.3):
"${name}expecting ..."
"op_${ltype}_${rtype}"

// After:
"{$name}expecting ..."
"op_{$ltype}_{$rtype}"
```

---

### P-14 — `mhash()` removed (PHP 8.1)
- **Severity:** Fatal (`Call to undefined function mhash()`) on PHP 8.1+
- **File:** `libraries/src/User/UserHelper.php`
- **Issue:** Four calls to the removed `mhash()` function were present, used to produce raw binary SHA1 and MD5 digests for legacy password hashing schemes (`SHA1`, `MD5`, `SHA1Salted`, `MD5Salted`).
- **Fix:** Replaced with `hash($algo, $data, true)`. The third argument `true` returns raw binary output, matching `mhash()`'s byte-for-byte output — this is critical for verifying existing stored passwords.

```php
// Before:
base64_encode(mhash(MHASH_SHA1, $plaintext))
base64_encode(mhash(MHASH_MD5, $plaintext))
base64_encode(mhash(MHASH_SHA1, $plaintext . $salt) . $salt)
base64_encode(mhash(MHASH_MD5, $plaintext . $salt) . $salt)

// After:
base64_encode(hash('sha1', $plaintext, true))
base64_encode(hash('md5', $plaintext, true))
base64_encode(hash('sha1', $plaintext . $salt, true) . $salt)
base64_encode(hash('md5', $plaintext . $salt, true) . $salt)
```

---

### P-15 — `strftime()` deprecated (PHP 8.1) / removed (PHP 9)
- **Severity:** `E_DEPRECATED` on PHP 8.1; fatal on PHP 9
- **Files patched:**
  - `libraries/src/HTML/HTMLHelper.php`
  - `libraries/joomla/form/fields/calendar.php`
- **Issue:** `strftime()` was used to format date values for display in calendar fields. PHP 8.1 deprecated `strftime()` and PHP 9 will remove it.
- **Fix:**
  - Added a new `public static function strftimeToDateFormat(string $strftimeFormat): string` method to `HTMLHelper`. It uses `strtr()` with a static map to convert `strftime` format specifiers (e.g. `%Y`, `%m`, `%d`) to their `date()` equivalents (e.g. `Y`, `m`, `d`). All standard `%`-specifiers are covered.
  - Both call sites updated to `date(static::strftimeToDateFormat($format), strtotime($value))` / `date(JHtml::strftimeToDateFormat($this->format), strtotime($this->value))`.
  - The method is declared `public` (not `protected`) because `calendar.php` calls it externally via the `JHtml` alias registered in `libraries/classmap.php`.

---

### P-16 — `utf8_encode()` in Twitter library (PHP 8.2)
- **Severity:** Fatal (`Call to undefined function utf8_encode()`) on PHP 8.2+
- **File:** `libraries/joomla/twitter/statuses.php`
- **Issue:** Two remaining calls to the removed `utf8_encode()` were present in the Twitter API status update methods; the first-pass fix (P-1) had not covered this file.
- **Fix:** Both replaced with `mb_convert_encoding($status, 'UTF-8', 'ISO-8859-1')`.
