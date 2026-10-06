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
 * Exports the site's database tables to XML files, in the format of the Joomla 4 and later database:export command.
 *
 * @since  3.17.0
 */
class DatabaseExportCommand extends AbstractCommand
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
	protected $name = 'database:export';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Export the database';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Writes each table with the site\'s table prefix to an XML file named after the table, holding its structure and data, '
		. 'or one table with --table. With --zip the files go into a data_exported_<date>.zip archive instead, or into one named as given, e.g. --zip=mysite.zip '
		. '(a name without a folder goes into --folder). '
		. 'The format is the one of the Joomla 4 and later database:export command; on MySQL and MariaDB each table also carries its exact '
		. 'CREATE TABLE statement, which database:import uses. Rows are read in batches, so large tables don\'t need much memory.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * Rows read per query
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const BATCH = 1000;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('folder', null, self::OPTION_REQUIRED, 'Path to write the export files to', '.');
		$this->addOption('table', null, self::OPTION_REQUIRED, 'The name of the database table to export');
		$this->addOption('zip', null, self::OPTION_OPTIONAL, 'Save the export to a ZIP archive, optionally with this file name');
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
		$io->title('Exporting Database');

		$totalTime = microtime(true);
		$db        = Factory::getDbo();
		$folder    = rtrim((string) $io->getOption('folder'), '/\\') ?: '.';
		$tableName = (string) $io->getOption('table');
		$zip       = $io->getOption('zip');

		// Over MCP, a folder of the site's protected backup folder, never one the assistant names: an export holds e.g. the users'
		// password hashes, and a folder the web serves (or a template's, which template:file:get reads) would give them away
		$mcp = Factory::getApplication()->getInterface() === 'MCP';

		if ($mcp)
		{
			$folder = AbstractTemplateCommand::getBackupFolder('database', !$io->isDryRun());

			if ($folder === false && !$io->isDryRun())
			{
				$io->error('The site\'s backup folder (backup/) could not be created: the site\'s root folder must be writable.');

				return self::FAILURE;
			}

			$folder = $folder === false ? 'backup/(protected folder)/database' : $folder;
		}

		try
		{
			$db->getExporter();
		}
		catch (\JDatabaseExceptionUnsupported $e)
		{
			$io->error(sprintf('The "%s" database driver does not support exporting data.', $db->getName()));

			return self::FAILURE;
		}

		if ((!$mcp || !$io->isDryRun()) && (!is_dir($folder) || !is_writable($folder)))
		{
			$io->error(sprintf('The folder %s does not exist or is not writable.', $folder));

			return self::FAILURE;
		}

		if ($zip && !class_exists('ZipArchive'))
		{
			$io->error('The PHP zip extension is needed to create ZIP files.');

			return self::FAILURE;
		}

		$prefix = $db->getPrefix();
		$tables = array();

		foreach ($db->getTableList() as $table)
		{
			if ($prefix === '' || strpos($table, $prefix) === 0)
			{
				$tables[] = $table;
			}
		}

		if ($tableName !== '')
		{
			if (!in_array($tableName, $tables, true))
			{
				$io->error(sprintf('The %s table does not exist in the database.', $tableName));

				return self::NOT_FOUND;
			}

			$tables = array($tableName);
		}

		if ($io->isDryRun())
		{
			foreach ($tables as $table)
			{
				$filename = $folder . '/' . $table . '.xml';
				$io->plan(sprintf('%s %s', is_file($filename) ? 'Overwrite' : 'Write', $filename), array('action' => 'export', 'table' => $table, 'file' => $filename));
			}

			if ($zip)
			{
				$io->plan('Put the files into a ZIP file and delete them', array('action' => 'zip'));
			}

			$io->setData('tables', $tables);

			return self::SUCCESS;
		}

		$files = array();

		foreach ($tables as $table)
		{
			$taskTime = microtime(true);
			$filename = $folder . '/' . $table . '.xml';

			$io->text(sprintf('Processing the %s table', $table));

			$rows    = $this->exportTable($table, $filename);
			$files[] = $filename;

			$io->text(sprintf('Exported %d rows of %s in %s seconds', $rows, $table, round(microtime(true) - $taskTime, 3)));
		}

		if ($zip)
		{
			$zipFile = $folder . '/data_exported_' . date('Y-m-d\TH-i-s') . '.zip';

			if (is_string($zip) && $zip !== '')
			{
				$zipFile = strpbrk($zip, '/\\') === false ? $folder . '/' . $zip : $zip;
				$zipFile = preg_match('/\.zip$/i', $zipFile) ? $zipFile : $zipFile . '.zip';
			}
			$archive = new \ZipArchive;

			if ($archive->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
			{
				$io->error(sprintf('Cannot create the ZIP file %s.', $zipFile));

				return self::FAILURE;
			}

			foreach ($files as $file)
			{
				$archive->addFile($file, basename($file));
			}

			if (!$archive->close())
			{
				$io->error(sprintf('Cannot write the ZIP file %s.', $zipFile));

				return self::FAILURE;
			}

			array_map('unlink', $files);
			$io->setData('zip', $zipFile);
		}
		else
		{
			$io->setData('files', $files);
		}

		$io->setData('tables', $tables);
		$io->success(sprintf('Export completed in %s seconds', round(microtime(true) - $totalTime, 3)));

		return self::SUCCESS;
	}

	/**
	 * Write a table's structure and data to an XML file.
	 *
	 * @param   string  $table     The table name
	 * @param   string  $filename  The file to write
	 *
	 * @return  integer  The number of rows
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	protected function exportTable($table, $filename)
	{
		$db      = Factory::getDbo();
		$generic = $db->getPrefix() === '' ? $table : preg_replace('/^' . preg_quote($db->getPrefix(), '/') . '/', '#__', $table);

		// The core exporter writes the document with the table's structure
		$lines = explode("\n", (string) $db->getExporter()->from($table)->withStructure());

		if (count($lines) < 5)
		{
			throw new \RuntimeException(sprintf('Cannot read the structure of the %s table.', $table));
		}

		$handle = fopen($filename, 'wb');

		if (!$handle)
		{
			throw new \RuntimeException(sprintf('Cannot write the file %s.', $filename));
		}

		$footer    = array_splice($lines, -2);
		$structure = array_pop($lines);

		foreach ($lines as $line)
		{
			fwrite($handle, $line . "\n");
		}

		// Add the exact definition, which a structure rebuilt from the columns can't match (defaults such as CURRENT_TIMESTAMP, table options)
		if (in_array($db->getServerType(), array('mysql'), true))
		{
			$create = $db->setQuery('SHOW CREATE TABLE ' . $db->quoteName($table))->loadRow();

			if ($create && isset($create[1]))
			{
				$statement = preg_replace('/^CREATE TABLE ' . preg_quote($db->quoteName($table), '/') . '/', 'CREATE TABLE ' . $db->quoteName($generic), $create[1]);
				fwrite($handle, '   <create_statement>' . $this->encode($statement) . '</create_statement>' . "\n");
			}
		}

		fwrite($handle, $structure . "\n");

		$count = $this->writeData($handle, $table, $generic);

		foreach ($footer as $line)
		{
			fwrite($handle, $line . "\n");
		}

		fclose($handle);

		return $count;
	}

	/**
	 * Write a table's rows, reading them in batches.
	 *
	 * @param   resource  $handle   The file
	 * @param   string    $table    The table name
	 * @param   string    $generic  The table name with the #__ prefix
	 *
	 * @return  integer  The number of rows
	 *
	 * @since   3.17.0
	 */
	protected function writeData($handle, $table, $generic)
	{
		$db      = Factory::getDbo();
		$columns = $db->getTableColumns($table, false);
		$mysql   = $db->getServerType() === 'mysql';
		$pgsql   = $db->getServerType() === 'postgresql';
		$binary  = array();
		$primary = array();
		$select  = array();

		foreach ($columns as $name => $column)
		{
			$type = strtolower(isset($column->Type) ? $column->Type : (isset($column->type) ? $column->type : ''));

			if (strpos($type, 'blob') !== false || strpos($type, 'binary') !== false || $type === 'bytea')
			{
				$binary[] = $name;
			}

			// MySQL prints FLOAT values with 6 significant digits, which don't convert back to the same value; as a double they do
			if ($mysql && preg_match('/^float\b/', $type))
			{
				$select[] = '(' . $db->quoteName($name) . ' + 0e0) AS ' . $db->quoteName($name);
			}
			else
			{
				$select[] = $db->quoteName($name);
			}
		}

		if ($mysql)
		{
			foreach ($db->getTableKeys($table) as $key)
			{
				if ($key->Key_name === 'PRIMARY')
				{
					$primary[(int) $key->Seq_in_index] = $key->Column_name;
				}
			}

			ksort($primary);
		}
		elseif ($pgsql)
		{
			$primary = $db->setQuery(
				'SELECT a.attname FROM pg_index i JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)'
				. ' WHERE i.indisprimary AND i.indrelid = ' . $db->quote($db->quoteName($table)) . '::regclass ORDER BY a.attnum'
			)->loadColumn();
		}

		$count  = 0;
		$offset = 0;
		$last   = null;
		$first  = $primary ? $columns[reset($primary)] : null;
		$single = count($primary) === 1 && preg_match('/int/i', isset($first->Type) ? $first->Type : (isset($first->type) ? $first->type : ''));

		do
		{
			$query = $db->getQuery(true)
				->select($select)
				->from($db->quoteName($table));

			if ($single)
			{
				// Keyset pagination stays fast on large tables
				$key = reset($primary);
				$query->order($db->quoteName($key));

				if ($last !== null)
				{
					$query->where($db->quoteName($key) . ' > ' . $db->quote($last));
				}

				$rows = $db->setQuery($query, 0, static::BATCH)->loadAssocList();
			}
			elseif ($primary)
			{
				$query->order($db->quoteName(array_values($primary)));
				$rows = $db->setQuery($query, $offset, static::BATCH)->loadAssocList();
			}
			else
			{
				// Without a primary key the order of batches isn't reliable, so read the table at once
				$rows = $db->setQuery($query)->loadAssocList();
			}

			if ($rows && $count === 0)
			{
				fwrite($handle, '  <table_data name="' . $generic . '">' . "\n");
			}

			foreach ($rows as $row)
			{
				fwrite($handle, '   <row>' . "\n");

				foreach ($row as $name => $value)
				{
					if ($pgsql)
					{
						$value = $this->normalisePostgresqlValue($value, in_array($name, $binary, true));
					}

					fwrite($handle, $this->field($name, $value, in_array($name, $binary, true)));
				}

				fwrite($handle, '   </row>' . "\n");
			}

			$count  += count($rows);
			$offset += static::BATCH;

			if ($single && $rows)
			{
				$lastRow = end($rows);
				$last    = $lastRow[reset($primary)];
			}
		}
		while (($single || $primary) && count($rows) === static::BATCH);

		if ($count)
		{
			fwrite($handle, '  </table_data>' . "\n");
		}

		return $count;
	}

	/**
	 * PostgreSQL returns bytea as a stream (PDO) or in its hex notation (pgsql extension), and booleans as PHP booleans (PDO).
	 *
	 * @param   mixed    $value   The value
	 * @param   boolean  $binary  Whether the column holds binary data
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	protected function normalisePostgresqlValue($value, $binary)
	{
		if (is_resource($value))
		{
			return stream_get_contents($value);
		}

		if (is_bool($value))
		{
			return $value ? '1' : '0';
		}

		if ($binary && is_string($value) && strpos($value, '\\x') === 0)
		{
			return (string) hex2bin(substr($value, 2));
		}

		return $value;
	}

	/**
	 * Build a field element.
	 *
	 * @param   string       $name    The column name
	 * @param   string|null  $value   The value
	 * @param   boolean      $binary  Whether the column holds binary data
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function field($name, $value, $binary)
	{
		$name = htmlspecialchars($name, ENT_COMPAT, 'UTF-8');

		if ($value === null)
		{
			return '    <field name="' . $name . '" value_is_null="1"></field>' . "\n";
		}

		$value = (string) $value;

		// XML can't hold binary data, invalid UTF-8 or most control characters, so those values are base64 encoded
		if ($binary || !preg_match('/^[\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]*$/u', $value))
		{
			return '    <field name="' . $name . '" encoding="base64">' . base64_encode($value) . '</field>' . "\n";
		}

		return '    <field name="' . $name . '">' . $this->encode($value) . '</field>' . "\n";
	}

	/**
	 * Escape text for XML content.
	 *
	 * @param   string  $text  The text
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function encode($text)
	{
		// XML parsers turn a raw carriage return into a line feed, so keep it as a character reference
		return str_replace("\r", '&#13;', htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'));
	}
}
