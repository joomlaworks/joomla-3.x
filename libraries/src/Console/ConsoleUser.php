<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\User\User;

/**
 * The in-memory user commands which change things act as: a Super User with no account.
 *
 * @since  3.17.0
 */
class ConsoleUser extends User
{
	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $isRoot = true;

	/**
	 * @since   3.17.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->guest    = 0;
		$this->name     = 'Command line';
		$this->username = 'cli';
	}

	/**
	 * Authorisation is computed per user ID, which this user doesn't have, so keep the Super User flag.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function clearAccessRights()
	{
		parent::clearAccessRights();

		$this->isRoot = true;
	}
}
