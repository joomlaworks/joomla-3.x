<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\User\User;

/**
 * Creates a user account.
 *
 * @since  3.17.0
 */
class UserAddCommand extends AbstractUserCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:add';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Add a user';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates an activated user account. Missing values are asked for, unless the command runs without interaction '
		. '(--no-interaction, --format=json, or no terminal); then --username, --name, --email and --password are required, '
		. 'and the user goes in the "New User Registration Group" of the Users options when --usergroup is not given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('username', null, self::OPTION_REQUIRED, 'username');
		$this->addOption('name', null, self::OPTION_REQUIRED, 'full name of user');
		$this->addOption('password', null, self::OPTION_REQUIRED, 'password');
		$this->addOption('email', null, self::OPTION_REQUIRED, 'email address');
		$this->addOption('usergroup', null, self::OPTION_REQUIRED, 'usergroup (separate multiple groups with comma ",")');
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
		$io->title('Add User');

		$values = array();

		foreach (array(
			'username' => 'Please enter a username',
			'name'     => 'Please enter a name (full name of user)',
			'email'    => 'Please enter an email address',
			'password' => 'Please enter a password',
		) as $option => $question)
		{
			$values[$option] = $this->getRequiredOption($io, $option, $question, $option === 'password');

			if ($values[$option] === '')
			{
				return self::INVALID;
			}
		}

		$groups = (string) $io->getOption('usergroup');

		if ($groups === '' && $io->isInteractive())
		{
			$default = $this->getGroupTitle(ComponentHelper::getParams('com_users')->get('new_usertype', 2));
			$groups  = (string) $io->ask('Please enter the user groups (separate multiple groups with a comma)', $default);
		}

		if ($groups === '')
		{
			$groupIds = array((int) ComponentHelper::getParams('com_users')->get('new_usertype', 2));
		}
		elseif (($groupIds = $this->getGroupIds($io, $groups)) === false)
		{
			return self::INVALID;
		}

		$filter = InputFilter::getInstance();
		$data   = array(
			'username'  => $filter->clean($values['username'], 'USERNAME'),
			'name'      => $filter->clean($values['name'], 'STRING'),
			'email'     => $values['email'],
			'password'  => $values['password'],
			'password2' => $values['password'],
			'groups'    => $groupIds,
			'block'     => 0,
		);

		$user = new User;

		if (!$user->bind($data) || !$user->save())
		{
			$io->error($user->getError() ?: 'The user was not created.');

			return self::FAILURE;
		}

		$io->setData('id', (int) $user->id);
		$io->setData('username', $user->username);
		$io->setData('groups', array_map(array($this, 'getGroupTitle'), $groupIds));
		$io->success(sprintf('User "%s" created with ID %d.', $user->username, $user->id));

		return self::SUCCESS;
	}
}
