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
 * Puts the site online.
 *
 * @since  3.17.0
 */
class SiteUpCommand extends ConfigSetCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'site:up';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Put the site into online mode';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Sets "Site Offline" in the global configuration to No.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
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
		$io->title('Site ' . ucfirst('online'));

		if (!$this->saveConfiguration($io, array('offline' => '0')))
		{
			return self::FAILURE;
		}

		if (!$io->isDryRun())
		{
			$io->success('Website is now online.');
		}

		return self::SUCCESS;
	}
}
