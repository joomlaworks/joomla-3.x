<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Sampledata.Blog
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;

/**
 * A sample data set of the Sample Data plugin (News, Blog): the content of the installer's set (data/<set>.json, made from the
 * same site as installation/sql/<dialect>/sample_<set>.sql, by docs/sample-data/build/build.sh), added to an existing site,
 * with a style of the set's template (Hammond, Finch) which becomes the site's default.
 *
 * The plugin records what the installed set added (the installer records its own set too), and installing a set first removes
 * the recorded one: the site holds one set at a time, never two mixed. The site's own content is never removed.
 *
 * @since  3.17.0
 */
class PlgSampledataBlogSet
{
	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  3.17.0
	 */
	protected $db;

	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.17.0
	 */
	protected $app;

	/**
	 * The sets: their template and the number of steps adding their articles, so that no request runs too long
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const SETS = array(
		'news' => array('template' => 'hammond', 'articleSteps' => 4),
		'blog' => array('template' => 'finch', 'articleSteps' => 1),
	);

	/**
	 * The record's lists of IDs (menutypes: names)
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const RECORD_LISTS = array('categories', 'tags', 'articles', 'menutypes', 'menuitems', 'modules', 'styles');

	/**
	 * The set: news or blog
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	private $name;

	/**
	 * The set's template
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	private $template;

	/**
	 * The steps adding articles
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	private $articleSteps;

	/**
	 * The sample data
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param   JApplicationCms  $app   The application
	 * @param   JDatabaseDriver  $db    The database
	 * @param   string           $name  The set (a key of SETS)
	 *
	 * @since   3.17.0
	 */
	public function __construct($app, $db, $name)
	{
		$this->app          = $app;
		$this->db           = $db;
		$this->name         = $name;
		$this->template     = self::SETS[$name]['template'];
		$this->articleSteps = self::SETS[$name]['articleSteps'];
	}

	/**
	 * The overview of the set, for the Sample Data module.
	 *
	 * @return  stdClass
	 *
	 * @since   3.17.0
	 */
	public function getOverview()
	{
		$data              = new stdClass;
		$data->name        = $this->name;
		$data->title       = JText::_('PLG_SAMPLEDATA_BLOG_' . strtoupper($this->name) . '_OVERVIEW_TITLE');
		$data->description = JText::_('PLG_SAMPLEDATA_BLOG_' . strtoupper($this->name) . '_OVERVIEW_DESC');
		$data->icon        = $this->name === 'news' ? 'stack' : 'pencil-2';
		$data->steps       = $this->articleSteps + 4;
		$record            = self::getRecord($this->db);
		$data->installed   = $record !== null && $record['set'] === $this->name;

		return $data;
	}

	/**
	 * Run a step.
	 *
	 * @param   integer  $step  The step
	 *
	 * @return  array  The JSON response to the module
	 *
	 * @since   3.17.0
	 */
	public function step($step)
	{
		$component = $step <= $this->articleSteps + 2 ? 'com_content' : ($step === $this->articleSteps + 3 ? 'com_menus' : 'com_modules');

		if ($step > 1 && (!ComponentHelper::isEnabled($component) || !Factory::getUser()->authorise('core.create', $component)))
		{
			return array('success' => true, 'message' => JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_SKIPPED', $step, $component));
		}

		try
		{
			if ($step === 1)
			{
				$message = $this->removeInstalled();
			}
			elseif ($step === 2)
			{
				$message = $this->addCategoriesAndTags();
			}
			elseif ($step <= $this->articleSteps + 2)
			{
				$message = $this->addArticles($step - 2);
			}
			elseif ($step === $this->articleSteps + 3)
			{
				$message = $this->addMenus();
			}
			else
			{
				$message = $this->addModules();
			}
		}
		catch (Exception $e)
		{
			return array('success' => false, 'message' => JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_FAILED', $step, $e->getMessage()));
		}

		return array('success' => true, 'message' => $message);
	}

	/**
	 * The sample data.
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	private function getData()
	{
		if ($this->data === null)
		{
			$this->data = json_decode((string) @file_get_contents(__DIR__ . '/data/' . $this->name . '.json'), true);

			if (!is_array($this->data))
			{
				throw new RuntimeException('data/' . $this->name . '.json');
			}
		}

		return $this->data;
	}

	/**
	 * What steps before this one added: aliases => IDs.
	 *
	 * @param   string  $name  categories, tags, articles or menus
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function getMap($name)
	{
		return (array) $this->app->getUserState('sampledata.' . $this->name . '.' . $name, array());
	}

	/**
	 * The language of the new items: the current one on a multilingual site.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function getItemLanguage()
	{
		return Multilanguage::isEnabled() ? Factory::getLanguage()->getTag() : '*';
	}

	/**
	 * Save with a model, trying other aliases when the alias is taken.
	 *
	 * @param   JModelAdmin  $model  The model
	 * @param   array        $data   The item
	 *
	 * @return  integer  The new item's ID
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	private function save($model, array $data)
	{
		$alias = $data['alias'];

		foreach (array('', '-' . $this->name, '-' . $this->name . '-2', '-' . $this->name . '-3') as $suffix)
		{
			// The model takes an empty ID for "the item saved last": always start a new one
			$model->setState($model->getName() . '.id', 0);
			$data['id']    = 0;
			$data['alias'] = $alias . $suffix;

			if ($model->save($data))
			{
				return (int) $model->getState($model->getName() . '.id');
			}
		}

		throw new RuntimeException($data['title'] . ': ' . JText::_($model->getError()));
	}

	/**
	 * Add the tags and the categories.
	 *
	 * @return  string  The step's message
	 *
	 * @since   3.17.0
	 */
	private function addCategoriesAndTags()
	{
		$data     = $this->getData();
		$user     = Factory::getUser();
		$access   = (int) $this->app->get('access', 1);
		$language = $this->getItemLanguage();

		// A new record: the site's default template style now is the one to go back to when the set's style is removed
		self::saveRecord($this->db, array('set' => $this->name, 'previousStyle' => $this->getDefaultStyle()));

		// Tags: the site's own when it has them already (those are never removed with the set)
		$tags    = array();
		$created = array();

		if (ComponentHelper::isEnabled('com_tags') && $user->authorise('core.create', 'com_tags'))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');

			foreach ($data['tags'] as $tag)
			{
				$table = JTable::getInstance('Tag', 'TagsTable');

				if (!$table->load(array('alias' => $tag['alias'])))
				{
					$table->reset();
					$table->id = 0;
					$table->setLocation(1, 'last-child');
					$table->bind(array(
						'title' => $tag['title'], 'alias' => $tag['alias'], 'published' => 1, 'access' => $access, 'language' => $language,
						'params' => '{}', 'metadata' => '{}', 'urls' => '{}', 'images' => '{}', 'description' => '', 'note' => '',
						'created_user_id' => $user->id, 'created_time' => Factory::getDate()->toSql(),
					));

					if (!$table->check() || !$table->store())
					{
						throw new RuntimeException($tag['title'] . ': ' . $table->getError());
					}

					$table->rebuildPath($table->id);
					$created[] = (int) $table->id;
				}

				$tags[$tag['alias']] = (string) $table->id;
			}
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');
		$model      = JModelLegacy::getInstance('Category', 'CategoriesModel', array('ignore_request' => true));
		$categories = array();

		foreach ($data['categories'] as $category)
		{
			$categories[$category['alias']] = $this->save($model, array(
				'title' => $category['title'], 'alias' => $category['alias'], 'description' => $category['description'],
				'parent_id' => 1, 'extension' => 'com_content', 'published' => 1, 'access' => $access, 'language' => $language,
				'params' => json_decode($category['params'], true), 'metadata' => json_decode($category['metadata'], true),
				'created_user_id' => $user->id, 'associations' => array(), 'note' => '', 'metadesc' => '', 'metakey' => '',
			));
		}

		$this->addToRecord('tags', $created);
		$this->addToRecord('categories', array_values($categories));

		$this->app->setUserState('sampledata.' . $this->name . '.tags', $tags);
		$this->app->setUserState('sampledata.' . $this->name . '.categories', $categories);
		$this->app->setUserState('sampledata.' . $this->name . '.articles', array());

		return JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP1_SUCCESS', count($categories), count($tags));
	}

	/**
	 * Add a part of the articles.
	 *
	 * @param   integer  $part  The part (1 to the set's article steps)
	 *
	 * @return  string  The step's message
	 *
	 * @since   3.17.0
	 */
	private function addArticles($part)
	{
		$data       = $this->getData();
		$user       = Factory::getUser();
		$access     = (int) $this->app->get('access', 1);
		$language   = $this->getItemLanguage();
		$categories = $this->getMap('categories');
		$tags       = $this->getMap('tags');
		$articles   = $this->getMap('articles');

		if (!$categories)
		{
			throw new RuntimeException(JText::_('PLG_SAMPLEDATA_BLOG_SET_NO_CATEGORIES'));
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models/', 'ContentModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/tables/');
		$model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));

		// As in the installer: the latest article was published 25 minutes ago, the others hours and days before it
		$latest = Factory::getDate('now')->toUnix() - 25 * 60;
		$chunk  = array_chunk($data['articles'], (int) ceil(count($data['articles']) / $this->articleSteps));
		$chunk  = isset($chunk[$part - 1]) ? $chunk[$part - 1] : array();
		$added  = array();

		foreach ($chunk as $article)
		{
			$date = Factory::getDate($latest - (int) $article['age'])->toSql();
			$id   = $this->save($model, array(
				'title' => $article['title'], 'alias' => $article['alias'], 'catid' => $categories[$article['category']],
				'introtext' => $article['introtext'], 'fulltext' => $article['fulltext'], 'state' => $article['state'],
				'featured' => $article['featured'], 'publish_up' => $date, 'created' => $date, 'created_by' => $user->id,
				'created_by_alias' => $article['created_by_alias'], 'images' => json_decode($article['images'], true),
				'urls' => json_decode($article['urls'], true), 'attribs' => json_decode($article['attribs'], true),
				'metadata' => json_decode($article['metadata'], true), 'metadesc' => $article['metadesc'], 'metakey' => $article['metakey'],
				'access' => $access, 'language' => $language, 'associations' => array(), 'xreference' => '',
				'tags' => array_values(array_intersect_key($tags, array_flip($article['tags']))),
			));

			if ($article['hits'])
			{
				$this->db->setQuery(
					$this->db->getQuery(true)
						->update($this->db->quoteName('#__content'))
						->set($this->db->quoteName('hits') . ' = ' . (int) $article['hits'])
						->where($this->db->quoteName('id') . ' = ' . $id)
				)->execute();
			}

			$articles[$article['alias']] = $id;
			$added[]                     = $id;
		}

		$this->addToRecord('articles', $added);
		$this->app->setUserState('sampledata.' . $this->name . '.articles', $articles);

		return JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_ARTICLES_SUCCESS', count($chunk));
	}

	/**
	 * Add the menus and the set's style of its template.
	 *
	 * @return  string  The step's message
	 *
	 * @since   3.17.0
	 */
	private function addMenus()
	{
		$data       = $this->getData();
		$categories = $this->getMap('categories');
		$articles   = $this->getMap('articles');
		$language   = $this->getItemLanguage();
		$access     = (int) $this->app->get('access', 1);
		$menus      = array();

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/models/', 'MenusModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables/');
		$model       = JModelLegacy::getInstance('Item', 'MenusModel', array('ignore_request' => true));
		$componentId = ComponentHelper::getComponent('com_content')->id;
		$items       = array();

		foreach ($data['menus'] as $menutype => $menu)
		{
			// A menu type of its own: e.g. newsmenu, else newsmenu2 ...
			$table = JTable::getInstance('Type', 'JTableMenu');

			for ($i = 1; $table->load(array('menutype' => $menutype . ($i > 1 ? $i : ''))); $i++)
			{
				$table->reset();
			}

			$table->reset();
			$table->id = 0;
			$table->bind(array('menutype' => $menutype . ($i > 1 ? $i : ''), 'title' => $menu['title'], 'description' => $menu['description'], 'client_id' => 0));

			if (!$table->check() || !$table->store())
			{
				throw new RuntimeException($menu['title'] . ': ' . $table->getError());
			}

			$menus[$menutype] = $table->menutype;
			$this->addToRecord('menutypes', array($table->menutype));

			foreach ($menu['items'] as $item)
			{
				$link = preg_replace_callback('/\{(category|article):([a-z0-9-]+)\}/', function ($m) use ($categories, $articles) {
					return $m[1] === 'category' ? $categories[$m[2]] : $articles[$m[2]];
				}, $item['link']);

				$items[] = $this->save($model, array(
					'menutype' => $table->menutype, 'title' => $item['title'], 'alias' => $item['alias'], 'link' => $link, 'type' => 'component',
					'component_id' => $componentId, 'published' => 1, 'parent_id' => 1, 'level' => 1, 'home' => 0, 'browserNav' => 0,
					'access' => $access, 'language' => $language, 'template_style_id' => 0, 'params' => json_decode($item['params'], true),
					'note' => '', 'img' => '', 'associations' => array(), 'client_id' => 0,
				));
			}
		}

		$this->addToRecord('menuitems', $items);
		$this->app->setUserState('sampledata.' . $this->name . '.menus', $menus);

		// The set's settings in a style of its template, which becomes the site's default template
		$installed = (int) $this->db->setQuery(
			$this->db->getQuery(true)
				->select($this->db->quoteName('extension_id'))
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('template'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->template))
				->where($this->db->quoteName('client_id') . ' = 0')
		)->loadResult();

		$menusAdded = JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_MENUS_SUCCESS', implode(', ', array_map(function ($menu)
		{
			return $menu['title'];
		}, $data['menus'])));

		if (!$installed)
		{
			return $menusAdded . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_NO_TEMPLATE', ucfirst($this->template));
		}

		$params              = $data['template'];
		$params['pagesMenu'] = isset($menus[$params['pagesMenu']]) ? $menus[$params['pagesMenu']] : '';
		$style               = (object) array(
			'id' => null, 'template' => $this->template, 'client_id' => 0, 'home' => '0',
			'title' => JText::_('PLG_SAMPLEDATA_BLOG_' . strtoupper($this->name) . '_TEMPLATE_STYLE'),
			'params' => json_encode($params),
		);
		$this->db->insertObject('#__template_styles', $style, 'id');
		$this->addToRecord('styles', array((int) $style->id));

		if (!Factory::getUser()->authorise('core.edit.state', 'com_templates'))
		{
			return $menusAdded . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STYLE_ADDED', $style->title);
		}

		// The site's default style (of all languages: "1") becomes this one
		$this->db->setQuery(
			$this->db->getQuery(true)
				->update($this->db->quoteName('#__template_styles'))
				->set($this->db->quoteName('home') . ' = ' . $this->db->quote('0'))
				->where($this->db->quoteName('client_id') . ' = 0')
				->where($this->db->quoteName('home') . ' = ' . $this->db->quote('1'))
		)->execute();
		$this->db->setQuery(
			$this->db->getQuery(true)
				->update($this->db->quoteName('#__template_styles'))
				->set($this->db->quoteName('home') . ' = ' . $this->db->quote('1'))
				->where($this->db->quoteName('id') . ' = ' . (int) $style->id)
		)->execute();

		return $menusAdded . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STYLE_DEFAULT', $style->title);
	}

	/**
	 * Add the modules, in the positions of the set's template.
	 *
	 * @return  string  The step's message
	 *
	 * @since   3.17.0
	 */
	private function addModules()
	{
		$data       = $this->getData();
		$categories = $this->getMap('categories');
		$menus      = $this->getMap('menus');
		$language   = $this->getItemLanguage();
		$access     = (int) $this->app->get('access', 1);

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/models/', 'ModulesModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/tables/');
		$model = JModelLegacy::getInstance('Module', 'ModulesModel', array('ignore_request' => true));

		foreach ($data['modules'] as $module)
		{
			$params = $module['params'];

			if (isset($params['menutype']))
			{
				// Without the menus (no permission for them), menu modules have nothing to show
				if (!isset($menus[$params['menutype']]))
				{
					continue;
				}

				$params['menutype'] = $menus[$params['menutype']];
			}

			if (!empty($params['catid']))
			{
				$params['catid'] = array_values(array_filter(array_map(function ($alias) use ($categories) {
					$alias = trim($alias, '{}');
					$alias = substr($alias, strpos($alias, ':') + 1);

					return isset($categories[$alias]) ? $categories[$alias] : 0;
				}, $params['catid'])));
			}

			$model->setState($model->getName() . '.id', 0);

			$saved = $model->save(array(
				'id' => 0, 'asset_id' => 0, 'title' => $module['title'], 'note' => '', 'content' => $module['content'],
				'position' => $module['position'], 'module' => $module['module'], 'showtitle' => $module['showtitle'],
				'ordering' => $module['ordering'], 'params' => $params, 'published' => 1, 'access' => $access, 'language' => $language,
				'client_id' => 0, 'assignment' => 0, 'assigned' => array(),
			));

			if (!$saved)
			{
				throw new RuntimeException($module['title'] . ': ' . JText::_($model->getError()));
			}

			$this->addToRecord('modules', array((int) $model->getState($model->getName() . '.id')));
		}

		return JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_MODULES_SUCCESS', ucfirst($this->template));
	}

	/**
	 * The record of the installed set: its name and the IDs of what it added. Kept in the plugin's parameters, in a hidden
	 * field of its form, so that saving the plugin keeps it.
	 *
	 * @param   JDatabaseDriver  $db  The database
	 *
	 * @return  array|null  set, previousStyle and the RECORD_LISTS; null when no set is recorded
	 *
	 * @since   3.17.0
	 */
	public static function getRecord($db)
	{
		$params = json_decode((string) $db->setQuery(self::getPluginQuery($db)->select($db->quoteName('params')))->loadResult(), true);
		$record = is_array($params) && isset($params['installed']) ? json_decode((string) $params['installed'], true) : null;

		if (!is_array($record) || !isset(self::SETS[isset($record['set']) ? $record['set'] : '']))
		{
			return null;
		}

		foreach (self::RECORD_LISTS as $list)
		{
			$record[$list] = isset($record[$list]) ? array_values(array_unique((array) $record[$list])) : array();
		}

		$record['previousStyle'] = isset($record['previousStyle']) ? (int) $record['previousStyle'] : 0;

		return $record;
	}

	/**
	 * Save the record of the installed set.
	 *
	 * @param   JDatabaseDriver  $db      The database
	 * @param   array|null       $record  The record; null for none
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function saveRecord($db, $record)
	{
		$params = json_decode((string) $db->setQuery(self::getPluginQuery($db)->select($db->quoteName('params')))->loadResult(), true);
		$params = is_array($params) ? $params : array();

		$params['installed'] = $record === null ? '' : json_encode($record);

		$db->setQuery(
			self::getPluginQuery($db)
				->clear('from')
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
		)->execute();
	}

	/**
	 * A query on the plugin's row of #__extensions.
	 *
	 * @param   JDatabaseDriver  $db  The database
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   3.17.0
	 */
	private static function getPluginQuery($db)
	{
		return $db->getQuery(true)
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('sampledata'))
			->where($db->quoteName('element') . ' = ' . $db->quote('blog'));
	}

	/**
	 * Add what a step added to the record.
	 *
	 * @param   string  $list    One of RECORD_LISTS
	 * @param   array   $values  The IDs (menu types: names)
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function addToRecord($list, array $values)
	{
		$record = self::getRecord($this->db);

		// Steps run in order, the second starting the record: without it, the steps were run out of order
		if ($record === null || $record['set'] !== $this->name)
		{
			throw new RuntimeException(JText::_('PLG_SAMPLEDATA_BLOG_SET_NO_CATEGORIES'));
		}

		$record[$list] = array_values(array_unique(array_merge($record[$list], $values)));

		self::saveRecord($this->db, $record);
	}

	/**
	 * The site's default template style (of all languages).
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function getDefaultStyle()
	{
		return (int) $this->db->setQuery(
			$this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__template_styles'))
				->where($this->db->quoteName('client_id') . ' = 0')
				->where($this->db->quoteName('home') . ' = ' . $this->db->quote('1'))
		)->loadResult();
	}

	/**
	 * Record the set the installer has just installed (on a new site, so everything matching the set is the set's).
	 *
	 * @param   JDatabaseDriver  $db    The database
	 * @param   string           $name  The set
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function recordInstallerSet($db, $name)
	{
		$data = isset(self::SETS[$name]) ? json_decode((string) @file_get_contents(__DIR__ . '/data/' . $name . '.json'), true) : null;

		if (!is_array($data))
		{
			return;
		}

		$quote = function ($values) use ($db)
		{
			return implode(',', array_map(array($db, 'quote'), $values ?: array('')));
		};

		$record = array('set' => $name, 'previousStyle' => 0);

		$record['categories'] = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
				->where($db->quoteName('parent_id') . ' = 1')
				->where($db->quoteName('alias') . ' IN (' . $quote(array_column($data['categories'], 'alias')) . ')')
		)->loadColumn();

		$record['articles'] = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__content'))
				->where($db->quoteName('catid') . ' IN (' . implode(',', array_map('intval', $record['categories'] ?: array(0))) . ')')
		)->loadColumn();

		$record['tags'] = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__tags'))
				->where($db->quoteName('id') . ' > 1')
				->where($db->quoteName('alias') . ' IN (' . $quote(array_column($data['tags'], 'alias')) . ')')
		)->loadColumn();

		// The menu items (never the home page, which a new site has without sample data too) and the menus holding them
		$aliases = array();

		foreach ($data['menus'] as $menu)
		{
			$aliases = array_merge($aliases, array_column($menu['items'], 'alias'));
		}

		$items = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName(array('id', 'menutype')))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('client_id') . ' = 0')
				->where($db->quoteName('home') . ' = 0')
				->where($db->quoteName('alias') . ' IN (' . $quote($aliases) . ')')
		)->loadObjectList();

		$record['menuitems'] = array_column($items, 'id');
		$record['menutypes'] = array_values(array_unique(array_column($items, 'menutype')));

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__modules'))
			->where($db->quoteName('client_id') . ' = 0');
		$where = array();

		foreach ($data['modules'] as $module)
		{
			$where[] = '(' . $db->quoteName('module') . ' = ' . $db->quote($module['module']) . ' AND ' . $db->quoteName('title') . ' = '
				. $db->quote($module['title']) . ' AND ' . $db->quoteName('position') . ' = ' . $db->quote($module['position']) . ')';
		}

		$record['modules'] = $where ? $db->setQuery($query->where('(' . implode(' OR ', $where) . ')'))->loadColumn() : array();
		$record['styles']  = array();

		foreach (self::RECORD_LISTS as $list)
		{
			if ($list !== 'menutypes')
			{
				$record[$list] = array_map('intval', $record[$list]);
			}
		}

		self::saveRecord($db, $record);
	}

	/**
	 * Remove the recorded set: everything it added, except categories and tags other content still uses.
	 *
	 * @return  string  The step's message
	 *
	 * @since   3.17.0
	 */
	private function removeInstalled()
	{
		$record = self::getRecord($this->db);

		if ($record === null)
		{
			return JText::_('PLG_SAMPLEDATA_BLOG_SET_STEP_NOTHING_REMOVED');
		}

		$db      = $this->db;
		$ints    = function ($values)
		{
			return implode(',', array_map('intval', $values ?: array(0)));
		};
		$count   = array_fill_keys(self::RECORD_LISTS, 0);
		$kept    = 0;
		$context = array('articles' => 'com_content.article', 'categories' => 'com_categories.category');

		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
		JPluginHelper::importPlugin('content');
		$dispatcher = JEventDispatcher::getInstance();

		$delete = function ($table, $list, $id) use (&$count, $dispatcher, $context)
		{
			if (!$table->load($id))
			{
				return;
			}

			if (!$table->delete($id))
			{
				throw new RuntimeException($table->getError());
			}

			$count[$list]++;

			// E.g. Smart Search removes them from its index
			if (isset($context[$list]))
			{
				$dispatcher->trigger('onContentAfterDelete', array($context[$list], $table));
			}
		};

		// Modules, and their menu assignments
		$table = JTable::getInstance('Module', 'JTable');

		foreach ($record['modules'] as $id)
		{
			$delete($table, 'modules', $id);
		}

		$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__modules_menu'))
			->where($db->quoteName('moduleid') . ' IN (' . $ints($record['modules']) . ')'))->execute();

		// Menu items, the assignments of modules to them, then the set's menus when nothing else is left in them
		$table = JTable::getInstance('Menu', 'JTable');

		foreach ($record['menuitems'] as $id)
		{
			$delete($table, 'menuitems', $id);
		}

		$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__modules_menu'))
			->where('ABS(' . $db->quoteName('menuid') . ') IN (' . $ints($record['menuitems']) . ')'))->execute();

		foreach ($record['menutypes'] as $menutype)
		{
			$items = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__menu'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype)))->loadResult();
			$table = JTable::getInstance('MenuType', 'JTable');

			if ($items === 0 && $table->load(array('menutype' => $menutype)))
			{
				$delete($table, 'menutypes', $table->id);
			}
		}

		// Articles
		$table = JTable::getInstance('Content', 'JTable');

		foreach ($record['articles'] as $id)
		{
			$delete($table, 'articles', $id);
		}

		foreach (array('#__content_frontpage', '#__content_rating') as $name)
		{
			$db->setQuery($db->getQuery(true)->delete($db->quoteName($name))
				->where($db->quoteName('content_id') . ' IN (' . $ints($record['articles']) . ')'))->execute();
		}

		// Categories and tags, unless other content still uses them
		$table = JTable::getInstance('Category', 'JTable');

		foreach ($record['categories'] as $id)
		{
			$used = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__content'))
				->where($db->quoteName('catid') . ' = ' . (int) $id))->loadResult()
				+ (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__categories'))
				->where($db->quoteName('parent_id') . ' = ' . (int) $id))->loadResult();

			if ($used)
			{
				$kept++;

				continue;
			}

			$delete($table, 'categories', $id);
		}

		$table = JTable::getInstance('Tag', 'TagsTable');

		foreach ($record['tags'] as $id)
		{
			$used = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__contentitem_tag_map'))
				->where($db->quoteName('tag_id') . ' = ' . (int) $id))->loadResult();

			if ($used)
			{
				$kept++;

				continue;
			}

			$delete($table, 'tags', $id);
		}

		// The set's template style; when it's the site's default, the one before it (or the oldest left) takes its place
		foreach ($record['styles'] as $id)
		{
			$style = $db->setQuery($db->getQuery(true)->select($db->quoteName(array('id', 'home')))->from($db->quoteName('#__template_styles'))
				->where($db->quoteName('id') . ' = ' . (int) $id)->where($db->quoteName('client_id') . ' = 0'))->loadObject();

			if (!$style)
			{
				continue;
			}

			if ($style->home !== '0')
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__template_styles'))
					->where($db->quoteName('client_id') . ' = 0')
					->where($db->quoteName('id') . ' NOT IN (' . $ints($record['styles']) . ')')
					->order('CASE WHEN ' . $db->quoteName('id') . ' = ' . $record['previousStyle'] . ' THEN 0 ELSE 1 END, ' . $db->quoteName('id'));
				$home  = (int) $db->setQuery($query, 0, 1)->loadResult();

				if ($home)
				{
					$db->setQuery($db->getQuery(true)->update($db->quoteName('#__template_styles'))
						->set($db->quoteName('home') . ' = ' . $db->quote($style->home))->where($db->quoteName('id') . ' = ' . $home))->execute();
				}
			}

			$db->setQuery($db->getQuery(true)->update($db->quoteName('#__menu'))->set($db->quoteName('template_style_id') . ' = 0')
				->where($db->quoteName('template_style_id') . ' = ' . (int) $id))->execute();
			$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__template_styles'))
				->where($db->quoteName('id') . ' = ' . (int) $id))->execute();
			$count['styles']++;
		}

		self::saveRecord($db, null);
		$this->app->setUserState('sampledata', null);

		$message = JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_REMOVED', JText::_('PLG_SAMPLEDATA_BLOG_' . strtoupper($record['set']) . '_OVERVIEW_TITLE'),
			$count['articles'], $count['categories'], $count['tags'], $count['menutypes'], $count['menuitems'], $count['modules'], $count['styles']);

		return $kept ? $message . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_SET_STEP_KEPT', $kept) : $message;
	}
}
