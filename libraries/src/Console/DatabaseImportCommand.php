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

/**
 * Imports database tables from the XML files of database:export.
 *
 * @since  3.17.0
 */
class DatabaseImportCommand extends AbstractCommand
{
	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $serverPathOptions = array('folder', 'zip');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'database:import';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Import the database';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Replaces database tables with those in the XML files of database:export (every .xml file of --folder, '
		. 'one table with --table, or the files of a ZIP archive in --folder given with --zip). Each table is dropped and created again '
		. 'before its rows are inserted. Table names are converted to this site\'s table prefix. Files of the Joomla 4 and later '
		. 'database:export command can be imported too. Only MySQL and MariaDB are supported. Asks for confirmation when interactive.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * Maximum size of one INSERT statement, well below the smallest default max_allowed_packet (1MB in MySQL 5.5)
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const MAX_STATEMENT = 524288;

	/**
	 * A temporary copy of a file, fixed to be valid XML
	 *
	 * @var    string|null
	 * @since  3.17.0
	 */
	private $compatibleCopy;

	/**
	 * Rows waiting to be inserted: table, column list and value tuples
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	private $pending = array('table' => null, 'columns' => null, 'values' => array(), 'size' => 0);

	/**
	 * Table name => DatabaseTableDefinition, for tables whose values are converted (from another kind of database, or into PostgreSQL)
	 *
	 * @var    DatabaseTableDefinition[]
	 * @since  3.17.0
	 */
	private $definitions = array();

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('folder', null, self::OPTION_REQUIRED, 'Path to the folder containing files to import', '.');
		$this->addOption('zip', null, self::OPTION_REQUIRED, 'The name of a ZIP file to import');
		$this->addOption('table', null, self::OPTION_REQUIRED, 'The name of the database table to import');
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
		$io->title('Importing Database');

		$totalTime = microtime(true);
		$db        = Factory::getDbo();
		$folder    = rtrim((string) $io->getOption('folder'), '/\\') ?: '.';
		$zipFile   = (string) $io->getOption('zip');
		$tableName = (string) $io->getOption('table');

		// Over MCP, from the folder database:export writes to there (never a folder the assistant names; a folder another command
		// gives, e.g. database:convert's temporary one, is used as it is)
		if (Factory::getApplication()->getInterface() === 'MCP' && (string) $io->getOption('folder') === '.')
		{
			$folder = AbstractTemplateCommand::getBackupFolder('database', false);

			if ($folder === false)
			{
				$io->error('There is no export in the site\'s backup folder: run database:export first.');

				return self::NOT_FOUND;
			}
		}

		if (!in_array($db->getServerType(), array('mysql', 'postgresql'), true))
		{
			$io->error(sprintf('Importing is only supported on MySQL/MariaDB, PostgreSQL and SQLite, not with the "%s" database driver.', $db->getName()));

			return self::FAILURE;
		}

		if (!class_exists('XMLReader'))
		{
			$io->error('The PHP xmlreader extension is needed to import.');

			return self::FAILURE;
		}

		$extracted = null;

		if ($zipFile !== '')
		{
			$extracted = $this->extractZip($io, $folder . '/' . $zipFile);

			if ($extracted === false)
			{
				return self::FAILURE;
			}

			$folder = $extracted;
		}

		try
		{
			if ($tableName !== '')
			{
				$files = array($folder . '/' . $tableName . '.xml');

				if (!is_file($files[0]))
				{
					$io->error(sprintf('The %s file does not exist.', basename($files[0])));

					return self::NOT_FOUND;
				}
			}
			else
			{
				$files = glob($folder . '/*.xml') ?: array();
				sort($files);
			}

			if (!$files)
			{
				$io->error(sprintf('There are no .xml files to import in %s.', $folder));

				return self::FAILURE;
			}

			if ($io->isDryRun())
			{
				$refused = false;

				foreach ($files as $file)
				{
					// The tables the file holds, checked as the import would check them
					try
					{
						foreach ($this->getTables($file) as $table)
						{
							$io->plan(sprintf('Replace the table %s with the contents of %s', $table, basename($file)),
								array('action' => 'import', 'table' => $table, 'file' => $file));
						}
					}
					catch (\RuntimeException $e)
					{
						$io->error(sprintf('%s would be refused: %s', basename($file), $e->getMessage()));
						$refused = true;
					}
				}

				return $refused ? self::FAILURE : self::SUCCESS;
			}

			if ($io->isInteractive()
				&& !$io->confirm(sprintf('This replaces %d table(s) of the database "%s". Continue?', count($files), Factory::getConfig()->get('db')), false))
			{
				$io->text('Nothing imported.');

				return self::SUCCESS;
			}

			$imported = array();

			foreach ($files as $file)
			{
				$taskTime = microtime(true);

				$io->text(sprintf('Importing %s', basename($file)));

				try
				{
					$result = $this->importFile($file);
				}
				catch (\Exception $e)
				{
					$io->error(sprintf('Error importing %s: %s', basename($file), $e->getMessage()));
					$io->setData('imported', $imported);

					return self::FAILURE;
				}

				foreach ($result as $table => $rows)
				{
					$imported[] = $table;
					$io->text(sprintf('Imported %d rows into %s in %s seconds', $rows, $table, round(microtime(true) - $taskTime, 3)));
				}
			}
		}
		finally
		{
			if ($extracted)
			{
				\JFolder::delete($extracted);
			}
		}

		$io->setData('imported', $imported);
		$io->success(sprintf('Import completed in %s seconds', round(microtime(true) - $totalTime, 3)));

		return self::SUCCESS;
	}

	/**
	 * Extract the XML files of a ZIP archive to a temporary folder.
	 *
	 * @param   CommandIO  $io       The output
	 * @param   string     $zipPath  The archive
	 *
	 * @return  string|false  The folder, false after reporting an error
	 *
	 * @since   3.17.0
	 */
	protected function extractZip(CommandIO $io, $zipPath)
	{
		if (!class_exists('ZipArchive'))
		{
			$io->error('The PHP zip extension is needed to import ZIP files.');

			return false;
		}

		$archive = new \ZipArchive;

		if (!is_file($zipPath) || $archive->open($zipPath) !== true)
		{
			$io->error(sprintf('Unable to open the archive %s.', $zipPath));

			return false;
		}

		$folder = Factory::getConfig()->get('tmp_path') . '/' . uniqid('dbimport_');

		if (!\JFolder::create($folder))
		{
			$io->error(sprintf('Cannot create the folder %s.', $folder));

			return false;
		}

		for ($i = 0; $i < $archive->numFiles; $i++)
		{
			$name = $archive->getNameIndex($i);

			// Only plain .xml files at the archive's root, so nothing can be written outside the folder
			if (!preg_match('/^[^\/\\\\]+\.xml$/i', $name) || $name[0] === '.')
			{
				continue;
			}

			$source = $archive->getStream($name);
			$target = fopen($folder . '/' . $name, 'wb');

			if (!$source || !$target)
			{
				$io->error(sprintf('Cannot extract %s.', $name));
				\JFolder::delete($folder);

				return false;
			}

			stream_copy_to_stream($source, $target);
			fclose($source);
			fclose($target);
		}

		$archive->close();

		return $folder;
	}

	/**
	 * Import the tables of an XML file.
	 *
	 * @param   string  $file  The file
	 *
	 * @return  array  Table name => number of rows
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function importFile($file)
	{
		$file              = $this->compatibleFile($file);
		$result            = array();
		$this->definitions = array();

		try
		{
			// First the structures, then the data. Every table name and statement is checked before any table is replaced.
			$reader     = $this->open($file);
			$dialect    = 'mysql';
			$structures = array();

			while ($reader->read())
			{
				if ($reader->nodeType !== \XMLReader::ELEMENT)
				{
					continue;
				}

				// The root element tells which kind of database the file comes from
				if ($reader->depth === 0)
				{
					$dialect = $reader->name === 'postgresqldump' ? 'postgresql' : 'mysql';
				}
				elseif ($reader->name === 'table_structure')
				{
					$structure    = simplexml_import_dom($reader->expand(new \DOMDocument));
					$structures[] = array($structure, $this->getCreateStatements($structure, $dialect));
				}
				elseif ($reader->name === 'table_data')
				{
					$this->getRealTableName((string) $reader->getAttribute('name'));
				}
			}

			$reader->close();

			foreach ($structures as $structure)
			{
				$table          = $this->createTable($structure[0], $dialect, $structure[1]);
				$result[$table] = 0;
			}
			$reader = $this->open($file);
			$table  = null;

			while ($reader->read())
			{
				if ($reader->nodeType !== \XMLReader::ELEMENT)
				{
					continue;
				}

				if ($reader->name === 'table_data')
				{
					$this->flush();
					$table = $this->getRealTableName((string) $reader->getAttribute('name'));

					if (!isset($result[$table]))
					{
						$result[$table] = 0;
					}
				}
				elseif ($reader->name === 'row' && $table !== null)
				{
					$this->addRow($table, simplexml_import_dom($reader->expand(new \DOMDocument)));
					$result[$table]++;
				}
			}

			$this->flush();
			$reader->close();
			$this->resetSequences();
		}
		finally
		{
			if (isset($this->compatibleCopy))
			{
				@unlink($this->compatibleCopy);
				$this->compatibleCopy = null;
			}
		}

		return $result;
	}

	/**
	 * The tables a file would replace, with every name and statement checked as importFile() checks them; nothing is changed.
	 *
	 * @param   string  $file  The file
	 *
	 * @return  string[]  The real table names
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function getTables($file)
	{
		$file   = $this->compatibleFile($file);
		$tables = array();

		try
		{
			$reader  = $this->open($file);
			$dialect = 'mysql';

			while ($reader->read())
			{
				if ($reader->nodeType !== \XMLReader::ELEMENT)
				{
					continue;
				}

				if ($reader->depth === 0)
				{
					$dialect = $reader->name === 'postgresqldump' ? 'postgresql' : 'mysql';
				}
				elseif ($reader->name === 'table_structure')
				{
					$structure = simplexml_import_dom($reader->expand(new \DOMDocument));
					$this->getCreateStatements($structure, $dialect);
					$tables[] = $this->getRealTableName((string) $structure['name']);
				}
				elseif ($reader->name === 'table_data')
				{
					$tables[] = $this->getRealTableName((string) $reader->getAttribute('name'));
				}
			}

			$reader->close();
		}
		finally
		{
			if (isset($this->compatibleCopy))
			{
				@unlink($this->compatibleCopy);
				$this->compatibleCopy = null;
			}
		}

		return array_values(array_unique($tables));
	}

	/**
	 * Return a file which XML parsers accept. The Joomla 4 and later exporter writes NULL values with an attribute without
	 * a value (value_is_null), which isn't valid XML, so such files are read from a corrected copy.
	 *
	 * @param   string  $file  The file
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function compatibleFile($file)
	{
		$in    = fopen($file, 'rb');
		$out   = null;
		$carry = '';

		while (!feof($in))
		{
			$chunk = $carry . fread($in, 1048576);

			// Keep the end of the chunk for the next one, so a split attribute is still found
			$carry = feof($in) ? '' : substr($chunk, -20);
			$chunk = feof($in) ? $chunk : substr($chunk, 0, -20);

			if ($out === null && strpos($chunk . $carry, ' value_is_null>') === false)
			{
				continue;
			}

			if ($out === null)
			{
				// Start over, writing the corrected copy
				$this->compatibleCopy = Factory::getConfig()->get('tmp_path') . '/' . uniqid('dbimport_') . '.xml';
				$out                  = fopen($this->compatibleCopy, 'wb');
				rewind($in);
				$carry = '';

				continue;
			}

			fwrite($out, str_replace(' value_is_null>', ' value_is_null="1">', $chunk));
		}

		fclose($in);

		if ($out === null)
		{
			return $file;
		}

		fclose($out);

		return $this->compatibleCopy;
	}

	/**
	 * @param   string  $file  The file
	 *
	 * @return  \XMLReader
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function open($file)
	{
		$reader = new \XMLReader;

		if (!$reader->open($file, null, LIBXML_NONET | LIBXML_PARSEHUGE))
		{
			throw new \RuntimeException('Cannot read the file.');
		}

		return $reader;
	}

	/**
	 * The statements which create a table, from its structure in a file: checked, but not run.
	 *
	 * @param   \SimpleXMLElement  $structure  The table_structure element
	 * @param   string             $dialect    The kind of database the file comes from: mysql or postgresql
	 *
	 * @return  array  statements, and the table definition (null when the file's own statement is used or the structure is merged)
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function getCreateStatements(\SimpleXMLElement $structure, $dialect)
	{
		$db         = Factory::getDbo();
		$generic    = (string) $structure['name'];
		$statements = array();
		$definition = null;

		$this->getRealTableName($generic);

		// From another kind of database, or into PostgreSQL: the structure is translated, and the values converted
		if ($dialect !== 'mysql' || $db->getServerType() !== 'mysql')
		{
			$definition = DatabaseTableDefinition::fromXml($structure, $dialect);
			$statements = $definition->getCreateStatements($db->getServerType(), $db);
		}
		elseif (isset($structure->create_statement) && trim((string) $structure->create_statement) !== '')
		{
			$statement = trim((string) $structure->create_statement);

			// The statement names the table with the #__ prefix, which the driver replaces: it must create this table, nothing else
			if (!preg_match('/^CREATE\\s+TABLE\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?`?' . preg_quote($generic, '/') . '`?\\s*\\(/i', $statement))
			{
				throw new \RuntimeException(sprintf('The structure of %s doesn\'t create that table.', $generic));
			}

			$statements = array($statement);
		}
		else
		{
			// Files of Joomla 4 and later: the importer writes the column types and extras as they are
			foreach ($structure->field as $field)
			{
				DatabaseTableDefinition::check('mysqlType', (string) $field['Type'], $generic);
				DatabaseTableDefinition::check('mysqlExtra', (string) $field['Extra'], $generic);
			}
		}

		// Some drivers run every statement of a query: each must be one statement
		foreach ($statements as $statement)
		{
			if (count(\JDatabaseDriver::splitSql($statement)) !== 1)
			{
				throw new \RuntimeException(sprintf('The structure of %s holds more than one statement.', $generic));
			}
		}

		return array($statements, $definition);
	}

	/**
	 * Replace a table with the structure from a file.
	 *
	 * @param   \SimpleXMLElement  $structure   The table_structure element
	 * @param   string             $dialect     The kind of database the file comes from: mysql or postgresql
	 * @param   array              $statements  The statements and definition from getCreateStatements()
	 *
	 * @return  string  The table name
	 *
	 * @since   3.17.0
	 */
	protected function createTable(\SimpleXMLElement $structure, $dialect, array $statements)
	{
		$db    = Factory::getDbo();
		$table = $this->getRealTableName((string) $structure['name']);

		list($statements, $definition) = $statements;

		$db->dropTable($table, true);

		if ($definition)
		{
			$this->definitions[$table] = $definition;
		}

		foreach ($statements as $statement)
		{
			$db->setQuery($statement)->execute();
		}

		// Files of Joomla 4 and later only describe the columns and keys
		if (!$statements)
		{
			$document = '<?xml version="1.0"?><mysqldump><database name="">' . $structure->asXML() . '</database></mysqldump>';
			$db->getImporter()->from($document)->withStructure()->mergeStructure();
		}

		return $table;
	}

	/**
	 * Queue a row, inserting the queue when it's full.
	 *
	 * @param   string             $table  The table name
	 * @param   \SimpleXMLElement  $row    The row element
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addRow($table, \SimpleXMLElement $row)
	{
		$db         = Factory::getDbo();
		$columns    = array();
		$values     = array();
		$definition = isset($this->definitions[$table]) ? $this->definitions[$table] : null;

		foreach ($row->field as $field)
		{
			$name      = (string) $field['name'];
			$columns[] = $name;
			$value     = null;

			if (!isset($field['value_is_null']))
			{
				$value = (string) $field;

				if ((string) $field['encoding'] === 'base64')
				{
					$value = base64_decode($value);
				}
			}

			if ($definition)
			{
				$values[] = $definition->convertValue($name, $value, $db->getServerType(), $db);
			}
			else
			{
				$values[] = $value === null ? 'NULL' : $db->quote($value);
			}
		}

		$columns = implode(',', $db->quoteName($columns));
		$tuple   = '(' . implode(',', $values) . ')';

		if ($this->pending['table'] !== $table || $this->pending['columns'] !== $columns
			|| $this->pending['size'] + strlen($tuple) > static::MAX_STATEMENT)
		{
			$this->flush();
		}

		$this->pending['table']    = $table;
		$this->pending['columns']  = $columns;
		$this->pending['values'][] = $tuple;
		$this->pending['size']    += strlen($tuple) + 1;
	}

	/**
	 * Insert the queued rows.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function flush()
	{
		if ($this->pending['values'])
		{
			$db = Factory::getDbo();
			$db->setQuery(
				'INSERT INTO ' . $db->quoteName($this->pending['table']) . ' (' . $this->pending['columns'] . ') VALUES '
				. implode(',', $this->pending['values'])
			)->execute();
		}

		$this->pending = array('table' => null, 'columns' => null, 'values' => array(), 'size' => 0);
	}

	/**
	 * PostgreSQL doesn't move a sequence past IDs which are given in an INSERT, so move each one past the imported rows.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function resetSequences()
	{
		$db = Factory::getDbo();

		if ($db->getServerType() !== 'postgresql')
		{
			return;
		}

		foreach ($this->definitions as $table => $definition)
		{
			foreach ($definition->getAutoIncrementColumns() as $column)
			{
				$db->setQuery(
					'SELECT setval(pg_get_serial_sequence(' . $db->quote($db->quoteName($table)) . ', ' . $db->quote($column) . '), '
					. 'COALESCE(MAX(' . $db->quoteName($column) . '), 0) + 1, false) FROM ' . $db->quoteName($table)
				)->execute();
			}
		}
	}

	/**
	 * @param   string  $table  The table name, with the #__ prefix
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getRealTableName($table)
	{
		// Only tables of the site (the exporter writes them with the #__ prefix)
		if (!preg_match('/^#__[A-Za-z0-9_]+$/', $table))
		{
			throw new \RuntimeException(sprintf('%s isn\'t the name of a table of this site.', $table === '' ? 'An empty name' : $table));
		}

		return preg_replace('/^#__/', Factory::getDbo()->getPrefix(), $table);
	}
}
