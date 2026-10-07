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

/**
 * Little WAF - a small, opt-in request filter for known attack signatures against
 * abandoned or historically vulnerable third-party extensions. The plugin itself is
 * off by default; once enabled, every protection below is on by default, since
 * turning the plugin on is itself the explicit opt-in.
 *
 * Deliberately not a general-purpose WAF: each filter here targets one specific,
 * previously-observed attack signature against one specific extension. It buys time
 * against a known pattern; it is not a substitute for updating or removing the
 * vulnerable extension it targets.
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
	 * POST data of logged-in users who may create content isn't checked: they write these tags in front-end editing, and
	 * the extension itself decides who may use them.
	 *
	 * @return  \Generator  where => value
	 *
	 * @since   3.17.0
	 */
	private function getRequestValues()
	{
		$query  = $this->server('QUERY_STRING');
		$target = $this->server('REQUEST_URI');

		if ($target === '')
		{
			$target = $this->server('SCRIPT_NAME') . $this->server('PATH_INFO') . ($query !== '' ? '?' . $query : '');
		}

		yield 'request' => $target;
		yield 'query string' => $query;
		yield 'path info' => $this->server('PATH_INFO');

		$sources = array('GET' => $_GET);

		if (!empty($_POST))
		{
			$user = Factory::getUser();

			if ($user->guest || !$user->authorise('core.create', 'com_content'))
			{
				$sources['POST'] = $_POST;
			}
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
				Log::addLogger(array('text_file' => 'littlewaf.php'), Log::ALL, array('littlewaf'));

				$request = preg_replace('/[\x00-\x1F\x7F]/', '?', substr($this->server('REQUEST_URI'), 0, static::LOG_LENGTH));

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
