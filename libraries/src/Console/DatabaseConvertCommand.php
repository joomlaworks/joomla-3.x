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
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'database:convert';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Move the site\'s database between MySQL/MariaDB and SQLite';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Copies every table with the site\'s table prefix into a new database, checks that each table has the same number of '
		. 'rows there, and only then points configuration.php at it. The old database is left as it is, so going back is a matter of '
		. 'converting again or restoring configuration.php. With --to=sqlite, the database goes into a new file (--file, by default '
		. 'database/joomla-<random>.sqlite); a folder inside the site gets rules which block web access to it. With --to=mysqli or '
		. '--to=pdomysql, the database (--database) must already exist and have no tables with the prefix. Put the site offline first '
		. '(site:down), so nothing changes while it\'s copied. Moving to SQLite also switches database sessions to PHP sessions, '
		. 'which don\'t write to the database on every page view (a Memcached, Redis or APCu session handler is kept).';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('to', null, self::OPTION_REQUIRED, 'The new database type: sqlite, mysqli or pdomysql');
		$this->addOption('file', null, self::OPTION_REQUIRED, 'SQLite: the new database file, absolute or relative to the site\'s root folder');
		$this->addOption('host', null, self::OPTION_REQUIRED, 'MySQL: the server', 'localhost');
		$this->addOption('user', null, self::OPTION_REQUIRED, 'MySQL: the user name');
		$this->addOption('password', null, self::OPTION_REQUIRED, 'MySQL: the password (asked for when interactive and not given)');
		$this->addOption('database', null, self::OPTION_REQUIRED, 'MySQL: the database name');
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
		$sqlite  = $source instanceof \JDatabaseDriverMysqlonsqlite;

		if (!in_array($to, array('sqlite', 'mysqli', 'pdomysql'), true))
		{
			$io->error('Give the new database type with --to: sqlite, mysqli or pdomysql.');

			return self::INVALID;
		}

		if ($source->getServerType() !== 'mysql')
		{
			$io->error('Only MySQL/MariaDB and SQLite databases can be converted.');

			return self::FAILURE;
		}

		if (($to === 'sqlite') === $sqlite)
		{
			$io->error($sqlite ? 'The site already uses SQLite.' : 'The site already uses a MySQL server; only the move to SQLite and back is supported.');

			return self::INVALID;
		}

		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $prefix))
		{
			$io->error('The table prefix may only hold letters, digits and underscores, and must start with a letter.');

			return self::INVALID;
		}

		$settings = $to === 'sqlite' ? $this->getSqliteSettings($io, $prefix) : $this->getMysqlSettings($io, $to, $prefix);

		if (!$settings)
		{
			return self::INVALID;
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

		if ($io->isInteractive() && !$io->confirm(sprintf('Copy the database to %s and switch the site to it?', $settings['description']), false))
		{
			$io->text('Nothing converted.');

			return self::SUCCESS;
		}

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

		if (!is_dir($folder) && !\JFolder::create($folder))
		{
			$io->error(sprintf('Cannot create the folder %s.', $folder));

			return false;
		}

		$root = realpath(JPATH_ROOT);
		$real = realpath($folder);

		// Inside the site, the file needs a folder of its own, whose web access can be blocked
		if ($root !== false && $real !== false && strpos($real . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) === 0)
		{
			if ($real === $root)
			{
				$io->error('The database file can\'t be in the site\'s root folder, where nothing can stop it from being downloaded.');

				return false;
			}

			\JDatabaseDriverMysqlonsqlite::protectFolder($real);
		}

		return array(
			'description'   => 'the SQLite database ' . $absolute,
			'options'       => array('driver' => 'mysqlonsqlite', 'database' => $path, 'prefix' => $prefix, 'create' => true),
			'configuration' => array('dbtype' => 'mysqlonsqlite', 'db' => $path, 'host' => 'localhost', 'user' => '', 'password' => '', 'dbprefix' => $prefix),
		);
	}

	/**
	 * Get the connection options and configuration of a MySQL database.
	 *
	 * @param   CommandIO  $io      The input values and the output
	 * @param   string     $driver  mysqli or pdomysql
	 * @param   string     $prefix  The table prefix
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	private function getMysqlSettings(CommandIO $io, $driver, $prefix)
	{
		$host     = (string) $io->getOption('host');
		$user     = (string) $io->getOption('user');
		$database = (string) $io->getOption('database');
		$password = $io->getOption('password');

		if ($user === '' || $database === '')
		{
			$io->error('Give the MySQL user and database with --user and --database.');

			return false;
		}

		if ($password === null && $io->isInteractive())
		{
			$password = $io->ask('Please enter the password of the MySQL user', '', true);
		}

		return array(
			'description'   => sprintf('the MySQL database %s on %s', $database, $host),
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
	private function writeConfiguration(CommandIO $io, array $changes)
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
			$io->error('Cannot write configuration.php. The new database is ready; set these options by hand: '
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
