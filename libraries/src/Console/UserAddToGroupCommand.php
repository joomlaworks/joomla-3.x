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
use Joomla\CMS\User\UserHelper;

/**
 * Adds a user to user groups.
 *
 * @since  3.17.0
 */
class UserAddToGroupCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:addtogroup';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Add a user to a group';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Adds a user to one or more user groups, given by title and separated by commas.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('username', null, self::OPTION_REQUIRED, 'username');
		$this->addOption('group', null, self::OPTION_REQUIRED, 'group');
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
		$io->title('Add User To Group');

		$username = $this->getRequiredOption($io, 'username', 'Please enter a username');

		if ($username === '')
		{
			return self::INVALID;
		}

		$userId = $this->getUserId($username);

		if (!$userId)
		{
			$io->error(sprintf('The user "%s" does not exist.', $username));

			return self::NOT_FOUND;
		}

		$groups = $this->getRequiredOption($io, 'group', 'Please enter the user groups (separate multiple groups with a comma)');

		if ($groups === '' || ($groupIds = $this->getGroupIds($io, $groups)) === false)
		{
			return self::INVALID;
		}

		$user = User::getInstance($userId);

		foreach ($groupIds as $groupId)
		{
			$title = $this->getGroupTitle($groupId);

			if ($io->isDryRun())
			{
				$io->plan(sprintf('Add "%s" to the group "%s"', $user->username, $title), array('action' => 'addtogroup', 'username' => $user->username, 'group' => $title));

				continue;
			}

			if (!UserHelper::addUserToGroup($user->id, $groupId))
			{
				$io->error(sprintf('Can\'t add "%s" to the group "%s".', $user->username, $title));

				return self::FAILURE;
			}

			$io->success(sprintf('Added "%s" to the group "%s".', $user->username, $title));
		}

		return self::SUCCESS;
	}
}
