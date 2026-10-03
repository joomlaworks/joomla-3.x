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
 * Puts the site offline.
 *
 * @since  3.17.0
 */
class SiteDownCommand extends ConfigSetCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'site:down';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Put the site into offline mode';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Sets "Site Offline" in the global configuration to Yes.';

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
		$io->title('Site ' . ucfirst('offline'));

		if (!$this->saveConfiguration($io, array('offline' => '1')))
		{
			return self::FAILURE;
		}

		$io->success('Website is now offline.');

		return self::SUCCESS;
	}
}
