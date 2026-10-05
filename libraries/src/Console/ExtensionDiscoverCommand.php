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
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

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

		if ($io->isDryRun())
		{
			return $this->planDiscover($io);
		}

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

	/**
	 * Report the extensions on disk which discovering would list, as InstallerModelDiscover::discover() finds them.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function planDiscover(CommandIO $io)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('element', 'folder', 'client_id', 'type')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('state') . ' <> -1');
		$known = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$known[implode(':', array($row->type, $row->element, $row->folder, $row->client_id))] = true;
		}

		$count = 0;

		foreach (\JInstaller::getInstance()->discover() as $result)
		{
			if (!isset($known[implode(':', array($result->type, $result->element, $result->folder, $result->client_id))]))
			{
				$count++;
				$io->plan(sprintf('List the %s "%s" as discovered', $result->type, $result->name),
					array('action' => 'discover', 'type' => $result->type, 'element' => $result->element, 'name' => $result->name));
			}
		}

		$io->setData('count', $count);

		if (!$count)
		{
			$io->text('No extensions would be discovered.');
		}

		return self::SUCCESS;
	}
}
