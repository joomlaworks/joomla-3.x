<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Table\Table;

/**
 * Shared code of the extension:* commands which change extensions.
 *
 * @since  3.17.0
 */
abstract class AbstractExtensionCommand extends AbstractCommand
{
	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * The exit code after loadExtension() failed: NOT_FOUND, or INVALID when a name matched several extensions
	 *
	 * @var    integer
	 * @since  3.18.0
	 */
	protected $lookupError = self::NOT_FOUND;

	/**
	 * @param   CommandIO  $io  The input values and the output
	 * @param   mixed      $id  The extension ID, or its name: the element (com_k2, mod_menu, en-GB) or, for plugins, templates,
	 *                          libraries and files, the element with its prefix (plg_system_k2, tpl_hammond)
	 *
	 * @return  \JTableExtension|false  The extension, false after reporting that it doesn't exist (or which ones a name matches)
	 *
	 * @since   3.17.0
	 */
	protected function loadExtension(CommandIO $io, $id)
	{
		$table = Table::getInstance('Extension');
		$id    = trim((string) $id);
		$given = $id;

		if ($id !== '' && !ctype_digit($id))
		{
			$matches = $this->findExtensionsByName($id);

			if (count($matches) > 1)
			{
				$list = array();

				foreach ($matches as $match)
				{
					$list[] = sprintf('%d (%s%s)', $match->extension_id, $match->type, $match->type === 'module' || $match->type === 'template'
						|| $match->type === 'language' ? ', ' . ((int) $match->client_id ? 'administrator' : 'site') : '');
				}

				$io->error(sprintf('Several extensions are named %s: IDs %s. Give the ID.', $given, implode(', ', $list)));
				$this->lookupError = self::INVALID;

				return false;
			}

			$id = $matches ? (string) $matches[0]->extension_id : '';
		}

		if (!ctype_digit($id) || (int) $id === 0 || !$table->load((int) $id) || (int) $table->state === -1)
		{
			$io->error(ctype_digit($given) ? sprintf('Extension with ID of %s not found.', $given) : sprintf('Extension named %s not found.', $given));
			$this->lookupError = self::NOT_FOUND;

			return false;
		}

		return $table;
	}

	/**
	 * The installed extensions (not only discovered) with a name: their element, or their element with its type's prefix
	 * (plg_<folder>_<element>, tpl_, lib_, files_), as in their package and manifest names.
	 *
	 * @param   string  $name  The name
	 *
	 * @return  object[]  Their extension_id, type and client_id
	 *
	 * @since   3.18.0
	 */
	protected function findExtensionsByName($name)
	{
		$db    = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('extension_id', 'type', 'client_id')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('state') . ' <> -1');

		$conditions = array($db->quoteName('element') . ' = ' . $db->quote($name));

		if (preg_match('/^plg_([^_]+)_(.+)$/', $name, $match))
		{
			$conditions[] = '(' . $db->quoteName('type') . ' = ' . $db->quote('plugin') . ' AND ' . $db->quoteName('folder') . ' = ' . $db->quote($match[1])
				. ' AND ' . $db->quoteName('element') . ' = ' . $db->quote($match[2]) . ')';
		}

		foreach (array('tpl' => 'template', 'lib' => 'library', 'files' => 'file') as $prefix => $type)
		{
			if (strpos($name, $prefix . '_') === 0 && strlen($name) > strlen($prefix) + 1)
			{
				$conditions[] = '(' . $db->quoteName('type') . ' = ' . $db->quote($type) . ' AND ' . $db->quoteName('element') . ' = '
					. $db->quote(substr($name, strlen($prefix) + 1)) . ')';
			}
		}

		$query->where('(' . implode(' OR ', $conditions) . ')')
			->order($db->quoteName('extension_id'));

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * @param   \JTableExtension  $extension  The extension
	 *
	 * @return  string  e.g. 'Plugin with ID of 123 "System - SEF"'
	 *
	 * @since   3.17.0
	 */
	protected function describe($extension)
	{
		return sprintf('%s with ID of %d "%s"', ucfirst($extension->type), $extension->extension_id, $extension->name);
	}
}
