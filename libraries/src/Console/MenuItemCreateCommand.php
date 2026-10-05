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
 * Creates a menu item.
 *
 * @since  3.17.0
 */
class MenuItemCreateCommand extends AbstractMenuItemCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:create';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Create a menu item';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates a menu item in a site menu, saved as the Menus manager saves it. It\'s unpublished unless --state=published is '
		. 'given. --menu and --title are required, and what it links to: --article, --category-blog, --category-list, --featured, --link (any '
		. 'component page or an external URL), --alias-of, --heading or --separator.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('menu', null, self::OPTION_REQUIRED, 'The menu (its unique name, e.g. mainmenu, or its title)');
		$this->addFieldOptions();
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
		$io->title('Create Menu Item');

		if ((string) $io->getOption('menu') === '' || (string) $io->getOption('title') === '')
		{
			$io->error('--menu and --title are required.');

			return self::INVALID;
		}

		$menu = $this->findMenu($io, (string) $io->getOption('menu'));

		if (!$menu)
		{
			return self::NOT_FOUND;
		}

		$target = $this->getTarget($io);

		if ($target === false)
		{
			return self::INVALID;
		}

		if ($target === null)
		{
			$io->error('Give what the menu item links to: --article, --category-blog, --category-list, --featured, --link, --alias-of, --heading or --separator.');

			return self::INVALID;
		}

		if (!$this->user->authorise('core.create', 'com_menus.menu.' . (int) $menu->id))
		{
			$io->error(sprintf('The user "%s" may not create items in this menu.', $this->user->username));

			return self::REFUSED;
		}

		list($type, $link, $aliasOf) = $target;

		$data = array('menutype' => $menu->menutype, 'parent_id' => 1);

		// The parent is needed before the model is primed
		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		$model = $this->getMenuItemModel(0, $menu->menutype, $data['parent_id'], $type, $link);
		$item  = $this->loadMenuItem($io, $model);

		if ($item === false)
		{
			return self::FAILURE;
		}

		$data = array_merge($item, array(
			'id'        => 0,
			'menutype'  => $menu->menutype,
			'type'      => $type,
			'link'      => $link,
			'published' => 0,
			'access'    => (int) Factory::getConfig()->get('access', 1),
			'language'  => '*',
			'home'      => 0,
		));

		if ($type === 'alias')
		{
			$data['params']['aliasoptions'] = $aliasOf;
		}

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		if ((int) $data['published'] !== 0 && !$this->user->authorise('core.edit.state', 'com_menus.menu.' . (int) $menu->id))
		{
			$io->error(sprintf('The user "%s" may not publish items in this menu; create it unpublished.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$this->planChanges($io, sprintf('Create the menu item "%s" in the menu "%s", linking to %s', $data['title'], $menu->title, $link ?: $type),
				array_intersect_key($data, array_flip(array('title', 'alias', 'type', 'link', 'parent_id', 'published', 'access', 'language', 'note'))));

			return self::SUCCESS;
		}

		$menuItem = $this->describeMenuItem($id);

		foreach ($menuItem as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Menu item "%s" created with ID %d (%s).', $menuItem['title'], $id, $menuItem['state']));

		return self::SUCCESS;
	}
}
