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
use Joomla\CMS\Session\MetadataManager;

/**
 * Removes expired rows from the session metadata table.
 *
 * @since  3.17.0
 */
class SessionMetadataGcCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'session:metadata:gc';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Perform session metadata garbage collection';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Removes the rows of expired sessions from the #__session table, which keeps the session metadata (e.g. for "Who\'s Online") whichever session handler is used.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $logged = false;

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Running Session Metadata Garbage Collection');

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Remove the session metadata older than %d minutes', Factory::getSession()->getExpire() / 60),
				array('action' => 'gc', 'olderThanSeconds' => (int) Factory::getSession()->getExpire()));

			return self::SUCCESS;
		}

		$manager = new MetadataManager(Factory::getApplication(), Factory::getDbo());
		$manager->deletePriorTo(time() - Factory::getSession()->getExpire());

		$io->success('Metadata garbage collection completed.');

		return self::SUCCESS;
	}
}
