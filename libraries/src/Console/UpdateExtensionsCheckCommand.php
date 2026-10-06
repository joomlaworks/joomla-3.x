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
use Joomla\CMS\Factory;
use Joomla\CMS\Updater\Updater;

/**
 * Checks the update sites for extension updates.
 *
 * @since  3.17.0
 */
class UpdateExtensionsCheckCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'update:extensions:check';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Check for pending extension updates';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Clears the cached update information, fetches every enabled update site and lists the extensions with an update available. '
		. 'With --use-cache, update sites checked within the "Updates Caching" time of the Installer options are not fetched again, which suits frequent cron jobs. '
		. 'With --dry-run, nothing is fetched and the updates found at the last check are listed.';

	/**
	 * It replaces the cached update information, so it isn't read-only
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * Routine, often run by cron jobs (cli/update_cron.php)
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $logged = false;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('use-cache', null, self::OPTION_NONE, 'Keep the cached update information and skip recently checked update sites');
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
		$io->title('Fetching Extension Updates');

		/** @var \InstallerModelUpdate $model */
		$model        = $this->getAdministratorModel('com_installer', 'Update', 'InstallerModel');
		$cacheTimeout = 0;

		if ($io->getOption('use-cache'))
		{
			$cacheTimeout = 3600 * ComponentHelper::getParams('com_installer')->get('cachetimeout', 6, 'int');
		}

		if ($io->isDryRun())
		{
			$io->plan($cacheTimeout ? 'Fetch the update sites not checked recently and add their updates to the cached update information'
				: 'Clear the cached update information and fetch every enabled update site', array('action' => 'refresh'));
			$io->text('As found at the last check:');
		}
		else
		{
			if (!$cacheTimeout)
			{
				$model->purge();
			}

			Updater::getInstance()->findUpdates(0, $cacheTimeout);
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('u.extension_id', 'u.name', 'u.client_id', 'u.type', 'u.version', 'u.folder', 'u.infourl', 'e.manifest_cache')))
			->from($db->quoteName('#__updates', 'u'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.extension_id') . ' = ' . $db->quoteName('u.extension_id'))
			->where($db->quoteName('u.extension_id') . ' NOT IN (0, 700)')
			->order($db->quoteName('u.name'));

		$updates = array();

		foreach ($db->setQuery($query)->loadObjectList() as $update)
		{
			$manifest = json_decode((string) $update->manifest_cache);

			$updates[] = array(
				'extension_id' => (int) $update->extension_id,
				'name'         => $update->name,
				'client'       => (int) $update->client_id === 1 ? 'administrator' : 'site',
				'type'         => $update->type,
				'installed'    => is_object($manifest) && isset($manifest->version) ? (string) $manifest->version : null,
				'available'    => $update->version,
				'folder'       => $update->folder,
				'infourl'      => $update->infourl,
			);
		}

		if (!$updates)
		{
			$io->success('There are no updates available.');
		}
		else
		{
			$io->text('There are updates available.');
		}

		$io->table(
			array(
				'extension_id' => 'Extension ID',
				'name'         => 'Name',
				'client'       => 'Location',
				'type'         => 'Type',
				'installed'    => 'Installed',
				'available'    => 'Available',
				'folder'       => 'Folder',
			),
			$updates,
			'updates'
		);

		return self::SUCCESS;
	}
}
