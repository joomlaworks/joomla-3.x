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
 * The News sample data set of the Sample Data plugin: the news site of the installer's News sample data (data/news.json, made
 * from the same site as installation/sql/<dialect>/sample_news.sql), added to an existing site, with a style of the Hammond
 * template for it which becomes the site's default.
 *
 * @since  3.17.0
 */
class PlgSampledataBlogNews
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
	 * The articles are added in this many steps, so that no request runs too long
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const ARTICLE_STEPS = 4;

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
	 * @param   JApplicationCms  $app  The application
	 * @param   JDatabaseDriver  $db   The database
	 *
	 * @since   3.17.0
	 */
	public function __construct($app, $db)
	{
		$this->app = $app;
		$this->db  = $db;
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
		$data->name        = 'news';
		$data->title       = JText::_('PLG_SAMPLEDATA_BLOG_NEWS_OVERVIEW_TITLE');
		$data->description = JText::_('PLG_SAMPLEDATA_BLOG_NEWS_OVERVIEW_DESC');
		$data->icon        = 'stack';
		$data->steps       = self::ARTICLE_STEPS + 3;

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
		$component = $step <= self::ARTICLE_STEPS + 1 ? 'com_content' : ($step === self::ARTICLE_STEPS + 2 ? 'com_menus' : 'com_modules');

		if (!ComponentHelper::isEnabled($component) || !Factory::getUser()->authorise('core.create', $component))
		{
			return array('success' => true, 'message' => JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STEP_SKIPPED', $step, $component));
		}

		try
		{
			if ($step === 1)
			{
				$message = $this->addCategoriesAndTags();
			}
			elseif ($step <= self::ARTICLE_STEPS + 1)
			{
				$message = $this->addArticles($step - 1);
			}
			elseif ($step === self::ARTICLE_STEPS + 2)
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
			return array('success' => false, 'message' => JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STEP_FAILED', $step, $e->getMessage()));
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
			$this->data = json_decode((string) @file_get_contents(__DIR__ . '/data/news.json'), true);

			if (!is_array($this->data))
			{
				throw new RuntimeException('data/news.json');
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
		return (array) $this->app->getUserState('sampledata.news.' . $name, array());
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

		foreach (array('', '-news', '-news-2', '-news-3') as $suffix)
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

		// Tags: the site's own when it has them already
		$tags = array();

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

		$this->app->setUserState('sampledata.news.tags', $tags);
		$this->app->setUserState('sampledata.news.categories', $categories);
		$this->app->setUserState('sampledata.news.articles', array());

		return JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STEP1_SUCCESS', count($categories), count($tags));
	}

	/**
	 * Add a part of the articles.
	 *
	 * @param   integer  $part  The part (1 to ARTICLE_STEPS)
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
			throw new RuntimeException(JText::_('PLG_SAMPLEDATA_BLOG_NEWS_NO_CATEGORIES'));
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models/', 'ContentModel');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/tables/');
		$model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));

		// As in the installer: the latest article was published 25 minutes ago, the others hours and days before it
		$latest = Factory::getDate('now')->toUnix() - 25 * 60;
		$chunk  = array_chunk($data['articles'], (int) ceil(count($data['articles']) / self::ARTICLE_STEPS));
		$chunk  = isset($chunk[$part - 1]) ? $chunk[$part - 1] : array();

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
		}

		$this->app->setUserState('sampledata.news.articles', $articles);

		return JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STEP_ARTICLES_SUCCESS', count($chunk));
	}

	/**
	 * Add the menus and the Hammond template's style for the news site.
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

		foreach ($data['menus'] as $menutype => $menu)
		{
			// A menu type of its own: newsmenu, else newsmenu2 ...
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

			foreach ($menu['items'] as $item)
			{
				$link = preg_replace_callback('/\{(category|article):([a-z0-9-]+)\}/', function ($m) use ($categories, $articles) {
					return $m[1] === 'category' ? $categories[$m[2]] : $articles[$m[2]];
				}, $item['link']);

				$this->save($model, array(
					'menutype' => $table->menutype, 'title' => $item['title'], 'alias' => $item['alias'], 'link' => $link, 'type' => 'component',
					'component_id' => $componentId, 'published' => 1, 'parent_id' => 1, 'level' => 1, 'home' => 0, 'browserNav' => 0,
					'access' => $access, 'language' => $language, 'template_style_id' => 0, 'params' => json_decode($item['params'], true),
					'note' => '', 'img' => '', 'associations' => array(), 'client_id' => 0,
				));
			}
		}

		$this->app->setUserState('sampledata.news.menus', $menus);

		// The news site's settings in a style of Hammond's own, which becomes the site's default template
		$hammond = (int) $this->db->setQuery(
			$this->db->getQuery(true)
				->select($this->db->quoteName('extension_id'))
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('template'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('hammond'))
				->where($this->db->quoteName('client_id') . ' = 0')
		)->loadResult();

		if (!$hammond)
		{
			return JText::_('PLG_SAMPLEDATA_BLOG_NEWS_STEP_MENUS_SUCCESS') . ' ' . JText::_('PLG_SAMPLEDATA_BLOG_NEWS_NO_HAMMOND');
		}

		$params              = $data['template'];
		$params['pagesMenu'] = isset($menus[$params['pagesMenu']]) ? $menus[$params['pagesMenu']] : '';
		$style               = (object) array(
			'id' => null, 'template' => 'hammond', 'client_id' => 0, 'home' => '0', 'title' => JText::_('PLG_SAMPLEDATA_BLOG_NEWS_TEMPLATE_STYLE'),
			'params' => json_encode($params),
		);
		$this->db->insertObject('#__template_styles', $style, 'id');

		if (!Factory::getUser()->authorise('core.edit.state', 'com_templates'))
		{
			return JText::_('PLG_SAMPLEDATA_BLOG_NEWS_STEP_MENUS_SUCCESS') . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STYLE_ADDED', $style->title);
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

		return JText::_('PLG_SAMPLEDATA_BLOG_NEWS_STEP_MENUS_SUCCESS') . ' ' . JText::sprintf('PLG_SAMPLEDATA_BLOG_NEWS_STYLE_DEFAULT', $style->title);
	}

	/**
	 * Add the modules, in the Hammond template's positions.
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
		}

		return JText::_('PLG_SAMPLEDATA_BLOG_NEWS_STEP_MODULES_SUCCESS');
	}
}
