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
 * Trash menu items.
 *
 * @since  3.17.0
 */
class MenuItemTrashCommand extends AbstractMenuItemStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:trash';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Trash menu items';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Trashes the given menu items, as the toolbar button of the Menus manager does, after checking that the acting user (--as) may. The home page can\'t be unpublished or trashed.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = -2;
}
