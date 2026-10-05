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
 * Unpublish menu items.
 *
 * @since  3.17.0
 */
class MenuItemUnpublishCommand extends AbstractMenuItemStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'menu:item:unpublish';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Unpublish menu items';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Unpublishes the given menu items, as the toolbar button of the Menus manager does, after checking that the acting user (--as) may. The home page can\'t be unpublished or trashed.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 0;
}
