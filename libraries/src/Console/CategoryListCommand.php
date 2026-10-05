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
 * Lists categories.
 *
 * @since  3.17.0
 */
class CategoryListCommand extends AbstractCategoryCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List categories';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the categories of articles (or of another component with --extension) in tree order, without the trashed ones '
		. 'unless --state asks for them, with the number of articles in each.';

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
		$this->addOption('parent', null, self::OPTION_REQUIRED, 'Only this category\'s subcategories (ID, path, alias or title)');
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
		$extension = (string) $io->getOption('extension');
		$db        = Factory::getDbo();
		$query     = $db->getQuery(true)
			->select($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.path', 'c.level', 'c.published', 'c.access', 'c.language', 'c.parent_id')))
			->from($db->quoteName('#__categories', 'c'))
			->where($db->quoteName('c.extension') . ' = ' . $db->quote($extension))
			->order($db->quoteName('c.lft'));

		if ($extension === 'com_content')
		{
			$count = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__content', 'a'))
				->where($db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id'))
				->where($db->quoteName('a.state') . ' <> -2');
			$query->select('(' . $count . ') AS ' . $db->quoteName('articles'));
		}

		$state = strtolower((string) $io->getOption('state'));

		if ($state === '')
		{
			$query->where($db->quoteName('c.published') . ' <> -2');
		}
		elseif ($state !== 'all')
		{
			if (($value = $this->parseState($state)) === null)
			{
				$io->error('--state must be published, unpublished, archived, trashed or all.');

				return self::INVALID;
			}

			$query->where($db->quoteName('c.published') . ' = ' . $value);
		}

		if ($io->getOption('parent') !== null)
		{
			if (($parent = $this->findCategory($io, $io->getOption('parent'), $extension)) === false)
			{
				return self::NOT_FOUND;
			}

			$query->join('INNER', $db->quoteName('#__categories', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . (int) $parent)
				->where($db->quoteName('c.lft') . ' > ' . $db->quoteName('p.lft'))
				->where($db->quoteName('c.rgt') . ' < ' . $db->quoteName('p.rgt'));
		}

		if ($io->getOption('language') !== null)
		{
			$query->where($db->quoteName('c.language') . ' = ' . $db->quote((string) $io->getOption('language')));
		}

		if ((string) $io->getOption('search') !== '')
		{
			$query->where($db->quoteName('c.title') . ' LIKE ' . $db->quote('%' . $db->escape((string) $io->getOption('search'), true) . '%', false));
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
				'articles' => isset($row->articles) ? (int) $row->articles : null,
			);
		}

		$io->title('Categories (' . $extension . ')');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching categories.');

			return self::SUCCESS;
		}

		$columns = array('id' => 'ID', 'title' => 'Title', 'path' => 'Path', 'level' => 'Level', 'state' => 'State', 'language' => 'Language', 'access' => 'Access');

		if ($extension === 'com_content')
		{
			$columns['articles'] = 'Articles';
		}

		$columns['parentId'] = 'Parent ID';
		$io->table($columns, $rows);

		return self::SUCCESS;
	}
}
