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
 * Shows a menu item.
 *
 * @since  3.17.0
 */
class MenuItemGetCommand extends AbstractMenuItemCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a menu item';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows a site menu item: its fields, link and options.';

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
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The menu item ID');
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
		$row = $this->findMenuItem($io, $io->getArgument('id'));

		if (!$row)
		{
			return self::NOT_FOUND;
		}

		$menuItem = $this->describeMenuItem($row->id);
		$io->title('Menu Item ' . $row->id);
		$list           = $menuItem;
		$list['params'] = json_encode($menuItem['params'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$io->definitionList($list);
		$io->setData('params', (object) $menuItem['params']);

		return self::SUCCESS;
	}
}
