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
 * Deletes a user account.
 *
 * @since  3.17.0
 */
class UserDeleteCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete a user';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes a user account, after a confirmation when the command is interactive. The last active Super User can\'t be deleted.';

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
		$io->title('Delete User');

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

		if ($io->isInteractive() && !$io->isDryRun() && !$io->confirm('Are you sure you want to delete this user?', false))
		{
			$io->text('User not deleted.');

			return self::SUCCESS;
		}

		$user = User::getInstance($userId);

		if (!$user->block && $this->countOtherActiveSuperUsers($userId) === 0)
		{
			foreach (UserHelper::getUserGroups($userId) as $groupId)
			{
				if ($this->isSuperUserGroup($groupId))
				{
					$io->error('You can\'t delete the last active Super User.');

					return self::REFUSED;
				}
			}
		}

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Delete the user "%s" (ID %d)', $username, $userId), array('action' => 'delete', 'username' => $username, 'id' => (int) $userId));

			return self::SUCCESS;
		}

		if (!$user->delete())
		{
			$io->error($user->getError() ?: sprintf('Can\'t delete the user "%s".', $username));

			return self::FAILURE;
		}

		$io->success(sprintf('User "%s" deleted.', $username));

		return self::SUCCESS;
	}
}
