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
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

/**
 * Blocks a user, so they can't log in.
 *
 * @since  3.17.0
 */
class UserBlockCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:block';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Block a user';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Blocks a user, like Users: Manage > Block, so they can\'t log in, and ends their sessions. The last active Super User can\'t be blocked.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * The blocked state this command sets
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $block = 1;

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
		$io->title($this->block ? 'Block User' : 'Unblock User');

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

		$user = User::getInstance($userId);

		if ((int) $user->block === $this->block)
		{
			$io->warning(sprintf('The user "%s" is already %s.', $username, $this->block ? 'blocked' : 'unblocked'));

			return self::SUCCESS;
		}

		if ($this->block && $this->countOtherActiveSuperUsers($userId) === 0)
		{
			foreach (UserHelper::getUserGroups($userId) as $groupId)
			{
				if ($this->isSuperUserGroup($groupId))
				{
					$io->error('You can\'t block the last active Super User.');

					return self::REFUSED;
				}
			}
		}

		if ($io->isDryRun())
		{
			$io->plan(sprintf('%s the user "%s"%s', $this->block ? 'Block' : 'Unblock', $username, $this->block ? ' and end their sessions' : ''),
				array('action' => $this->block ? 'block' : 'unblock', 'username' => $username, 'id' => (int) $userId));

			return self::SUCCESS;
		}

		$user->block = $this->block;

		if (!$user->save(true))
		{
			$io->error($user->getError() ?: sprintf('The user "%s" was not changed.', $username));

			return self::FAILURE;
		}

		if ($this->block)
		{
			// Ends their sessions with the database session handler, and removes them from Who's Online with the others
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__session'))
				->where($db->quoteName('userid') . ' = ' . (int) $userId);
			$db->setQuery($query)->execute();
		}

		$io->success(sprintf('The user "%s" is now %s.', $username, $this->block ? 'blocked' : 'unblocked'));

		return self::SUCCESS;
	}
}
