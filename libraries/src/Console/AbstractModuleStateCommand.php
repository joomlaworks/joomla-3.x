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
 * Base class of module:publish, module:unpublish and module:trash.
 *
 * @since  3.17.0
 */
abstract class AbstractModuleStateCommand extends AbstractStateCommand
{
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
