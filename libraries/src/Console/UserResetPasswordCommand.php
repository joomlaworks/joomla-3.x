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
 * Changes a user's password.
 *
 * @since  3.17.0
 */
class UserResetPasswordCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:reset-password';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change a user\'s password';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Sets a new password for a user. Missing values are asked for, unless the command runs without interaction; '
		. 'then --username and --password are required. Give the password at the prompt to keep it out of the shell history.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('username', null, self::OPTION_REQUIRED, 'username');
		$this->addOption('password', null, self::OPTION_REQUIRED, 'password');
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
		$io->title('Change Password');

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

		$password = $this->getRequiredOption($io, 'password', 'Please enter a new password', true);

		if ($password === '')
		{
			return self::INVALID;
		}

		$user = User::getInstance($userId);
		$data = array('password' => $password, 'password2' => $password);

		if (!$user->bind($data) || !$user->save(true))
		{
			$io->error($user->getError() ?: 'The password was not changed.');

			return self::FAILURE;
		}

		$io->success('Password changed.');

		return self::SUCCESS;
	}
}
