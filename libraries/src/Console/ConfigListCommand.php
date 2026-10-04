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
 * Lists the global configuration options: config:get without an option name.
 *
 * @since  3.17.0
 */
class ConfigListCommand extends ConfigGetCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'config:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List all configuration options and their values';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->help = 'Lists every configuration option with its value, like config:get without an option name. '
			. 'With --group, only the options of a group: ' . implode(', ', array_keys(self::GROUPS)) . '. '
			. 'Passwords and keys are masked unless --show-secrets is given.';

		$this->addOption('group', 'g', self::OPTION_REQUIRED, 'Name of the option group');
		$this->addOption('show-secrets', null, self::OPTION_NONE, 'Show passwords and keys instead of masking them');
	}
}
