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
 * Base class of the module commands: loading modules, and the fields module:create and module:update share.
 *
 * @since  3.17.0
 */
abstract class AbstractModuleCommand extends AbstractContentCommand
{
	/**
	 * Menu assignment names => the model's values
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const PAGES = array('all' => 0, 'none' => '-', 'only' => 1, 'except' => -1);

	/**
	 * Add the options of the module's fields.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addFieldOptions()
	{
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The title');
		$this->addOption('position', null, self::OPTION_REQUIRED, 'The template position (e.g. position-7, sidebar-right)');
		$this->addOption('content', null, self::OPTION_REQUIRED, 'The content (HTML) of a Custom module (mod_custom)');
		$this->addOption('content-file', null, self::OPTION_REQUIRED, 'Read the content from this file ("-" for the standard input)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished or trashed');
		$this->addOption('show-title', null, self::OPTION_REQUIRED, 'Show the title: yes or no');
		$this->addOption('pages', null, self::OPTION_REQUIRED, 'Where it shows: all, none, only (the --menu-items) or except (the --menu-items)');
		$this->addOption('menu-items', null, self::OPTION_REQUIRED, 'Menu item IDs for --pages=only or except, separated by commas');
		$this->addOption('params', null, self::OPTION_REQUIRED, 'Module options as a JSON object, merged into the current ones (e.g. {"count":"5"})');
		$this->addOption('access', null, self::OPTION_REQUIRED, 'The access level: ID or title');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'The language tag (e.g. en-GB), or * for all');
		$this->addOption('note', null, self::OPTION_REQUIRED, 'An administrator note');
	}

	/**
	 * Apply the given options to the module's data.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   array      $data  The module's data, changed in place
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function applyFieldOptions(CommandIO $io, array &$data)
	{
		foreach (array('title', 'position', 'note') as $field)
		{
			if ($io->getOption($field) !== null)
			{
				$data[$field] = (string) $io->getOption($field);
			}
		}

		$text = $this->getText($io, 'content');

		if ($text === false)
		{
			return false;
		}

		if ($text !== null)
		{
			$data['content'] = $text;
		}

		$value = $io->getOption('state');

		if ($value !== null && (($data['published'] = $this->parseState($value)) === null || $data['published'] === 2))
		{
			$io->error('--state must be published, unpublished or trashed.');

			return false;
		}

		$value = $io->getOption('show-title');

		if ($value !== null)
		{
			if (!in_array(strtolower($value), array('yes', 'no', '1', '0'), true))
			{
				$io->error('--show-title must be yes or no.');

				return false;
			}

			$data['showtitle'] = in_array(strtolower($value), array('yes', '1'), true) ? 1 : 0;
		}

		$pages = $io->getOption('pages');
		$items = $io->getOption('menu-items');

		if ($pages !== null)
		{
			if (!array_key_exists(strtolower($pages), self::PAGES))
			{
				$io->error('--pages must be all, none, only or except.');

				return false;
			}

			$data['assignment'] = self::PAGES[strtolower($pages)];
		}

		if ($items !== null)
		{
			$ids = array_values(array_filter(array_map('intval', explode(',', $items))));

			if ($this->checkMenuItems($io, $ids) === false)
			{
				return false;
			}

			$data['assigned'] = $ids;
		}

		if (in_array($data['assignment'], array(1, -1), true) && empty($data['assigned']))
		{
			$io->error('--pages=only and --pages=except need the menu items, with --menu-items.');

			return false;
		}

		$value = $io->getOption('params');

		if ($value !== null)
		{
			$params = json_decode($value, true);

			if (!is_array($params))
			{
				$io->error('--params must be a JSON object, e.g. {"count":"5"}.');

				return false;
			}

			$data['params'] = array_merge((array) $data['params'], $params);
		}

		$value = $io->getOption('access');

		if ($value !== null && ($data['access'] = $this->findAccessLevel($io, $value)) === false)
		{
			return false;
		}

		$value = $io->getOption('language');

		if ($value !== null && ($data['language'] = $this->findLanguage($io, $value)) === false)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param   CommandIO  $io   The input values and the output
	 * @param   integer[]  $ids  Menu item IDs
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function checkMenuItems(CommandIO $io, array $ids)
	{
		if (!$ids)
		{
			return true;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
			->where($db->quoteName('client_id') . ' = 0');
		$missing = array_diff($ids, array_map('intval', $db->setQuery($query)->loadColumn()));

		if ($missing)
		{
			$io->error('There are no site menu items with the IDs ' . implode(', ', $missing) . '. menu:item:list shows them.');

			return false;
		}

		return true;
	}

	/**
	 * Load a module as the module form has it.
	 *
	 * @param   CommandIO             $io     The input values and the output
	 * @param   \ModulesModelModule  $model  The model
	 * @param   string                $id     The module ID
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	protected function loadModule(CommandIO $io, $model, $id)
	{
		if (!ctype_digit((string) $id) || !(int) $id)
		{
			$io->error(sprintf('"%s" is not a module ID.', $id));

			return false;
		}

		$item = $model->getItem((int) $id);

		if (!$item || empty($item->id))
		{
			$io->error(sprintf('There is no module with ID %d.', $id));

			return false;
		}

		$data             = get_object_vars($item);
		$data['assigned'] = isset($data['assigned']) ? array_values(array_map('abs', array_map('intval', (array) $data['assigned']))) : array();

		return $data;
	}

	/**
	 * The module as shown by the commands.
	 *
	 * @param   array  $data  The module's data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function describeModule(array $data)
	{
		$pages = array_search($data['assignment'], self::PAGES, true);

		return array(
			'id'        => (int) $data['id'],
			'title'     => $data['title'],
			'type'      => $data['module'],
			'client'    => (int) $data['client_id'] ? 'administrator' : 'site',
			'position'  => $data['position'],
			'state'     => $this->stateName($data['published']),
			'showTitle' => (bool) $data['showtitle'],
			'pages'     => $pages === false ? (string) $data['assignment'] : $pages,
			'menuItems' => $data['assigned'],
			'access'    => (int) $data['access'],
			'language'  => $data['language'],
			'note'      => $data['note'],
			'params'    => (array) $data['params'],
			'content'   => $data['content'],
		);
	}
}
