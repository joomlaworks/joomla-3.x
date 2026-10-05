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
 * Changes a menu item.
 *
 * @since  3.17.0
 */
class MenuItemUpdateCommand extends AbstractMenuItemCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change a menu item';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes the given fields of a menu item and leaves the others as they are; --article, --link etc. change what it links to, '
		. 'and --params is merged into its options. A menu item someone has open for editing is refused unless --force is given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The menu item ID');
		$this->addFieldOptions();
		$this->addOption('force', null, self::OPTION_NONE, 'Change the menu item even when someone has it open for editing');
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
		$io->title('Change Menu Item');

		$row = $this->findMenuItem($io, $io->getArgument('id'));

		if (!$row)
		{
			return self::NOT_FOUND;
		}

		if (!$this->checkNotCheckedOut($io, $row))
		{
			return self::REFUSED;
		}

		$target = $this->getTarget($io);

		if ($target === false)
		{
			return self::INVALID;
		}

		$asset = $this->getMenuAsset($row->menutype);

		if (!$this->user->authorise('core.edit', $asset))
		{
			$io->error(sprintf('The user "%s" may not edit the items of this menu.', $this->user->username));

			return self::REFUSED;
		}

		$model   = $this->getMenuItemModel($row->id, $row->menutype, $row->parent_id, $target ? $target[0] : null, $target ? $target[1] : null);
		$current = $this->loadMenuItem($io, $this->getMenuItemModel($row->id, $row->menutype, $row->parent_id));
		$data    = $this->loadMenuItem($io, $model);

		if ($current === false || $data === false)
		{
			return self::FAILURE;
		}

		if ($target)
		{
			$data['type'] = $target[0];
			$data['link'] = $target[1];

			if ($target[0] === 'alias')
			{
				$data['params']['aliasoptions'] = $target[2];
			}
		}

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		if ((int) $data['published'] !== (int) $current['published'] && !$this->user->authorise('core.edit.state', $asset))
		{
			$io->error(sprintf('The user "%s" may not change the state of this menu item.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$changes = $data;
			$changes['params'] = json_encode($data['params']);
			$current['params'] = json_encode($current['params']);
			$this->planChanges($io, sprintf('Change the menu item %d "%s"', $row->id, $row->title),
				array_intersect_key($changes, array_flip(array('title', 'alias', 'type', 'link', 'parent_id', 'published', 'access', 'language', 'note', 'browserNav', 'params'))),
				$current);

			return self::SUCCESS;
		}

		$menuItem = $this->describeMenuItem($id);

		foreach ($menuItem as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Menu item %d "%s" saved.', $id, $menuItem['title']));

		return self::SUCCESS;
	}
}
