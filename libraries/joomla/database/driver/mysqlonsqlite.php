<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Database
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * SQLite database driver which runs MySQL SQL, so core and extensions work unchanged, through the "MySQL on SQLite"
 * emulation layer (libraries/vendor/wordpress/mysql-on-sqlite). It reports itself as a MySQL server: code chooses its
 * MySQL queries and SQL files, and the layer translates them.
 *
 * The "database" option is the path of the SQLite file; relative paths are relative to the site's root folder.
 *
 * @since  3.17.0
 */
class JDatabaseDriverMysqlonsqlite extends JDatabaseDriverPdomysql
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	public $name = 'mysqlonsqlite';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	public $serverType = 'mysql';

	/**
	 * The collation the layer gives text columns, which compares like MySQL's utf8mb4_unicode_ci
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	const COLLATION = 'utf8mb4_unicode_ci';

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 *
	 * @since   3.17.0
	 */
	public function __construct($options)
	{
		// The SQLite file is created only when asked to, e.g. by the installer; a wrong path must not give a site an empty database
		$options['create'] = !empty($options['create']);

		parent::__construct($options);
	}

	/**
	 * Test to see if the driver can be used: PHP 7.4 or newer, the PDO SQLite extension and SQLite 3.37.0 or newer.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isSupported()
	{
		static $supported = null;

		if ($supported === null)
		{
			$supported = false;

			if (PHP_VERSION_ID >= 70400 && class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true))
			{
				try
				{
					$pdo       = new PDO('sqlite::memory:');
					$supported = version_compare($pdo->query('SELECT sqlite_version()')->fetchColumn(), '3.37.0', '>=');
				}
				catch (Exception $e)
				{
					$supported = false;
				}
			}
		}

		return $supported;
	}

	/**
	 * Get the absolute path of the SQLite file.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getDatabasePath()
	{
		$path = trim((string) $this->options['database']);

		if ($path !== '' && !preg_match('#^([a-z]:)?[/\\\\]#i', $path) && defined('JPATH_ROOT'))
		{
			$path = JPATH_ROOT . '/' . $path;
		}

		return $path;
	}

	/**
	 * Write a consistent copy of the database to a new file, also while other requests use it.
	 *
	 * @param   string  $path  The absolute path of the new file, which must not exist
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	public function copyTo($path)
	{
		$this->connect();

		if (file_exists($path))
		{
			throw new RuntimeException(sprintf('The file "%s" already exists.', $path));
		}

		$this->connection->get_sqlite_pdo()->exec('VACUUM INTO ' . $this->connection->get_sqlite_pdo()->quote($path));

		$copy  = new PDO('sqlite:' . $path);
		$check = $copy->query('PRAGMA quick_check')->fetchColumn();
		$copy  = null;

		if ($check !== 'ok')
		{
			static::deleteDatabaseFiles($path);

			throw new RuntimeException(sprintf('The copy of the database failed its integrity check: %s', $check));
		}
	}

	/**
	 * Optimise the database file: update the query planner's statistics (ANALYZE) and, unless only that is asked for, rebuild the
	 * file without its free pages (VACUUM). Native SQLite statements, which the MySQL translation layer doesn't know. VACUUM needs
	 * a moment with no other writes; other requests wait for it (busy timeout).
	 *
	 * @param   boolean  $analyzeOnly  Only update the statistics
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	public function optimize($analyzeOnly = false)
	{
		$this->connect();

		$pdo = $this->connection->get_sqlite_pdo();
		$pdo->exec('ANALYZE');

		if (!$analyzeOnly)
		{
			$pdo->exec('VACUUM');

			// In WAL mode the rebuilt database is in the write-ahead log until a checkpoint copies it back and empties the log
			$pdo->query('PRAGMA wal_checkpoint(TRUNCATE)')->fetchAll();
		}

		$pdo->exec('PRAGMA optimize');
	}

	/**
	 * Use another database file from now on, e.g. after copyTo(). Objects holding this driver follow it.
	 *
	 * @param   string  $path  The absolute path of the file
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 * @throws  JDatabaseExceptionConnecting
	 */
	public function switchTo($path)
	{
		$this->disconnect();
		$this->options['database'] = $path;
		$this->connect();
	}

	/**
	 * Delete a database file and the files SQLite keeps next to it.
	 *
	 * @param   string  $path  The absolute path of the file
	 *
	 * @return  boolean  True if none of them is left
	 *
	 * @since   3.17.0
	 */
	public static function deleteDatabaseFiles($path)
	{
		foreach (array('', '-wal', '-shm', '-journal') as $suffix)
		{
			if (file_exists($path . $suffix))
			{
				@unlink($path . $suffix);
			}
		}

		clearstatcache();

		return !file_exists($path) && !file_exists($path . '-wal');
	}

	/**
	 * Check a new place for a database file: a database file name, and inside the site a folder of its own (not the site's root,
	 * and no folder which holds anything else, e.g. images/). The database file holds content anyone can write, such as article
	 * text, so a name a web server would run (e.g. .php) or a folder whose web access can't be blocked would expose or run it.
	 *
	 * @param   string  $path  The absolute path of the database file; its folder must exist
	 *
	 * @return  string  An empty string when the place is fine, otherwise "extension", "root" or "folder"
	 *
	 * @since   3.17.0
	 */
	public static function checkLocation($path)
	{
		if (!preg_match('#\.(sqlite3?|db3?)$#i', basename($path)))
		{
			return 'extension';
		}

		// The folder may have been created or removed by an earlier request: no cached answers
		clearstatcache(true);

		$root   = realpath(JPATH_ROOT);
		$folder = realpath(dirname($path));

		if ($root === false || $folder === false || strpos($folder . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) !== 0)
		{
			return '';
		}

		if ($folder === $root)
		{
			return 'root';
		}

		if (!is_dir($folder))
		{
			return '';
		}

		// Only the protection files and database files (with SQLite's -wal, -shm and -journal files) may be there
		try
		{
			foreach (new DirectoryIterator($folder) as $entry)
			{
				$name = $entry->getFilename();

				if ($entry->isDot() || in_array($name, array('.htaccess', 'web.config', 'index.html'), true)
					|| ($entry->isFile() && preg_match('#\.(sqlite3?|db3?)(-wal|-shm|-journal)?$#i', $name)))
				{
					continue;
				}

				return 'folder';
			}
		}
		catch (UnexpectedValueException $e)
		{
			// A folder which can't be read can't be checked
			return 'folder';
		}

		return '';
	}

	/**
	 * Block web access to the folder of a database file inside the site, with .htaccess (Apache) and web.config (IIS) rules.
	 * Servers such as nginx ignore these, so database files there should also have a name nobody can guess.
	 *
	 * @param   string  $folder  The folder
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function protectFolder($folder)
	{
		$files = array(
			'.htaccess'  => "# Blocks web access to the database in this folder\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<configuration>\n\t<system.webServer>\n\t\t<security>\n"
				. "\t\t\t<requestFiltering>\n\t\t\t\t<fileExtensions allowUnlisted=\"false\" />\n\t\t\t</requestFiltering>\n"
				. "\t\t</security>\n\t</system.webServer>\n</configuration>\n",
			'index.html' => '<!DOCTYPE html><title></title>',
		);

		foreach ($files as $name => $content)
		{
			if (!is_file($folder . '/' . $name))
			{
				file_put_contents($folder . '/' . $name, $content);
			}
		}
	}

	/**
	 * Open the SQLite file.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 * @throws  JDatabaseExceptionConnecting
	 */
	public function connect()
	{
		if ($this->connection)
		{
			return;
		}

		if (!static::isSupported())
		{
			throw new JDatabaseExceptionUnsupported('The SQLite database driver needs PHP 7.4 or newer and the PDO SQLite extension with SQLite 3.37.0 or newer.');
		}

		$path = $this->getDatabasePath();

		if ($path === '' || (!$this->options['create'] && !is_file($path)))
		{
			throw new JDatabaseExceptionConnecting(sprintf('The SQLite database file "%s" does not exist.', $path), 1);
		}

		if (!class_exists('WP_MySQL_On_SQLite', false))
		{
			require_once JPATH_PLATFORM . '/vendor/wordpress/mysql-on-sqlite/src/load.php';
		}

		try
		{
			$this->connection = new WP_MySQL_On_SQLite('mysql-on-sqlite:path=' . str_replace(';', ';;', $path) . ';dbname=joomla');
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);

			// Joomla's MySQL drivers turn strict mode off: Joomla 3 stores zero dates, which MySQL 8's defaults reject
			$this->connection->query("SET @@SESSION.sql_mode = ''");

			JDatabaseMysqlonsqliteFunctions::register($this->connection);
		}
		catch (Exception $e)
		{
			$this->connection = null;

			throw new JDatabaseExceptionConnecting('Could not open the SQLite database: ' . $e->getMessage(), 2, $e);
		}

		$this->utf8mb4 = true;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function disconnect()
	{
		$this->freeResult();
		$this->connection = null;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function connected()
	{
		return is_object($this->connection);
	}

	/**
	 * There is one database, the file.
	 *
	 * @param   string  $database  Ignored
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function select($database)
	{
		$this->connect();

		return true;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function setUtf()
	{
		return true;
	}

	/**
	 * The layer doesn't report the collation variables, so give the one it uses.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getCollation()
	{
		return static::COLLATION;
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getConnectionCollation()
	{
		return static::COLLATION;
	}

	/**
	 * Set the query to run. The layer has no prepared statements, so the query is only stored; execute() runs it.
	 *
	 * @param   mixed    $query          The SQL statement to set either as a JDatabaseQuery object or a string.
	 * @param   integer  $offset         The affected row offset to set.
	 * @param   integer  $limit          The maximum affected rows to set.
	 * @param   array    $driverOptions  Ignored
	 *
	 * @return  JDatabaseDriver  This object to support method chaining.
	 *
	 * @since   3.17.0
	 */
	public function setQuery($query, $offset = null, $limit = null, $driverOptions = array())
	{
		$this->connect();
		$this->freeResult();

		if (is_string($query))
		{
			// Allows bound variables in a direct query
			$query = $this->getQuery(true)->setQuery($query);
		}

		if ($query instanceof JDatabaseQueryLimitable && !is_null($offset) && !is_null($limit))
		{
			$query = $query->processLimit($query, $limit, $offset);
		}

		return JDatabaseDriver::setQuery($query, $offset, $limit);
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		$this->connect();

		$sql = $this->replacePrefix((string) $this->sql);

		// The layer resets its last insert ID when a statement starts, so keep the one LAST_INSERT_ID() must return
		if (stripos($sql, 'last_insert_id') !== false)
		{
			JDatabaseMysqlonsqliteFunctions::$lastInsertId = (int) $this->connection->lastInsertId();
		}

		$this->count++;
		$this->errorNum = 0;
		$this->errorMsg = '';

		if ($this->debug)
		{
			$this->log[] = $sql;

			JLog::add($sql, JLog::DEBUG, 'databasequery');

			$this->timings[] = microtime(true);
		}

		try
		{
			$this->prepared = $this->connection->query($sql);
			$this->executed = true;
		}
		catch (Exception $e)
		{
			$this->executed = false;
			$this->errorNum = (int) $e->getCode();
			$this->errorMsg = $e->getMessage();

			JLog::add(JText::sprintf('JLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), JLog::ERROR, 'database-error');

			throw new JDatabaseExceptionExecuting($sql, $this->errorMsg, $this->errorNum, $e);
		}

		if ($this->debug)
		{
			$this->timings[] = microtime(true);
			$this->callStacks[] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}

		return $this->prepared;
	}

	/**
	 * Get the number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE.
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public function getAffectedRows()
	{
		$this->connect();

		return $this->prepared instanceof PDOStatement ? $this->prepared->rowCount() : 0;
	}

	/**
	 * Get the number of rows the last statement returned (or changed, for INSERT, UPDATE, REPLACE or DELETE).
	 *
	 * @param   PDOStatement  $cursor  An optional result set; its query must be known (the last one, or PHP 8.1+).
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public function getNumRows($cursor = null)
	{
		$this->connect();

		$cursor = $cursor instanceof PDOStatement ? $cursor : $this->prepared;

		if (!$cursor instanceof PDOStatement)
		{
			return 0;
		}

		// SQLite counts no rows for a SELECT, so lists counted this way (those grouping rows) would be empty: run it again and count
		if ($cursor->columnCount() === 0)
		{
			return $cursor->rowCount();
		}

		$sql = $cursor === $this->prepared ? $this->replacePrefix((string) $this->sql) : (string) $cursor->queryString;

		if ($sql === '')
		{
			return $cursor->rowCount();
		}

		$rows   = 0;
		$result = $this->connection->query($sql);

		while ($result->fetch(PDO::FETCH_NUM) !== false)
		{
			$rows++;
		}

		$result->closeCursor();

		return $rows;
	}

	/**
	 * Get the version of the emulated MySQL server, e.g. "8.0.38-mysql-on-sqlite-3.0.2".
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getVersion()
	{
		$this->connect();

		return (string) $this->connection->query('SELECT VERSION()')->fetchColumn();
	}

	/**
	 * Describe the database version for site administrators, e.g. "3.46.1 (MySQL 8.0.38 emulation)". getVersion() returns the
	 * emulated MySQL version, which the code needs (e.g. for minimum version checks), but which isn't what stores the data.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getVersionDescription()
	{
		return sprintf('%s (MySQL %s emulation)', $this->getSqliteVersion(), strtok($this->getVersion(), '-'));
	}

	/**
	 * Get the version of the SQLite library.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getSqliteVersion()
	{
		$this->connect();

		return $this->connection->get_sqlite_version();
	}
}
