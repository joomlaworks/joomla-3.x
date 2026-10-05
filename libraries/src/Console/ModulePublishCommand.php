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
 * Publish modules.
 *
 * @since  3.17.0
 */
class ModulePublishCommand extends AbstractModuleStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:publish';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Publish modules';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Publishes the given modules, as the toolbar button of the Modules manager does, after checking that the acting user (--as) may.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 1;
}
