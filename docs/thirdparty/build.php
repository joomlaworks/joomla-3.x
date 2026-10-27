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
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

$root     = dirname(__DIR__, 2);
$composer = json_decode(file_get_contents($root . '/libraries/vendor/composer/installed.json'), true);
$packages = array();

foreach (isset($composer['packages']) ? $composer['packages'] : $composer as $package)
{
	$packages[$package['name']] = $package;
}

/*
 * group => entries: name, version, license, url, path, and how to check them:
 * - check: array(file, pattern): the pattern's first group must be the version; a "date" group (YYYY-MM-DD) gives the date
 * - composer: a package name in installed.json: its version and time
 * - date: a date stated elsewhere (YYYY-MM-DD), with "dateSource" saying where
 */
$groups = array(
	'JavaScript Libraries' => array(
		array('name' => 'jQuery', 'version' => '1.12.4', 'license' => 'MIT', 'url' => 'https://jquery.com', 'path' => 'media/jui/js/jquery.js',
			'note' => 'Patched by Joomla', 'check' => array('media/jui/js/jquery.js', '/jQuery JavaScript Library v([0-9.]+)-joomla.*?Date: (?<date>\d{4}-\d{2}-\d{2})/s')),
		array('name' => 'jQuery Migrate', 'version' => '1.4.1', 'license' => 'MIT', 'url' => 'https://github.com/jquery/jquery-migrate', 'path' => 'media/jui/js/jquery-migrate.js',
			'check' => array('media/jui/js/jquery-migrate.js', '/jQuery Migrate - v([0-9.]+) - (?<date>\d{4}-\d{2}-\d{2})/')),
		array('name' => 'jQuery UI (Core, Sortable)', 'version' => '1.9.2', 'license' => 'MIT', 'url' => 'https://jqueryui.com', 'path' => 'media/jui/js/jquery.ui.core.js',
			'check' => array('media/jui/js/jquery.ui.core.js', '/jQuery UI - v([0-9.]+) - (?<date>\d{4}-\d{2}-\d{2})/')),
		array('name' => 'MooTools Core', 'version' => '1.4.5', 'license' => 'MIT', 'url' => 'https://mootools.net', 'path' => 'media/system/js/mootools-core.js',
			'check' => array('media/system/js/mootools-core-uncompressed.js', "/version: '([0-9.]+)'/")),
		array('name' => 'MooTools More', 'version' => '1.4.0.1', 'license' => 'MIT', 'url' => 'https://mootools.net/more', 'path' => 'media/system/js/mootools-more.js',
			'check' => array('media/system/js/mootools-more.js', '/version:"([0-9.]+)"/')),
		array('name' => 'Chosen', 'version' => '1.6.2', 'license' => 'MIT', 'url' => 'https://harvesthq.github.io/chosen/', 'path' => 'media/jui/js/chosen.jquery.js',
			'check' => array('media/jui/js/chosen.jquery.js', '/Version ([0-9.]+)/')),
		array('name' => 'Ajax Chosen', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/meltingice/ajax-chosen', 'path' => 'media/jui/js/ajax-chosen.js'),
		array('name' => 'Ajax Autocomplete for jQuery', 'version' => '1.4.11', 'license' => 'MIT', 'url' => 'https://github.com/devbridge/jQuery-Autocomplete', 'path' => 'media/jui/js/jquery.autocomplete.js',
			'check' => array('media/jui/js/jquery.autocomplete.js', '/Ajax Autocomplete for jQuery, version ([0-9.]+)/')),
		array('name' => 'jQuery MiniColors', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/claviska/jquery-minicolors', 'path' => 'media/jui/js/jquery.minicolors.js'),
		array('name' => 'jQuery Simple Colors', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/tkrotoff/jquery-simplecolorpicker', 'path' => 'media/jui/js/jquery.simplecolors.js',
			'note' => 'Adapted by Peter van Westen'),
		array('name' => 'Jcrop', 'version' => '2.0.4', 'license' => 'MIT', 'url' => 'https://github.com/tapmodo/Jcrop', 'path' => 'media/system/js/jquery.Jcrop.js',
			'check' => array('media/system/js/jquery.Jcrop.js', '/Jcrop[^\n]*v([0-9.]+)/')),
		array('name' => 'Punycode.js', 'version' => '1.4.1', 'license' => 'MIT', 'url' => 'https://github.com/mathiasbynens/punycode.js', 'path' => 'media/system/js/punycode.js',
			'check' => array('media/system/js/punycode-uncompressed.js', '/mths.be\/punycode v([0-9.]+)/')),
		array('name' => 'SqueezeBox', 'version' => '1.3', 'license' => 'MIT', 'url' => 'https://github.com/digitarald/squeezebox', 'path' => 'media/system/js/modal.js',
			'note' => 'Patched by Joomla', 'check' => array('media/system/js/modal-uncompressed.js', '/@version\s+([0-9.]+)/')),
		array('name' => 'HTML5 Shiv', 'version' => '3.7.3', 'license' => 'MIT', 'url' => 'https://github.com/aFarkas/html5shiv', 'path' => 'media/jui/js/html5.js',
			'check' => array('media/jui/js/html5-uncompressed.js', '/HTML5 Shiv ([0-9.]+)/')),
		array('name' => 'Polyfill.io polyfills (classList, Event, Map, filter)', 'version' => '3.14.0', 'license' => 'MIT', 'url' => 'https://github.com/Financial-Times/polyfill-library', 'path' => 'media/system/js/polyfill.map.js',
			'check' => array('media/system/js/polyfill.map-uncompressed.js', '/v([0-9.]+)/')),
		array('name' => 'MediaElement.js', 'version' => '2.23.0', 'license' => 'MIT', 'url' => 'https://www.mediaelementjs.com', 'path' => 'media/media/js/mediaelement-and-player.js',
			'check' => array('media/media/js/mediaelement-and-player.js', '/([0-9]+\.[0-9]+\.[0-9]+)/')),
	),
	'CSS and JavaScript Frameworks' => array(
		array('name' => 'Bootstrap', 'version' => '2.3.2', 'license' => 'Apache-2.0', 'url' => 'https://getbootstrap.com/2.3.2/', 'path' => 'media/jui/js/bootstrap.js',
			'note' => 'JavaScript and CSS', 'check' => array('media/jui/js/bootstrap.js', '/bootstrap-transition\.js v([0-9.]+)/')),
	),
	'Editors' => array(
		array('name' => 'TinyMCE', 'version' => '8.9.2', 'license' => 'GPL-2.0-or-later', 'url' => 'https://www.tiny.cloud', 'path' => 'media/editors/tinymce_latest',
			'check' => array('media/editors/tinymce_latest/tinymce.min.js', '/TinyMCE version ([0-9.]+)/')),
		array('name' => 'TinyMCE (legacy)', 'version' => '4.5.12', 'license' => 'LGPL-2.1', 'url' => 'https://www.tiny.cloud', 'path' => 'media/editors/tinymce',
			'check' => array('media/editors/tinymce/changelog.txt', '/Version ([0-9.]+) \((?<date>\d{4}-\d{2}-\d{2})\)/')),
		array('name' => 'CodeMirror', 'version' => '5.65.21', 'license' => 'MIT', 'url' => 'https://codemirror.net/5/', 'path' => 'media/editors/codemirror',
			'check' => array('media/editors/codemirror/lib/codemirror.js', '/CodeMirror\.version = "([0-9.]+)"/')),
		array('name' => 'EasyMDE', 'version' => '2.21.0', 'license' => 'MIT', 'url' => 'https://github.com/Ionaru/easy-markdown-editor', 'path' => 'media/editors/easymde/vendor/easymde',
			'check' => array('media/editors/easymde/vendor/easymde/easymde.min.css', '/easymde v([0-9.]+)/')),
		array('name' => 'marked', 'version' => '18.1.1', 'license' => 'MIT', 'url' => 'https://marked.js.org', 'path' => 'media/editors/easymde/vendor/marked',
			'check' => array('media/editors/easymde/vendor/marked/marked.umd.js', '/marked v([0-9.]+)/')),
		array('name' => 'Turndown', 'version' => '7.2.4', 'license' => 'MIT', 'url' => 'https://github.com/mixmark-io/turndown', 'path' => 'media/editors/easymde/vendor/turndown'),
		array('name' => 'Editor.js', 'version' => '2.31.7', 'license' => 'Apache-2.0', 'url' => 'https://editorjs.io', 'path' => 'media/editors/editorjs/vendor/editorjs',
			'check' => array('media/editors/editorjs/vendor/editorjs/editorjs.umd.js', '/"([0-9]+\.[0-9]+\.[0-9]+)"/')),
	),
	'Fonts and Icons' => array(
		array('name' => 'IcoMoon (Joomla icon font)', 'version' => '', 'license' => 'Royalty-free', 'url' => 'https://icomoon.io', 'path' => 'media/jui/fonts'),
		array('name' => 'Bootstrap Icons', 'version' => '1.13.2', 'license' => 'MIT', 'url' => 'https://icons.getbootstrap.com', 'path' => 'media/editors/easymde/vendor/bootstrap-icons'),
	),
	// Not bundled: services the CAPTCHA plugins connect to, with the API version they use
	'External Services' => array(
		array('name' => 'Google reCAPTCHA', 'version' => 'v2, v3', 'license' => 'Google\'s terms of service', 'url' => 'https://developers.google.com/recaptcha', 'path' => 'plugins/captcha/recaptcha',
			'note' => 'Service by Google; used by "CAPTCHA - Google reCAPTCHA"'),
		array('name' => 'Cloudflare Turnstile', 'version' => 'v0', 'license' => 'Cloudflare\'s terms of service', 'url' => 'https://developers.cloudflare.com/turnstile/', 'path' => 'plugins/captcha/turnstile',
			'note' => 'Service by Cloudflare; used by "CAPTCHA - Cloudflare Turnstile"'),
		array('name' => 'hCaptcha', 'version' => 'v1', 'license' => 'hCaptcha\'s terms of service', 'url' => 'https://www.hcaptcha.com', 'path' => 'plugins/captcha/hcaptcha',
			'note' => 'Service by Intuition Machines; used by "CAPTCHA - hCaptcha"'),
		array('name' => 'Akismet', 'version' => '1.1', 'license' => 'Akismet\'s terms of service', 'url' => 'https://akismet.com', 'path' => 'plugins/captcha/akismet',
			'note' => 'Service by Automattic (REST API 1.1); used by "CAPTCHA - Akismet"'),
	),
	'PHP Libraries' => array(
		array('name' => 'PHPMailer', 'composer' => 'phpmailer/phpmailer', 'license' => 'LGPL-2.1', 'url' => 'https://github.com/PHPMailer/PHPMailer', 'path' => 'libraries/vendor/phpmailer/phpmailer',
			'note' => 'Patched (php_uname() guard)'),
		array('name' => 'SimplePie', 'composer' => 'simplepie/simplepie', 'license' => 'BSD-3-Clause', 'url' => 'https://simplepie.org', 'path' => 'libraries/vendor/simplepie/simplepie',
			'note' => 'Joomla\'s maintained copy'),
		array('name' => 'lessphp', 'version' => '0.5.0', 'license' => 'MIT', 'url' => 'https://github.com/leafo/lessphp', 'path' => 'libraries/vendor/leafo/lessphp',
			'note' => 'With Joomla\'s PHP 8 fixes', 'check' => array('libraries/vendor/leafo/lessphp/lessc.inc.php', '/VERSION = "v([0-9.]+)"/')),
		array('name' => 'sodium_compat', 'composer' => 'paragonie/sodium_compat', 'license' => 'ISC', 'url' => 'https://github.com/paragonie/sodium_compat', 'path' => 'libraries/vendor/paragonie/sodium_compat'),
		array('name' => 'Symfony Polyfill (ctype)', 'composer' => 'symfony/polyfill-ctype', 'license' => 'MIT', 'url' => 'https://github.com/symfony/polyfill-ctype', 'path' => 'libraries/vendor/symfony/polyfill-ctype'),
		array('name' => 'Symfony Polyfill (PHP 7.3)', 'composer' => 'symfony/polyfill-php73', 'license' => 'MIT', 'url' => 'https://github.com/symfony/polyfill-php73', 'path' => 'libraries/vendor/symfony/polyfill-php73'),
		array('name' => 'Symfony Yaml', 'composer' => 'symfony/yaml', 'license' => 'MIT', 'url' => 'https://symfony.com/components/Yaml', 'path' => 'libraries/vendor/symfony/yaml'),
		array('name' => 'PSR Log', 'composer' => 'psr/log', 'license' => 'MIT', 'url' => 'https://www.php-fig.org/psr/psr-3/', 'path' => 'libraries/vendor/psr/log'),
		array('name' => 'PSR Container', 'composer' => 'psr/container', 'license' => 'MIT', 'url' => 'https://www.php-fig.org/psr/psr-11/', 'path' => 'libraries/vendor/psr/container'),
		array('name' => 'TYPO3 PHAR Stream Wrapper', 'composer' => 'typo3/phar-stream-wrapper', 'license' => 'MIT', 'url' => 'https://github.com/TYPO3/phar-stream-wrapper', 'path' => 'libraries/vendor/typo3/phar-stream-wrapper',
			'note' => 'Patched'),
		array('name' => 'IconCaptcha', 'version' => '4.0.6', 'license' => 'MIT', 'url' => 'https://github.com/fabianwennink/IconCaptcha-PHP', 'path' => 'libraries/vendor/fabianwennink/iconcaptcha',
			'note' => 'With its widget (media/plg_captcha_iconcaptcha)', 'date' => '2025-06-16', 'dateSource' => 'CHANGELOG.md ("June 16, 2025")', 'check' => array('libraries/vendor/fabianwennink/iconcaptcha/CHANGELOG.md', '/^## ([0-9.]+) - /m')),
		array('name' => 'Google reCAPTCHA PHP client', 'composer' => 'google/recaptcha', 'license' => 'BSD-3-Clause', 'url' => 'https://github.com/google/recaptcha', 'path' => 'libraries/vendor/google/recaptcha'),
		array('name' => 'MySQL on SQLite (WordPress)', 'version' => '3.0.2', 'license' => 'GPL-2.0-or-later', 'url' => 'https://github.com/WordPress/sqlite-database-integration', 'path' => 'libraries/vendor/wordpress/mysql-on-sqlite',
			'note' => 'Patched (PATCHES.md)', 'check' => array('libraries/vendor/wordpress/mysql-on-sqlite/PATCHES.md', '/version ([0-9.]+), commit/')),
		array('name' => 'FOF (Framework on Framework)', 'version' => '2.5.5', 'license' => 'GPL-2.0-or-later', 'url' => 'https://github.com/akeeba/fof', 'path' => 'libraries/fof',
			'check' => array('libraries/fof/version.txt', '/^([0-9.]+)\s+(?<date>\d{4}-\d{2}-\d{2})/')),
		array('name' => 'Akeeba Restore (Kickstart)', 'version' => '', 'license' => 'GPL-2.0-or-later', 'url' => 'https://github.com/akeeba/kickstart', 'path' => 'administrator/components/com_joomlaupdate/restore.php',
			'note' => 'Joomla Update\'s extractor; patched'),
		array('name' => 'phpass', 'version' => '0.5', 'license' => 'Public domain', 'url' => 'https://www.openwall.com/phpass/', 'path' => 'libraries/phpass',
			'check' => array('libraries/phpass/PasswordHash.php', '/Version ([0-9.]+)/')),
		array('name' => 'PHP UTF-8', 'version' => '0.5', 'license' => 'LGPL-2.1', 'url' => 'https://sourceforge.net/projects/phputf8/', 'path' => 'libraries/phputf8',
			'check' => array('libraries/phputf8/README', '/Version ([0-9.]+)/')),
		array('name' => 'IDNA Convert', 'version' => '0.8.0', 'license' => 'LGPL-2.1', 'url' => 'https://github.com/phlylabs/idna-convert', 'path' => 'libraries/idna_convert',
			'check' => array('libraries/idna_convert/idna_convert.class.php', '/@version ([0-9.]+) (?<date>\d{4}-\d{2}-\d{2})/')),
		array('name' => 'PHP Encryption (Defuse)', 'version' => '', 'license' => 'BSD-2-Clause', 'url' => 'https://github.com/defuse/php-encryption', 'path' => 'libraries/php-encryption'),
	),
);

$out    = array();
$errors = array();

foreach ($groups as $group => $entries)
{
	foreach ($entries as $entry)
	{
		$date = '';

		if (isset($entry['composer']))
		{
			if (!isset($packages[$entry['composer']]))
			{
				$errors[] = $entry['name'] . ': ' . $entry['composer'] . ' isn\'t in installed.json';

				continue;
			}

			$package          = $packages[$entry['composer']];
			$entry['version'] = ltrim($package['version'], 'v');
			$date             = isset($package['time']) ? substr($package['time'], 0, 10) : '';
		}
		elseif (isset($entry['check']))
		{
			list($file, $pattern) = $entry['check'];

			$text = @file_get_contents($root . '/' . $file);

			if ($text === false || !preg_match($pattern, $text, $match))
			{
				$errors[] = $entry['name'] . ': no version found in ' . $file;

				continue;
			}

			if ($match[1] !== $entry['version'])
			{
				$errors[] = $entry['name'] . ': ' . $file . ' says ' . $match[1] . ', this list ' . $entry['version'];

				continue;
			}

			$date = isset($match['date']) ? $match['date'] : '';
		}

		// A date the software states somewhere the check can't read it
		if ($date === '' && isset($entry['date']))
		{
			$date = $entry['date'];
		}

		if (!file_exists($root . '/' . $entry['path']))
		{
			$errors[] = $entry['name'] . ': ' . $entry['path'] . ' doesn\'t exist';

			continue;
		}

		$out[$group][] = array(
			'name'    => $entry['name'],
			'version' => $entry['version'],
			'date'    => $date !== '' ? str_replace('-', '.', $date) : '',
			'license' => $entry['license'],
			'url'     => $entry['url'],
			'path'    => $entry['path'],
			'note'    => isset($entry['note']) ? $entry['note'] : '',
		);
	}
}

if ($errors)
{
	fwrite(STDERR, implode("\n", $errors) . "\n");

	exit(1);
}

// Groups and their entries alphabetically, so entries are easy to spot
uksort($out, 'strcasecmp');

foreach ($out as &$entries)
{
	usort($entries, function ($a, $b)
	{
		return strcasecmp($a['name'], $b['name']);
	});
}

unset($entries);

file_put_contents(
	$root . '/administrator/components/com_admin/thirdparty.json',
	json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

echo array_sum(array_map('count', $out)) . " entries written\n";
