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
 * Looks for extensions on disk which aren't installed.
 *
 * @since  3.17.0
 */
class ExtensionDiscoverCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:discover';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Discover extensions';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Looks for extensions whose files are on disk but which aren\'t installed, like Extensions: Discover. '
		. 'List them with extension:discover:list and install them with extension:discover:install.';

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Discover Extensions');

		/** @var \InstallerModelDiscover $model */
		$model = $this->getAdministratorModel('com_installer', 'Discover', 'InstallerModel');
		$model->discover();

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('state') . ' = -1');
		$count = (int) $db->setQuery($query)->loadResult();

		$io->setData('count', $count);

		if ($count === 0)
		{
			$io->text('No extensions were discovered.');
		}
		else
		{
			$io->text(sprintf('%d extension(s) discovered. Run extension:discover:list to see them.', $count));
		}

		return self::SUCCESS;
	}
}
