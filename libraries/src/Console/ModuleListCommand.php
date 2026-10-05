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
 * Lists modules.
 *
 * @since  3.17.0
 */
class ModuleListCommand extends AbstractModuleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List modules';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the site\'s modules (or the administrator\'s with --client=administrator) by position, without the trashed ones '
		. 'unless --state asks for them.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
		$this->addOption('position', null, self::OPTION_REQUIRED, 'Only this position ("none" for modules without one)');
		$this->addOption('type', null, self::OPTION_REQUIRED, 'Only this module type (e.g. mod_custom)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, trashed or all (default: all but trashed)');
		$this->addOption('search', null, self::OPTION_REQUIRED, 'Only titles containing this text');
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
		$client = (string) $io->getOption('client');

		if (!in_array($client, array('site', 'administrator'), true))
		{
			$io->error('--client must be site or administrator.');

			return self::INVALID;
		}

		$db    = Factory::getDbo();
		$pages = $db->getQuery(true)
			->select('MIN(' . $db->quoteName('mm.menuid') . ')')
			->from($db->quoteName('#__modules_menu', 'mm'))
			->where($db->quoteName('mm.moduleid') . ' = ' . $db->quoteName('m.id'));
		$query = $db->getQuery(true)
			->select($db->quoteName(array('m.id', 'm.title', 'm.module', 'm.position', 'm.published', 'm.access', 'm.language', 'm.ordering', 'm.note')))
			->select('(' . $pages . ') AS ' . $db->quoteName('pages'))
			->from($db->quoteName('#__modules', 'm'))
			->where($db->quoteName('m.client_id') . ' = ' . ($client === 'site' ? 0 : 1))
			->order($db->quoteName('m.position') . ', ' . $db->quoteName('m.ordering') . ', ' . $db->quoteName('m.id'));

		$state = strtolower((string) $io->getOption('state'));

		if ($state === '')
		{
			$query->where($db->quoteName('m.published') . ' <> -2');
		}
		elseif ($state !== 'all')
		{
			if (($value = $this->parseState($state)) === null)
			{
				$io->error('--state must be published, unpublished, trashed or all.');

				return self::INVALID;
			}

			$query->where($db->quoteName('m.published') . ' = ' . $value);
		}

		if ($io->getOption('position') !== null)
		{
			$query->where($db->quoteName('m.position') . ' = ' . $db->quote($io->getOption('position') === 'none' ? '' : (string) $io->getOption('position')));
		}

		if ($io->getOption('type') !== null)
		{
			$query->where($db->quoteName('m.module') . ' = ' . $db->quote((string) $io->getOption('type')));
		}

		if ((string) $io->getOption('search') !== '')
		{
			$query->where($db->quoteName('m.title') . ' LIKE ' . $db->quote('%' . $db->escape((string) $io->getOption('search'), true) . '%', false));
		}

		$rows = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			// #__modules_menu: 0 all pages, a positive ID only those, a negative ID all except those, no row none
			$pages = $row->pages === null ? 'none' : ((int) $row->pages === 0 ? 'all' : ((int) $row->pages > 0 ? 'only' : 'except'));

			$rows[] = array(
				'id'       => (int) $row->id,
				'title'    => $row->title,
				'type'     => $row->module,
				'position' => $row->position,
				'state'    => $this->stateName($row->published),
				'pages'    => $pages,
				'access'   => (int) $row->access,
				'language' => $row->language,
				'note'     => $row->note,
			);
		}

		$io->title('Modules (' . $client . ')');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching modules.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'title' => 'Title', 'type' => 'Type', 'position' => 'Position', 'state' => 'State', 'pages' => 'Pages',
			'access' => 'Access', 'language' => 'Language', 'note' => 'Note'), $rows);

		return self::SUCCESS;
	}
}
