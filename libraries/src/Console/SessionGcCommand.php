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
 * Runs the garbage collection of the session handler.
 *
 * @since  3.17.0
 */
class SessionGcCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'session:gc';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Perform session garbage collection';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Removes expired sessions from the configured session handler. '
		. 'The site and the administrator share the same session storage and lifetime here, so --application only exists for compatibility with Joomla 4 and later.';

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
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('application', null, self::OPTION_REQUIRED, 'The application to run garbage collection for (site or administrator)', 'site');
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
		$application = (string) $io->getOption('application');

		if (!in_array($application, array('site', 'administrator'), true))
		{
			$io->error('The --application option must be "site" or "administrator".');

			return self::INVALID;
		}

		$io->title('Running Session Garbage Collection');

		// The command line's own session is in memory only, so clean the configured session storage directly
		$config  = Factory::getConfig();
		$storage = \JSessionStorage::getInstance($config->get('session_handler', 'none'));
		$expire  = (int) $config->get('lifetime') ? (int) $config->get('lifetime') * 60 : 900;

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Remove the sessions older than %d minutes from the "%s" session storage', $expire / 60, $config->get('session_handler', 'none')),
				array('action' => 'gc', 'handler' => $config->get('session_handler', 'none'), 'olderThanSeconds' => $expire));

			return self::SUCCESS;
		}

		if ($storage->gc($expire) === false)
		{
			$io->error('Garbage collection was not completed. Either the operation failed or it is not supported on your platform.');

			return self::FAILURE;
		}

		$io->success('Garbage collection completed.');

		return self::SUCCESS;
	}
}
