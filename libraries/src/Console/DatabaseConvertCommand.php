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
use Joomla\Registry\Registry;

/**
 * Moves the site's database between MySQL/MariaDB and SQLite.
 *
 * @since  3.17.0
 */
class DatabaseConvertCommand extends AbstractCommand
{
	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $serverPathOptions = array('file');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'database:convert';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Move the site\'s database between MySQL/MariaDB, PostgreSQL and SQLite';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Copies every table with the site\'s table prefix into a new database, checks that each table has the same number of '
		. 'rows there, and only then points configuration.php at it. The old database is left as it is, so going back is a matter of '
		. 'converting again or restoring configuration.php. With --to=sqlite, the database goes into a new file (--file, by default '
		. 'database/joomla-<random>.sqlite); a folder inside the site gets rules which block web access to it. With --to=mysqli, '
		. '--to=pdomysql (MySQL/MariaDB), --to=postgresql or --to=pgsql (PostgreSQL), the database (--database) must already exist '
		. 'and have no tables with the prefix. Between MySQL and PostgreSQL, column types are translated and Joomla\'s "no date" '
		. 'values (0000-00-00 00:00:00 and 1970-01-01 00:00:00) converted. The site is offline while its database is copied '
		. '(unless it already was), and online again after the switch, or with its current database if the conversion fails. Moving to SQLite also switches database sessions to PHP sessions, '
		. 'which don\'t write to the database on every page view (a Memcached, Redis or APCu session handler is kept).';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * It writes configuration.php (the site's new database), which every request runs.
	 *
	 * @param   array|null  $options    The options of a run
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function writesCode(?array $options = null, ?array $arguments = null)
	{
		return true;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('to', null, self::OPTION_REQUIRED, 'The new database type: sqlite, mysqli, pdomysql, postgresql or pgsql');
		$this->addOption('file', null, self::OPTION_REQUIRED, 'SQLite: the new database file, absolute or relative to the site\'s root folder');
		$this->addOption('host', null, self::OPTION_REQUIRED, 'MySQL/PostgreSQL: the server', 'localhost');
		$this->addOption('user', null, self::OPTION_REQUIRED, 'MySQL/PostgreSQL: the user name');
		$this->addOption('password', null, self::OPTION_REQUIRED, 'MySQL/PostgreSQL: the password (asked for when interactive and not given)');
		$this->addOption('database', null, self::OPTION_REQUIRED, 'MySQL/PostgreSQL: the database name');
		$this->addOption('prefix', null, self::OPTION_REQUIRED, 'The table prefix in the new database (by default the current one)');
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
		$io->title('Convert Database');

		$config  = Factory::getConfig();
		$source  = Factory::getDbo();
		$to      = strtolower((string) $io->getOption('to'));
		$prefix  = (string) ($io->getOption('prefix') ?: $config->get('dbprefix'));
		$families = array('sqlite' => 'SQLite', 'mysqli' => 'MySQL/MariaDB', 'pdomysql' => 'MySQL/MariaDB', 'postgresql' => 'PostgreSQL', 'pgsql' => 'PostgreSQL');

		if (!isset($families[$to]))
		{
			$io->error('Give the new database type with --to: sqlite, mysqli, pdomysql, postgresql or pgsql.');

			return self::INVALID;
		}

		if ($source instanceof \JDatabaseDriverMysqlonsqlite)
		{
			$current = 'SQLite';
		}
		elseif ($source->getServerType() === 'mysql')
		{
			$current = 'MySQL/MariaDB';
		}
		elseif ($source->getServerType() === 'postgresql')
		{
			$current = 'PostgreSQL';
		}
		else
		{
			$io->error('Only MySQL/MariaDB, PostgreSQL and SQLite databases can be converted.');

			return self::FAILURE;
		}

		if ($families[$to] === $current)
		{
			$io->error(sprintf('The site already uses %s; convert it to another kind of database.', $current));

			return self::INVALID;
		}

		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $prefix))
		{
			$io->error('The table prefix may only hold letters, digits and underscores, and must start with a letter.');

			return self::INVALID;
		}

		$settings = $to === 'sqlite' ? $this->getSqliteSettings($io, $prefix) : $this->getServerSettings($io, $to, $families[$to], $prefix);

		if (!$settings)
		{
			return self::INVALID;
		}

		// Connecting creates a new SQLite database file
		if ($io->isDryRun() && $to === 'sqlite')
		{
			return $this->planConversion($io, $settings, $current);
		}

		try
		{
			$target = \JDatabaseDriver::getInstance($settings['options']);
			$target->getVersion();
		}
		catch (\Exception $e)
		{
			$io->error('Cannot connect to the new database: ' . $e->getMessage());

			return self::FAILURE;
		}

		$existing = preg_grep('/^' . preg_quote($prefix, '/') . '/', $target->getTableList());

		if ($existing)
		{
			$io->error(sprintf('The new database already has %d table(s) with the prefix "%s". Use an empty database or another prefix.', count($existing), $prefix));

			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			return $this->planConversion($io, $settings, $current);
		}

		if ($io->isInteractive() && !$io->confirm(sprintf('Copy the database to %s and switch the site to it?', $settings['description']), false))
		{
			$io->text('Nothing converted.');

			return self::SUCCESS;
		}

		// Offline while the database is copied, so nothing changes in it meanwhile; back online with the switch
		$takeOffline = !$config->get('offline');

		if ($takeOffline)
		{
			if (!$this->writeConfiguration($io, array('offline' => 1), 'Cannot take the site offline, as configuration.php can\'t be written.'))
			{
				return self::FAILURE;
			}

			$io->text('The site is offline while its database is copied.');
		}

		$result = self::FAILURE;

		try
		{
			$result = $this->copyAndSwitch($io, $config, $source, $target, $settings, $to, $prefix);
		}
		finally
		{
			// The switch writes configuration.php online again; otherwise restore it as it was
			if ($takeOffline && $result !== self::SUCCESS)
			{
				if ($this->writeConfiguration($io, array(), 'Cannot put the site back online: set "offline" to 0 in configuration.php.'))
				{
					$io->text('The site is online again, still with its current database.');
				}
			}
		}

		if ($takeOffline && $result === self::SUCCESS)
		{
			$io->text('The site is online again.');
		}

		return $result;
	}

	/**
	 * Copy the database, compare the copy, fix its structure and switch configuration.php to it.
	 *
	 * @param   CommandIO         $io        The input values and the output
	 * @param   Registry          $config    The site's configuration
	 * @param   \JDatabaseDriver  $source    The current database
	 * @param   \JDatabaseDriver  $target    The new database
	 * @param   array             $settings  The new database's options and configuration
	 * @param   string            $to        The new database type
	 * @param   string            $prefix    The table prefix in the new database
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function copyAndSwitch(CommandIO $io, $config, $source, $target, array $settings, $to, $prefix)
	{
		/** @var \Joomla\CMS\Application\ConsoleApplication $app */
		$app    = Factory::getApplication();
		$folder = $config->get('tmp_path') . '/' . uniqid('dbconvert_');

		try
		{
			if (!\JFolder::create($folder))
			{
				$io->error('Cannot create the temporary folder ' . $folder . '.');

				return self::FAILURE;
			}

			$io->text('Exporting the current database ...');

			if ($app->runCommand('database:export', array(), array('folder' => $folder, 'quiet' => true)) !== self::SUCCESS)
			{
				$io->error('The export failed.');

				return self::FAILURE;
			}

			$io->text(sprintf('Importing it into %s ...', $settings['description']));

			// The import writes to the site's database object, so point it at the new database for the duration
			$previous           = Factory::$database;
			Factory::$database  = $target;

			try
			{
				$imported = $app->runCommand('database:import', array(), array('folder' => $folder, 'quiet' => true, 'no-interaction' => true));
			}
			finally
			{
				Factory::$database = $previous;
			}

			if ($imported !== self::SUCCESS)
			{
				$io->error('The import failed. The site still uses its current database.');

				return self::FAILURE;
			}
		}
		finally
		{
			if (is_dir($folder))
			{
				\JFolder::delete($folder);
			}
		}

		$io->text('Comparing the tables ...');

		$differences = $this->compare($source, $target, $prefix);

		foreach ($differences as $difference)
		{
			$io->error($difference);
		}

		if ($differences)
		{
			$io->error('The copy differs from the current database, so the site still uses its current database.');

			return self::FAILURE;
		}

		// The core tables differ between MySQL and PostgreSQL (types, indexes, the utf8mb4 conversion record): give the new database
		// the structure Joomla expects there, as Extensions: Database > Fix does
		$io->text('Checking the structure of the new database ...');

		// PostgreSQL has no record of MySQL's utf8mb4 conversion; the tables were just created with utf8mb4 (SQLite is UTF-8 anyway)
		if ($target->getServerType() === 'mysql' && !in_array($prefix . 'utf8_conversion', $target->getTableList(), true))
		{
			$target->setQuery(
				'CREATE TABLE ' . $target->quoteName('#__utf8_conversion') . ' (' . $target->quoteName('converted') . ' tinyint NOT NULL DEFAULT 0)'
				. ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci'
			)->execute();
			$target->setQuery(
				'INSERT INTO ' . $target->quoteName('#__utf8_conversion') . ' (' . $target->quoteName('converted') . ') VALUES ('
				. ($target->hasUTF8mb4Support() ? 5 : 3) . ')'
			)->execute();
		}

		// ... and MySQL's record of it has no use on PostgreSQL, whose Joomla schema doesn't have it
		if ($target->getServerType() === 'postgresql' && in_array($prefix . 'utf8_conversion', $target->getTableList(), true))
		{
			$target->dropTable($prefix . 'utf8_conversion');
		}

		$previous          = Factory::$database;
		Factory::$database = $target;

		try
		{
			$checked = $app->runCommand('maintenance:database', array(), array('fix' => true, 'quiet' => true));
		}
		finally
		{
			Factory::$database = $previous;
		}

		if ($checked !== self::SUCCESS)
		{
			$io->warning('Joomla\'s database check still reports problems in the new database. After the switch, see them with: php cli/joomla.php maintenance:database');
		}

		// Database sessions would write to SQLite's single database file on every page view
		if ($to === 'sqlite' && $config->get('session_handler') === 'database')
		{
			$settings['configuration']['session_handler'] = 'none';
		}

		if (!$this->writeConfiguration($io, $settings['configuration']))
		{
			return self::FAILURE;
		}

		$io->setData('database', $settings['configuration']['db']);
		$io->setData('dbtype', $settings['configuration']['dbtype']);
		$io->setData('sessionHandler', isset($settings['configuration']['session_handler']) ? $settings['configuration']['session_handler'] : $config->get('session_handler'));
		$io->success(sprintf('The site now uses %s. The old database was left as it is.', $settings['description']));

		if (isset($settings['configuration']['session_handler']))
		{
			$io->text('Sessions are now stored by PHP instead of the database, so everyone has to log in again.');
		}

		return self::SUCCESS;
	}

	/**
	 * Report what the conversion would do.
	 *
	 * @param   CommandIO  $io        The input values and the output
	 * @param   array      $settings  The new database's settings
	 * @param   string     $current   The kind of the current database
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function planConversion(CommandIO $io, array $settings, $current)
	{
		$tables = count(preg_grep('/^' . preg_quote(Factory::getDbo()->getPrefix(), '/') . '/', Factory::getDbo()->getTableList()));

		$io->plan('Take the site offline while copying, unless it already is', array('action' => 'offline'));
		$io->plan(sprintf('Copy %d table(s) of the %s database to %s, and compare their rows', $tables, $current, $settings['description']),
			array('action' => 'copy', 'tables' => $tables, 'to' => $settings['description']));
		$io->plan('Fix the new database\'s structure (maintenance:database --fix)', array('action' => 'fix'));
		$io->plan('Switch configuration.php to the new database and put the site back online', array('action' => 'switch'));

		return self::SUCCESS;
	}

	/**
	 * Get the connection options and configuration of a new SQLite database.
	 *
	 * @param   CommandIO  $io      The input values and the output
	 * @param   string     $prefix  The table prefix
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	private function getSqliteSettings(CommandIO $io, $prefix)
	{
		if (!\JDatabaseDriverMysqlonsqlite::isSupported())
		{
			$io->error('SQLite needs PHP 7.4 or newer and the PDO SQLite extension with SQLite 3.37.0 or newer.');

			return false;
		}

		$path     = (string) ($io->getOption('file') ?: 'database/joomla-' . bin2hex(random_bytes(8)) . '.sqlite');
		$absolute = preg_match('#^([a-z]:)?[/\\\\]#i', $path) ? $path : JPATH_ROOT . '/' . $path;
		$folder   = dirname($absolute);

		if (file_exists($absolute))
		{
			$io->error(sprintf('The file %s already exists.', $absolute));

			return false;
		}

		$messages = array(
			'extension' => 'The database file name must end in .sqlite, .sqlite3, .db or .db3.',
			'root'      => 'The database file can\'t be in the site\'s root folder, where nothing can stop it from being downloaded.',
			'folder'    => sprintf('The folder %s has other files in it. Inside the site, the database needs a folder of its own (e.g. database/), as web access to the whole folder is blocked.', $folder),
		);
		$problem  = \JDatabaseDriverMysqlonsqlite::checkLocation($absolute);

		if ($problem !== '')
		{
			$io->error($messages[$problem]);

			return false;
		}

		if (!is_dir($folder) && !$io->isDryRun() && !\JFolder::create($folder))
		{
			$io->error(sprintf('Cannot create the folder %s.', $folder));

			return false;
		}

		$root = realpath(JPATH_ROOT);
		$real = realpath($folder);

		// Inside the site, the file needs a folder of its own, whose web access can be blocked
		if ($root !== false && $real !== false && strpos($real . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) === 0)
		{
			// The folder may have just been created
			$problem = \JDatabaseDriverMysqlonsqlite::checkLocation($real . '/' . basename($absolute));

			if ($problem !== '')
			{
				$io->error($messages[$problem]);

				return false;
			}

			if (!$io->isDryRun())
			{
				\JDatabaseDriverMysqlonsqlite::protectFolder($real);
			}
		}

		return array(
			'description'   => 'the SQLite database ' . $absolute,
			'options'       => array('driver' => 'mysqlonsqlite', 'database' => $path, 'prefix' => $prefix, 'create' => true),
			'configuration' => array('dbtype' => 'mysqlonsqlite', 'db' => $path, 'host' => 'localhost', 'user' => '', 'password' => '', 'dbprefix' => $prefix),
		);
	}

	/**
	 * Get the connection options and configuration of a database on a MySQL/MariaDB or PostgreSQL server.
	 *
	 * @param   CommandIO  $io      The input values and the output
	 * @param   string     $driver  mysqli, pdomysql, postgresql or pgsql
	 * @param   string     $kind    The kind of database, for messages
	 * @param   string     $prefix  The table prefix
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	private function getServerSettings(CommandIO $io, $driver, $kind, $prefix)
	{
		$host     = (string) $io->getOption('host');
		$user     = (string) $io->getOption('user');
		$database = (string) $io->getOption('database');
		$password = $io->getOption('password');

		if ($user === '' || $database === '')
		{
			$io->error(sprintf('Give the %s user and database with --user and --database.', $kind));

			return false;
		}

		if ($password === null && $io->isInteractive())
		{
			$password = $io->ask(sprintf('Please enter the password of the %s user', $kind), '', true);
		}

		return array(
			'description'   => sprintf('the %s database %s on %s', $kind, $database, $host),
			'options'       => array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => (string) $password, 'database' => $database, 'prefix' => $prefix),
			'configuration' => array('dbtype' => $driver, 'db' => $database, 'host' => $host, 'user' => $user, 'password' => (string) $password, 'dbprefix' => $prefix),
		);
	}

	/**
	 * Compare the row counts of the site's tables in both databases.
	 *
	 * @param   \JDatabaseDriver  $source  The current database
	 * @param   \JDatabaseDriver  $target  The new database
	 * @param   string            $prefix  The table prefix in the new database
	 *
	 * @return  string[]  The differences
	 *
	 * @since   3.17.0
	 */
	private function compare($source, $target, $prefix)
	{
		$differences  = array();
		$sourcePrefix = $source->getPrefix();
		$targetTables = $target->getTableList();

		foreach (preg_grep('/^' . preg_quote($sourcePrefix, '/') . '/', $source->getTableList()) as $table)
		{
			$name = $prefix . substr($table, strlen($sourcePrefix));

			if (!in_array($name, $targetTables, true))
			{
				$differences[] = sprintf('The table %s is missing in the new database.', $name);

				continue;
			}

			$count = (int) $source->setQuery('SELECT COUNT(*) FROM ' . $source->quoteName($table))->loadResult();
			$copy  = (int) $target->setQuery('SELECT COUNT(*) FROM ' . $target->quoteName($name))->loadResult();

			if ($count !== $copy)
			{
				$differences[] = sprintf('The table %s has %d rows, its copy %d.', $table, $count, $copy);
			}
		}

		return $differences;
	}

	/**
	 * Write the new database settings to configuration.php.
	 *
	 * @param   CommandIO  $io       The output
	 * @param   array      $changes  Option => value
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function writeConfiguration(CommandIO $io, array $changes, $failure = null)
	{
		$file   = JPATH_CONFIGURATION . '/configuration.php';
		$config = new Registry(get_object_vars(new \JConfig));

		foreach ($changes as $option => $value)
		{
			$config->set($option, $value);
		}

		// Like Global Configuration: writable while writing, read-only afterwards
		if (\JPath::isOwner($file))
		{
			\JPath::setPermissions($file, '0644');
		}

		if (!\JFile::write($file, $config->toString('PHP', array('class' => 'JConfig', 'closingtag' => false))))
		{
			$io->error($failure ?: 'Cannot write configuration.php. The new database is ready; set these options by hand: '
				. json_encode(array_diff_key($changes, array('password' => true))));

			return false;
		}

		if (\JPath::isOwner($file))
		{
			\JPath::setPermissions($file, '0444');
		}

		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($file, true);
		}

		return true;
	}
}
