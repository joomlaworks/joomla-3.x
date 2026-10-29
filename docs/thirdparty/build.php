<?php

/**
 * Builds administrator/components/com_admin/thirdparty.json, the third-party software System Information lists: the primary
 * software bundled (not its plugins or the libraries it bundles itself), grouped, with its version, the date it states (as
 * YYYY.MM.DD; nothing when it states none), its licence, home page and location.
 *
 * Each entry's version is checked against its files (a pattern) or Composer's record (installed.json), so updating a library
 * without updating this list stops the build. Run it after adding, updating or removing third-party code:
 *
 *     php docs/thirdparty/build.php
 *
 * With --latest it also asks each entry's upstream (npm, Packagist, GitHub) for its latest release and keeps the answers in
 * docs/thirdparty/latest.json, which the plain build reads (it needs no network): System Information shows the entries at
 * their latest release in green, and the latest version of the others. Run it from time to time, and before a release.
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

$root     = dirname(__DIR__, 2);
$composer = json_decode(file_get_contents($root . '/libraries/vendor/composer/installed.json'), true);
$packages = array();

foreach (isset($composer['packages']) ? $composer['packages'] : $composer as $package) {
    $packages[$package['name']] = $package;
}

/*
 * group => entries: name, version, license, url, path, and how to check them:
 * - check: array(file, pattern): the pattern's first group must be the version; a "date" group (YYYY-MM-DD) gives the date
 * - composer: a package name in installed.json: its version and time
 * - use: "shared" when extensions may use it too (front-end libraries loaded through JHtml, editors, libraries extensions call),
 *   "core" when only Joomla 3.x UTD's own code does, "sub:<name>" for a sub-package of one of its extensions (a judgement,
 *   shown as the "Used by" column). Icons bundled as images or SVG aren't listed; icon fonts are
 * - upstream: where releases are published, for --latest: "npm:<package>", "packagist:<vendor/package>", "github:<owner/repo>",
 *   or "current" (always current: online services, whose current API the plugins use, and software without newer releases)
 * - date: the release date found elsewhere (YYYY-MM-DD: the project's releases, tags, registry or announcements; for
 *   copies Joomla adapted and kept, Joomla's last change), with "dateSource" saying where (shown as the date's tooltip)
 */
$groups = array(
    'JavaScript Libraries' => array(
        array('name' => 'jQuery', 'use' => 'shared', 'upstream' => 'npm:jquery', 'version' => '1.12.4', 'license' => 'MIT', 'url' => 'https://jquery.com', 'path' => 'media/system/js/jquery.js',
            'note' => 'Patched by Joomla', 'check' => array('media/system/js/jquery.js', '/jQuery JavaScript Library v([0-9.]+)-joomla.*?Date: (?<date>\d{4}-\d{2}-\d{2})/s')),
        array('name' => 'jQuery Migrate', 'use' => 'shared', 'upstream' => 'npm:jquery-migrate', 'version' => '1.4.1', 'license' => 'MIT', 'url' => 'https://github.com/jquery/jquery-migrate', 'path' => 'media/system/js/jquery-migrate.js',
            'check' => array('media/system/js/jquery-migrate.js', '/jQuery Migrate - v([0-9.]+) - (?<date>\d{4}-\d{2}-\d{2})/')),
        array('name' => 'jQuery UI (Core)', 'use' => 'shared', 'upstream' => 'npm:jquery-ui', 'version' => '1.14.2', 'license' => 'MIT', 'url' => 'https://jqueryui.com', 'note' => 'Built by Joomla 3.x UTD from its official build, with the 1.9 helpers it removed', 'path' => 'media/system/js/jquery.ui.core.js',
            'check' => array('media/system/js/jquery.ui.core.js', '/jQuery UI - v([0-9.]+) - (?<date>\d{4}-\d{2}-\d{2})/')),
        array('name' => 'MooTools Core', 'use' => 'shared', 'upstream' => 'github:mootools/mootools-core', 'version' => '1.4.5', 'date' => '2012-02-27', 'dateSource' => 'GitHub tag 1.4.5 (annotated)', 'license' => 'MIT', 'url' => 'https://mootools.net', 'path' => 'media/system/js/mootools-core.js', 'note' => 'Patched by Joomla 3.x UTD (Array.from)',
            'check' => array('media/system/js/mootools-core-uncompressed.js', "/version: '([0-9.]+)'/")),
        array('name' => 'MooTools More', 'use' => 'shared', 'upstream' => 'github:mootools/mootools-more', 'version' => '1.4.0.1', 'date' => '2011-09-10', 'dateSource' => 'GitHub tag 1.4.0.1 (annotated)', 'license' => 'MIT', 'url' => 'https://mootools.net/more', 'path' => 'media/system/js/mootools-more.js',
            'check' => array('media/system/js/mootools-more.js', '/version:"([0-9.]+)"/')),
        array('name' => 'JalaliDatePicker', 'use' => 'core', 'upstream' => 'npm:@majidh1/jalalidatepicker', 'version' => '1.0.0', 'date' => '2026-07-07', 'dateSource' => 'npm release 1.0.0', 'license' => 'MIT', 'url' => 'https://github.com/majidh1/JalaliDatePicker', 'path' => 'media/system/js/jalalidatepicker.min.js',
            'note' => 'Date fields of languages using the Jalali calendar', 'check' => array('media/system/js/jalalidatepicker-license.txt', '/JalaliDatePicker ([0-9.]+)/')),
        array('name' => 'Chosen', 'use' => 'shared', 'upstream' => 'npm:chosen-js', 'version' => '1.6.2', 'date' => '2016-08-02', 'dateSource' => 'GitHub release v1.6.2', 'license' => 'MIT', 'url' => 'https://harvesthq.github.io/chosen/', 'path' => 'media/system/css/chosen.css', 'note' => 'Its stylesheet and sprite only (the markup templates style), image paths changed by Joomla 3.x UTD; the script is Joomla 3.x UTD\'s own (CMS.chosen in global.js)',
            'check' => array('media/system/css/chosen.css', '/Version ([0-9.]+)/')),
        array('name' => 'Ajax Autocomplete for jQuery', 'use' => 'shared', 'upstream' => 'npm:devbridge-autocomplete', 'version' => '1.5.0', 'date' => '2025-07-09', 'dateSource' => 'npm, devbridge-autocomplete 1.5.0; GitHub tag v1.5.0', 'license' => 'MIT', 'url' => 'https://github.com/devbridge/jQuery-Autocomplete', 'path' => 'media/system/js/jquery.autocomplete.js',
            'check' => array('media/system/js/jquery.autocomplete.js', '/Ajax Autocomplete for jQuery, version ([0-9.]+)/')),
        array('name' => 'diff-match-patch', 'use' => 'core', 'version' => '', 'license' => 'Apache-2.0', 'url' => 'https://github.com/google/diff-match-patch', 'path' => 'media/com_contenthistory/js/contenthistory.js',
            'note' => 'Google Code release as stock Joomla 3 shipped it (older than the GitHub repository, so without a version); the comparison around it is Joomla\'s'),
    ),
    'CSS and JavaScript Frameworks' => array(
        array('name' => 'Bootstrap', 'use' => 'shared', 'upstream' => 'npm:bootstrap', 'version' => '2.3.2', 'date' => '2013-05-17', 'dateSource' => 'Bootstrap blog, "Bootstrap 2.3.2 released" (its GitHub release record is from July 2013)', 'license' => 'Apache-2.0', 'url' => 'https://getbootstrap.com/2.3.2/', 'path' => 'media/system/js/bootstrap.js',
            'note' => 'JavaScript and CSS; the stylesheets\' image paths changed by Joomla 3.x UTD', 'check' => array('media/system/js/bootstrap.js', '/bootstrap-transition\.js v([0-9.]+)/')),
    ),
    'Editors' => array(
        array('name' => 'TinyMCE', 'use' => 'shared', 'upstream' => 'npm:tinymce', 'version' => '8.10.0', 'date' => '2026-10-14', 'dateSource' => 'TinyMCE changelog ("8.10.0 - 2026-10-14")', 'license' => 'GPL-2.0-or-later', 'url' => 'https://www.tiny.cloud', 'path' => 'plugins/editors/tinymce_latest',
            'check' => array('plugins/editors/tinymce_latest/tinymce.min.js', '/TinyMCE version ([0-9.]+)/')),
        array('name' => 'TinyMCE (legacy)', 'use' => 'shared', 'upstream' => 'npm:tinymce', 'version' => '4.5.12', 'license' => 'LGPL-2.1', 'url' => 'https://www.tiny.cloud', 'path' => 'plugins/editors/tinymce',
            'check' => array('plugins/editors/tinymce/changelog.txt', '/Version ([0-9.]+) \((?<date>\d{4}-\d{2}-\d{2})\)/')),
        array('name' => 'CodeMirror', 'use' => 'shared', 'upstream' => 'npm:codemirror', 'version' => '5.65.21', 'date' => '2026-02-07', 'dateSource' => 'GitHub tag 5.65.21 (codemirror/codemirror5); npm agrees', 'license' => 'MIT', 'url' => 'https://codemirror.net/5/', 'path' => 'plugins/editors/codemirror',
            'check' => array('plugins/editors/codemirror/lib/codemirror.js', '/CodeMirror\.version = "([0-9.]+)"/')),
        array('name' => 'EasyMDE', 'use' => 'shared', 'upstream' => 'npm:easymde', 'version' => '2.21.0', 'date' => '2026-05-03', 'dateSource' => 'GitHub tag 2.21.0; npm agrees', 'license' => 'MIT', 'url' => 'https://github.com/Ionaru/easy-markdown-editor', 'path' => 'plugins/editors/easymde/vendor/easymde',
            'check' => array('plugins/editors/easymde/vendor/easymde/easymde.min.css', '/easymde v([0-9.]+)/')),
        array('name' => 'marked', 'use' => 'sub:EasyMDE', 'upstream' => 'npm:marked', 'version' => '18.1.2', 'date' => '2026-10-26', 'dateSource' => 'GitHub release v18.1.2', 'license' => 'MIT', 'url' => 'https://marked.js.org', 'note' => 'Converts the editor\'s Markdown to HTML', 'path' => 'plugins/editors/easymde/vendor/marked',
            'check' => array('plugins/editors/easymde/vendor/marked/marked.umd.js', '/marked v([0-9.]+)/')),
        array('name' => 'Turndown', 'use' => 'sub:EasyMDE', 'upstream' => 'npm:turndown', 'version' => '7.2.4', 'date' => '2026-04-03', 'dateSource' => 'GitHub tag v7.2.4; npm agrees', 'license' => 'MIT', 'url' => 'https://github.com/mixmark-io/turndown', 'note' => 'Converts HTML to Markdown for the editor', 'path' => 'plugins/editors/easymde/vendor/turndown'),
        array('name' => 'Editor.js', 'use' => 'shared', 'upstream' => 'npm:@editorjs/editorjs', 'version' => '2.31.7', 'date' => '2026-09-17', 'dateSource' => 'GitHub release v2.31.7; npm agrees', 'license' => 'Apache-2.0', 'url' => 'https://editorjs.io', 'path' => 'plugins/editors/editorjs/vendor/editorjs',
            'check' => array('plugins/editors/editorjs/vendor/editorjs/editorjs.umd.js', '/"([0-9]+\.[0-9]+\.[0-9]+)"/')),
    ),
    'Fonts and Icons' => array(
        array('name' => 'IcoMoon (Joomla icon font)', 'use' => 'shared', 'version' => '', 'date' => '2013-10-02', 'dateSource' => 'Joomla\'s last change to the font (joomla-cms 48e2895, "Added new IcoMoon icon font set")', 'license' => 'Royalty-free', 'url' => 'https://icomoon.io', 'path' => 'media/system/fonts'),
    ),
    // Not bundled: services the CAPTCHA plugins connect to, with the API version they use
    'External Services' => array(
        array('name' => 'Google reCAPTCHA', 'use' => 'core', 'upstream' => 'current', 'version' => 'v2, v3', 'license' => 'Google\'s terms of service', 'url' => 'https://developers.google.com/recaptcha', 'path' => 'plugins/captcha/recaptcha',
            'note' => 'Service by Google; used by "CAPTCHA - Google reCAPTCHA"'),
        array('name' => 'Cloudflare Turnstile', 'use' => 'core', 'upstream' => 'current', 'version' => 'v0', 'license' => 'Cloudflare\'s terms of service', 'url' => 'https://developers.cloudflare.com/turnstile/', 'path' => 'plugins/captcha/turnstile',
            'note' => 'Service by Cloudflare; used by "CAPTCHA - Cloudflare Turnstile"'),
        array('name' => 'hCaptcha', 'use' => 'core', 'upstream' => 'current', 'version' => 'v1', 'license' => 'hCaptcha\'s terms of service', 'url' => 'https://www.hcaptcha.com', 'path' => 'plugins/captcha/hcaptcha',
            'note' => 'Service by Intuition Machines; used by "CAPTCHA - hCaptcha"'),
        array('name' => 'Akismet', 'use' => 'core', 'upstream' => 'current', 'version' => '1.1', 'license' => 'Akismet\'s terms of service', 'url' => 'https://akismet.com', 'path' => 'plugins/captcha/akismet',
            'note' => 'Service by Automattic (REST API 1.1); used by "CAPTCHA - Akismet"'),
    ),
    'PHP Libraries' => array(
        array('name' => 'PHPMailer', 'use' => 'shared', 'upstream' => 'packagist:phpmailer/phpmailer', 'composer' => 'phpmailer/phpmailer', 'license' => 'LGPL-2.1', 'url' => 'https://github.com/PHPMailer/PHPMailer', 'path' => 'libraries/vendor/phpmailer/phpmailer',
            'note' => 'Patched by Joomla 3.x UTD (php_uname() guard)'),
        array('name' => 'SimplePie', 'use' => 'shared', 'upstream' => 'packagist:simplepie/simplepie', 'composer' => 'simplepie/simplepie', 'license' => 'BSD-3-Clause', 'url' => 'https://simplepie.org', 'path' => 'libraries/vendor/simplepie/simplepie',
            'note' => 'Needs PHP 7.2 (unused by core; JFactory::getFeedParser() returns false on PHP 7.1); patched by Joomla 3.x UTD (PHP 8.5, upstream #949 and #979)'),
        array('name' => 'lessphp', 'use' => 'shared', 'upstream' => 'packagist:leafo/lessphp', 'version' => '0.5.0', 'date' => '2014-11-24', 'dateSource' => 'Packagist and GitHub tag v0.5.0', 'license' => 'MIT', 'url' => 'https://github.com/leafo/lessphp', 'path' => 'libraries/vendor/leafo/lessphp',
            'note' => 'Patched by Joomla and by Joomla 3.x UTD (PHP 8)', 'check' => array('libraries/vendor/leafo/lessphp/lessc.inc.php', '/VERSION = "v([0-9.]+)"/')),
        array('name' => 'sodium_compat', 'use' => 'shared', 'upstream' => 'packagist:paragonie/sodium_compat', 'composer' => 'paragonie/sodium_compat', 'license' => 'ISC', 'url' => 'https://github.com/paragonie/sodium_compat', 'note' => 'Patched by Joomla 3.x UTD (PHP 8)', 'path' => 'libraries/vendor/paragonie/sodium_compat'),
        array('name' => 'Symfony Polyfill (ctype)', 'use' => 'shared', 'upstream' => 'packagist:symfony/polyfill-ctype', 'composer' => 'symfony/polyfill-ctype', 'license' => 'MIT', 'url' => 'https://github.com/symfony/polyfill-ctype', 'path' => 'libraries/vendor/symfony/polyfill-ctype'),
        array('name' => 'Symfony Yaml', 'use' => 'core', 'upstream' => 'packagist:symfony/yaml', 'composer' => 'symfony/yaml', 'license' => 'MIT', 'url' => 'https://symfony.com/components/Yaml', 'note' => 'Patched by Joomla 3.x UTD (PHP 8)', 'path' => 'libraries/vendor/symfony/yaml'),
        array('name' => 'PSR Log', 'use' => 'core', 'upstream' => 'packagist:psr/log', 'composer' => 'psr/log', 'license' => 'MIT', 'url' => 'https://www.php-fig.org/psr/psr-3/', 'path' => 'libraries/vendor/psr/log'),
        array('name' => 'TYPO3 PHAR Stream Wrapper', 'use' => 'core', 'upstream' => 'packagist:typo3/phar-stream-wrapper', 'composer' => 'typo3/phar-stream-wrapper', 'license' => 'MIT', 'url' => 'https://github.com/TYPO3/phar-stream-wrapper', 'path' => 'libraries/vendor/typo3/phar-stream-wrapper',
            'note' => 'Patched by Joomla 3.x UTD (PHP 8.4, safe unserialize)'),
        array('name' => 'IconCaptcha', 'use' => 'core', 'upstream' => 'packagist:fabianwennink/iconcaptcha', 'version' => '4.0.6', 'license' => 'MIT', 'url' => 'https://github.com/fabianwennink/IconCaptcha-PHP', 'path' => 'plugins/captcha/iconcaptcha/lib',
            'note' => 'With its widget (its css and js folders)', 'date' => '2025-06-16', 'dateSource' => 'CHANGELOG.md ("June 16, 2025")', 'check' => array('plugins/captcha/iconcaptcha/lib/CHANGELOG.md', '/^## ([0-9.]+) - /m')),
        array('name' => 'MySQL on SQLite (WordPress)', 'use' => 'core', 'upstream' => 'github:WordPress/sqlite-database-integration', 'version' => '3.1.1', 'date' => '2026-10-21', 'dateSource' => 'GitHub release v3.1.1', 'license' => 'GPL-2.0-or-later', 'url' => 'https://github.com/WordPress/sqlite-database-integration', 'path' => 'libraries/vendor/wordpress/mysql-on-sqlite',
            'note' => 'Patched by Joomla 3.x UTD (PATCHES.md)', 'check' => array('libraries/vendor/wordpress/mysql-on-sqlite/PATCHES.md', '/version ([0-9.]+), commit/')),
        array('name' => 'phpass', 'use' => 'core', 'upstream' => 'current', 'version' => '0.5', 'date' => '2017-05-07', 'dateSource' => 'Openwall, date of phpass-0.5.tar.gz', 'license' => 'Public domain', 'url' => 'https://www.openwall.com/phpass/', 'note' => 'Patched by Joomla 3.x UTD (PHP 8)', 'path' => 'libraries/phpass',
            'check' => array('libraries/phpass/PasswordHash.php', '/Version ([0-9.]+)/')),
        array('name' => 'IDNA Convert', 'use' => 'core', 'upstream' => 'github:phlylabs/idna-convert', 'version' => '0.8.0', 'license' => 'LGPL-2.1', 'url' => 'https://github.com/phlylabs/idna-convert', 'note' => 'Patched by Joomla and by Joomla 3.x UTD (PHP 8)', 'path' => 'libraries/idna_convert',
            'check' => array('libraries/idna_convert/idna_convert.class.php', '/@version ([0-9.]+) (?<date>\d{4}-\d{2}-\d{2})/')),
        array('name' => 'PHP Encryption (Defuse)', 'use' => 'core', 'upstream' => 'packagist:defuse/php-encryption', 'version' => '1.1', 'date' => '2015-01-24', 'dateSource' => 'GitHub tag v1.1 (annotated), which Crypto.php matches but for PHP 8 casts', 'license' => 'BSD-2-Clause', 'url' => 'https://github.com/defuse/php-encryption', 'path' => 'libraries/php-encryption', 'note' => 'Patched by Joomla 3.x UTD (PHP 8)'),
    ),
);

$out        = array();
$errors     = array();
$latestFile = __DIR__ . '/latest.json';
$latest     = is_file($latestFile) ? json_decode(file_get_contents($latestFile), true) : array();
$latest     = is_array($latest) ? $latest : array();

/**
 * @param   string  $url  An API address
 *
 * @return  mixed  Its JSON answer, or null
 */
function fetchJson($url)
{
    $headers = array('User-Agent: joomla-3.x-utd-thirdparty-build', 'Accept: application/json');

    if (getenv('GITHUB_TOKEN') && strpos($url, 'https://api.github.com/') === 0) {
        $headers[] = 'Authorization: Bearer ' . getenv('GITHUB_TOKEN');
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $headers));
    $body = curl_exec($curl);
    $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

    return $code === 200 && is_string($body) ? json_decode($body, true) : null;
}

/**
 * @param   array  $versions  Version strings
 *
 * @return  string|null  The highest stable one
 */
function highestStable(array $versions)
{
    $stable = array();

    foreach ($versions as $version) {
        $version = ltrim((string) $version, 'vV');

        if (preg_match('/^\d+(\.\d+)*$/', $version)) {
            $stable[] = $version;
        }
    }

    // Projects numbering x.y.z: a stray two-part tag ("1.20" among MooTools' 1.x.y) isn't a later release
    $full = array_filter($stable, function ($version) {
        return substr_count($version, '.') >= 2;
    });

    $best = null;

    foreach ($full ?: $stable as $version) {
        if ($best === null || version_compare($version, $best, '>')) {
            $best = $version;
        }
    }

    return $best;
}

/**
 * @param   string  $upstream  npm:..., packagist:... or github:...
 * @param   string  $major     The bundled version's major version, for the latest release of that series
 *
 * @return  array|null  version (the latest release), and series: the latest release in $major and its date (npm and
 *                      Packagist only), or null when nothing was found
 */
function latestRelease($upstream, $major)
{
    list($type, $name) = explode(':', $upstream, 2);
    $releases = array();

    switch ($type) {
        case 'npm':
            $data = fetchJson('https://registry.npmjs.org/' . str_replace('/', '%2F', $name));

            if (!isset($data['dist-tags']['latest'])) {
                return null;
            }

            foreach (isset($data['time']) ? $data['time'] : array() as $version => $time) {
                $releases[$version] = substr($time, 0, 10);
            }

            $latest = ltrim($data['dist-tags']['latest'], 'v');
            break;

        case 'packagist':
            $data = fetchJson('https://repo.packagist.org/p2/' . $name . '.json');

            if (!isset($data['packages'][$name])) {
                return null;
            }

            foreach ($data['packages'][$name] as $package) {
                $releases[ltrim($package['version'], 'vV')] = isset($package['time']) ? substr($package['time'], 0, 10) : '';
            }

            $latest = highestStable(array_keys($releases));
            break;

        case 'github':
            // The latest release, else the highest tag
            $data = fetchJson('https://api.github.com/repos/' . $name . '/releases/latest');

            if (isset($data['tag_name'])) {
                return array('version' => ltrim($data['tag_name'], 'vV'), 'series' => null);
            }

            $data   = fetchJson('https://api.github.com/repos/' . $name . '/tags?per_page=100');
            $latest = is_array($data) ? highestStable(array_column($data, 'name')) : null;

            return $latest === null ? null : array('version' => $latest, 'series' => null);

        default:
            return null;
    }

    $inSeries = highestStable(array_filter(array_keys($releases), function ($version) use ($major) {
        return strpos(ltrim($version, 'vV'), $major . '.') === 0;
    }));

    return array(
        'version' => $latest,
        'series'  => $inSeries === null ? null : array('major' => $major, 'version' => $inSeries, 'date' => $releases[$inSeries]),
    );
}

if (in_array('--latest', $argv, true)) {
    foreach ($groups as $entries) {
        foreach ($entries as $entry) {
            if (empty($entry['upstream']) || $entry['upstream'] === 'current') {
                continue;
            }

            // Composer-managed entries take their version from installed.json
            $version = isset($entry['composer'], $packages[$entry['composer']]) ? ltrim($packages[$entry['composer']]['version'], 'vV')
                : (isset($entry['version']) ? (string) $entry['version'] : '');
            $found   = latestRelease($entry['upstream'], (string) strtok($version, '.'));

            if ($found === null) {
                fwrite(STDERR, $entry['name'] . ': no latest release found at ' . $entry['upstream'] . "\n");

                continue;
            }

            $latest[$entry['upstream']] = array_filter(array('version' => $found['version'], 'series' => $found['series'], 'checked' => gmdate('Y-m-d')));
        }
    }

    ksort($latest);
    file_put_contents($latestFile, json_encode($latest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

foreach ($groups as $group => $entries) {
    foreach ($entries as $entry) {
        $date   = '';
        $source = '';

        if (isset($entry['composer'])) {
            if (!isset($packages[$entry['composer']])) {
                $errors[] = $entry['name'] . ': ' . $entry['composer'] . ' isn\'t in installed.json';

                continue;
            }

            $package          = $packages[$entry['composer']];
            $entry['version'] = ltrim($package['version'], 'v');
            $date             = isset($package['time']) ? substr($package['time'], 0, 10) : '';
            $source           = $date !== '' ? 'Composer (installed.json)' : '';
        } elseif (isset($entry['check'])) {
            list($file, $pattern) = $entry['check'];

            $text = @file_get_contents($root . '/' . $file);

            if ($text === false || !preg_match($pattern, $text, $match)) {
                $errors[] = $entry['name'] . ': no version found in ' . $file;

                continue;
            }

            if ($match[1] !== $entry['version']) {
                $errors[] = $entry['name'] . ': ' . $file . ' says ' . $match[1] . ', this list ' . $entry['version'];

                continue;
            }

            $date   = isset($match['date']) ? $match['date'] : '';
            $source = $date !== '' ? $file : '';
        }

        // A date found elsewhere: the project's releases, tags or announcements
        if ($date === '' && isset($entry['date'])) {
            $date   = $entry['date'];
            $source = isset($entry['dateSource']) ? $entry['dateSource'] : '';
        }

        if (!file_exists($root . '/' . $entry['path'])) {
            $errors[] = $entry['name'] . ': ' . $entry['path'] . ' doesn\'t exist';

            continue;
        }

        $upstream = isset($entry['upstream']) ? $entry['upstream'] : '';
        $known    = isset($latest[$upstream]['version']);

        // Not the latest release, but the latest of its series, which is still maintained (a release within a year of the check)
        $series = '';

        if ($known && isset($latest[$upstream]['series']['version']) && $entry['version'] !== ''
            && version_compare(ltrim($entry['version'], 'v'), $latest[$upstream]['version'], '<')
            && version_compare(ltrim($entry['version'], 'v'), $latest[$upstream]['series']['version'], '>=')
            && strtotime($latest[$upstream]['series']['date']) >= strtotime($latest[$upstream]['checked'] . ' -1 year')) {
            $series = $latest[$upstream]['series']['major'] . '.x';
        }

        $out[$group][] = array(
            'name'       => $entry['name'],
            'version'    => $entry['version'],
            'date'       => $date !== '' ? str_replace('-', '.', $date) : '',
            'dateSource' => $source,
            'license'    => $entry['license'],
            'url'        => $entry['url'],
            'path'       => $entry['path'],
            'note'       => isset($entry['note']) ? $entry['note'] : '',
            'latest'     => $known ? $latest[$upstream]['version'] : '',
            'isLatest'   => $upstream === 'current' || ($known && $entry['version'] !== '' && version_compare(ltrim($entry['version'], 'v'), $latest[$upstream]['version'], '>=')),
            'checked'    => $known ? str_replace('-', '.', $latest[$upstream]['checked']) : '',
            'use'        => isset($entry['use']) ? $entry['use'] : 'core',
            'series'     => $series,
            'seriesLatest' => $known && isset($latest[$upstream]['series']['version']) && $latest[$upstream]['series']['version'] !== $latest[$upstream]['version']
                ? $latest[$upstream]['series']['major'] . '.x: ' . $latest[$upstream]['series']['version'] . ' (' . str_replace('-', '.', $latest[$upstream]['series']['date']) . ')' : '',
        );
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");

    exit(1);
}

// Groups and their entries alphabetically, so entries are easy to spot
uksort($out, 'strcasecmp');

foreach ($out as &$entries) {
    usort($entries, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
}

unset($entries);

file_put_contents(
    $root . '/administrator/components/com_admin/thirdparty.json',
    json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

echo array_sum(array_map('count', $out)) . " entries written\n";
