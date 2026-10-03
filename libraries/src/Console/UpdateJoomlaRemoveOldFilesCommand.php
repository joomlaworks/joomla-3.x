<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Removes the files and folders which earlier Joomla versions had and the current one doesn't.
 *
 * @since  3.17.0
 */
class UpdateJoomlaRemoveOldFilesCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'update:joomla:remove-old-files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Remove old system files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes the files and folders of earlier Joomla versions which are no longer part of Joomla, as a Joomla update does. '
		. 'With --dry-run it only lists those which still exist.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('dry-run', null, self::OPTION_NONE, 'List the files and folders which would be removed, without removing them');
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
		$dryRun = (bool) $io->getOption('dry-run');

		$io->title('Removing Unneeded Files & Folders' . ($dryRun ? ' - Dry Run' : ''));

		\JLoader::register('JoomlaInstallerScript', JPATH_ADMINISTRATOR . '/components/com_admin/script.php');

		$script = new \JoomlaInstallerScript;
		$status = $script->deleteUnexistingFiles($dryRun, true);

		foreach (array_merge($status['files_errors'], $status['folders_errors']) as $error)
		{
			$io->error($error);
		}

		if ($dryRun)
		{
			foreach (array_merge($status['files_exist'], $status['folders_exist']) as $path)
			{
				$io->writeln($path);
			}

			$io->setData('files', $status['files_exist']);
			$io->setData('folders', $status['folders_exist']);
			$io->success(sprintf('%d files and %d folders would be removed.', count($status['files_exist']), count($status['folders_exist'])));

			return self::SUCCESS;
		}

		$io->setData('files', $status['files_deleted']);
		$io->setData('folders', $status['folders_deleted']);
		$io->setData('errors', array_merge($status['files_errors'], $status['folders_errors']));
		$io->success(sprintf('%d files and %d folders removed.', count($status['files_deleted']), count($status['folders_deleted'])));

		return $status['files_errors'] || $status['folders_errors'] ? self::FAILURE : self::SUCCESS;
	}
}
