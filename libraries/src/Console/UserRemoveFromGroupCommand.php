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
 * Removes a user from user groups.
 *
 * @since  3.17.0
 */
class UserRemoveFromGroupCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:removefromgroup';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Remove a user from a group';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Removes a user from one or more user groups, given by title and separated by commas. A user must stay in at least '
		. 'one group, and the last active Super User can\'t lose Super User rights.';

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
		$io->title('Remove User From Group');

		$username = $this->getRequiredOption($io, 'username', 'Please enter a username');

		if ($username === '')
		{
			return self::INVALID;
		}

		$userId = $this->getUserId($username);

		if (!$userId)
		{
			$io->error(sprintf('The user "%s" does not exist.', $username));

			return self::FAILURE;
		}

		$groups = $this->getRequiredOption($io, 'group', 'Please enter the user groups (separate multiple groups with a comma)');

		if ($groups === '' || ($groupIds = $this->getGroupIds($io, $groups)) === false)
		{
			return self::INVALID;
		}

		$user    = User::getInstance($userId);
		$current = array_map('intval', UserHelper::getUserGroups($userId));

		foreach ($groupIds as $groupId)
		{
			$title = $this->getGroupTitle($groupId);

			if (!in_array($groupId, $current, true))
			{
				$io->warning(sprintf('"%s" is not in the group "%s".', $user->username, $title));

				continue;
			}

			if (count($current) < 2)
			{
				$io->error(sprintf('Can\'t remove "%s" from the group "%s": every user needs to be in at least one group.', $user->username, $title));

				return self::FAILURE;
			}

			$remaining = array_diff($current, array($groupId));

			if (!$user->block && $this->isSuperUserGroup($groupId) && $this->countOtherActiveSuperUsers($userId) === 0
				&& !array_filter($remaining, array($this, 'isSuperUserGroup')))
			{
				$io->error(sprintf('Can\'t remove "%s" from the group "%s": the site needs at least one active Super User.', $user->username, $title));

				return self::FAILURE;
			}

			if (!UserHelper::removeUserFromGroup($user->id, $groupId))
			{
				$io->error(sprintf('Can\'t remove "%s" from the group "%s".', $user->username, $title));

				return self::FAILURE;
			}

			$current = array_values($remaining);
			$io->success(sprintf('Removed "%s" from the group "%s".', $user->username, $title));
		}

		return self::SUCCESS;
	}
}
