<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Shows the last entries of a log file, optionally filtered.
 *
 * @since  3.17.0
 */
class LogTailCommand extends AbstractLogCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'log:tail';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show the last entries of a log file';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows the last entries of a log file listed by log:list (by its name, e.g. error.php), newest last. Joomla\'s log files are '
		. 'split into their fields (date and time, priority, IP address, category, message); other files, such as PHP\'s error log, give one '
		. 'entry per line. --priority, --category, --grep and --since narrow the entries down before --lines picks the last ones.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('file', self::ARGUMENT_REQUIRED, 'The log file, as log:list names it (e.g. error.php)');
		$this->addOption('lines', 'l', self::OPTION_REQUIRED, 'How many entries to show', 50);
		$this->addOption('priority', null, self::OPTION_REQUIRED, 'Only these priorities, separated by commas (e.g. error,warning)');
		$this->addOption('category', null, self::OPTION_REQUIRED, 'Only this category (e.g. update, jerror)');
		$this->addOption('grep', null, self::OPTION_REQUIRED, 'Only entries containing this text (not case-sensitive)');
		$this->addOption('since', null, self::OPTION_REQUIRED, 'Only entries from this date and time on (e.g. 2026-10-01 or "-2 hours")');
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
		$name  = (string) $io->getArgument('file');
		$files = $this->getLogFiles();

		if (!isset($files[$name]))
		{
			$io->error(sprintf('There is no log file named "%s". Run log:list to see them.', $name));

			return self::NOT_FOUND;
		}

		$lines = (int) $io->getOption('lines');

		if ($lines < 1)
		{
			$io->error('--lines must be a positive number.');

			return self::INVALID;
		}

		$since = (string) $io->getOption('since');

		if ($since !== '' && ($since = strtotime($since)) === false)
		{
			$io->error('--since must be a date and time, e.g. 2026-10-01 or "-2 hours".');

			return self::INVALID;
		}

		$priorities = array_filter(array_map('strtoupper', array_map('trim', explode(',', (string) $io->getOption('priority')))), 'strlen');
		$category   = strtolower((string) $io->getOption('category'));
		$grep       = (string) $io->getOption('grep');
		$entries    = array();
		$total      = 0;

		foreach ($this->readEntries($files[$name]) as $entry)
		{
			$total++;

			if ($priorities && (!isset($entry['priority']) || !in_array(strtoupper($entry['priority']), $priorities, true)))
			{
				continue;
			}

			if ($category !== '' && (!isset($entry['category']) || strtolower($entry['category']) !== $category))
			{
				continue;
			}

			if ($grep !== '' && stripos(implode(' ', $entry), $grep) === false)
			{
				continue;
			}

			if ($since !== '' && (!isset($entry['datetime']) || ($time = strtotime($entry['datetime'])) === false || $time < $since))
			{
				continue;
			}

			$entries[] = $entry;
		}

		$entries = array_slice($entries, -$lines);

		$io->title('Log: ' . $name);
		$io->setData('file', $files[$name]);
		$io->setData('matching', count($entries));
		$io->setData('total', $total);

		if (!$entries)
		{
			$io->setData('items', array());
			$io->text('No matching entries.');

			return self::SUCCESS;
		}

		$columns = array();

		foreach (array_keys($entries[count($entries) - 1]) as $field)
		{
			$columns[$field] = ucfirst($field);
		}

		$io->table($columns, $entries);

		return self::SUCCESS;
	}
}
