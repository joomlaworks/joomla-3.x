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
 * Lists articles.
 *
 * @since  3.17.0
 */
class ArticleListCommand extends AbstractArticleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List articles';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists articles, newest first, without the trashed ones unless --state asks for them. Filter by category (with its '
		. 'subcategories), state, featured, language, author or a text in the title; page with --limit and --offset. article:get shows one.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * Sort orders => columns
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const ORDERS = array('id' => 'a.id', 'title' => 'a.title', 'created' => 'a.created', 'modified' => 'a.modified', 'hits' => 'a.hits',
		'publish_up' => 'a.publish_up');

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('category', null, self::OPTION_REQUIRED, 'Only this category and its subcategories (ID, path, alias or title)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, archived, trashed or all (default: all but trashed)');
		$this->addOption('featured', null, self::OPTION_NONE, 'Only featured articles');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'Only this language (e.g. en-GB, or * for "All")');
		$this->addOption('author', null, self::OPTION_REQUIRED, 'Only the articles of this username');
		$this->addOption('search', null, self::OPTION_REQUIRED, 'Only titles containing this text (or "id:12")');
		$this->addOption('order', null, self::OPTION_REQUIRED, 'Sort by id, title, created, modified, hits or publish_up; add " asc" for ascending', 'created');
		$this->addOption('limit', null, self::OPTION_REQUIRED, 'How many articles', 20);
		$this->addOption('offset', null, self::OPTION_REQUIRED, 'Skip this many articles', 0);
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
		$query = $db->getQuery(true)
			->select($db->quoteName(array('a.id', 'a.title', 'a.alias', 'a.state', 'a.featured', 'a.access', 'a.language', 'a.created', 'a.modified',
				'a.publish_up', 'a.hits', 'a.catid')))
			->select($db->quoteName('c.path', 'category'))
			->select($db->quoteName('u.username', 'author'))
			->from($db->quoteName('#__content', 'a'))
			->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));

		$category = $io->getOption('category');

		if ($category !== null)
		{
			if (($catid = $this->findCategory($io, $category)) === false)
			{
				return self::NOT_FOUND;
			}

			$query->join('INNER', $db->quoteName('#__categories', 'parent') . ' ON ' . $db->quoteName('parent.id') . ' = ' . (int) $catid)
				->where($db->quoteName('c.lft') . ' >= ' . $db->quoteName('parent.lft'))
				->where($db->quoteName('c.rgt') . ' <= ' . $db->quoteName('parent.rgt'));
		}

		$state = strtolower((string) $io->getOption('state'));

		if ($state === '')
		{
			$query->where($db->quoteName('a.state') . ' <> -2');
		}
		elseif ($state !== 'all')
		{
			if (($value = $this->parseState($state)) === null)
			{
				$io->error('--state must be published, unpublished, archived, trashed or all.');

				return self::INVALID;
			}

			$query->where($db->quoteName('a.state') . ' = ' . $value);
		}

		if ($io->getOption('featured'))
		{
			$query->where($db->quoteName('a.featured') . ' = 1');
		}

		if ($io->getOption('language') !== null)
		{
			$query->where($db->quoteName('a.language') . ' = ' . $db->quote((string) $io->getOption('language')));
		}

		if ($io->getOption('author') !== null)
		{
			$query->where($db->quoteName('u.username') . ' = ' . $db->quote((string) $io->getOption('author')));
		}

		$search = (string) $io->getOption('search');

		if (stripos($search, 'id:') === 0)
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
		}
		elseif ($search !== '')
		{
			$query->where($db->quoteName('a.title') . ' LIKE ' . $db->quote('%' . $db->escape($search, true) . '%', false));
		}

		$order = explode(' ', strtolower(trim((string) $io->getOption('order'))));

		if (!isset(self::ORDERS[$order[0]]) || (isset($order[1]) && !in_array($order[1], array('asc', 'desc'), true)))
		{
			$io->error('--order must be id, title, created, modified, hits or publish_up, optionally followed by asc or desc.');

			return self::INVALID;
		}

		$limit  = (int) $io->getOption('limit');
		$offset = (int) $io->getOption('offset');

		if ($limit < 1 || $offset < 0)
		{
			$io->error('--limit must be positive and --offset not negative.');

			return self::INVALID;
		}

		$count = clone $query;
		$count->clear('select')->select('COUNT(*)');
		$total = (int) $db->setQuery($count)->loadResult();

		$query->order($db->quoteName(self::ORDERS[$order[0]]) . ' ' . (isset($order[1]) ? strtoupper($order[1]) : ($order[0] === 'title' ? 'ASC' : 'DESC')))
			->order($db->quoteName('a.id') . ' DESC');

		$rows = array();

		foreach ($db->setQuery($query, $offset, $limit)->loadObjectList() as $row)
		{
			$rows[] = array(
				'id'       => (int) $row->id,
				'title'    => $row->title,
				'alias'    => $row->alias,
				'category' => $row->category,
				'state'    => $this->stateName($row->state),
				'featured' => (bool) $row->featured,
				'language' => $row->language,
				'author'   => $row->author,
				'created'  => $row->created,
				'modified' => $row->modified,
				'hits'     => (int) $row->hits,
			);
		}

		$io->title('Articles');
		$io->setData('total', $total);
		$io->setData('offset', $offset);

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No matching articles.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'title' => 'Title', 'category' => 'Category', 'state' => 'State', 'featured' => 'Featured', 'language' => 'Language',
			'author' => 'Author', 'created' => 'Created', 'modified' => 'Modified', 'alias' => 'Alias', 'hits' => 'Hits'), $rows);

		if ($total > $offset + count($rows))
		{
			$io->text(sprintf('Showing %d-%d of %d; use --offset=%d for more.', $offset + 1, $offset + count($rows), $total, $offset + count($rows)));
		}

		return self::SUCCESS;
	}
}
