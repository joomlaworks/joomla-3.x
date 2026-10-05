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

/**
 * Lists the users and their groups.
 *
 * @since  3.17.0
 */
class UserListCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'user:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List all users';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists all users with their ID, username, name, email, blocked state and user groups, or only those whose username, '
		. 'name or email matches the pattern, e.g. "*@example.com" or "*smith*" (with the wildcards * and ?, quoted so the shell '
		. 'doesn\'t expand them; without wildcards the match is exact). Matching ignores case.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('pattern', self::ARGUMENT_OPTIONAL, 'Only list users whose username, name or email matches, e.g. "*smith*"');
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
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'title')))
			->from($db->quoteName('#__usergroups'));
		$titles = $db->setQuery($query)->loadAssocList('id', 'title');

		$query = $db->getQuery(true)
			->select($db->quoteName(array('user_id', 'group_id')))
			->from($db->quoteName('#__user_usergroup_map'));
		$groups = array();

		foreach ($db->setQuery($query)->loadObjectList() as $map)
		{
			$groups[(int) $map->user_id][] = isset($titles[$map->group_id]) ? $titles[$map->group_id] : (string) $map->group_id;
		}

		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'username', 'name', 'email', 'block')))
			->from($db->quoteName('#__users'))
			->order($db->quoteName('id'));
		$users   = array();
		$pattern = (string) $io->getArgument('pattern');

		foreach ($db->setQuery($query)->loadObjectList() as $user)
		{
			if ($pattern !== '' && !static::matchesPattern($pattern, array($user->username, $user->name, $user->email)))
			{
				continue;
			}

			$users[] = array(
				'id'       => (int) $user->id,
				'username' => $user->username,
				'name'     => $user->name,
				'email'    => $user->email,
				'blocked'  => (int) $user->block === 1,
				'groups'   => isset($groups[(int) $user->id]) ? $groups[(int) $user->id] : array(),
			);
		}

		$io->title('List Users');

		if (!$users && $pattern !== '')
		{
			$io->text(sprintf('No users match "%s".', $pattern));
		}
		$io->table(
			array('id' => 'ID', 'username' => 'Username', 'name' => 'Name', 'email' => 'Email', 'blocked' => 'Blocked', 'groups' => 'Groups'),
			$users,
			'users'
		);

		return self::SUCCESS;
	}
}
