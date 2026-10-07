<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;

/**
 * Checks the site's health in one report: versions, database, updates, settings, files and folders.
 *
 * @since  3.17.0
 */
class SiteHealthCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'site:health';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Check the site\'s health in one report';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Checks the PHP and database versions against what this Joomla version supports, the database structure, pending Joomla and '
		. 'extension updates (as last checked; run update:extensions:check to refresh), settings which shouldn\'t stay on a live site (debug, '
		. 'error reporting), the installation folder, file and folder permissions, and the cache and session handlers. Each check is "ok", '
		. '"info", "warning" or "error". With --strict, the command fails when there is an error.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * End of security support of PHP versions (https://www.php.net/supported-versions.php)
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const PHP_END_OF_LIFE = array(
		'7.1' => '2019-12-01', '7.2' => '2020-11-30', '7.3' => '2021-12-06', '7.4' => '2022-11-28', '8.0' => '2023-11-26',
		'8.1' => '2025-12-31', '8.2' => '2026-12-31', '8.3' => '2027-12-31', '8.4' => '2028-12-31', '8.5' => '2029-12-31',
	);

	/**
	 * The checks made
	 *
	 * @var    array[]
	 * @since  3.17.0
	 */
	private $checks = array();

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('strict', null, self::OPTION_NONE, 'Fail (exit code 1) when a check reports an error');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Site Health');

		foreach (array('checkPhp', 'checkDatabase', 'checkDatabaseStructure', 'checkUpdates', 'checkSettings', 'checkInstallationFolder',
			'checkFiles', 'checkHandlers', 'checkSecurity') as $method)
		{
			try
			{
				$this->$method();
			}
			catch (\Throwable $e)
			{
				$this->add(strtolower(substr($method, 5)), 'error', 'The check failed: ' . $e->getMessage());
			}
		}

		$counts = array('ok' => 0, 'info' => 0, 'warning' => 0, 'error' => 0);

		foreach ($this->checks as $check)
		{
			$counts[$check['status']]++;
		}

		$io->table(array('check' => 'Check', 'status' => 'Status', 'message' => 'Message'), $this->checks, 'checks');
		$io->setData('summary', $counts);

		$summary = sprintf('%d ok, %d info, %d warning(s), %d error(s).', $counts['ok'], $counts['info'], $counts['warning'], $counts['error']);

		if ($counts['error'])
		{
			$io->warning($summary);

			if ($io->getOption('strict'))
			{
				$io->error('The site has health errors.');

				return self::FAILURE;
			}

			return self::SUCCESS;
		}

		$counts['warning'] ? $io->warning($summary) : $io->success($summary);

		return self::SUCCESS;
	}

	/**
	 * @param   string  $check    The check's name
	 * @param   string  $status   ok, info, warning or error
	 * @param   string  $message  What was found, and what to do about it
	 * @param   array   $details  More values for the JSON output
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function add($check, $status, $message, array $details = array())
	{
		$this->checks[] = array_merge(array('check' => $check, 'status' => $status, 'message' => $message), $details ? array('details' => $details) : array());
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkPhp()
	{
		$branch  = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$eol     = isset(self::PHP_END_OF_LIFE[$branch]) ? self::PHP_END_OF_LIFE[$branch] : null;
		$details = array('version' => PHP_VERSION, 'securitySupportUntil' => $eol);

		if (version_compare(PHP_VERSION, '7.4', '<'))
		{
			$this->add('php', 'warning', sprintf('PHP %s works, but 7.4 is the tested baseline; prefer PHP 8.2 or newer.', PHP_VERSION), $details);
		}
		elseif ($eol !== null && $eol < date('Y-m-d'))
		{
			$this->add('php', 'warning', sprintf('PHP %s no longer gets security fixes (since %s). Move to a supported PHP version.', $branch, $eol), $details);
		}
		else
		{
			$this->add('php', 'ok', sprintf('PHP %s%s.', PHP_VERSION, $eol ? ', with security support until ' . $eol : ''), $details);
		}

		$missing = array_values(array_filter(array('json', 'xml', 'zlib', 'mbstring', 'gd', 'curl', 'openssl', 'zip'), function ($extension)
		{
			return !extension_loaded($extension);
		}));

		if ($missing)
		{
			$this->add('php_extensions', 'warning', 'Missing PHP extensions: ' . implode(', ', $missing) . '.', array('missing' => $missing));
		}
		else
		{
			$this->add('php_extensions', 'ok', 'The recommended PHP extensions are loaded.');
		}
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkDatabase()
	{
		$db      = Factory::getDbo();
		$version = $db->getVersion();

		if ($db instanceof \JDatabaseDriverMysqlonsqlite)
		{
			$this->add('database', 'info', 'SQLite (experimental): ' . $db->getVersionDescription() . '. Suits small to medium sites; it handles one write at a time.');

			return;
		}

		// The server's name: MariaDB uses the MySQL drivers (its version reads e.g. "11.8.9-MariaDB")
		$server = array('mysql' => 'MySQL', 'postgresql' => 'PostgreSQL', 'mssql' => 'SQL Server')[$db->getServerType()] ?? $db->getServerType();

		if (stripos($version, 'mariadb') !== false)
		{
			$server  = 'MariaDB';
			$version = preg_replace('/-MariaDB.*$/i', '', $version);
		}

		if (!$db->isMinimumVersion())
		{
			$this->add('database', 'error', sprintf('The %s server %s is older than this Joomla version supports. Joomla updates are withheld until it is upgraded.',
				$server, $version));

			return;
		}

		$this->add('database', 'ok', sprintf('%s %s with the "%s" driver.', $server, $version, $db->getName()));
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkDatabaseStructure()
	{
		$problems = MaintenanceDatabaseCommand::getProblems();

		if ($problems)
		{
			$this->add('database_structure', 'warning', sprintf('%d database structure problem(s). Run maintenance:database to see them, and --fix to fix them.',
				count($problems)), array('problems' => $problems));

			return;
		}

		$this->add('database_structure', 'ok', 'The database table structure is up to date.');
	}

	/**
	 * Updates as last found (the update check isn't run here: it would contact every update site).
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkUpdates()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('u.extension_id', 'u.name', 'u.version')))
			->select($db->quoteName('e.manifest_cache'))
			->from($db->quoteName('#__updates', 'u'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.extension_id') . ' = ' . $db->quoteName('u.extension_id'))
			->where($db->quoteName('u.extension_id') . ' <> 0');
		$updates = $db->setQuery($query)->loadObjectList();
		$core    = null;
		$others  = array();

		foreach ($updates as $update)
		{
			$manifest = json_decode((string) $update->manifest_cache, true);
			$current  = is_array($manifest) && isset($manifest['version']) ? $manifest['version'] : '';

			if ($current !== '' && version_compare($update->version, $current, '<='))
			{
				continue;
			}

			if ((int) $update->extension_id === 700)
			{
				$core = $update->version;

				continue;
			}

			$others[] = array('id' => (int) $update->extension_id, 'name' => $update->name, 'version' => $update->version, 'installed' => $current);
		}

		if ($core !== null)
		{
			$this->add('joomla_update', 'warning', sprintf('Joomla %s is available (%s installed). Run core:update.', $core, (new Version)->getShortVersion()),
				array('available' => $core));
		}
		else
		{
			$this->add('joomla_update', 'ok', 'No Joomla update was found at the last check (run core:update:check to check now).');
		}

		if ($others)
		{
			$this->add('extension_updates', 'warning', sprintf('%d extension update(s) available. Run extension:update with their IDs.', count($others)),
				array('updates' => $others));
		}
		else
		{
			$this->add('extension_updates', 'ok', 'No extension updates were found at the last check (run update:extensions:check to check now).');
		}
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkSettings()
	{
		$config = Factory::getConfig();

		if ($config->get('debug'))
		{
			$this->add('debug', 'warning', 'Debug System is on. It slows the site down and shows internal details; turn it off on a live site (config:set debug=false).');
		}
		else
		{
			$this->add('debug', 'ok', 'Debug System is off.');
		}

		if (in_array($config->get('error_reporting'), array('development', 'maximum'), true))
		{
			$this->add('error_reporting', 'warning', sprintf('Error Reporting is "%s", so visitors may see PHP messages; use "default" or "none" on a live site.',
				$config->get('error_reporting')));
		}
		else
		{
			$this->add('error_reporting', 'ok', sprintf('Error Reporting is "%s".', $config->get('error_reporting')));
		}

		if ($config->get('offline'))
		{
			$this->add('offline', 'info', 'The site is offline (site:up puts it back online).');
		}
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkInstallationFolder()
	{
		if (is_dir(JPATH_ROOT . '/installation'))
		{
			$this->add('installation_folder', 'error', 'The installation folder is still there. Delete it: until then the installer can be reached.');

			return;
		}

		$this->add('installation_folder', 'ok', 'The installation folder has been removed.');
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkFiles()
	{
		$config = Factory::getConfig();
		$file   = JPATH_CONFIGURATION . '/configuration.php';
		$mode   = @fileperms($file);

		if ($mode !== false && ($mode & 0002))
		{
			$this->add('configuration_file', 'error', sprintf('configuration.php can be changed by any user of the server (permissions %o). Use 0444 or 0644.', $mode & 0777));
		}
		else
		{
			$this->add('configuration_file', 'ok', sprintf('configuration.php permissions: %o.', $mode & 0777));
		}

		foreach (array('tmp_path' => 'Temporary folder', 'log_path' => 'Logs folder') as $key => $label)
		{
			$path = (string) $config->get($key);

			if ($path === '' || !is_dir($path))
			{
				$this->add($key, 'error', sprintf('%s %s doesn\'t exist. Correct it in Global Configuration (config:set %s=...).', $label, $path, $key));
			}
			elseif (!is_writable($path))
			{
				$this->add($key, 'warning', sprintf('%s %s isn\'t writable here (the web server\'s user may differ from this command line\'s).', $label, $path));
			}
			else
			{
				$this->add($key, 'ok', sprintf('%s %s is writable.', $label, $path));
			}
		}

		$db = Factory::getDbo();

		// A SQLite database inside the site must be in a folder whose web access is blocked
		if ($db instanceof \JDatabaseDriverMysqlonsqlite)
		{
			$path   = $db->getDatabasePath();
			$folder = realpath(dirname($path));
			$root   = realpath(JPATH_ROOT);

			if ($folder !== false && $root !== false && strpos($folder . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) === 0)
			{
				$protected = is_file($folder . '/.htaccess') && is_file($folder . '/web.config');
				$this->add('sqlite_location', $protected ? 'info' : 'error', $protected
					? 'The SQLite database is inside the site, in a folder with web-access rules. Servers such as nginx ignore them: a folder outside the web root is safer.'
					: 'The SQLite database is inside the site, in a folder without web-access rules, so it may be downloadable.', array('path' => $path));
			}
			else
			{
				$this->add('sqlite_location', 'ok', 'The SQLite database is outside the site\'s folder.', array('path' => $path));
			}
		}
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkHandlers()
	{
		$config  = Factory::getConfig();
		$session = (string) $config->get('session_handler');
		$cache   = (int) $config->get('caching');

		if ($session === 'database' && Factory::getDbo() instanceof \JDatabaseDriverMysqlonsqlite)
		{
			$this->add('session_handler', 'warning', 'Sessions are stored in the SQLite database, which then writes on every page view. Use PHP sessions (config:set session_handler=none).');
		}
		else
		{
			$this->add('session_handler', 'ok', sprintf('Session handler: %s.', $session === 'none' ? 'PHP' : $session));
		}

		$this->add('cache', $cache ? 'ok' : 'info', $cache
			? sprintf('Caching is on (%s, handler %s).', $cache === 2 ? 'progressive' : 'conservative', $config->get('cache_handler'))
			: 'Caching is off. Turning it on (config:set caching=1) usually speeds the site up.');
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function checkSecurity()
	{
		$config = Factory::getConfig();

		if ((int) $config->get('force_ssl') !== 2)
		{
			$this->add('force_ssl', 'info', 'Force HTTPS is not set for the entire site. Set it once the site has a certificate (config:set force_ssl=2).');
		}
		else
		{
			$this->add('force_ssl', 'ok', 'Force HTTPS is on for the entire site.');
		}

		if (!PluginHelper::isEnabled('system', 'littlewaf'))
		{
			$this->add('little_waf', 'info', 'The Little WAF plugin is off. It blocks known attacks on vulnerable third-party extensions (extension:enable with its ID).');
		}
		else
		{
			$this->add('little_waf', 'ok', 'Little WAF is on.');
		}
	}
}
