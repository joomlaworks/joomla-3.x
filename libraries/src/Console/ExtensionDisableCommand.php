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
 * Disables an extension.
 *
 * @since  3.17.0
 */
class ExtensionDisableCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:disable';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Disable an extension';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Disables an extension, like Extensions: Manage. Protected core extensions and templates used by a default template style can\'t be disabled.';

	/**
	 * The state this command sets
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 0;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('extensionId', self::ARGUMENT_REQUIRED, 'ID of the extension (see extension:list)');
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
		$verb = $this->state ? 'enabled' : 'disabled';

		$io->title(($this->state ? 'Enable' : 'Disable') . ' Extension');

		$extension = $this->loadExtension($io, $io->getArgument('extensionId'));

		if (!$extension)
		{
			return self::FAILURE;
		}

		if (!$this->state && (int) $extension->protected === 1)
		{
			$io->error($this->describe($extension) . ' is protected.');

			return self::FAILURE;
		}

		if (!$this->state && $extension->type === 'template')
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__template_styles'))
				->where($db->quoteName('template') . ' = ' . $db->quote($extension->element))
				->where($db->quoteName('client_id') . ' = ' . (int) $extension->client_id)
				->where($db->quoteName('home') . ' <> ' . $db->quote('0'));

			if ($db->setQuery($query)->loadResult())
			{
				$io->error($this->describe($extension) . ' is used by a default template style.');

				return self::FAILURE;
			}
		}

		if ((int) $extension->enabled === $this->state)
		{
			$io->warning($this->describe($extension) . ' is already ' . $verb . '.');

			return self::SUCCESS;
		}

		/** @var \InstallerModelManage $model */
		$model = $this->getAdministratorModel('com_installer', 'Manage', 'InstallerModel');
		$ids   = array((int) $extension->extension_id);

		if (!$model->publish($ids, $this->state))
		{
			$io->error($this->describe($extension) . ' was not ' . $verb . '. ' . $model->getError());

			return self::FAILURE;
		}

		$io->success($this->describe($extension) . ' ' . $verb . '.');

		return self::SUCCESS;
	}
}
