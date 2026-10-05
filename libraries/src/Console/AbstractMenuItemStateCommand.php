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
 * Base class of menu:item:publish, menu:item:unpublish and menu:item:trash.
 *
 * @since  3.17.0
 */
abstract class AbstractMenuItemStateCommand extends AbstractStateCommand
{
	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array('com_menus', 'Item', 'MenusModel');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = 'menu item';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__menu', 'title', 'published');

	/**
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getAssetName($row)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu_types'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote($row->menutype));

		return 'com_menus.menu.' . (int) $db->setQuery($query)->loadResult();
	}
}
