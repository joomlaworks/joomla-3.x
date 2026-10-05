<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * Base class of the menu item commands: menus, loading menu items, what a menu item links to, and the fields menu:item:create
 * and menu:item:update share. Only the site's menus.
 *
 * @since  3.17.0
 */
abstract class AbstractMenuItemCommand extends AbstractContentCommand
{
	/**
	 * Add the options of the menu item's fields.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addFieldOptions()
	{
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The title');
		$this->addOption('alias', null, self::OPTION_REQUIRED, 'The URL alias (made from the title when empty)');
		$this->addOption('article', null, self::OPTION_REQUIRED, 'Link to this article (ID): a "Single Article" menu item');
		$this->addOption('category-blog', null, self::OPTION_REQUIRED, 'Link to this category (ID or path) as a "Category Blog"');
		$this->addOption('category-list', null, self::OPTION_REQUIRED, 'Link to this category (ID or path) as a "Category List"');
		$this->addOption('featured', null, self::OPTION_NONE, 'Link to the "Featured Articles"');
		$this->addOption('link', null, self::OPTION_REQUIRED, 'Any other link: index.php?option=... for a component page, or an external URL');
		$this->addOption('alias-of', null, self::OPTION_REQUIRED, 'Make it a "Menu Item Alias" of this menu item (ID)');
		$this->addOption('heading', null, self::OPTION_NONE, 'Make it a "Menu Heading" (no link)');
		$this->addOption('separator', null, self::OPTION_NONE, 'Make it a "Separator" (no link)');
		$this->addOption('parent', null, self::OPTION_REQUIRED, 'The parent menu item (ID; "root" for the top level)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished or trashed');
		$this->addOption('access', null, self::OPTION_REQUIRED, 'The access level: ID or title');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'The language tag (e.g. en-GB), or * for all');
		$this->addOption('new-window', null, self::OPTION_REQUIRED, 'Open the link in a new window: yes or no');
		$this->addOption('params', null, self::OPTION_REQUIRED, 'Menu item options as a JSON object, merged into the current ones');
		$this->addOption('note', null, self::OPTION_REQUIRED, 'An administrator note');
	}

	/**
	 * Find a site menu by its unique name (menutype) or title.
	 *
	 * @param   CommandIO  $io     The input values and the output
	 * @param   string     $value  The menu
	 *
	 * @return  object|false  The menu's id, menutype and title
	 *
	 * @since   3.17.0
	 */
	protected function findMenu(CommandIO $io, $value)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'menutype', 'title')))
			->from($db->quoteName('#__menu_types'))
			->where($db->quoteName('client_id') . ' = 0')
			->where('(' . $db->quoteName('menutype') . ' = ' . $db->quote($value) . ' OR LOWER(' . $db->quoteName('title') . ') = '
				. $db->quote(strtolower($value)) . ')');
		$menu = $db->setQuery($query)->loadObject();

		if (!$menu)
		{
			$io->error(sprintf('There is no site menu "%s". menu:list shows them.', $value));

			return false;
		}

		return $menu;
	}

	/**
	 * The asset holding the permissions of a menu's items.
	 *
	 * @param   string  $menutype  The menu
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getMenuAsset($menutype)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu_types'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype));

		return 'com_menus.menu.' . (int) $db->setQuery($query)->loadResult();
	}

	/**
	 * Work out the type and link the options ask for.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  array|null|false  array(type, link, alias target), null when no link option is given, false on error
	 *
	 * @since   3.17.0
	 */
	protected function getTarget(CommandIO $io)
	{
		$given = array_filter(array('article', 'category-blog', 'category-list', 'featured', 'link', 'alias-of', 'heading', 'separator'), function ($option) use ($io)
		{
			$value = $io->getOption($option);

			return $value !== null && $value !== false;
		});

		if (count($given) > 1)
		{
			$io->error('Give only one of --article, --category-blog, --category-list, --featured, --link, --alias-of, --heading and --separator.');

			return false;
		}

		if (!$given)
		{
			return null;
		}

		$option = reset($given);
		$value  = (string) $io->getOption($option);
		$db     = Factory::getDbo();

		switch ($option)
		{
			case 'article':
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__content'))
					->where($db->quoteName('id') . ' = ' . (int) $value);

				if (!ctype_digit($value) || !$db->setQuery($query)->loadResult())
				{
					$io->error(sprintf('There is no article with ID %s.', $value));

					return false;
				}

				return array('component', 'index.php?option=com_content&view=article&id=' . (int) $value, 0);

			case 'category-blog':
			case 'category-list':
				if (($catid = $this->findCategory($io, $value)) === false)
				{
					return false;
				}

				return array('component', 'index.php?option=com_content&view=category' . ($option === 'category-blog' ? '&layout=blog' : '') . '&id=' . $catid, 0);

			case 'featured':
				return array('component', 'index.php?option=com_content&view=featured', 0);

			case 'alias-of':
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__menu'))
					->where($db->quoteName('id') . ' = ' . (int) $value)
					->where($db->quoteName('client_id') . ' = 0')
					->where($db->quoteName('id') . ' > 1');

				if (!ctype_digit($value) || !$db->setQuery($query)->loadResult())
				{
					$io->error(sprintf('There is no site menu item with ID %s.', $value));

					return false;
				}

				return array('alias', 'index.php?Itemid=', (int) $value);

			case 'heading':
			case 'separator':
				return array($option, '', 0);
		}

		// --link: a component page or an external URL
		if (strpos($value, 'index.php?') === 0)
		{
			parse_str((string) parse_url($value, PHP_URL_QUERY), $args);

			if (empty($args['option']) || !ComponentHelper::isEnabled($args['option']))
			{
				$io->error('A component link must name an enabled component, e.g. index.php?option=com_contact&view=contact&id=1.');

				return false;
			}

			return array('component', $value, 0);
		}

		if (!preg_match('#^(https?://|mailto:|tel:|/|\\#)#i', $value))
		{
			$io->error('--link must be index.php?option=... or a URL (https://..., mailto:, tel:, /path or #anchor).');

			return false;
		}

		return array('url', $value, 0);
	}

	/**
	 * Apply the given options (but the link, see getTarget()) to the menu item's data.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   array      $data  The menu item's data, changed in place
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function applyFieldOptions(CommandIO $io, array &$data)
	{
		foreach (array('title', 'alias', 'note') as $field)
		{
			if ($io->getOption($field) !== null)
			{
				$data[$field] = (string) $io->getOption($field);
			}
		}

		$value = $io->getOption('parent');

		if ($value !== null)
		{
			if (strtolower($value) === 'root')
			{
				$data['parent_id'] = 1;
			}
			else
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select($db->quoteName('menutype'))
					->from($db->quoteName('#__menu'))
					->where($db->quoteName('id') . ' = ' . (int) $value)
					->where($db->quoteName('id') . ' > 1');
				$menutype = ctype_digit((string) $value) ? $db->setQuery($query)->loadResult() : null;

				if ($menutype !== $data['menutype'])
				{
					$io->error(sprintf('There is no menu item %s in the menu "%s" to be the parent.', $value, $data['menutype']));

					return false;
				}

				if (!empty($data['id']) && (int) $value === (int) $data['id'])
				{
					$io->error('A menu item can\'t be its own parent.');

					return false;
				}

				$data['parent_id'] = (int) $value;
			}
		}

		$value = $io->getOption('state');

		if ($value !== null && (($data['published'] = $this->parseState($value)) === null || $data['published'] === 2))
		{
			$io->error('--state must be published, unpublished or trashed.');

			return false;
		}

		$value = $io->getOption('new-window');

		if ($value !== null)
		{
			if (!in_array(strtolower($value), array('yes', 'no', '1', '0'), true))
			{
				$io->error('--new-window must be yes or no.');

				return false;
			}

			$data['browserNav'] = in_array(strtolower($value), array('yes', '1'), true) ? 1 : 0;
		}

		$value = $io->getOption('params');

		if ($value !== null)
		{
			$params = json_decode($value, true);

			if (!is_array($params))
			{
				$io->error('--params must be a JSON object, e.g. {"show_title":"0"}.');

				return false;
			}

			$data['params'] = array_merge((array) $data['params'], $params);
		}

		$value = $io->getOption('access');

		if ($value !== null && ($data['access'] = $this->findAccessLevel($io, $value)) === false)
		{
			return false;
		}

		$value = $io->getOption('language');

		if ($value !== null && ($data['language'] = $this->findLanguage($io, $value)) === false)
		{
			return false;
		}

		return true;
	}

	/**
	 * Load the menu item model, primed for an item as the administrator's "Menu Item Type" choice does.
	 *
	 * @param   integer  $id        The menu item ID (0 for a new one)
	 * @param   string   $menutype  The menu
	 * @param   integer  $parentId  The parent menu item
	 * @param   string   $type      component, url, alias, heading or separator; null keeps the item's
	 * @param   string   $link      The link; null keeps the item's
	 *
	 * @return  \MenusModelItem
	 *
	 * @since   3.17.0
	 */
	protected function getMenuItemModel($id, $menutype, $parentId, $type = null, $link = null)
	{
		$model = $this->getModel('com_menus', 'Item', 'MenusModel');
		$model->setState('item.id', (int) $id);
		$model->setState('item.menutype', $menutype);
		$model->setState('item.parent_id', (int) $parentId);
		$model->setState('item.client_id', 0);
		$model->setState('item.type', $type);
		$model->setState('item.link', $link);

		return $model;
	}

	/**
	 * Load a menu item as the menu item form has it.
	 *
	 * @param   CommandIO        $io     The input values and the output
	 * @param   \MenusModelItem  $model  The model
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	protected function loadMenuItem(CommandIO $io, $model)
	{
		$item = $model->getItem();

		if (!$item)
		{
			$io->error($model->getError() ?: 'The menu item can\'t be loaded.');

			return false;
		}

		$data                 = get_object_vars($item);
		$data['menuordering'] = 0;

		// The request variables are part of the link; the form keeps them apart from the options
		if (isset($data['request']) && is_array($data['request']))
		{
			$data['params'] = array_diff_key((array) $data['params'], $data['request']);
		}

		return $data;
	}

	/**
	 * Find an existing site menu item.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 * @param   string     $id  The menu item ID
	 *
	 * @return  object|false  Its id, menutype, parent_id, title, published and checked_out
	 *
	 * @since   3.17.0
	 */
	protected function findMenuItem(CommandIO $io, $id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'menutype', 'parent_id', 'title', 'published', 'checked_out')))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('id') . ' = ' . (int) $id)
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('id') . ' > 1');
		$row = ctype_digit((string) $id) ? $db->setQuery($query)->loadObject() : null;

		if (!$row)
		{
			$io->error(sprintf('There is no site menu item with ID %s.', $id));

			return false;
		}

		return $row;
	}

	/**
	 * The menu item as shown by the commands.
	 *
	 * @param   integer  $id  The menu item ID
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function describeMenuItem($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('id') . ' = ' . (int) $id);
		$row = $db->setQuery($query)->loadObject();

		return array(
			'id'        => (int) $row->id,
			'title'     => $row->title,
			'alias'     => $row->alias,
			'path'      => $row->path,
			'menu'      => $row->menutype,
			'type'      => $row->type,
			'link'      => $row->link,
			'parentId'  => (int) $row->parent_id,
			'level'     => (int) $row->level,
			'state'     => $this->stateName($row->published),
			'home'      => (bool) $row->home,
			'access'    => (int) $row->access,
			'language'  => $row->language,
			'newWindow' => (bool) $row->browserNav,
			'note'      => $row->note,
			'params'    => (array) json_decode((string) $row->params, true),
		);
	}
}
