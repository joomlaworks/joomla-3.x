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
 * Uninstalls an extension.
 *
 * @since  3.17.0
 */
class ExtensionRemoveCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:remove';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Remove an extension';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Uninstalls an extension, like Extensions: Manage > Uninstall, after a confirmation when the command is interactive. '
		. 'Protected core extensions can\'t be uninstalled.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('extensionId', self::ARGUMENT_REQUIRED, 'ID of the extension to remove (see extension:list)');
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
		$io->title('Remove Extension');

		$extension = $this->loadExtension($io, $io->getArgument('extensionId'));

		if (!$extension)
		{
			return self::FAILURE;
		}

		if ((int) $extension->protected === 1)
		{
			$io->error($this->describe($extension) . ' is protected and can\'t be removed.');

			return self::FAILURE;
		}

		if ($io->isInteractive() && !$io->confirm('Are you sure you want to remove ' . $this->describe($extension) . '?', false))
		{
			$io->text('Extension not removed.');

			return self::SUCCESS;
		}

		$description = $this->describe($extension);

		/** @var \InstallerModelManage $model */
		$model = $this->getAdministratorModel('com_installer', 'Manage', 'InstallerModel');

		if (!$model->remove(array((int) $extension->extension_id)))
		{
			$io->error($description . ' was not removed.');

			return self::FAILURE;
		}

		$io->success($description . ' removed.');

		return self::SUCCESS;
	}
}
