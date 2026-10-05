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
 * Lists the log files which log:tail can read.
 *
 * @since  3.17.0
 */
class LogListCommand extends AbstractLogCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'log:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the log files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the log files in the logs folder of Global Configuration (e.g. error.php, joomla_update.php, everything.php when '
		. 'Debug System logs everything), with their size and last change, and PHP\'s error log as "php-error-log" when this command line\'s '
		. 'PHP writes one to a file. Read one with log:tail. Changes made by users are in the User Actions Log instead (actionlog:list).';

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Log Files');

		$rows = array();

		foreach ($this->getLogFiles() as $name => $path)
		{
			$rows[] = array(
				'name'     => $name,
				'size'     => (int) filesize($path),
				'modified' => gmdate('Y-m-d H:i:s', (int) filemtime($path)) . ' UTC',
				'path'     => $path,
			);
		}

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('There are no log files.');

			return self::SUCCESS;
		}

		$io->table(array('name' => 'Name', 'size' => 'Size (bytes)', 'modified' => 'Last changed', 'path' => 'Path'), $rows);

		return self::SUCCESS;
	}
}
