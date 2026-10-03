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
 * Enables an extension.
 *
 * @since  3.17.0
 */
class ExtensionEnableCommand extends ExtensionDisableCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:enable';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Enable an extension';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Enables an extension, like Extensions: Manage.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 1;
}
