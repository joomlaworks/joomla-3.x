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
 * Lists the site's menus.
 *
 * @since  3.17.0
 */
class MenuListCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the site\'s menus';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the site\'s menus, with how many items each has (not counting trashed ones). menu:item:list shows their items.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$db    = Factory::getDbo();
		$count = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__menu', 'm'))
			->where($db->quoteName('m.menutype') . ' = ' . $db->quoteName('t.menutype'))
			->where($db->quoteName('m.published') . ' <> -2');
		$query = $db->getQuery(true)
			->select($db->quoteName(array('t.id', 't.menutype', 't.title', 't.description')))
			->select('(' . $count . ') AS ' . $db->quoteName('items'))
			->from($db->quoteName('#__menu_types', 't'))
			->where($db->quoteName('t.client_id') . ' = 0')
			->order($db->quoteName('t.title'));

		$rows = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$rows[] = array('id' => (int) $row->id, 'menutype' => $row->menutype, 'title' => $row->title, 'description' => $row->description, 'items' => (int) $row->items);
		}

		$io->title('Menus');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('There are no site menus.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'menutype' => 'Unique name', 'title' => 'Title', 'items' => 'Items', 'description' => 'Description'), $rows);

		return self::SUCCESS;
	}
}
