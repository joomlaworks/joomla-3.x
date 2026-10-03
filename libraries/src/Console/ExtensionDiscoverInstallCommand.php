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
use Joomla\CMS\Installer\Installer;

/**
 * Installs discovered extensions.
 *
 * @since  3.17.0
 */
class ExtensionDiscoverInstallCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:discover:install';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Install discovered extensions';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Installs one discovered extension (--eid) or all of them. Run extension:discover first.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('eid', null, self::OPTION_REQUIRED, 'The ID of the extension to discover');
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
		$io->title('Install Discovered Extensions');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('extension_id', 'name')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('state') . ' = -1');
		$eid = (string) $io->getOption('eid');

		if ($eid !== '')
		{
			if (!ctype_digit($eid))
			{
				$io->error('The --eid option must be an extension ID.');

				return self::INVALID;
			}

			$query->where($db->quoteName('extension_id') . ' = ' . (int) $eid);
		}

		$discovered = $db->setQuery($query)->loadObjectList();

		if (!$discovered)
		{
			if ($eid !== '')
			{
				$io->error(sprintf('There is no discovered extension with ID %s.', $eid));

				return self::FAILURE;
			}

			$io->text('There are no pending discovered extensions to install. Perhaps you need to run extension:discover first?');

			return self::SUCCESS;
		}

		$installed = array();
		$failed    = array();

		foreach ($discovered as $extension)
		{
			$installer = new Installer;

			if ($installer->discover_install($extension->extension_id))
			{
				$installed[] = (int) $extension->extension_id;
				$io->text(sprintf('Installed "%s" (ID %d).', $extension->name, $extension->extension_id));
			}
			else
			{
				$failed[] = (int) $extension->extension_id;
				$io->error(sprintf('Unable to install "%s" (ID %d).', $extension->name, $extension->extension_id));
			}
		}

		$io->setData('installed', $installed);
		$io->setData('failed', $failed);

		if ($failed)
		{
			return self::FAILURE;
		}

		$io->success(sprintf('%d extension(s) installed.', count($installed)));

		return self::SUCCESS;
	}
}
