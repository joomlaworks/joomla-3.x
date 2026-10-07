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
 * Lists the entries of the User Actions Log.
 *
 * @since  3.17.0
 */
class ActionlogListCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'actionlog:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the entries of the User Actions Log';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the latest entries of the User Actions Log (Users > User Actions Log): who did what, and when, newest first. Changes '
		. 'made from the command line or through the MCP server are recorded as the user "cli". Filter with --user, --extension and --since.';

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
		$this->addOption('user', null, self::OPTION_REQUIRED, 'Only the actions of this username ("cli" for the command line)');
		$this->addOption('extension', null, self::OPTION_REQUIRED, 'Only the actions on this extension (e.g. com_content, com_users)');
		$this->addOption('since', null, self::OPTION_REQUIRED, 'Only the actions from this date and time on (e.g. 2026-10-01 or "-1 day")');
		$this->addOption('limit', null, self::OPTION_REQUIRED, 'How many entries to show', 50);
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
		$helper = JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php';

		if (!is_file($helper))
		{
			$io->error('The User Actions Log component isn\'t installed.');

			return self::NOT_FOUND;
		}

		$limit = (int) $io->getOption('limit');

		if ($limit < 1)
		{
			$io->error('--limit must be a positive number.');

			return self::INVALID;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('a.id', 'a.message_language_key', 'a.message', 'a.log_date', 'a.extension', 'a.user_id', 'a.ip_address', 'a.item_id')))
			->select($db->quoteName('u.username'))
			->from($db->quoteName('#__action_logs', 'a'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
			->order($db->quoteName('a.id') . ' DESC');

		$user = (string) $io->getOption('user');

		if ($user === 'cli')
		{
			$query->where($db->quoteName('a.user_id') . ' = 0');
		}
		elseif ($user !== '')
		{
			$query->where($db->quoteName('u.username') . ' = ' . $db->quote($user));
		}

		$extension = (string) $io->getOption('extension');

		if ($extension !== '')
		{
			$query->where('(' . $db->quoteName('a.extension') . ' = ' . $db->quote($extension) . ' OR ' . $db->quoteName('a.extension') . ' LIKE '
				. $db->quote($db->escape($extension, true) . '.%', false) . ')');
		}

		$since = (string) $io->getOption('since');

		if ($since !== '')
		{
			if (($time = strtotime($since)) === false)
			{
				$io->error('--since must be a date and time, e.g. 2026-10-01 or "-1 day".');

				return self::INVALID;
			}

			$query->where($db->quoteName('a.log_date') . ' >= ' . $db->quote(gmdate('Y-m-d H:i:s', $time)));
		}

		\JLoader::register('ActionlogsHelper', $helper);
		Factory::getLanguage()->load('com_actionlogs', JPATH_ADMINISTRATOR);

		// The messages of Joomla's own actions (logins, saved articles...) are in the Action Log plugins' language files
		\ActionlogsHelper::loadActionLogPluginsLanguage();

		$rows = array();

		foreach ($db->setQuery($query, 0, $limit)->loadObjectList() as $log)
		{
			\ActionlogsHelper::loadTranslationFiles(strtok($log->extension, '.'));

			$message = \ActionlogsHelper::getHumanReadableLogMessage($log, false);
			$message = html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8');

			$rows[] = array(
				'id'        => (int) $log->id,
				'date'      => $log->log_date . ' UTC',
				'user'      => (int) $log->user_id === 0 ? 'cli' : (string) $log->username,
				'extension' => $log->extension,
				'message'   => $message,
				'itemId'    => (int) $log->item_id,
				'ip'        => strpos((string) $log->ip_address, 'COM_ACTIONLOGS_') === 0 ? null : $log->ip_address,
			);
		}

		$io->title('User Actions Log');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching entries.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'date' => 'Date', 'user' => 'User', 'extension' => 'Extension', 'message' => 'Message'), $rows);

		return self::SUCCESS;
	}
}
