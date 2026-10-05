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
 * Lists menu items.
 *
 * @since  3.17.0
 */
class MenuItemListCommand extends AbstractMenuItemCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List menu items';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the items of the site\'s menus (or of one with --menu) in tree order, without the trashed ones unless --state asks '
		. 'for them. The home page is marked.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('menu', null, self::OPTION_REQUIRED, 'Only this menu (its unique name or title)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, trashed or all (default: all but trashed)');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'Only this language (e.g. en-GB, or *)');
		$this->addOption('search', null, self::OPTION_REQUIRED, 'Only titles containing this text');
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
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'menutype', 'title', 'alias', 'path', 'level', 'type', 'link', 'published', 'home', 'access', 'language', 'parent_id')))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('id') . ' > 1')
			->order($db->quoteName('menutype') . ', ' . $db->quoteName('lft'));

		if ($io->getOption('menu') !== null)
		{
			if (!($menu = $this->findMenu($io, (string) $io->getOption('menu'))))
			{
				return self::NOT_FOUND;
			}

			$query->where($db->quoteName('menutype') . ' = ' . $db->quote($menu->menutype));
		}

		$state = strtolower((string) $io->getOption('state'));

		if ($state === '')
		{
			$query->where($db->quoteName('published') . ' <> -2');
		}
		elseif ($state !== 'all')
		{
			if (($value = $this->parseState($state)) === null)
			{
				$io->error('--state must be published, unpublished, trashed or all.');

				return self::INVALID;
			}

			$query->where($db->quoteName('published') . ' = ' . $value);
		}

		if ($io->getOption('language') !== null)
		{
			$query->where($db->quoteName('language') . ' = ' . $db->quote((string) $io->getOption('language')));
		}

		if ((string) $io->getOption('search') !== '')
		{
			$query->where($db->quoteName('title') . ' LIKE ' . $db->quote('%' . $db->escape((string) $io->getOption('search'), true) . '%', false));
		}

		$rows = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$rows[] = array(
				'id'       => (int) $row->id,
				'menu'     => $row->menutype,
				'title'    => $row->title,
				'path'     => $row->path,
				'level'    => (int) $row->level,
				'type'     => $row->type,
				'link'     => $row->link,
				'state'    => $this->stateName($row->published),
				'home'     => (bool) $row->home,
				'access'   => (int) $row->access,
				'language' => $row->language,
				'parentId' => (int) $row->parent_id,
			);
		}

		$io->title('Menu Items');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching menu items.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'menu' => 'Menu', 'title' => 'Title', 'path' => 'Path', 'type' => 'Type', 'link' => 'Link', 'state' => 'State',
			'home' => 'Home', 'language' => 'Language', 'parentId' => 'Parent ID', 'level' => 'Level', 'access' => 'Access'), $rows);

		return self::SUCCESS;
	}
}
