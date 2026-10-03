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

		if ($db->getServerType() !== 'mysql')
		{
			$io->error(sprintf('Importing is only supported on MySQL and MariaDB, not with the "%s" database driver.', $db->getName()));

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

					return self::FAILURE;
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
		$file   = $this->compatibleFile($file);
		$result = array();

		try
		{
			// First the structures, then the data
			$reader = $this->open($file);

			while ($reader->read())
			{
				if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'table_structure')
				{
					$table          = $this->createTable(simplexml_import_dom($reader->expand(new \DOMDocument)));
					$result[$table] = 0;
				}
			}

			$reader->close();
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
	 * Drop a table and create it from its exported structure.
	 *
	 * @param   \SimpleXMLElement  $structure  The table_structure element
	 *
	 * @return  string  The table name
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function createTable(\SimpleXMLElement $structure)
	{
		$db      = Factory::getDbo();
		$generic = (string) $structure['name'];
		$table   = $this->getRealTableName($generic);

		if ($table === '')
		{
			throw new \RuntimeException('A table has no name.');
		}

		$db->dropTable($table, true);

		if (isset($structure->create_statement) && trim((string) $structure->create_statement) !== '')
		{
			// The statement names the table with the #__ prefix, which the driver replaces
			$db->setQuery((string) $structure->create_statement)->execute();

			return $table;
		}

		// Files of Joomla 4 and later only describe the columns and keys
		$document = '<?xml version="1.0"?><mysqldump><database name="">' . $structure->asXML() . '</database></mysqldump>';
		$db->getImporter()->from($document)->withStructure()->mergeStructure();

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
		$db      = Factory::getDbo();
		$columns = array();
		$values  = array();

		foreach ($row->field as $field)
		{
			$columns[] = (string) $field['name'];

			if (isset($field['value_is_null']))
			{
				$values[] = 'NULL';

				continue;
			}

			$value = (string) $field;

			if ((string) $field['encoding'] === 'base64')
			{
				$value = base64_decode($value);
			}

			$values[] = $db->quote($value);
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
	 * @param   string  $table  The table name, with the #__ prefix
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getRealTableName($table)
	{
		return preg_replace('/^#__/', Factory::getDbo()->getPrefix(), $table);
	}
}
