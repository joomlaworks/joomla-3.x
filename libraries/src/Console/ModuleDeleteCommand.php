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
 * Deletes trashed modules.
 *
 * @since  3.17.0
 */
class ModuleDeleteCommand extends AbstractDeleteCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete trashed modules for good';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes the given modules for good, as Empty Trash in the Modules manager does. Only trashed modules are deleted (module:trash first).';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array('com_modules', 'Module', 'ModulesModel');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = 'module';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__modules', 'title', 'published');

	/**
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getAssetName($row)
	{
		return 'com_modules.module.' . (int) $row->id;
	}
}
