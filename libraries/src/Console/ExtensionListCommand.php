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
 * Lists the installed extensions.
 *
 * @since  3.17.0
 */
class ExtensionListCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List installed extensions';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the installed extensions, optionally only those of one type (component, module, plugin, template, language, library, package, file).';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('type', null, self::OPTION_REQUIRED, 'Only list extensions of this type');
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
		$type       = (string) $io->getOption('type');
		$extensions = $this->getExtensions(false, $type);

		if (!$extensions && $type !== '')
		{
			$io->error(sprintf('Cannot find extensions of the type "%s".', $type));

			return self::FAILURE;
		}

		$io->title('Installed Extensions');
		$this->showExtensions($io, $extensions);

		return self::SUCCESS;
	}

	/**
	 * @param   boolean  $discovered  Discovered but not installed extensions instead of installed ones
	 * @param   string   $type        Only extensions of this type
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getExtensions($discovered, $type = '')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('extension_id', 'name', 'type', 'element', 'folder', 'client_id', 'enabled', 'protected', 'manifest_cache')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('state') . ($discovered ? ' = -1' : ' <> -1'))
			->order($db->quoteName(array('type', 'name')));

		if ($type !== '')
		{
			$query->where($db->quoteName('type') . ' = ' . $db->quote($type));
		}

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * @param   CommandIO  $io          The output
	 * @param   array      $extensions  The extension rows
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function showExtensions(CommandIO $io, array $extensions)
	{
		$rows = array();

		foreach ($extensions as $extension)
		{
			$manifest = json_decode((string) $extension->manifest_cache);

			$rows[] = array(
				'name'         => $extension->name,
				'extension_id' => (int) $extension->extension_id,
				'version'      => is_object($manifest) && isset($manifest->version) ? (string) $manifest->version : null,
				'type'         => $extension->type,
				'element'      => $extension->element,
				'folder'       => $extension->folder,
				'client'       => (int) $extension->client_id === 1 ? 'administrator' : 'site',
				'enabled'      => (int) $extension->enabled === 1,
				'protected'    => (int) $extension->protected === 1,
			);
		}

		$io->table(
			array(
				'name'         => 'Name',
				'extension_id' => 'Extension ID',
				'version'      => 'Version',
				'type'         => 'Type',
				'element'      => 'Element',
				'folder'       => 'Folder',
				'client'       => 'Client',
				'enabled'      => 'Enabled',
				'protected'    => 'Protected',
			),
			$rows,
			'extensions'
		);
	}
}
