<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.littlewaf
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * Little WAF - a small request filter for known attack signatures against abandoned
 * or historically vulnerable third-party extensions. Enabled by default since 3.18
 * (new sites, and once on update), with every protection below on.
 *
 * Deliberately not a general-purpose WAF: each filter here targets one specific,
 * previously-observed attack signature against one specific extension. It buys time
 * against a known pattern; it is not a substitute for updating or removing the
 * vulnerable extension it targets.
 *
 * Generic protections catch whole classes of attack which never look like a real
 * request: probes for other platforms' files, executable uploads, PHP-serialized
 * objects and known attack tools. It can also send security headers.
 *
 * It also normalises the addresses of front-end pages (URL Normalizations), with a
 * permanent redirect: repeated query parameters (crawlers append Joomla's start,
 * limitstart or format again and again), doubled question marks, text after an
 * ampersand in the path, and feeds of search results.
 *
 * @since  3.16.0
 */
class PlgSystemLittlewaf extends CMSPlugin
{
	/**
	 * The filters: param => the plugins (system or content) of the extension it protects, and the tag it blocks, matched
	 * case-insensitively on the canonical (percent-decoded) value, without the "u" flag so invalid UTF-8 can't make it fail
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const FILTERS = array(
		// Sourcerer's {source}...{/source} (also {source 0} etc.), which unpatched versions run as PHP
		'filter_sourcerer'       => array('sourcerer', '/\{\/?source(?![A-Za-z0-9_])/i'),
		// Modules Anywhere's {module ...} and {modulepos ...}
		'filter_modulesanywhere' => array('modulesanywhere', '/\{module(?:pos)?(?![A-Za-z0-9_])/i'),
	);

	/**
	 * At most this many characters of the request are logged
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const LOG_LENGTH = 500;

	/**
	 * The log file isn't written once it's larger than this many bytes
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const LOG_MAX_SIZE = 10485760;

	/**
	 * Path segments of other platforms and of files no site serves through Joomla (lower case): a request for them is a probe
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const PROBE_SEGMENTS = array(
		'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php', 'wp-config.php', 'wp-cron.php', 'xmlrpc.php',
		'wlwmanifest.xml', '.git', '.svn', '.hg', '.bzr', '.aws', '.ssh', '.vscode', '.idea', '.ds_store', 'phpmyadmin',
		'adminer.php', 'phpinfo.php', 'eval-stdin.php', 'cgi-bin',
	);

	/**
	 * Last path segments which are backups, dumps or secrets (".env", ".env.local", "dump.sql", "configuration.php.bak", ...)
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const PROBE_FILE = '/^\.env(?:\..*)?$|\.(?:sql|sql\.gz|sql\.zip|bak|old|orig|save|swp)$|~$/';

	/**
	 * Parts of an uploaded file's name which make it executable on a web server: Joomla's own forbidden extensions
	 * (InputFilter::isSafeFile()), in any position (shell.php.jpg), and server configuration files
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const EXECUTABLE_EXTENSIONS = array(
		'php', 'phps', 'pht', 'phtml', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'php9', 'phar', 'inc', 'pl', 'cgi', 'fcgi',
		'java', 'jar', 'py', 'asp', 'aspx', 'jsp', 'shtml',
	);

	/**
	 * Names of server configuration files, which change how a folder's files run
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const SERVER_FILES = array('.htaccess', '.htpasswd', '.user.ini', 'php.ini', 'web.config');

	/**
	 * A PHP-serialized object ("O:8:\"stdClass\":1:{", or "C:" for Serializable), as object injection sends it ("O:+8:" was
	 * accepted by old PHP versions)
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const SERIALIZED_OBJECT = '/[OC]:\+?\d+:"[A-Za-z0-9_\\\\]+":\d+:\{/';

	/**
	 * User agents of attack and scanning tools (matched case-insensitively)
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const SCANNERS = array(
		'sqlmap', 'nikto', 'nuclei', 'masscan', 'zgrab', 'wpscan', 'acunetix', 'netsparker', 'nessus', 'openvas', 'w3af', 'havij',
		'dirbuster', 'gobuster', 'fuzz faster u fool', 'nmap scripting engine', 'commix', 'jaeles', 'arachni', 'skipfish',
	);

	/**
	 * The security headers: param => the header, and the values it may have (null: any value without control characters)
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const HEADERS = array(
		'header_nosniff'            => array('X-Content-Type-Options', array('nosniff')),
		'header_xss_protection'     => array('X-XSS-Protection', array('0', '1; mode=block')),
		'header_referrer_policy'    => array('Referrer-Policy', array(
			'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin',
			'strict-origin-when-cross-origin',
		)),
		'header_frame_options'      => array('X-Frame-Options', array('SAMEORIGIN', 'DENY')),
		'header_hsts'               => array('Strict-Transport-Security', array('max-age=31536000', 'max-age=31536000; includeSubDomains')),
		'header_permissions_policy' => array('Permissions-Policy', null),
	);

	/**
	 * The headers' defaults: only those which can't change how a site works are sent unless the site admin chooses
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const HEADER_DEFAULTS = array(
		'header_nosniff'         => 'nosniff',
		'header_xss_protection'  => '0',
		'header_referrer_policy' => 'strict-origin-when-cross-origin',
	);

	/**
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  3.16.0
	 */
	protected $app;

	/**
	 * Runs on every request, as early as possible (before routing/component dispatch),
	 * so a blocked request never reaches any component, plugin, or third-party code.
	 *
	 * @return  void
	 *
	 * @since   3.16.0
	 */
	public function onAfterInitialise()
	{
		// Only ever filters site (frontend) requests. The administrator application is
		// never touched, since legitimate content authoring there (e.g. a trusted editor
		// intentionally using a real Sourcerer {source} tag) must not be blocked by this.
		if ($this->app->isClient('administrator'))
		{
			return;
		}

		$this->sendSecurityHeaders();
		$this->blockProbes();
		$this->filterGeneric();
		$this->filter();

		// After the filters: a request they block gets its 403, not a redirect
		$this->normalise();
	}

	/**
	 * Feeds of search results (a feed for every search a crawler makes up) go to the home page. After routing, when the
	 * component is known, and still before it runs.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onAfterRoute()
	{
		if (!$this->app->isClient('site') || !$this->params->get('normalize_search_feeds', 1) || !$this->isRedirectable())
		{
			return;
		}

		$input = $this->app->input;

		if ($input->getCmd('format') !== 'feed')
		{
			return;
		}

		// Core's Search and Smart Search, and components searching with a "search" task or a searchword (K2's item list)
		if (in_array($input->getCmd('option'), array('com_search', 'com_finder'), true) || $input->getCmd('task') === 'search'
			|| $input->getString('searchword', '') !== '')
		{
			$this->app->redirect(Uri::base(), 301);
		}
	}

	/**
	 * Adds the security headers chosen, except those the response already has (from PHP or an extension; headers the web
	 * server adds aren't visible here). Code which sets one of them later with "replace" (e.g. Users' Referrer-Policy on
	 * its password reset pages) still decides. The page cache doesn't store them with its pages, as they're set before it
	 * looks for one.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	private function sendSecurityHeaders()
	{
		$present = array();

		foreach ($this->app->getHeaders() as $header)
		{
			$present[strtolower($header['name'])] = true;
		}

		foreach (headers_list() as $line)
		{
			$present[strtolower(trim(strstr($line, ':', true)))] = true;
		}

		foreach (static::HEADERS as $param => $header)
		{
			$defaults = static::HEADER_DEFAULTS;
			$default  = array_key_exists($param, $defaults) ? $defaults[$param] : '';
			$value   = trim((string) $this->params->get($param, $default));

			// The switch of X-Content-Type-Options is a Yes/No
			if ($param === 'header_nosniff')
			{
				$value = $value === '0' ? '' : 'nosniff';
			}

			if ($value === '' || isset($present[strtolower($header[0])]))
			{
				continue;
			}

			if ($header[1] === null ? preg_match('/[\x00-\x1F\x7F]/', $value) : !in_array($value, $header[1], true))
			{
				continue;
			}

			// Browsers ignore it over plain HTTP, and a site which can't keep HTTPS mustn't promise it
			if ($param === 'header_hsts' && !$this->app->isSSLConnection())
			{
				continue;
			}

			$this->app->setHeader($header[0], $value);
		}
	}

	/**
	 * Answers requests for other platforms' files, version control folders, backups and secrets with a plain 404, before
	 * Joomla builds its error page for them (with the shipped .htaccess, every missing file reaches Joomla). Not logged:
	 * scans send them by the hundred.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	private function blockProbes()
	{
		if (!$this->params->get('block_probes', 1) || !static::isProbe($this->requestPath()))
		{
			return;
		}

		$this->app->setHeader('status', 404, true);
		$this->app->setBody('Not Found');

		echo $this->app->toString();
		$this->app->close();
	}

	/**
	 * Whether a path (relative to the site, decoded) asks for another platform's file, a version control folder, a backup or a
	 * secret.
	 *
	 * @param   string  $path  The path
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	public static function isProbe($path)
	{
		$segments = array_values(array_filter(explode('/', strtolower($path)), 'strlen'));

		if (!$segments)
		{
			return false;
		}

		if (array_intersect($segments, static::PROBE_SEGMENTS))
		{
			return true;
		}

		return (bool) preg_match(static::PROBE_FILE, end($segments));
	}

	/**
	 * The request's path relative to the site's base, percent-decoded (index.php and path info included).
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private function requestPath()
	{
		$target = $this->requestTarget();
		$path   = rawurldecode((string) strtok($target, '?'));
		$base   = rtrim(Uri::base(true), '/');

		if ($base !== '' && stripos($path, $base . '/') === 0)
		{
			$path = substr($path, strlen($base));
		}

		return $path;
	}

	/**
	 * The generic protections: executable uploads, PHP-serialized objects and attack tools are blocked (403).
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	private function filterGeneric()
	{
		if ($this->params->get('block_scanners', 1))
		{
			$agent = strtolower($this->server('HTTP_USER_AGENT'));

			foreach (static::SCANNERS as $scanner)
			{
				if (strpos($agent, $scanner) !== false)
				{
					$this->block('scanners', 'user agent');
				}
			}
		}

		if ($this->params->get('block_executable_uploads', 1) && !empty($_FILES))
		{
			foreach ($this->flatten(array_map(function ($file) { return isset($file['name']) ? $file['name'] : ''; }, $_FILES)) as $name)
			{
				if (static::isExecutableName($name))
				{
					$this->block('executable_uploads', 'file names');
				}
			}
		}

		if ($this->params->get('block_serialized', 1))
		{
			$values = $this->getRequestValues();

			foreach ($values as $where => $value)
			{
				if ($this->matches(static::SERIALIZED_OBJECT, $value))
				{
					$this->block('serialized', $where);
				}
			}

			foreach ($this->getHeaderValues() as $where => $value)
			{
				if ($this->matches(static::SERIALIZED_OBJECT, $value))
				{
					$this->block('serialized', $where);
				}
			}
		}
	}

	/**
	 * Whether an uploaded file's name makes it executable on a web server (any part after the first dot, and server
	 * configuration files), as Windows servers read it too (trailing dots and spaces dropped).
	 *
	 * @param   string  $name  The file name
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	public static function isExecutableName($name)
	{
		$name = (string) $name;

		// A NUL byte ends the name where the file system writes it
		if (strpos($name, "\0") !== false)
		{
			return true;
		}

		$name = strtolower(rtrim(substr(strrchr('/' . str_replace('\\', '/', $name), '/'), 1), ". \t"));

		if (in_array($name, static::SERVER_FILES, true))
		{
			return true;
		}

		$parts = explode('.', $name);
		array_shift($parts);

		foreach ($parts as $part)
		{
			if (in_array(rtrim($part, ' '), static::EXECUTABLE_EXTENSIONS, true))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The request's cookies and headers (names as PHP gives them: HTTP_USER_AGENT, ...), where object injection is sent too.
	 *
	 * @return  \Generator  where => value
	 *
	 * @since   3.18.0
	 */
	private function getHeaderValues()
	{
		foreach ($this->flatten($_COOKIE) as $value)
		{
			yield 'cookies' => $value;
		}

		foreach ($_SERVER as $name => $value)
		{
			if (strpos($name, 'HTTP_') === 0 && $name !== 'HTTP_COOKIE' && is_scalar($value))
			{
				yield 'headers' => (string) $value;
			}
		}
	}

	/**
	 * Blocks requests carrying a protected extension's tag.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	private function filter()
	{
		$patterns = array();
		$enabled  = null;

		foreach (static::FILTERS as $param => $filter)
		{
			if (!$this->params->get($param, 1))
			{
				continue;
			}

			$enabled = $enabled === null ? $this->getEnabledPlugins() : $enabled;

			// A tag is only dangerous where the extension which processes it is on
			if (in_array($filter[0], $enabled, true))
			{
				$patterns[substr($param, 7)] = $filter[1];
			}
		}

		if (!$patterns)
		{
			return;
		}

		foreach ($this->getRequestValues() as $where => $value)
		{
			foreach ($patterns as $filter => $pattern)
			{
				if ($this->matches($pattern, $value))
				{
					$this->block($filter, $where);
				}
			}
		}
	}

	/**
	 * Redirects (301) to the normalised address of the request, when the URL Normalizations change it: one redirect for all.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	private function normalise()
	{
		if (!$this->isRedirectable())
		{
			return;
		}

		$target = $this->requestTarget();

		if (strpos($target, '&') === false && strpos($target, '??') === false && substr($target, -1) !== '?')
		{
			return;
		}

		$normalised = static::normaliseTarget(
			$target,
			(bool) $this->params->get('normalize_duplicates', 1),
			(bool) $this->params->get('normalize_question_marks', 1),
			(bool) $this->params->get('normalize_path_ampersand', 1),
			(bool) $this->params->get('normalize_entities', 1)
		);

		if ($normalised === $target)
		{
			return;
		}

		// Always an absolute address on this site: a path starting with "//" mustn't become another host
		$this->app->redirect(Uri::getInstance()->toString(array('scheme', 'host', 'port')) . $normalised, 301);
	}

	/**
	 * The normalised request target (path and query, as sent: nothing is decoded or reordered).
	 *
	 * @param   string   $target      The request target
	 * @param   boolean  $duplicates  Remove repeated query parameters (the first is kept, where it is) and empty ones; lists
	 *                                (names ending in "[]") may repeat
	 * @param   boolean  $questions   Remove question marks starting the query ("??a=1") and an empty query ("/page?")
	 * @param   boolean  $ampersand   Remove what follows an ampersand in the path ("/page&a=1"); the query stays
	 * @param   boolean  $entities    Unescape HTML-escaped ampersands in the query ("a=1&amp;b=2", also "&amp;amp;")
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public static function normaliseTarget($target, $duplicates = true, $questions = true, $ampersand = true, $entities = true)
	{
		$position = strpos($target, '?');
		$path     = $position === false ? $target : substr($target, 0, $position);
		$query    = $position === false ? null : substr($target, $position + 1);

		if ($ampersand && ($cut = strpos($path, '&')) !== false)
		{
			$path = substr($path, 0, $cut);
			$path = $path === '' ? '/' : $path;
		}

		if ($query !== null && $questions)
		{
			$query = ltrim($query, '?');
		}

		// Links copied out of HTML ("&amp;"): PHP reads "amp;b" as the name
		while ($query !== null && $entities && stripos($query, '&amp;') !== false)
		{
			$query = str_ireplace('&amp;', '&', $query);
		}

		if ($query !== null && $duplicates)
		{
			$seen = array();
			$kept = array();

			foreach (explode('&', $query) as $part)
			{
				$equals = strpos($part, '=');
				$name   = urldecode($equals === false ? $part : substr($part, 0, $equals));

				// Nothing PHP would read ("&&", "=value")
				if (trim($name) === '')
				{
					continue;
				}

				if (substr($name, -2) !== '[]')
				{
					// The name as PHP reads it: "a.b", "a b" and "a_b" are one parameter
					$key = strtr(ltrim($name, ' '), ' .', '__');

					if (isset($seen[$key]))
					{
						continue;
					}

					$seen[$key] = true;
				}

				$kept[] = $part;
			}

			$query = implode('&', $kept);
		}

		return $path . ($query === null || ($query === '' && ($duplicates || $questions)) ? '' : '?' . $query);
	}

	/**
	 * Whether the request may be redirected: only a page (GET or HEAD), as a redirect would lose posted data.
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	private function isRedirectable()
	{
		return in_array(strtoupper($this->server('REQUEST_METHOD')), array('GET', 'HEAD'), true);
	}

	/**
	 * The protected extensions which have an enabled plugin, in any plugin group: the group a vendor registers its plugin in
	 * mustn't decide whether its filter is on.
	 *
	 * @return  string[]  Plugin elements
	 *
	 * @since   3.17.0
	 */
	private function getEnabledPlugins()
	{
		$elements = array_unique(array_map(function ($filter) { return $filter[0]; }, static::FILTERS));

		try
		{
			$db = Factory::getDbo();

			return $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('element'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('enabled') . ' = 1')
					->where($db->quoteName('element') . ' IN (' . implode(',', array_map(array($db, 'quote'), $elements)) . ')')
			)->loadColumn() ?: array();
		}
		catch (\RuntimeException $e)
		{
			// Without the database the site can't run the extensions either; filter rather than guess
			return $elements;
		}
	}

	/**
	 * The parts of the request the filters check: the request target (rebuilt as JUri does where the server doesn't give
	 * REQUEST_URI), the query string and path info, the names and values of GET and POST data, and uploaded file names.
	 * The editor's text of logged-in users who may write articles (or edit modules) isn't checked: they write these tags in
	 * front-end editing, and the extension itself decides who may use them. The rest of their POST data is.
	 *
	 * @return  \Generator  where => value
	 *
	 * @since   3.17.0
	 */
	private function getRequestValues()
	{
		$query = $this->server('QUERY_STRING');

		yield 'request' => $this->requestTarget();
		yield 'query string' => $query;
		yield 'path info' => $this->server('PATH_INFO');

		$sources = array('GET' => $_GET);

		if (!empty($_POST))
		{
			$sources['POST'] = $this->withoutEditorText($_POST);
		}

		if (!empty($_FILES))
		{
			$sources['file names'] = array_map(function ($file) { return isset($file['name']) ? $file['name'] : ''; }, $_FILES);
		}

		foreach ($sources as $source => $values)
		{
			foreach ($this->flatten($values) as $value)
			{
				yield $source => $value;
			}
		}
	}

	/**
	 * The request target, rebuilt as JUri does where the server doesn't give REQUEST_URI.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function requestTarget()
	{
		$target = $this->server('REQUEST_URI');

		if ($target === '')
		{
			$query  = $this->server('QUERY_STRING');
			$target = $this->server('SCRIPT_NAME') . $this->server('PATH_INFO') . ($query !== '' ? '?' . $query : '');
		}

		return $target;
	}

	/**
	 * POST data without the editor's text of a logged-in user who may write articles (anywhere: the component or a category),
	 * or edit modules (a module's content). Everything else they send is still checked.
	 *
	 * @param   array  $post  The POST data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function withoutEditorText(array $post)
	{
		$user = Factory::getUser();

		if ($user->guest || !isset($post['jform']) || !is_array($post['jform']))
		{
			return $post;
		}

		foreach (array('core.create', 'core.edit', 'core.edit.own') as $action)
		{
			if ($user->authorise($action, 'com_content') || $user->getAuthorisedCategories('com_content', $action))
			{
				unset($post['jform']['articletext']);

				break;
			}
		}

		if ($user->authorise('core.edit', 'com_modules') || $user->authorise('core.create', 'com_modules'))
		{
			unset($post['jform']['content']);
		}

		return $post;
	}

	/**
	 * A server value as the server gave it (the input filters would change it).
	 *
	 * @param   string  $name  The name
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function server($name)
	{
		return isset($_SERVER[$name]) && is_scalar($_SERVER[$name]) ? (string) $_SERVER[$name] : '';
	}

	/**
	 * Every key and value of (nested) request data.
	 *
	 * @param   array  $values  The data
	 *
	 * @return  \Generator
	 *
	 * @since   3.17.0
	 */
	private function flatten(array $values)
	{
		foreach ($values as $key => $value)
		{
			yield (string) $key;

			if (is_array($value))
			{
				foreach ($this->flatten($value) as $inner)
				{
					yield $inner;
				}
			}
			elseif (is_scalar($value))
			{
				yield (string) $value;
			}
		}
	}

	/**
	 * Whether a value holds the tag, as it is or percent-encoded (up to twice, in any mix of encoded and plain characters).
	 *
	 * @param   string  $pattern  The tag's pattern
	 * @param   string  $value    The value
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function matches($pattern, $value)
	{
		for ($pass = 0; $pass < 3; $pass++)
		{
			if (preg_match($pattern, $value))
			{
				return true;
			}

			$decoded = rawurldecode($value);

			if ($decoded === $value)
			{
				break;
			}

			$value = $decoded;
		}

		return false;
	}

	/**
	 * Logs (if enabled) and terminates the request with a 403 response.
	 *
	 * @param   string  $filter  The filter key that matched, for logging.
	 * @param   string  $where   The part of the request that matched, for logging.
	 *
	 * @return  void  This method does not return.
	 *
	 * @since   3.16.0
	 */
	private function block($filter, $where)
	{
		$this->app->setHeader('status', 403, true);
		$this->app->setBody('Forbidden');

		if ($this->params->get('log_blocked', 1))
		{
			// A request which can't be logged (e.g. the log folder isn't writable) is still blocked
			try
			{
				// Blocked requests never reach the log rotation, so a flood of them stops being logged at LOG_MAX_SIZE
				$file = rtrim((string) $this->app->get('log_path', JPATH_ADMINISTRATOR . '/logs'), '/\\') . '/littlewaf.php';

				if (is_file($file) && filesize($file) > static::LOG_MAX_SIZE)
				{
					throw new \RuntimeException('The log file is full');
				}

				Log::addLogger(array('text_file' => 'littlewaf.php'), Log::ALL, array('littlewaf'));

				$request = preg_replace('/[\x00-\x1F\x7F]/', '?', substr($this->requestTarget(), 0, static::LOG_LENGTH));

				Log::add(
					sprintf('Little WAF blocked [%s] in the %s from %s: %s', $filter, $where, $this->server('REMOTE_ADDR') ?: 'unknown', $request),
					Log::WARNING,
					'littlewaf'
				);
			}
			catch (\Throwable $e)
			{
			}
		}

		echo $this->app->toString();
		$this->app->close();
	}
}
