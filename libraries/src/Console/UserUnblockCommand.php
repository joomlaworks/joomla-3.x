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
 * Unblocks a user, so they can log in again.
 *
 * @since  3.17.0
 */
class UserUnblockCommand extends UserBlockCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:unblock';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Unblock a user';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Unblocks a user, like Users: Manage > Unblock, so they can log in again.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $block = 0;
}
