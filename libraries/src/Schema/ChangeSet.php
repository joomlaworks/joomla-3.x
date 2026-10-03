<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Schema;

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');

/**
 * Contains a set of JSchemaChange objects for a particular instance of Joomla.
 * Each of these objects contains a DDL query that should have been run against
 * the database when this database was created or updated. This enables the
 * Installation Manager to check that the current database schema is up to date.
 *
 * @since  2.5
 */
class ChangeSet
{
	/**
	 * Array of ChangeItem objects
	 *
	 * @var    ChangeItem[]
	 * @since  2.5
	 */
	protected $changeItems = array();

	/**
	 * \JDatabaseDriver object
	 *
	 * @var    \JDatabaseDriver
	 * @since  2.5
	 */
	protected $db = null;

	/**
	 * Folder where SQL update files will be found
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $folder = null;

	/**
	 * The singleton instance of this object
	 *
	 * @var    ChangeSet
	 * @since  3.5.1
	 */
	protected static $instance;

	/**
	 * Tables which removable core components drop when they are uninstalled (see their uninstall SQL)
	 *
	 * @var    array
	 * @since  3.16.0
	 */
	protected static $removableComponentTables = array(
		'com_banners'   => '(?:banners|banner_clients|banner_tracks)',
		'com_contact'   => 'contact_details',
		'com_finder'    => 'finder_\w+',
		'com_newsfeeds' => 'newsfeeds',
	);

	/**
	 * Constructor: builds array of $changeItems by processing the .sql files in a folder.
	 * The folder for the Joomla core updates is `administrator/components/com_admin/sql/updates/<database>`.
	 *
	 * @param   \JDatabaseDriver  $db      The current database object
	 * @param   string            $folder  The full path to the folder containing the update queries
	 *
	 * @since   2.5
	 */
	public function __construct($db, $folder = null)
	{
		$this->db = $db;
		$this->folder = $folder;
		$updateFiles = $this->getUpdateFiles();
		$updateQueries = $this->getUpdateQueries($updateFiles);
		$removedTables = static::getRemovedComponentTablesPattern($db);

		foreach ($updateQueries as $obj)
		{
			// The tables of an uninstalled core component are gone on purpose, there is nothing to check or fix
			if (static::targetsRemovedComponentTable($obj->updateQuery, $removedTables))
			{
				continue;
			}

			$changeItem = ChangeItem::getInstance($db, $obj->file, $obj->updateQuery);

			if ($changeItem->queryType === 'UTF8CNV')
			{
				// Execute the special update query for utf8mb4 conversion status reset
				try
				{
					$this->db->setQuery($changeItem->updateQuery)->execute();
				}
				catch (\RuntimeException $e)
				{
					\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				}
			}
			else
			{
				// Normal change item
				$this->changeItems[] = $changeItem;
			}
		}

		// If on mysql, add a query at the end to check for utf8mb4 conversion status
		if ($this->db->getServerType() === 'mysql')
		{
			// Let the update query do nothing when being executed
			$tmpSchemaChangeItem = ChangeItem::getInstance(
				$db,
				'database.php',
				'UPDATE ' . $this->db->quoteName('#__utf8_conversion')
				. ' SET ' . $this->db->quoteName('converted') . ' = '
				. $this->db->quoteName('converted') . ';');

			// Set to not skipped
			$tmpSchemaChangeItem->checkStatus = 0;

			// Set the check query
			if ($this->db->hasUTF8mb4Support())
			{
				$converted = 5;
				$tmpSchemaChangeItem->queryType = 'UTF8_CONVERSION_UTF8MB4';
			}
			else
			{
				$converted = 3;
				$tmpSchemaChangeItem->queryType = 'UTF8_CONVERSION_UTF8';
			}

			$tmpSchemaChangeItem->checkQuery = 'SELECT '
				. $this->db->quoteName('converted')
				. ' FROM ' . $this->db->quoteName('#__utf8_conversion')
				. ' WHERE ' . $this->db->quoteName('converted') . ' = ' . $converted;

			// Set expected records from check query
			$tmpSchemaChangeItem->checkQueryExpected = 1;

			$tmpSchemaChangeItem->msgElements = array();

			$this->changeItems[] = $tmpSchemaChangeItem;
		}
	}

	/**
	 * Builds a regular expression matching the tables of the removable core components which are not installed.
	 *
	 * @param   \JDatabaseDriver  $db  The database driver
	 *
	 * @return  string|null  The pattern, or null when all of them are installed or the state can't be read
	 *
	 * @since   3.16.0
	 */
	public static function getRemovedComponentTablesPattern($db)
	{
		$query = $db->getQuery(true)
			->select($db->quoteName('element'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('element') . ' IN (' . implode(',', $db->quote(array_keys(static::$removableComponentTables))) . ')')
			->where($db->quoteName('state') . ' <> -1');

		try
		{
			$installed = $db->setQuery($query)->loadColumn();
		}
		catch (\RuntimeException $e)
		{
			return null;
		}

		$removed = array_diff_key(static::$removableComponentTables, array_flip($installed));

		return $removed ? '/^#__(?:' . implode('|', $removed) . ')$/i' : null;
	}

	/**
	 * Checks whether an SQL statement changes one of the given tables. Only the table the statement acts on counts,
	 * not tables merely mentioned in its data (e.g. "dbtable" entries in #__content_types).
	 *
	 * @param   string       $query    The SQL statement
	 * @param   string|null  $pattern  The pattern from getRemovedComponentTablesPattern()
	 *
	 * @return  boolean
	 *
	 * @since   3.16.0
	 */
	public static function targetsRemovedComponentTable($query, $pattern)
	{
		if ($pattern === null)
		{
			return false;
		}

		$query = preg_replace(array('#/\*.*?\*/#s', '#^\s*--.*$#m'), '', (string) $query);

		$statements = array(
			'/^\s*(?:ALTER\s+TABLE|CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
			. '|(?:INSERT|REPLACE)\s+(?:IGNORE\s+)?INTO|UPDATE|DELETE\s+FROM|TRUNCATE(?:\s+TABLE)?|RENAME\s+TABLE'
			. '|CREATE\s+(?:UNIQUE\s+)?(?:(?:NON)?CLUSTERED\s+)?INDEX\s+\S+\s+ON|DROP\s+INDEX\s+\S+\s+ON)\s+[`"\[]?(#__\w+)/i',

			// SQL Azure: stored procedure calls such as EXECUTE "#removeDefault" "#__table", 'column'
			'/^\s*EXEC(?:UTE)?\s+\S+\s+["\[]?(#__\w+)/i',

			// SQL Azure: batches working on a table assigned to a variable first
			'/^\s*DECLARE\s+@table\b[\s\S]*?SET\s+@table\s*=\s*["\'\[]?(#__\w+)/i',
		);

		foreach ($statements as $statement)
		{
			if (preg_match($statement, $query, $matches))
			{
				return (bool) preg_match($pattern, $matches[1]);
			}
		}

		return false;
	}

	/**
	 * Returns a reference to the ChangeSet object, only creating it if it doesn't already exist.
	 *
	 * @param   \JDatabaseDriver  $db      The current database object
	 * @param   string            $folder  The full path to the folder containing the update queries
	 *
	 * @return  ChangeSet
	 *
	 * @since   2.5
	 */
	public static function getInstance($db, $folder = null)
	{
		if (!is_object(static::$instance))
		{
			static::$instance = new ChangeSet($db, $folder);
		}

		return static::$instance;
	}

	/**
	 * Checks the database and returns an array of any errors found.
	 * Note these are not database errors but rather situations where
	 * the current schema is not up to date.
	 *
	 * @return   array Array of errors if any.
	 *
	 * @since    2.5
	 */
	public function check()
	{
		$errors = array();

		foreach ($this->changeItems as $item)
		{
			if ($item->check() === -2)
			{
				// Error found
				$errors[] = $item;
			}
		}

		return $errors;
	}

	/**
	 * Runs the update query to apply the change to the database
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function fix()
	{
		$this->check();

		foreach ($this->changeItems as $item)
		{
			$item->fix();
		}
	}

	/**
	 * Returns an array of results for this set
	 *
	 * @return  array  associative array of changeitems grouped by unchecked, ok, error, and skipped
	 *
	 * @since   2.5
	 */
	public function getStatus()
	{
		$result = array('unchecked' => array(), 'ok' => array(), 'error' => array(), 'skipped' => array());

		foreach ($this->changeItems as $item)
		{
			switch ($item->checkStatus)
			{
				case 0:
					$result['unchecked'][] = $item;
					break;
				case 1:
					$result['ok'][] = $item;
					break;
				case -2:
					$result['error'][] = $item;
					break;
				case -1:
					$result['skipped'][] = $item;
					break;
			}
		}

		return $result;
	}

	/**
	 * Gets the current database schema, based on the highest version number.
	 * Note that the .sql files are named based on the version and date, so
	 * the file name of the last file should match the database schema version
	 * in the #__schemas table.
	 *
	 * @return  string  the schema version for the database
	 *
	 * @since   2.5
	 */
	public function getSchema()
	{
		$updateFiles = $this->getUpdateFiles();
		$result = new \SplFileInfo(array_pop($updateFiles));

		return $result->getBasename('.sql');
	}

	/**
	 * Get list of SQL update files for this database
	 *
	 * @return  array  list of sql update full-path names
	 *
	 * @since   2.5
	 */
	private function getUpdateFiles()
	{
		// Get the folder from the database name
		$sqlFolder = $this->db->getServerType();

		// For `mssql` server types, convert the type to `sqlazure`
		if ($sqlFolder === 'mssql')
		{
			$sqlFolder = 'sqlazure';
		}

		// Default folder to core com_admin
		if (!$this->folder)
		{
			$this->folder = JPATH_ADMINISTRATOR . '/components/com_admin/sql/updates/';
		}

		return \JFolder::files(
			$this->folder . '/' . $sqlFolder, '\.sql$', 1, true, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array('^\..*', '.*~'), true
		);
	}

	/**
	 * Get array of SQL queries
	 *
	 * @param   array  $sqlfiles  Array of .sql update filenames.
	 *
	 * @return  array  Array of \stdClass objects where:
	 *                    file=filename,
	 *                    update_query = text of SQL update query
	 *
	 * @since   2.5
	 */
	private function getUpdateQueries(array $sqlfiles)
	{
		// Hold results as array of objects
		$result = array();

		foreach ($sqlfiles as $file)
		{
			$buffer = file_get_contents($file);

			// Create an array of queries from the sql file
			$queries = \JDatabaseDriver::splitSql($buffer);

			foreach ($queries as $query)
			{
				$fileQueries = new \stdClass;
				$fileQueries->file = $file;
				$fileQueries->updateQuery = $query;
				$result[] = $fileQueries;
			}
		}

		return $result;
	}
}
