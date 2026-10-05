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
use Joomla\CMS\Version;

/**
 * Shows an overview of the site: versions, environment and the main settings.
 *
 * @since  3.17.0
 */
class SiteInfoCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'site:info';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show an overview of the site and its environment';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows the Joomla, PHP and database versions, the site paths and the main global settings. A good first call to learn about a site.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$config  = Factory::getConfig();
		$db      = Factory::getDbo();
		$version = new Version;

		try
		{
			$dbVersion = $db->getVersion();
		}
		catch (\Exception $e)
		{
			$dbVersion = null;
		}

		$dbServer = $db->getServerType();

		// The SQLite driver reports MySQL to the code; show what really stores the data
		if ($db instanceof \JDatabaseDriverMysqlonsqlite)
		{
			$dbServer  = 'sqlite';
			$dbVersion = $dbVersion !== null ? $db->getVersionDescription() : null;
		}

		$io->title('Site Information');
		$io->definitionList(array(
			'sitename'        => $config->get('sitename'),
			'joomlaVersion'   => $version->getShortVersion(),
			'phpVersion'      => PHP_VERSION,
			'phpSapi'         => PHP_SAPI,
			'os'              => PHP_OS,
			'databaseType'    => $config->get('dbtype'),
			'databaseServer'  => $dbServer,
			'databaseVersion' => $dbVersion,
			'databaseName'    => $config->get('db'),
			'tablePrefix'     => $config->get('dbprefix'),
			'rootPath'        => JPATH_ROOT,
			'configPath'      => JPATH_CONFIGURATION . '/configuration.php',
			'liveSite'        => $config->get('live_site'),
			'offline'         => (bool) $config->get('offline'),
			'debug'           => (bool) $config->get('debug'),
			'errorReporting'  => $config->get('error_reporting'),
			'caching'         => (int) $config->get('caching'),
			'cacheHandler'    => $config->get('cache_handler'),
			'sessionHandler'  => $config->get('session_handler'),
			'forceSsl'        => (int) $config->get('force_ssl'),
			'sef'             => (bool) $config->get('sef'),
			'sefRewrite'      => (bool) $config->get('sef_rewrite'),
			'gzip'            => (bool) $config->get('gzip'),
			'timezone'        => $config->get('offset'),
			'mailer'          => $config->get('mailer'),
		));

		return self::SUCCESS;
	}
}
