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
	 * @param   CommandIO  $io  The input values and the output
	 * @param   mixed      $id  The extension ID
	 *
	 * @return  \JTableExtension|false  The extension, false after reporting that it doesn't exist
	 *
	 * @since   3.17.0
	 */
	protected function loadExtension(CommandIO $io, $id)
	{
		$table = Table::getInstance('Extension');

		if (!ctype_digit((string) $id) || (int) $id === 0 || !$table->load((int) $id) || (int) $table->state === -1)
		{
			$io->error(sprintf('Extension with ID of %s not found.', $id));

			return false;
		}

		return $table;
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
