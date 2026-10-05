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
 * Updates extensions which have an update available, like Extensions: Update.
 *
 * @since  3.17.0
 */
class ExtensionUpdateCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Update extensions';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Updates the given extensions, like Extensions: Update, using the updates found by update:extensions:check '
		. '(which lists their extension IDs). Download keys set for an update site are used. Joomla itself is updated with core:update.';

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
		$this->addArgument('extensionId', self::ARGUMENT_REQUIRED | self::ARGUMENT_ARRAY, 'IDs of the extensions to update (see update:extensions:check)');
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
		$io->title('Update Extensions');

		$db      = Factory::getDbo();
		$updates = array();

		foreach ((array) $io->getArgument('extensionId') as $id)
		{
			if (!ctype_digit((string) $id) || (int) $id === 0)
			{
				$io->error(sprintf('"%s" is not an extension ID.', $id));

				return self::INVALID;
			}

			if ((int) $id === 700)
			{
				$io->error('Joomla itself is updated with core:update.');

				return self::INVALID;
			}

			$query = $db->getQuery(true)
				->select($db->quoteName(array('update_id', 'name', 'version')))
				->from($db->quoteName('#__updates'))
				->where($db->quoteName('extension_id') . ' = ' . (int) $id);
			$update = $db->setQuery($query, 0, 1)->loadObject();

			if (!$update)
			{
				$io->error(sprintf('There is no update for the extension with ID %d. Run update:extensions:check to look for updates.', $id));

				return self::NOT_FOUND;
			}

			$updates[(int) $id] = $update;
		}

		/** @var \InstallerModelUpdate $model */
		if ($io->isDryRun())
		{
			foreach ($updates as $id => $update)
			{
				$io->plan(sprintf('Update %s (ID %d) to %s', $update->name, $id, $update->version),
					array('action' => 'update', 'id' => $id, 'name' => $update->name, 'version' => $update->version));
			}

			return self::SUCCESS;
		}

		$model     = $this->getAdministratorModel('com_installer', 'Update', 'InstallerModel');
		$stability = (int) ComponentHelper::getParams('com_installer')->get('minimum_stability', Updater::STABILITY_STABLE);
		$updated   = array();
		$failed    = array();

		foreach ($updates as $id => $update)
		{
			$io->text(sprintf('Updating %s to %s ...', $update->name, $update->version));

			// The model reports the result through the messages
			$model->update(array((int) $update->update_id), $stability);

			if ($model->getState('result'))
			{
				$updated[] = $id;
			}
			else
			{
				$failed[] = $id;
			}
		}

		$io->setData('updated', $updated);
		$io->setData('failed', $failed);

		if ($failed)
		{
			$io->error(sprintf('%d of %d extension(s) were not updated.', count($failed), count($updates)));

			return self::FAILURE;
		}

		$io->success(sprintf('%d extension(s) updated.', count($updated)));

		return self::SUCCESS;
	}
}
