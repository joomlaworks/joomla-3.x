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
 * Lists tags.
 *
 * @since  3.17.0
 */
class TagListCommand extends AbstractTagCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'tag:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List tags';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the tags in tree order, without the trashed ones unless --state asks for them, with the number of items tagged '
		. 'with each.';

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
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, archived, trashed or all (default: all but trashed)');
		$this->addOption('parent', null, self::OPTION_REQUIRED, 'Only this tag\'s child tags (ID, path, alias or title)');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'Only this language (e.g. en-GB, or *)');
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
		$db    = Factory::getDbo();
		$count = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__contentitem_tag_map', 'm'))
			->where($db->quoteName('m.tag_id') . ' = ' . $db->quoteName('t.id'));
		$query = $db->getQuery(true)
			->select($db->quoteName(array('t.id', 't.title', 't.alias', 't.path', 't.level', 't.published', 't.access', 't.language', 't.parent_id')))
			->select('(' . $count . ') AS ' . $db->quoteName('items'))
			->from($db->quoteName('#__tags', 't'))
			->where($db->quoteName('t.id') . ' > 1')
			->order($db->quoteName('t.lft'));

		$state = strtolower((string) $io->getOption('state'));

		if ($state === '')
		{
			$query->where($db->quoteName('t.published') . ' <> -2');
		}
		elseif ($state !== 'all')
		{
			if (($value = $this->parseState($state)) === null)
			{
				$io->error('--state must be published, unpublished, archived, trashed or all.');

				return self::INVALID;
			}

			$query->where($db->quoteName('t.published') . ' = ' . $value);
		}

		if ($io->getOption('parent') !== null)
		{
			if (($parent = $this->findTag($io, $io->getOption('parent'))) === false)
			{
				return self::NOT_FOUND;
			}

			$query->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . (int) $parent)
				->where($db->quoteName('t.lft') . ' > ' . $db->quoteName('p.lft'))
				->where($db->quoteName('t.rgt') . ' < ' . $db->quoteName('p.rgt'));
		}

		if ($io->getOption('language') !== null)
		{
			$query->where($db->quoteName('t.language') . ' = ' . $db->quote((string) $io->getOption('language')));
		}

		if ((string) $io->getOption('search') !== '')
		{
			$query->where($db->quoteName('t.title') . ' LIKE ' . $db->quote('%' . $db->escape((string) $io->getOption('search'), true) . '%', false));
		}

		$rows = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$rows[] = array(
				'id'       => (int) $row->id,
				'title'    => $row->title,
				'path'     => $row->path,
				'level'    => (int) $row->level,
				'parentId' => (int) $row->parent_id,
				'state'    => $this->stateName($row->published),
				'access'   => (int) $row->access,
				'language' => $row->language,
				'items'    => (int) $row->items,
			);
		}

		$io->title('Tags');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching tags.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'title' => 'Title', 'path' => 'Path', 'level' => 'Level', 'state' => 'State', 'language' => 'Language',
			'access' => 'Access', 'items' => 'Items', 'parentId' => 'Parent ID'), $rows);

		return self::SUCCESS;
	}
}
