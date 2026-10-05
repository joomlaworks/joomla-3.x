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
 * Optimises the site's database tables: rebuilds them without their unused space and updates the statistics the database
 * uses to plan queries.
 *
 * @since  3.17.0
 */
class DatabaseOptimizeCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'database:optimize';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Optimise the database tables';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Rebuilds the site\'s tables without their unused space and updates the statistics the database plans queries with: '
		. 'OPTIMIZE TABLE on MySQL/MariaDB (InnoDB tables are rebuilt and analysed), VACUUM ANALYZE on PostgreSQL (VACUUM FULL with --full, '
		. 'which also gives the space back to the system but locks each table meanwhile) and VACUUM and ANALYZE on SQLite (always the whole '
		. 'file). With --analyze it only updates the statistics, which is quick. Shows the size before and after. Rebuilding a large table '
		. 'takes time and can hold up writes to it, so run it when the site is quiet (or offline, with site:down).';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('table', null, self::OPTION_REQUIRED, 'Only this table (e.g. #__session or jos_session); not for SQLite, which is optimised as a whole');
		$this->addOption('analyze', null, self::OPTION_NONE, 'Only update the statistics (quick, no rebuild)');
		$this->addOption('full', null, self::OPTION_NONE, 'PostgreSQL: VACUUM FULL, which gives the space back to the system but locks each table meanwhile');
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
		$io->title('Optimise Database');

		$db      = Factory::getDbo();
		$analyze = (bool) $io->getOption('analyze');
		$table   = (string) $io->getOption('table');
		$sqlite  = $db instanceof \JDatabaseDriverMysqlonsqlite;
		$kind    = $sqlite ? 'sqlite' : $db->getServerType();

		if (!in_array($kind, array('mysql', 'postgresql', 'sqlite'), true))
		{
			$io->error(sprintf('Optimising is supported on MySQL/MariaDB, PostgreSQL and SQLite, not with the "%s" database driver.', $db->getName()));

			return self::FAILURE;
		}

		if ($io->getOption('full') && $kind !== 'postgresql')
		{
			$io->error('--full is for PostgreSQL only.');

			return self::INVALID;
		}

		if ($sqlite && $table !== '')
		{
			$io->error('SQLite is optimised as a whole file; leave out --table.');

			return self::INVALID;
		}

		$prefix = $db->getPrefix();
		$tables = array_values(preg_grep('/^' . preg_quote($prefix, '/') . '/', $db->getTableList()));

		if ($table !== '')
		{
			$table = preg_replace('/^#__/', $prefix, $table);

			if (!in_array($table, $tables, true))
			{
				$io->error(sprintf('The %s table does not exist in the database.', $table));

				return self::NOT_FOUND;
			}

			$tables = array($table);
		}

		$action = $analyze ? 'Update the statistics of' : ($kind === 'postgresql' && $io->getOption('full') ? 'VACUUM FULL' : 'Optimise');
		$before = $this->getSizes($kind, $tables);

		if ($io->isDryRun())
		{
			if ($sqlite)
			{
				$io->plan(sprintf('%s the SQLite database file (%s)', $analyze ? 'Update the statistics of' : 'Rebuild (VACUUM) and analyse', $this->formatSize(array_sum($before))),
					array('action' => $analyze ? 'analyze' : 'vacuum', 'size' => array_sum($before)));

				return self::SUCCESS;
			}

			foreach ($tables as $name)
			{
				$io->plan(sprintf('%s %s (%s)', $action, $name, $this->formatSize(isset($before[$name]) ? $before[$name] : 0)),
					array('action' => $analyze ? 'analyze' : 'optimize', 'table' => $name, 'size' => isset($before[$name]) ? $before[$name] : null));
			}

			return self::SUCCESS;
		}

		@set_time_limit(0);
		$start   = microtime(true);
		$results = array();
		$failed  = 0;

		if ($sqlite)
		{
			$db->optimize($analyze);
		}
		else
		{
			foreach ($tables as $name)
			{
				$message = $this->optimizeTable($db, $kind, $name, $analyze, (bool) $io->getOption('full'));

				if ($message !== true)
				{
					$failed++;
					$io->warning(sprintf('%s: %s', $name, $message));
				}

				$results[$name] = $message === true ? 'ok' : $message;
			}
		}

		$after     = $this->getSizes($kind, $tables);
		$rows      = array();
		$sizeFirst = array_sum($before);
		$sizeAfter = array_sum($after);

		if (!$sqlite)
		{
			foreach ($tables as $name)
			{
				$rows[] = array(
					'table'  => $name,
					'before' => isset($before[$name]) ? $before[$name] : null,
					'after'  => isset($after[$name]) ? $after[$name] : null,
					'result' => $results[$name],
				);
			}

			$io->table(array('table' => 'Table', 'before' => 'Size before (bytes)', 'after' => 'Size after (bytes)', 'result' => 'Result'), $rows, 'tables');
		}

		$io->setData('sizeBefore', $sizeFirst);
		$io->setData('sizeAfter', $sizeAfter);
		$io->setData('seconds', round(microtime(true) - $start, 2));

		$summary = sprintf('%s: %s before, %s after, in %s seconds.', $sqlite ? 'Database file' : count($tables) . ' table(s)', $this->formatSize($sizeFirst),
			$this->formatSize($sizeAfter), round(microtime(true) - $start, 2));

		if ($failed)
		{
			$io->error(sprintf('%d table(s) could not be optimised. %s', $failed, $summary));

			return self::FAILURE;
		}

		$io->success($summary);

		return self::SUCCESS;
	}

	/**
	 * Optimise one table on MySQL/MariaDB or PostgreSQL.
	 *
	 * @param   \JDatabaseDriver  $db       The database
	 * @param   string            $kind     mysql or postgresql
	 * @param   string            $table    The table
	 * @param   boolean           $analyze  Only update the statistics
	 * @param   boolean           $full     PostgreSQL VACUUM FULL
	 *
	 * @return  true|string  True, or what went wrong
	 *
	 * @since   3.17.0
	 */
	private function optimizeTable($db, $kind, $table, $analyze, $full)
	{
		try
		{
			if ($kind === 'postgresql')
			{
				// VACUUM can't run inside a transaction; the drivers run each statement on its own
				$db->setQuery(($analyze ? 'ANALYZE ' : 'VACUUM ' . ($full ? 'FULL ' : '') . 'ANALYZE ') . $db->quoteName($table))->execute();

				return true;
			}

			// MySQL reports per table (InnoDB: a note that it recreates and analyses instead, then OK); an "error" row means it failed
			$rows = $db->setQuery(($analyze ? 'ANALYZE' : 'OPTIMIZE') . ' TABLE ' . $db->quoteName($table))->loadObjectList();

			foreach ((array) $rows as $row)
			{
				$row = array_change_key_case((array) $row, CASE_LOWER);

				if (isset($row['msg_type']) && strtolower($row['msg_type']) === 'error')
				{
					return (string) $row['msg_text'];
				}
			}

			return true;
		}
		catch (\RuntimeException $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * The size of the tables (MySQL: data, indexes and free space; PostgreSQL: with indexes and TOAST), or of the SQLite file.
	 *
	 * @param   string    $kind    mysql, postgresql or sqlite
	 * @param   string[]  $tables  The tables
	 *
	 * @return  integer[]  Table (or "file") => bytes
	 *
	 * @since   3.17.0
	 */
	private function getSizes($kind, array $tables)
	{
		$db    = Factory::getDbo();
		$sizes = array();

		try
		{
			if ($kind === 'sqlite')
			{
				$path = $db->getDatabasePath();
				clearstatcache();

				return array('file' => (int) @filesize($path) + (int) @filesize($path . '-wal'));
			}

			if ($kind === 'postgresql')
			{
				foreach ($tables as $table)
				{
					$sizes[$table] = (int) $db->setQuery('SELECT pg_total_relation_size(' . $db->quote($table) . '::regclass)')->loadResult();
				}

				return $sizes;
			}

			// InnoDB's statistics are estimates, refreshed by OPTIMIZE/ANALYZE
			$query = $db->getQuery(true)
				->select($db->quoteName('TABLE_NAME', 'name'))
				->select('(' . $db->quoteName('DATA_LENGTH') . ' + ' . $db->quoteName('INDEX_LENGTH') . ' + ' . $db->quoteName('DATA_FREE') . ') AS ' . $db->quoteName('size'))
				->from($db->quoteName('information_schema.TABLES'))
				->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()');

			foreach ($db->setQuery($query)->loadObjectList() as $row)
			{
				if (in_array($row->name, $tables, true))
				{
					$sizes[$row->name] = (int) $row->size;
				}
			}
		}
		catch (\RuntimeException $e)
		{
			// Sizes are informative only
		}

		return $sizes;
	}

	/**
	 * @param   integer  $bytes  A size
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function formatSize($bytes)
	{
		$units = array('bytes', 'KB', 'MB', 'GB', 'TB');
		$i     = 0;

		while ($bytes >= 1024 && $i < count($units) - 1)
		{
			$bytes /= 1024;
			$i++;
		}

		return $i ? sprintf('%.1f %s', $bytes, $units[$i]) : $bytes . ' bytes';
	}
}
