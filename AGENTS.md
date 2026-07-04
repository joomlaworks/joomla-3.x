# Joomla 3.x (JoomlaWorks Security Distribution) — Agent Onboarding & Patch Log

---

## For the AI Agent — Read This First

This file is the primary onboarding document for resuming work on this project. Read it fully before making any changes.

### What this project is

A security-hardened, PHP 8.x-compatible fork of Joomla 3.10.20 eLTS, maintained by JoomlaWorks (Fotis Evangelou). It is **not** an official Joomla release. The goal is to keep Joomla 3.x sites running safely on modern PHP and MySQL/MariaDB versions, with backported CVE fixes from Joomla 4/5/6.

- **GitHub repo:** https://github.com/joomlaworks/joomla-3.x
- **Current version:** Joomla 3.14.0 (released July 4, 2026)
- **Minimum PHP:** 7.4 — **all code changes must remain compatible with PHP 7.4**
- **Tested up to PHP:** 8.5
- **Database support:** MySQL 5.7+, MySQL 8.x, MariaDB, PostgreSQL, SQL Azure

### Key conventions to follow

- **PHP 7.4 minimum:** The `?Type` nullable syntax is fine (valid since PHP 7.1). The `#[\AllowDynamicProperties]` attribute is fine (parsed as a comment on PHP 7.x). Never use PHP 8.0+ syntax that would be a parse error on 7.4.
- **No comments unless the WHY is non-obvious.** Do not narrate what code does.
- **Security patches:** Always check `libraries/vendor/joomla/filter/src/InputFilter.php` (vendor-level) AND `libraries/src/Filter/InputFilter.php` (CMS-level) — they are separate implementations and both may need patching.
- **SQL changes** affect three files: `installation/sql/mysql/joomla.sql`, `installation/sql/postgresql/joomla.sql`, `installation/sql/sqlazure/joomla.sql`. Schema migration SQL goes in `administrator/components/com_admin/sql/updates/{mysql,postgresql,sqlazure}/`. File naming: `{version}-{YYYY-MM-DD}.sql` (e.g. `3.12.0-2026-05-21.sql`).
- **Changelog:** `CHANGELOG.md` is the detailed log; `README.md` has a brief per-version summary. Always update both.
- **AGENTS.md** (this file): append a new section for every version worked on, documenting every change made. This is how future sessions resume without losing context.

### Open considerations for future sessions

- **Language-file version fingerprinting:** As of 3.14.0, `administrator/language/en-GB/{en-GB.xml,install.xml}`, `language/en-GB/{en-GB.xml,install.xml}`, and `administrator/manifests/packages/pkg_en-GB.xml` correctly report the running version (previously stuck at the stale `3.10.20` from the original eLTS base, which caused false-positive "vulnerable old version" flags from some scanners). The tradeoff: unauthenticated vulnerability scanners/bots commonly fingerprint Joomla version by reading these language metafiles (no login required), and an accurate `3.14` value now lets them pinpoint the exact version precisely — and a version number with no corresponding official Joomla release could itself signal "this is a patched fork," inviting more targeted probing. Consider whether to deliberately desynchronize or obscure the language-file version from `JVERSION` in a future release, without breaking the update system's version comparisons. Not yet decided or implemented — raised by the maintainer on 2026-07-04, no fix applied.

### What already exists and must NOT be re-applied

The following fixes are already in the codebase. Do not duplicate them:

- CVE-2025-54476 + H-1 extension (whitespace stripping + `\r\v\f` in `checkAttribute()`)
- CVE-2025-63083 (pagebreak toc.php htmlspecialchars)
- CVE-2026-21629 (com_ajax guest check)
- H-2 through H-8 (joomlaupdate ACL, TOTP timing, Yubikey HMAC, restore.php, RSS escaping, password reset timing, eval() removal)
- U-1 through U-3 (MediaHelper blocklist, sniff offset, images/.htaccess)
- P-1 through P-16 (all PHP 8.x compat fixes documented in the 3.11 section below)
- CVE-2025-63082 (data: URI blocking in checkAttribute)
- CVE-2024-40747 (ModuleHelper chrome attribs escaping)
- CVE-2025-25226 (quoteNameStr null byte/backslash rejection)
- CVE-2026-21631 (com_associations edit.php data-title escaping)
- All `#[\AllowDynamicProperties]` additions (Table, CMSObject, idna_convert)
- All remaining implicit-nullable fixes in fof/, vendor/joomla/session, vendor/joomla/data, vendor/joomla/di, vendor/google/recaptcha, vendor/symfony/yaml, vendor/joomla/filesystem, plugins/privacy/*
- All `(boolean)` → `(bool)` casts (libraries/src/ and libraries/joomla/, 13 files)
- `Uri::getInstance()` null guard (null → 'SERVER' coercion)
- `Uri::getInstance()` `HTTP_HOST` absent-in-CLI guard (`$httpHost` variable with `'localhost'` fallback before the SERVER URI branch — both Apache and IIS paths)
- Q-3 through Q-11 PHP 8.x fixes (see 3.13 patch log): null guards in `HtmlView::escape()`, `Date::__construct()`, `ListModel::populateState()` (×2), `utf8_ltrim/rtrim/trim`, `Json::stringToObject()`; declared properties `$registeredurlparams` on `CMSApplication`, `$itemTags` on `TagsHelper`, `$empty` and `$dates` on `FinderIndexerQuery`
- Q-12/Q-13 PHP 8.5 fixes (see 3.14.0 patch log, in progress): `HtmlDocument::getBuffer()`/`setBuffer()` null-array-offset normalization; `Image::destroy()`/`Backgroundfill::execute()` no longer call the deprecated `imagedestroy()`
- `fixSchemas()` in `com_admin/script.php` (auto-runs SQL migrations + syncs `#__schemas` + syncs `manifest_cache.version` on upgrade)
- `administrator/manifests/files/joomla.xml` version and `<updateservers>` URL (updated; do not revert)
- `deleteUnexistingFiles()` 3.12 entries (beez3, hathor, eos310, phpversioncheck directories and language files)
- `com_joomlaupdate` OPcache fix (`cleanup()` reads version from `joomla.xml`, `complete.php` uses session value)

### Update server

The update server uses a **two-file architecture** that mirrors `update.joomla.org` exactly. The update site type in `#__update_sites` is `collection`, which means Joomla uses `CollectionAdapter` to parse `list.xml`. `CollectionAdapter` only understands `<extensionset>/<extension>` tags — it will silently return nothing if given an `<updates>` file.

- **`docs/list.xml`** — `<extensionset>` collection. Each `<extension>` entry has a `targetplatformversion` regex matched against `JVERSION` on the visitor's site, and a `detailsurl` pointing to `extension.xml`. Add one entry per supported minor version.
- **`docs/extension.xml`** — `<updates>` details file. Contains the `<update>` block with the download URL. `com_joomlaupdate` fetches this via `detailsurl` to display the "Update Now" button and download the package.
- **Download URL:** `https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip`
- **GitHub Action:** `.github/workflows/rolling-release.yml` — rebuilds the zip on every push to `main` using `git archive`

**To release a new version (e.g. 3.14.0):**
1. Bump `MINOR_VERSION` (and `RELEASE`, `DEV_LEVEL`) in `libraries/src/Version.php`
2. In `docs/list.xml`: bump `version="3.13.0"` → `version="3.14.0"` in all `<extension>` entries, and add a new entry for `targetplatformversion="3.14"`
3. In `docs/extension.xml`: bump `<version>3.13.0</version>` → `<version>3.14.0</version>` and update `<infourl>`
4. In `administrator/manifests/files/joomla.xml`: bump `<version>` to match and update `<creationDate>`
5. Update `CHANGELOG.md` and `README.md`, push

### Removed in 3.12 (do not re-add)

- `plugins/quickicon/eos310` and all its language/media files
- `plugins/quickicon/phpversioncheck` and its language files
- `templates/beez3` and its language files
- `administrator/templates/hathor` and its language files

### CVEs confirmed NOT applicable to 3.x (do not re-investigate)

| CVE | Reason |
|-----|--------|
| CVE-2026-21630 | SQL injection in com_content webservice — 4.0.0+ only |
| CVE-2026-21632 | XSS in article title outputs — 4.0.0+ only |
| CVE-2026-23898 | Arbitrary file deletion in com_joomlaupdate — 4.0.0+ only |
| CVE-2026-23899 | Improper access check in webservice endpoints — 4.0.0+ only |

> **Note:** CVE-2025-63082 and CVE-2026-21631 were initially marked "4.0.0+ only" in the 3.11 review but were later confirmed to affect 3.x and have been patched in 3.12.

---

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

## CVEs Reviewed but Not Applicable to 3.x (at 3.11 review time)

| CVE | Reason not patched |
|-----|--------------------|
| CVE-2025-63082 | ~~XSS for data URLs — affects 4.0.0+ only~~ **CORRECTION: patched in 3.12** |
| CVE-2026-21630 | SQL injection in com_content webservice — 4.0.0+ only (no webservice API in 3.x) |
| CVE-2026-21631 | ~~XSS in com_associations comparison view — 4.0.0+ only~~ **CORRECTION: patched in 3.12** |
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

---

# Joomla 3.12.0 — Patch Log

**Date:** May 21, 2026
**Base version:** Joomla 3.11.0
**Patched version:** Joomla 3.12.0

---

## PHP 8.x Compatibility Fixes — Session 3

### Q-1 — `#[\AllowDynamicProperties]` on base classes (PHP 8.2)
- **Files:** `libraries/src/Table/Table.php`, `libraries/src/Object/CMSObject.php`, `libraries/idna_convert/idna_convert.class.php`
- **Issue:** PHP 8.2 deprecated dynamic property creation. `Table` uses `bind()` to set arbitrary database column names as properties; `CMSObject` uses `set()` for arbitrary property names. Both are intentional by design. `idna_convert` has internal dynamic properties.
- **Fix:** Added `#[\AllowDynamicProperties]` attribute to all three classes. On PHP 7.x the `#` is a line comment so the attribute line is silently ignored — fully backward-compatible. The attribute propagates to all subclasses, fixing every concrete Table subclass and every JObject descendant in one change.

### Q-2 — Remaining implicit-nullable typed parameters (PHP 8.1/8.5)
- **Scope:** 17 files missed by the P-10 automated pass
- **Files:**
  - `libraries/fof/form/header.php`, `libraries/fof/encrypt/aes.php`, `libraries/fof/encrypt/aes/interface.php`, `libraries/fof/encrypt/aes/mcrypt.php`, `libraries/fof/encrypt/aes/openssl.php`, `libraries/fof/database/query.php`, `libraries/fof/database/factory.php` — FOF library
  - `libraries/vendor/joomla/session/Joomla/Session/Session.php`
  - `libraries/vendor/joomla/data/src/DataObject.php`, `DataSet.php`, `DumpableInterface.php`
  - `libraries/vendor/joomla/di/src/Container.php`
  - `libraries/vendor/google/recaptcha/src/ReCaptcha/ReCaptcha.php`
  - `libraries/vendor/symfony/yaml/Exception/ParseException.php`
  - `libraries/vendor/joomla/filesystem/src/Exception/FilesystemException.php`
  - `plugins/privacy/actionlogs/actionlogs.php`, `message/message.php`, `content/content.php`, `contact/contact.php`, `consents/consents.php`, `user/user.php` (3 occurrences)
- **Fix:** `Type $p = null` → `?Type $p = null` on all affected signatures.

---

## Security Patches — Session 3 (backported from Joomla 4/5/6 via TLWebdesign/Joomla-3-EOL-Security-Fixes audit)

### S-1 — CVE-2025-63082 — Data URI XSS in InputFilter
- **File:** `libraries/vendor/joomla/filter/src/InputFilter.php`
- **Change:** After the existing whitespace stripping in `checkAttribute()`, added a check that rejects any `data:` URI unless it matches `^data:image/(png|gif|jpe?g|webp);base64,`. Previously marked "4.0.0+ only" in 3.11 — this was incorrect; the filter class is shared and the vulnerability is present in 3.x.

### S-2 — CVE-2024-40747 — XSS via module chrome attributes
- **File:** `libraries/src/Helper/ModuleHelper.php`
- **Change:** In `renderModule()`, all string values in `$attribs` are now run through `htmlspecialchars(ENT_QUOTES, 'UTF-8')` after the `onRenderModule` event fires and before they are passed to `modChrome_*` template functions, which echo them directly into HTML.

### S-3 — CVE-2025-25226 — SQL injection via database identifier names
- **File:** `libraries/joomla/database/driver.php`
- **Change:** `quoteNameStr()` now iterates over all identifier parts before quoting and throws `InvalidArgumentException` if any part contains a null byte (`\x00`) or a backslash (`\`). Both can break out of the quoting delimiters.

### S-4 — CVE-2026-21631 — XSS in com_associations side-by-side editor
- **File:** `administrator/components/com_associations/views/association/tmpl/edit.php`
- **Change:** `$this->referenceTitle`, `$this->referenceTitleValue`, and `$this->targetTitle` were echoed raw into `data-*` HTML attributes. All three are now wrapped with `$this->escape()`. Previously marked "4.0.0+ only" — this was incorrect; `com_associations` has been in core since Joomla 3.7.

---

## Update Server Infrastructure

Two-file architecture (mirrors `update.joomla.org` exactly — see "Update server" in the guidance section above for the full explanation):

- **`docs/list.xml`** — `<extensionset>` collection. Each `<extension>` entry has `version`, `targetplatformversion` (regex against `JVERSION`), and `detailsurl` pointing to `extension.xml`. Served at `https://joomlaworks.github.io/joomla-3.x/list.xml`.
- **`docs/extension.xml`** — `<updates>` details file with the actual `<update>` block (download URL, `<targetplatform>`, `<php_minimum>`, etc.). Served at `https://joomlaworks.github.io/joomla-3.x/extension.xml`. This is what `com_joomlaupdate` fetches to display the update and get the package URL.
- **`docs/.nojekyll`** — Prevents GitHub Pages from running Jekyll on the `docs/` folder (required so XML files are served raw).
- **`.github/workflows/rolling-release.yml`** — On every push to `main`: runs `git archive --format=zip HEAD -o joomla-latest.zip`, deletes and recreates the `rolling` GitHub Release. The download URL `…/releases/download/rolling/joomla-latest.zip` is permanent. `git archive` produces a root-level zip (no subdirectory prefix), which is required by Kickstart inside `com_joomlaupdate`.
- **Installation SQL** (all three DB variants) — `#__update_sites` row 1 (`type='collection'`) now points to `https://joomlaworks.github.io/joomla-3.x/list.xml` instead of `update.joomla.org`.
- **`com_joomlaupdate/models/default.php`** — All `$updateURL` cases (default, next, testing) now point to the GitHub Pages feed.

---

## Removed Extensions & Templates

The following items were removed from the distribution entirely in 3.12:

| Item | Type | Reason |
|------|------|--------|
| `plugins/quickicon/eos310` | Plugin | Joomla 3.x end-of-service notice — irrelevant in this distribution |
| `plugins/quickicon/phpversioncheck` | Plugin | PHP version nag — irrelevant given our active PHP 8.x support |
| `templates/beez3` | Frontend template | Legacy, unmaintained; protostar is the maintained default |
| `administrator/templates/hathor` | Backend template | Legacy, unmaintained; isis is the maintained default |

Also removed: associated language files in `administrator/language/en-GB/` and `language/en-GB/`, and the `media/plg_quickicon_eos310/` JS directory.

**Installation SQL** — all four extension rows, their `#__template_styles` entries (style IDs 4 and 5), and the hathor `#__postinstall_messages` entry were removed from all three installation SQL files.

**Migration SQL** — `administrator/components/com_admin/sql/updates/{mysql,postgresql,sqlazure}/3.12.0-2026-05-21.sql` runs automatically on upgrade via `com_joomlaupdate`. It:
1. Reassigns any site using beez3/hathor as their global default template to protostar/isis (using a derived-subquery `EXISTS` check to avoid MySQL's "can't update target table" restriction)
2. Deletes all `#__template_styles` rows for beez3 and hathor
3. Deletes the four `#__extensions` records
4. Deletes the hathor `#__postinstall_messages` record
5. Cleans up any orphaned `#__update_sites_extensions` rows

---

## Post-Release Bug Fixes — May 22, 2026

Three bugs discovered after the 3.12.0 release was published, fixed before the next version bump.

### B-1 — Update feed format wrong: `<updates>` instead of `<extensionset>` in `list.xml`
- **Files:** `docs/list.xml` (rewritten), `docs/extension.xml` (new file)
- **Root cause:** The `#__update_sites` entry for the Joomla core has `type='collection'`. This means Joomla uses `CollectionAdapter` to parse `list.xml`. `CollectionAdapter` only handles `<extensionset>/<extension>` elements — it silently ignores `<updates>/<update>` content entirely. The original `list.xml` was in `<updates>` format, so every update check returned nothing.
- **Fix:** Rewrote `docs/list.xml` as an `<extensionset>` collection with one `<extension>` entry per supported minor version (3.10, 3.11, 3.12), each pointing `detailsurl` to `docs/extension.xml`. Created `docs/extension.xml` as the `<updates>` details file containing the download URL and `<targetplatform>` check. This matches the `update.joomla.org/core/list.xml` + `extension.xml` architecture exactly.

### B-2 — `MINOR_VERSION = 11` in `Version.php` (should be 12)
- **File:** `libraries/src/Version.php`
- **Root cause:** When bumping from 3.11 to 3.12, `MINOR_VERSION` was not updated. Since `JVERSION` is computed as `MAJOR_VERSION . '.' . MINOR_VERSION . '.' . PATCH_VERSION` (via `getShortVersion()` in `libraries/cms.php`), the constant `JVERSION` evaluated to `'3.11.0'` instead of `'3.12.0'`. The deprecated `RELEASE` constant was correctly set to `'3.12'`, but nothing used it for the version constant.
- **Impact:** After upgrading to 3.12, the site would still report `JVERSION = '3.11.0'`. The `com_joomlaupdate` "hasUpdate" check (`version_compare($latest, JVERSION, '>')`) would then evaluate `version_compare('3.12.0', '3.11.0', '>') = true` — meaning the update notification would reappear on every check even after a successful upgrade.
- **Fix:** `MINOR_VERSION` changed from `11` to `12`.

### B-4 — PHP 8.5: `(boolean)` cast deprecated
- **Files:** `libraries/src/Date/Date.php`, `libraries/src/Helper/TagsHelper.php`, `libraries/src/Table/ContentHistory.php`, `libraries/src/Table/Table.php`, `libraries/src/Log/Logger/FormattedtextLogger.php`, `libraries/src/Language/Text.php`, `libraries/src/Language/Associations.php`, `libraries/src/Layout/BaseLayout.php`, `libraries/src/Microdata/Microdata.php`, `libraries/src/Access/Rule.php`, `libraries/joomla/database/iterator.php`, `libraries/joomla/database/exporter.php`, `libraries/joomla/database/importer.php`
- **Root cause:** PHP 8.5 deprecated the non-canonical `(boolean)` cast alias in favour of `(bool)`. 17 occurrences across 13 files. The first deprecation notice to appear (from `Date.php` during CLI bootstrap) contributes to the "headers already sent" cascade.
- **Fix:** `sed -i 's/(boolean)/(bool)/g'` across all 13 files.

### B-5 — PHP 8.5: `null` used as array offset in `Uri::getInstance()`
- **File:** `libraries/src/Uri/Uri.php`
- **Root cause:** `Uri::getInstance($uri)` uses `$uri` as a key in `static::$instances[$uri]`. In CLI context, callers such as `WebApplication` pass `$this->get('uri.request')` which returns `null` when the registry key is unset. PHP 8.5 deprecated using `null` as an array offset. The resulting deprecation notice is the first line of output in a CLI script, which triggers the fatal "headers already sent" session startup failure (same cascade as P-7/P-8).
- **Fix:** Added `if ($uri === null) { $uri = 'SERVER'; }` guard at the top of `getInstance()`, before the array key is first used (line 59, 119, 122 in original). `'SERVER'` is the default value already used when no argument is passed, making this semantically correct.

### B-3 — Migration SQL used wrong column name `language_key` on `#__postinstall_messages`
- **Files:** `administrator/components/com_admin/sql/updates/mysql/3.12.0-2026-05-21.sql`, `…/postgresql/…`, `…/sqlazure/…`
- **Root cause:** The `#__postinstall_messages` table has no `language_key` column. The correct column for the language key is `title_key`. The migration SQL's `DELETE FROM #__postinstall_messages WHERE language_key = 'TPL_HATHOR_MESSAGE_POSTINSTALL_TITLE'` triggered MySQL error 1054 ("Unknown column 'language_key' in 'where clause'") mid-migration on first upgrade attempt.
- **Fix:** Column name changed from `language_key` to `title_key` in all three SQL variants. Sites that hit this error mid-migration need to run the corrected DELETE manually: `DELETE FROM #__postinstall_messages WHERE title_key = 'TPL_HATHOR_MESSAGE_POSTINSTALL_TITLE';`

---

# Joomla 3.13.0 — Patch Log

**Date:** May 31, 2026
**Base version:** Joomla 3.12.0
**Patched version:** Joomla 3.13.0

---

## PHP 8.x Compatibility Fixes — Session 4

The following fixes were identified from a community report posted in GitHub Discussions (user MaghSamana). All are backward-compatible with PHP 7.4.

### Q-3 — `htmlspecialchars(null)` in `HtmlView::escape()` (PHP 8.1)
- **File:** `libraries/src/MVC/View/HtmlView.php`
- **Issue:** `escape()` passed `$var` directly to `htmlspecialchars()` or a custom escape function. When `$var` is `null` (common for optional database fields), PHP 8.1 deprecated passing `null` to `htmlspecialchars()`. This fires on every view that renders a null field through `escape()`.
- **Fix:** Added `if ($var === null) { return ''; }` guard at the top of `escape()` before any function call.

### Q-4 — `strtoupper(null)` in `ListModel::populateState()` (PHP 8.1)
- **File:** `libraries/src/MVC/Model/ListModel.php` (two occurrences, lines 568 and 616)
- **Issue:** `$value` comes from `getUserStateFromRequest()` which returns `null` when the key is absent and no default is set. `strtoupper(null)` was deprecated in PHP 8.1.
- **Fix:** Changed both to `strtoupper((string) $value)`.

### Q-5 — `DateTime::__construct(null)` in `Date::__construct()` (PHP 8.1)
- **File:** `libraries/src/Date/Date.php`
- **Issue:** The parent `DateTime::__construct()` was called with `$date` which can be `null` if a caller passes `null` explicitly (e.g. `new JDate(null)` from third-party extensions). PHP 8.1 deprecated passing `null` where a `string` is expected.
- **Fix:** Changed to `parent::__construct($date ?? 'now', $tz)`.

### Q-6 — Dynamic property `$registeredurlparams` on `CMSApplication` (PHP 8.2)
- **File:** `libraries/src/Application/CMSApplication.php`
- **Issue:** `BaseController` (and the FOF controller) assign `$app->registeredurlparams` directly on the application object in both frontend and backend contexts. The property was never declared in any application class, triggering PHP 8.2 dynamic property deprecation on every cacheable request from any controller that registers URL params.
- **Fix:** Declared `public $registeredurlparams = null;` in `CMSApplication` (the common base for `SiteApplication` and `AdministratorApplication`).

### Q-7 — `trim(null)` / `ltrim(null)` / `rtrim(null)` in phputf8 (PHP 8.1)
- **File:** `libraries/vendor/joomla/string/src/phputf8/trim.php`
- **Issue:** `utf8_ltrim()`, `utf8_rtrim()`, and `utf8_trim()` all call the native PHP trim functions with `$str` which can be `null`. PHP 8.1 deprecated passing `null` to these string functions.
- **Fix:** Added `(string)` cast: `ltrim((string) $str)`, `rtrim((string) $str)`, `trim((string) $str)` in the short-circuit early-return paths of all three functions.

### Q-8 — `trim(null)` in `Registry/Format/Json::stringToObject()` (PHP 8.1)
- **File:** `libraries/vendor/joomla/registry/src/Format/Json.php`
- **Issue:** `$data` can be `null` when passed from upstream code. PHP 8.1 deprecated `null` to `trim()`.
- **Fix:** Changed to `$data = trim((string) $data);`.

### Q-9 — Dynamic property `$itemTags` on `TagsHelper` (PHP 8.2)
- **File:** `libraries/src/Helper/TagsHelper.php`
- **Issue:** `getItemTags()` assigns `$this->itemTags = $db->loadObjectList()` without the property being declared in the class. Triggers PHP 8.2 dynamic property deprecation whenever tags are loaded.
- **Fix:** Declared `public $itemTags = array();` in the class body, before `$typeAlias`.

### Q-10 — Dynamic properties `$empty` and `$dates` on `FinderIndexerQuery` (PHP 8.2)
- **File:** `administrator/components/com_finder/helpers/indexer/query.php`
- **Issue:** Both `$this->empty` and `$this->dates` are assigned in `__construct()` without class-level declarations, triggering PHP 8.2 dynamic property deprecation whenever a Smart Search query is constructed.
- **Fix:** Declared `public $empty = false;` and `public $dates;` in the class body after `$when2`.

---

## CLI Compatibility Fix

### Q-11 — Undefined `HTTP_HOST` warning in CLI context in `Uri::getInstance()`
- **File:** `libraries/src/Uri/Uri.php`
- **Issue:** When building the server URI (`$uri == 'SERVER'`), both the Apache path (line 95) and the IIS/fallback path (line 106) accessed `$_SERVER['HTTP_HOST']` directly. `HTTP_HOST` is never set in CLI context. The resulting PHP warning printed to stdout, poisoning CLI output and cascading into the "headers already sent" session startup failure. This is separate from the B-5 fix (which guarded against `null` as the `$uri` argument itself).
- **Fix:** Added `$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';` before the Apache/IIS conditional, and replaced both raw `$_SERVER['HTTP_HOST']` accesses with `$httpHost`.

---

## Infrastructure & Update Fixes

### I-1 — Auto-fix schema mismatch on upgrade
- **File:** `administrator/components/com_admin/script.php`
- **Issue:** The `update()` method called `updateDatabase()` (MySQL engine change + plugin cleanup only) but never ran the SQL migration files in `sql/updates/`. After every upgrade, the Extensions → Manage → Database view showed "Database update version (old) does not match CMS version (new)", requiring a manual "Fix" click that ran `JSchemaChangeset::fix()` + updated `#__schemas` + updated `manifest_cache.version`.
- **Fix:** Added protected method `fixSchemas()` and called it from `update()` after `updateDatabase()`. The method: (1) instantiates `JSchemaChangeset` over `sql/updates/`; (2) calls `fix()` to apply all pending migration files; (3) updates the `#__schemas` row for extension_id 700 to the latest SQL file version; (4) syncs `manifest_cache.version` for extension_id 700 to `$cmsVersion->getShortVersion()`.

### I-2 — `joomla.xml` version stale + wrong update server URL
- **File:** `administrator/manifests/files/joomla.xml`
- **Issue:** The manifest `<version>` field was still `3.10.20-elts` (never updated from the original eLTS value). Since `updateManifestCaches()` reads this file and writes to `manifest_cache`, every upgrade stored `3.10.20-elts` as the Joomla extension version — the actual root cause of the "database update version does not match" banner. Additionally, `<updateservers>` still pointed to `https://update.joomla.org/core/list.xml`; the JInstaller processes this during an upgrade and would silently revert `#__update_sites` row 1 back to the official Joomla server, breaking future update notifications.
- **Fix:** Updated `<version>` to the current release version and `<creationDate>` to the release date. Changed `<updateservers>` URL to `https://joomlaworks.github.io/joomla-3.x/list.xml`.
- **Ongoing:** This file must be updated on every version bump (added to the release checklist as step 4).

### I-3 — Filesystem cleanup missing for 3.12 removed items
- **File:** `administrator/components/com_admin/script.php` (`deleteUnexistingFiles()`)
- **Issue:** The 3.12 migration SQL correctly removed DB records for `beez3`, `hathor`, `eos310`, and `phpversioncheck` — but Kickstart (the zip extractor used by `com_joomlaupdate`) only overlays files and never deletes anything absent from the package. Files were left on disk. `deleteUnexistingFiles()` is the correct mechanism but the 3.12 entries were never added.
- **Fix:** Added a `// Joomla 3.12.0` block to `$files` (language files for all four items) and `$folders` (`/templates/beez3`, `/administrator/templates/hathor`, `/plugins/quickicon/eos310`, `/plugins/quickicon/phpversioncheck`, `/media/plg_quickicon_eos310`).

### I-4 — `com_joomlaupdate` complete screen shows old version (OPcache)
- **Files:** `administrator/components/com_joomlaupdate/controllers/update.php`, `administrator/components/com_joomlaupdate/views/default/tmpl/complete.php`
- **Issue:** After an upgrade, the "Your Joomla version is now X.Y.Z" message on the complete screen used `JVERSION`. PHP-FPM runs multiple worker processes each with their own OPcache. `opcache_reset()` is called during `finalise()` but only resets the current worker; the `cleanup()` and `complete` requests can land on different workers with stale `Version.php` still cached, so `JVERSION` evaluates to the pre-upgrade value.
- **Fix:** In `cleanup()`, after `$model->cleanUp()`: read the new version from `administrator/manifests/files/joomla.xml` on disk using `simplexml_load_file()` (XML files are never bytecode-cached by OPcache); store it in `com_joomlaupdate.newversion` user state. In `complete.php`: read from that session key (falling back to `JVERSION`) and clear it immediately after use.

---

# Joomla 3.14.0 — Patch Log

**Date:** July 4, 2026
**Base version:** Joomla 3.13.0
**Patched version:** Joomla 3.14.0

---

## PHP 8.x Compatibility Fixes — Session 5

Both fixes below were contributed via GitHub PR #13 (github.com/joomlaworks/joomla-3.x/pull/13) by community member @raramuridesign. Verified against upstream PHP 8.5 deprecation notices before merging; both are backward-compatible with PHP 7.4.

### Q-12 — `null` used as array offset in `HtmlDocument::getBuffer()` / `setBuffer()` (PHP 8.5)
- **File:** `libraries/src/Document/HtmlDocument.php`
- **Issue:** `getBuffer($type, $name, $attribs)` and `setBuffer()` index `parent::$_buffer[$type][$name][$title]` directly. `$name` defaults to `null` and `$title` (pulled from `$attribs['title']`) is `null` whenever no title is set — the common case for most modules/components. PHP 8.5 deprecates using `null` as an array offset (same class of issue as B-5/Q-11 against `Uri::getInstance()`), so this fired on nearly every page render.
- **Fix:** In `getBuffer()`, added local `$bufferName`/`$bufferTitle` set to `''` when `$name`/`$title` are `null`, and used them for the buffer-indexing reads (the `isset()` check, the cached-`$data` read, and the final return). In `setBuffer()`, `$options['type']`/`['name']`/`['title']` are read through `?? ''` before being used as array keys, so both the write path and the read path converge on `''` as the actual stored key.

### Q-13 — `imagedestroy()` deprecated (PHP 8.5); stray `(boolean)` cast
- **Files:** `libraries/vendor/joomla/image/src/Image.php`, `libraries/vendor/joomla/image/src/Filter/Backgroundfill.php`
- **Issue:** `Image::destroy()` called `imagedestroy($this->getHandle())`. Since PHP 8.0, GD uses refcounted `GdImage` objects instead of resources, so `imagedestroy()` has had no effect for several major versions; PHP 8.5 deprecates calling it at all. `Backgroundfill::execute()` had a second occurrence — `imagedestroy($bg)` on a local temporary handle — found via codebase-wide grep after fixing the first (confirmed `imagedestroy` had no other call sites anywhere in the tree). Also found one remaining `(boolean)` cast in `Image::setThumbnailGenerate()`, missed by the B-4 sweep in 3.12 (that pass's file list didn't include this vendor file).
- **Fix:** `Image::destroy()` now sets `$this->handle = null` and returns `true` directly instead of calling `imagedestroy()` — `isLoaded()` (via `isValidImage($this->handle)`) still correctly reports `false` afterward, preserving the method's observable contract. In `Backgroundfill::execute()`, the `imagedestroy($bg)` call was simply removed — `$bg` is a local variable that goes out of scope at the end of the method, so the `GdImage` object is reclaimed by normal refcounting with no explicit call needed. `(boolean)` → `(bool)` in `setThumbnailGenerate()`.

---

## Language File Version Fix

### L-1 — Stale `3.10.20` version in en-GB language metafiles
- **Files:** `administrator/language/en-GB/en-GB.xml`, `administrator/language/en-GB/install.xml`, `language/en-GB/en-GB.xml`, `language/en-GB/install.xml`, `administrator/manifests/packages/pkg_en-GB.xml`
- **Issue:** These files still carried `<version>3.10.20</version>` (and `3.10.20.1` for the package manifest) — the original eLTS base version, never updated across the 3.11/3.12/3.13 bumps. Some vulnerability scanners fingerprint the Joomla version from these language metafiles (readable pre-auth, unlike `JVERSION` which requires more work to expose), so sites running this distribution were being flagged as "Joomla 3.10.20" and matched against CVEs already patched here.
- **Fix:** Bumped `<version>` to `3.14` (`pkg_en-GB.xml` to `3.14`, dropping the redundant `.1` patch suffix) and `<creationDate>` to `July 4th, 2026` in all five files, aligning them with the actual running version.
- **Open question:** see "Open considerations for future sessions" at the top of this file — reporting the true version here is more accurate but also lets scanners precisely fingerprint this fork. Not resolved in this release.

---

## Version Bump

**File:** `libraries/src/Version.php`

| Constant | Before | After |
|----------|--------|-------|
| `MINOR_VERSION` | `13` | `14` |
| `PATCH_VERSION` | `0` | `0` |
| `RELEASE` (deprecated) | `'3.13'` | `'3.14'` |
| `RELDATE` | `'31-May-2026'` | `'4-July-2026'` |

Also updated per the release checklist: `administrator/manifests/files/joomla.xml` (`<version>3.14.0</version>`, `<creationDate>July 4th, 2026</creationDate>`), `docs/list.xml` (all `<extension>` rows bumped to `version="3.14.0"`, new row added for `targetplatformversion="3.14"`), `docs/extension.xml` (`<version>3.14.0</version>`, name/description/infourl updated to 3.14).

The installation now reports itself as **Joomla! 3.14.0**.
