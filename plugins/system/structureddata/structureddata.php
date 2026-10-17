<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.structureddata
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Structured data (schema.org JSON-LD), Open Graph and X (Twitter) card tags for the site's pages.
 *
 * Articles, categories, tags, the archive and search results get tags of their own; every other page gets the site's (its
 * name, description, image, publisher). The type of an article's structured data comes from its category (or a parent
 * category), else its first tag with one, else the plugin's option. Tags another extension already set (e.g. K2's) are
 * left alone: a family of tags (Open Graph, X, JSON-LD) is added only when the page has none of it yet.
 *
 * Other plugins can add to or change what's output: before it's written, "onStructuredDataBeforeRender" is triggered with
 * the page's context (e.g. "com_content.article") and an object holding the page's description ($data->page) and the tags
 * as arrays: $data->opengraph and $data->x (name => content, an array for repeated tags), $data->graph (the JSON-LD
 * nodes). A family left empty, or already set by another extension, isn't written.
 *
 * @since  3.18.0
 */
class PlgSystemStructureddata extends CMSPlugin
{
	/**
	 * @var    boolean
	 * @since  3.18.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The types an article can be: schema.org name => language string suffix
	 *
	 * @var    string[]
	 * @since  3.18.0
	 */
	const TYPES = array(
		'Article', 'NewsArticle', 'BlogPosting', 'OpinionNewsArticle', 'AnalysisNewsArticle', 'ReportageNewsArticle', 'TechArticle',
		'ScholarlyArticle', 'Report', 'Review', 'Product', 'Service', 'Course', 'Recipe', 'SoftwareApplication', 'WebPage',
	);

	/**
	 * The article types (headline, sections, word count)
	 *
	 * @var    string[]
	 * @since  3.18.0
	 */
	const ARTICLE_TYPES = array(
		'Article', 'NewsArticle', 'BlogPosting', 'OpinionNewsArticle', 'AnalysisNewsArticle', 'ReportageNewsArticle', 'TechArticle',
		'ScholarlyArticle', 'Report',
	);

	/**
	 * Image sizes read in this request: absolute URL => array(width, height) or false
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	private $imageSizes = array();

	/**
	 * Add the type field to Articles' categories and to tags.
	 *
	 * @param   JForm  $form  The form
	 * @param   mixed  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!$form instanceof JForm || !in_array($form->getName(), array('com_categories.categorycom_content', 'com_tags.tag'), true))
		{
			return true;
		}

		$options = '<option value="">PLG_SYSTEM_STRUCTUREDDATA_TYPE_INHERIT</option>';

		foreach (static::TYPES as $type)
		{
			$options .= '<option value="' . $type . '">PLG_SYSTEM_STRUCTUREDDATA_TYPE_' . strtoupper($type) . '</option>';
		}

		$form->load('<form><fields name="params"><fieldset name="structureddata" label="PLG_SYSTEM_STRUCTUREDDATA_FIELDSET_ITEM">'
			. '<field name="structureddata_type" type="list" default="" filter="cmd" label="PLG_SYSTEM_STRUCTUREDDATA_ITEM_TYPE_LABEL"'
			. ' description="' . ($form->getName() === 'com_tags.tag' ? 'PLG_SYSTEM_STRUCTUREDDATA_TAG_TYPE_DESC' : 'PLG_SYSTEM_STRUCTUREDDATA_CATEGORY_TYPE_DESC') . '">'
			. $options . '</field></fieldset></fields></form>');

		return true;
	}

	/**
	 * Add the tags to the page's head, once the component and the modules have set theirs.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onBeforeCompileHead()
	{
		$app = Factory::getApplication();
		$doc = Factory::getDocument();

		if (!$app->isClient('site') || !$doc instanceof JDocumentHtml || $app->input->get('tmpl') === 'component')
		{
			return;
		}

		// Never let a page fail for its metadata
		try
		{
			$page = $this->describePage($app, $doc);
			$data = (object) array(
				'page'      => $page,
				'opengraph' => $this->params->get('opengraph', 1) && !$this->hasTags($doc, 'og:') ? $this->openGraphTags($page) : array(),
				'x'         => $this->params->get('xcards', 1) && !$this->hasTags($doc, 'twitter:') ? $this->xCardTags($page) : array(),
				'graph'     => $this->hasJsonLd($doc) ? array() : $this->buildGraph($app, $page),
			);
		}
		catch (Throwable $e)
		{
			return;
		}

		// Other plugins may add their own properties or change these
		try
		{
			$app->triggerEvent('onStructuredDataBeforeRender', array($page->context, $data));
		}
		catch (Throwable $e)
		{
			// A failing extension doesn't take the tags down with it
		}

		foreach (array('opengraph' => 'property', 'x' => 'name') as $family => $attribute)
		{
			foreach ((array) $data->$family as $name => $content)
			{
				$content = is_array($content) ? array_values(array_filter(array_map('strval', $content), 'strlen')) : (string) $content;

				if ($content !== array() && $content !== '')
				{
					$doc->setMetaData((string) $name, $content, $attribute);
				}
			}
		}

		// Search engines may show large image previews (e.g. Google Discover), whatever else the page's robots meta says
		if ($this->params->get('max_image_preview', 1))
		{
			$robots = trim((string) $doc->getMetaData('robots'));

			if (stripos($robots, 'max-image-preview') === false)
			{
				$doc->setMetaData('robots', ($robots !== '' ? rtrim($robots, ', ') . ', ' : '') . 'max-image-preview:large');
			}
		}

		if ($data->graph)
		{
			$json = json_encode(array('@context' => 'https://schema.org', '@graph' => array_values((array) $data->graph)),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

			if ($json !== false)
			{
				$doc->addCustomTag('<script type="application/ld+json">' . $json . '</script>');
			}
		}
	}

	/**
	 * What the page is and what its tags say: its kind (article, collection, search, site), title, description, image, URL, and
	 * for an article its dates, author, category, tags, type and rating.
	 *
	 * @param   JApplicationSite  $app  The application
	 * @param   JDocumentHtml     $doc  The page
	 *
	 * @return  stdClass
	 *
	 * @since   3.18.0
	 */
	protected function describePage($app, $doc)
	{
		$input  = $app->input;
		$option = $input->getCmd('option');
		$view   = $input->getCmd('view');
		$page   = (object) array(
			'context'     => $option . ($view !== '' ? '.' . $view : ''),
			'kind'        => $this->isHome($app) ? 'home' : 'site',
			'title'       => $this->pageTitle((string) $doc->getTitle()),
			'description' => trim((string) $doc->getDescription()) ?: $this->siteDescription(),
			'image'       => $this->defaultImage(),
			'url'         => $this->currentUrl(),
			'article'     => null,
		);

		if ($option === 'com_content' && $view === 'article' && $input->getInt('id'))
		{
			$article = $this->loadArticle($input->getInt('id'));

			if ($article)
			{
				$page->kind        = 'article';
				$page->article     = $article;
				$page->title       = $article->title;
				$page->description = $article->description ?: $page->description;
				$page->image       = $article->image ?: $page->image;
				$page->url         = $article->url;
			}
		}
		elseif ($option === 'com_content' && $view === 'category' && $input->getInt('id'))
		{
			$category = JCategories::getInstance('Content')->get($input->getInt('id'));

			if ($category && (int) $category->published === 1)
			{
				$params            = new Registry($category->params);
				$page->kind        = 'collection';
				$page->title       = $category->title;
				$page->description = trim((string) $category->metadesc) ?: ($this->excerpt($category->description) ?: $page->description);
				$page->image       = $this->image($params->get('image'), $params->get('image_alt') ?: $category->title) ?: $page->image;
				$page->url         = $this->route(ContentHelperRoute::getCategoryRoute($category->id, $category->language));
			}
		}
		elseif ($option === 'com_tags' && $view === 'tag')
		{
			$ids = array_filter(array_map('intval', (array) $input->get('id', array(), 'array')));
			$tag = $ids ? $this->loadTag((int) reset($ids)) : null;

			if ($tag)
			{
				$images            = new Registry($tag->images);
				$page->kind        = 'collection';
				$page->title       = $tag->title;
				$page->description = trim((string) $tag->metadesc) ?: ($this->excerpt($tag->description) ?: $page->description);
				$page->image       = $this->image($images->get('image_fulltext') ?: $images->get('image_intro'), $tag->title) ?: $page->image;
			}
		}
		elseif (($option === 'com_content' && in_array($view, array('categories', 'archive'), true)) || ($option === 'com_tags' && $view === 'tags'))
		{
			$page->kind = 'collection';
		}
		elseif (($option === 'com_search' && $view === 'search') || ($option === 'com_finder' && $view === 'search'))
		{
			$page->kind = 'search';
		}

		// The home page is the site's: its name and description
		if ($page->kind === 'home')
		{
			$page->title       = $this->siteName();
			$page->description = $this->siteDescription() ?: $page->description;
		}

		return $page;
	}

	/**
	 * Whether the page is the home page: the default menu item, and the request its own link (Joomla also makes the home item
	 * active on pages without a menu item of their own, e.g. tags and search).
	 *
	 * @param   JApplicationSite  $app  The application
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	protected function isHome($app)
	{
		$active = $app->getMenu()->getActive();
		$input  = $app->input;

		if (!$active || !$active->home)
		{
			return false;
		}

		$query = $active->query;

		return (isset($query['option']) ? $query['option'] : '') === $input->getCmd('option')
			&& (isset($query['view']) ? $query['view'] : '') === $input->getCmd('view')
			&& (!isset($query['id']) || (int) $query['id'] === $input->getInt('id'));
	}

	/**
	 * The page's title without the site's name, which Global Configuration may add before or after it.
	 *
	 * @param   string  $title  The document's title
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function pageTitle($title)
	{
		$config   = Factory::getConfig();
		$sitename = (string) $config->get('sitename');
		$mode     = (int) $config->get('sitename_pagetitles', 0);

		if ($sitename !== '' && $mode === 1)
		{
			$prefix = JText::sprintf('JPAGETITLE', $sitename, '');

			if ($prefix !== '' && strpos($title, $prefix) === 0)
			{
				$title = substr($title, strlen($prefix));
			}
		}
		elseif ($sitename !== '' && $mode === 2)
		{
			$suffix = JText::sprintf('JPAGETITLE', '', $sitename);

			if ($suffix !== '' && substr($title, -strlen($suffix)) === $suffix)
			{
				$title = substr($title, 0, -strlen($suffix));
			}
		}

		return trim($title) !== '' ? trim($title) : $this->siteName();
	}

	/**
	 * An article, as the page shows it, with what its tags need. Null when it isn't published (the page then shows an error).
	 *
	 * @param   integer  $id  The article ID
	 *
	 * @return  stdClass|null
	 *
	 * @since   3.18.0
	 */
	protected function loadArticle($id)
	{
		JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('a.id', 'a.title', 'a.alias', 'a.introtext', 'a.fulltext', 'a.catid', 'a.created', 'a.created_by',
				'a.created_by_alias', 'a.modified', 'a.publish_up', 'a.images', 'a.metakey', 'a.metadesc', 'a.language', 'a.state', 'a.access')))
			->select($db->quoteName(array('c.title', 'c.access', 'u.name'), array('category_title', 'category_access', 'author_name')))
			->from($db->quoteName('#__content', 'a'))
			->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
			->where($db->quoteName('a.id') . ' = ' . (int) $id);

		$row = $db->setQuery($query)->loadObject();

		if (!$row || !in_array((int) $row->state, array(1, 2), true))
		{
			return null;
		}

		$images = new Registry($row->images);
		$text   = (string) $row->introtext . ' ' . (string) $row->fulltext;

		// The text as the visitor may read it: without access to the article, Joomla shows its intro text only
		$levels = Factory::getUser()->getAuthorisedViewLevels();
		$body   = $this->plainText(in_array((int) $row->access, $levels) && in_array((int) $row->category_access, $levels) ? $text : $row->introtext);

		$article = (object) array(
			'id'          => (int) $row->id,
			'title'       => (string) $row->title,
			'description' => trim((string) $row->metadesc) ?: $this->excerpt($row->introtext ?: $row->fulltext),
			'url'         => $this->route(ContentHelperRoute::getArticleRoute($row->id . ':' . $row->alias, $row->catid, $row->language)),
			'published'   => $this->date($row->publish_up && $row->publish_up !== $db->getNullDate() ? $row->publish_up : $row->created),
			'modified'    => $this->date($row->modified && $row->modified !== $db->getNullDate() ? $row->modified : $row->created),
			'author'      => trim((string) $row->created_by_alias) ?: (string) $row->author_name,
			'category'    => (string) $row->category_title,
			'keywords'    => array(),
			'tags'        => array(),
			'language'    => $row->language === '*' ? '' : (string) $row->language,
			'body'        => $body,
			'words'       => count(preg_split('/\s+/u', $body, -1, PREG_SPLIT_NO_EMPTY)),
			'image'       => null,
			'images'      => array(),
			'rating'      => null,
			'type'        => $this->params->get('article_type', 'Article'),
		);

		// The full text image and the intro image, the larger first (the structured data lists both; the social tags use the
		// first), else the first image in the text
		foreach (array('image_fulltext', 'image_intro') as $field)
		{
			$image = $this->image($images->get($field), $images->get($field . '_alt') ?: $row->title);

			if ($image && (!$article->images || $article->images[0]['url'] !== $image['url']))
			{
				$article->images[] = $image;
			}
		}

		if (!$article->images && preg_match('/<img\s[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $text, $match))
		{
			$image = $this->image(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), $row->title);

			if ($image)
			{
				$article->images[] = $image;
			}
		}

		$article->image = $article->images ? $article->images[0] : null;

		// Tags, in their order on the article
		if (TagsHelper::isAvailable())
		{
			$tagsQuery = $db->getQuery(true)
				->select($db->quoteName(array('t.id', 't.title', 't.params')))
				->from($db->quoteName('#__contentitem_tag_map', 'm'))
				->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
				->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
				->where($db->quoteName('m.content_item_id') . ' = ' . (int) $row->id)
				->where($db->quoteName('t.published') . ' = 1')
				->order($db->quoteName('t.lft'));

			$article->tags = $db->setQuery($tagsQuery)->loadObjectList();
		}

		foreach ($article->tags as $tag)
		{
			$article->keywords[] = $tag->title;
		}

		foreach (explode(',', (string) $row->metakey) as $keyword)
		{
			if (trim($keyword) !== '' && !in_array(trim($keyword), $article->keywords, true))
			{
				$article->keywords[] = trim($keyword);
			}
		}

		$article->type = $this->resolveType((int) $row->catid, $article->tags);

		// The voting plugin's ratings, for the types which can carry one
		if ($this->params->get('ratings', 1) && PluginHelper::isEnabled('content', 'vote')
			&& in_array($article->type, array('Product', 'Course', 'Recipe', 'SoftwareApplication'), true))
		{
			$ratingQuery = $db->getQuery(true)
				->select($db->quoteName(array('rating_sum', 'rating_count')))
				->from($db->quoteName('#__content_rating'))
				->where($db->quoteName('content_id') . ' = ' . (int) $row->id);

			$rating = $db->setQuery($ratingQuery)->loadObject();

			if ($rating && (int) $rating->rating_count > 0)
			{
				$article->rating = array(round($rating->rating_sum / $rating->rating_count, 1), (int) $rating->rating_count);
			}
		}

		return $article;
	}

	/**
	 * The structured data type of an article: its category's (or the nearest parent category's with one), else its first tag's
	 * with one, else the plugin's.
	 *
	 * @param   integer  $catid  The article's category
	 * @param   array    $tags   Its tags (with their params)
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function resolveType($catid, array $tags)
	{
		$category = JCategories::getInstance('Content')->get($catid);

		while ($category && $category->id !== 'root')
		{
			$type = (new Registry($category->params))->get('structureddata_type');

			if (in_array($type, static::TYPES, true))
			{
				return $type;
			}

			$category = $category->getParent();
		}

		foreach ($tags as $tag)
		{
			$type = (new Registry($tag->params))->get('structureddata_type');

			if (in_array($type, static::TYPES, true))
			{
				return $type;
			}
		}

		$type = $this->params->get('article_type', 'Article');

		return in_array($type, static::TYPES, true) ? $type : 'Article';
	}

	/**
	 * @param   integer  $id  The tag ID
	 *
	 * @return  stdClass|null
	 *
	 * @since   3.18.0
	 */
	protected function loadTag($id)
	{
		if (!TagsHelper::isAvailable())
		{
			return null;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'title', 'description', 'images', 'metadesc')))
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' = ' . (int) $id)
			->where($db->quoteName('published') . ' = 1');

		return $db->setQuery($query)->loadObject() ?: null;
	}

	/**
	 * Open Graph: title, description, URL, image, site name, locale; for an article its dates, section and tags.
	 *
	 * @param   stdClass  $page  describePage()
	 *
	 * @return  array  Property => content (an array for repeated properties)
	 *
	 * @since   3.18.0
	 */
	protected function openGraphTags($page)
	{
		$tags = array(
			'og:type'        => $page->kind === 'article' ? 'article' : 'website',
			'og:title'       => $page->title,
			'og:description' => $page->description,
			'og:url'         => $page->url,
			'og:site_name'   => $this->siteName(),
			'og:locale'      => str_replace('-', '_', $this->languageTag($page)),
		);

		if ($page->image)
		{
			$tags['og:image'] = $page->image['url'];

			if ($page->image['width'])
			{
				$tags['og:image:width']  = $page->image['width'];
				$tags['og:image:height'] = $page->image['height'];
			}

			$tags['og:image:alt'] = $page->image['alt'];
		}

		if ($page->article)
		{
			$tags['article:published_time'] = $page->article->published;
			$tags['article:modified_time']  = $page->article->modified;
			$tags['article:section']        = $page->article->category;
			$tags['article:tag']            = array();

			foreach ($page->article->tags as $tag)
			{
				$tags['article:tag'][] = $tag->title;
			}

			if ($this->params->get('facebook'))
			{
				$tags['article:publisher'] = $this->params->get('facebook');
			}
		}

		if ($this->params->get('fb_app_id'))
		{
			$tags['fb:app_id'] = $this->params->get('fb_app_id');
		}

		return $tags;
	}

	/**
	 * The X (Twitter) card: its kind, the site's and the author's accounts, title, description and image.
	 *
	 * @param   stdClass  $page  describePage()
	 *
	 * @return  array  Name => content
	 *
	 * @since   3.18.0
	 */
	protected function xCardTags($page)
	{
		$tags = array(
			'twitter:card'        => $page->image ? $this->params->get('x_card', 'summary_large_image') : 'summary',
			'twitter:site'        => $this->handle($this->params->get('x_site')),
			'twitter:creator'     => $page->article ? $this->handle($this->params->get('x_creator')) : '',
			'twitter:title'       => $page->title,
			'twitter:description' => $page->description,
		);

		if ($page->image)
		{
			$tags['twitter:image']     = $page->image['url'];
			$tags['twitter:image:alt'] = $page->image['alt'];
		}

		return $tags;
	}

	/**
	 * The JSON-LD graph: the site's publisher and the website on every page, the breadcrumbs, and the page's own node.
	 *
	 * @param   JApplicationSite  $app   The application
	 * @param   stdClass          $page  describePage()
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected function buildGraph($app, $page)
	{
		$root     = Uri::root();
		$language = $this->languageTag($page);
		$graph    = array($this->publisherNode(), array_filter(array(
			'@type'         => 'WebSite',
			'@id'           => $root . '#website',
			'url'           => $root,
			'name'          => $this->siteName(),
			'alternateName' => (string) $this->params->get('alternate_name'),
			'description'   => $this->siteDescription(),
			'inLanguage'    => $language,
			'publisher'     => array('@id' => $root . '#publisher'),
		)));

		if ($page->kind === 'article')
		{
			$graph[] = $this->articleNode($page, $language);
		}
		elseif ($page->kind === 'collection' || $page->kind === 'search')
		{
			$graph[] = array_filter(array(
				'@type'       => $page->kind === 'search' ? 'SearchResultsPage' : 'CollectionPage',
				'@id'         => $page->url . '#webpage',
				'url'         => $page->url,
				'name'        => $page->title,
				'description' => $page->description,
				'image'       => $page->image ? $this->imageNode($page->image) : null,
				'inLanguage'  => $language,
				'isPartOf'    => array('@id' => $root . '#website'),
			));
		}

		if ($this->params->get('breadcrumbs', 1) && ($breadcrumbs = $this->breadcrumbsNode($app, $page)))
		{
			$graph[] = $breadcrumbs;
		}

		return $graph;
	}

	/**
	 * The organization or person the site belongs to: name, URL, logo, description, profiles, address and contact point.
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected function publisherNode()
	{
		$root     = Uri::root();
		$person   = $this->params->get('represents', 'organization') === 'person';
		$logo     = $this->image($this->params->get('logo'), $this->siteName());
		$profiles = array();

		foreach (array('facebook', 'x', 'instagram', 'linkedin', 'youtube', 'tiktok', 'threads', 'mastodon', 'bluesky', 'github', 'wikipedia') as $network)
		{
			$profiles[] = $this->params->get($network);
		}

		$profiles = array_merge($profiles, preg_split('/\s+/', (string) $this->params->get('other_profiles')));
		$profiles = array_values(array_unique(array_filter($profiles, function ($url)
		{
			return (bool) preg_match('#^https?://#i', (string) $url);
		})));

		$address = array_filter(array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => (string) $this->params->get('street'),
			'addressLocality' => (string) $this->params->get('locality'),
			'addressRegion'   => (string) $this->params->get('region'),
			'postalCode'      => (string) $this->params->get('postal_code'),
			'addressCountry'  => (string) $this->params->get('country'),
		));

		$contact = array_filter(array(
			'@type'       => 'ContactPoint',
			'contactType' => (string) $this->params->get('contact_type', 'customer service'),
			'telephone'   => (string) $this->params->get('telephone'),
			'email'       => (string) $this->params->get('email'),
		));

		$type = $person ? 'Person' : $this->params->get('org_type', 'Organization');

		return array_filter(array(
			'@type'         => preg_match('/^[A-Za-z]+$/', $type) ? $type : 'Organization',
			'@id'           => $root . '#publisher',
			'name'          => $this->siteName(),
			'alternateName' => (string) $this->params->get('alternate_name'),
			'url'           => $root,
			'logo'          => !$person && $logo ? $this->imageNode($logo) : null,
			'image'         => $person && $logo ? $this->imageNode($logo) : null,
			'description'   => $this->siteDescription(),
			'sameAs'        => $profiles ?: null,
			'address'       => count($address) > 1 ? $address : null,
			'telephone'     => (string) $this->params->get('telephone'),
			'email'         => (string) $this->params->get('email'),
			'contactPoint'  => !$person && (isset($contact['telephone']) || isset($contact['email'])) ? $contact : null,
		));
	}

	/**
	 * The article as its type: an article (headline, text, section, keywords, word count), a review of the subject in its title, a
	 * product, service, course, recipe or application named by it, or a web page. All carry their description, image, URL,
	 * language and the page they're on; articles, reviews, recipes and pages also their dates, author and publisher.
	 *
	 * @param   stdClass  $page      describePage()
	 * @param   string    $language  The language tag
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected function articleNode($page, $language)
	{
		$root    = Uri::root();
		$article = $page->article;
		$type    = $article->type;
		$author  = $article->author !== '' ? array('@type' => 'Person', 'name' => $article->author) : null;
		$node    = array(
			'@type'            => $type,
			'@id'              => $article->url . '#main',
			'url'              => $article->url,
			'name'             => $article->title,
			'description'      => $page->description,
			'image'            => $article->images ? array_map(array($this, 'imageNode'), $article->images) : ($page->image ? array($this->imageNode($page->image)) : null),
			'inLanguage'       => $language,
			'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => $article->url),
		);

		$authored = in_array($type, static::ARTICLE_TYPES, true) || in_array($type, array('Review', 'Recipe', 'WebPage'), true);

		if ($authored)
		{
			$node['datePublished'] = $article->published;
			$node['dateModified']  = $article->modified;
			$node['author']        = $author;
			$node['publisher']     = array('@id' => $root . '#publisher');
		}

		if (in_array($type, static::ARTICLE_TYPES, true))
		{
			$node['headline']       = $this->truncate($article->title, 110);
			$node['articleSection'] = $article->category;
			$node['keywords']       = $article->keywords ? implode(', ', $article->keywords) : null;
			$node['articleBody']    = $article->body;
			$node['wordCount']      = $article->words ?: null;
			unset($node['name']);
		}
		elseif ($type === 'Review')
		{
			$node['itemReviewed'] = array('@type' => 'Thing', 'name' => $article->title);
			$node['reviewBody']   = $article->body ?: $page->description;
		}
		elseif ($type === 'Product')
		{
			$node['category'] = $article->category;
		}
		elseif ($type === 'Service' || $type === 'Course')
		{
			$node['provider'] = array('@id' => $root . '#publisher');

			if ($type === 'Service')
			{
				$node['serviceType'] = $article->category;
			}
		}
		elseif ($type === 'Recipe')
		{
			$node['recipeCategory'] = $article->category;
			$node['keywords']       = $article->keywords ? implode(', ', $article->keywords) : null;
		}
		elseif ($type === 'SoftwareApplication')
		{
			$node['applicationCategory'] = $article->category;
		}
		elseif ($type === 'WebPage')
		{
			$node['isPartOf'] = array('@id' => $root . '#website');
			unset($node['mainEntityOfPage']);
		}

		if ($article->rating)
		{
			$node['aggregateRating'] = array('@type' => 'AggregateRating', 'ratingValue' => $article->rating[0], 'ratingCount' => $article->rating[1],
				'bestRating' => 5, 'worstRating' => 1);
		}

		return array_filter($node, function ($value)
		{
			return $value !== null && $value !== '' && $value !== array();
		});
	}

	/**
	 * The breadcrumbs, from the site's pathway: home, then each item, the last one the page itself. Null on the home page.
	 *
	 * @param   JApplicationSite  $app   The application
	 * @param   stdClass          $page  describePage()
	 *
	 * @return  array|null
	 *
	 * @since   3.18.0
	 */
	protected function breadcrumbsNode($app, $page)
	{
		$items = $app->getPathway()->getPathway();

		if (!$items)
		{
			return null;
		}

		$home  = $app->getMenu()->getDefault(Factory::getLanguage()->getTag());
		$list  = array(array('@type' => 'ListItem', 'position' => 1, 'name' => $home ? $home->title : $this->siteName(), 'item' => Uri::root()));
		$count = count($items);

		foreach (array_values($items) as $i => $item)
		{
			$url = $i === $count - 1 || empty($item->link) ? $page->url : $this->route($item->link);

			$list[] = array('@type' => 'ListItem', 'position' => $i + 2, 'name' => html_entity_decode((string) $item->name, ENT_QUOTES, 'UTF-8'), 'item' => $url);
		}

		return array('@type' => 'BreadcrumbList', '@id' => $page->url . '#breadcrumb', 'itemListElement' => $list);
	}

	/**
	 * @param   array  $image  image()
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected function imageNode(array $image)
	{
		return array_filter(array('@type' => 'ImageObject', 'url' => $image['url'], 'width' => $image['width'], 'height' => $image['height']));
	}

	/**
	 * An image as the tags give it: its absolute URL, size (when it's a file of the site) and description. Null for no image.
	 *
	 * @param   mixed   $src  The image's path or URL
	 * @param   string  $alt  Its description
	 *
	 * @return  array|null
	 *
	 * @since   3.18.0
	 */
	protected function image($src, $alt = '')
	{
		// Joomla 4's media field adds "#joomlaImage://..." to the path; content may encode spaces
		$src = trim(preg_replace('/#.*$/', '', (string) $src));

		if ($src === '' || preg_match('#^(data|javascript):#i', $src))
		{
			return null;
		}

		$root = Uri::root();
		$base = Uri::root(true);

		if (preg_match('#^https?://#i', $src))
		{
			$url = $src;
		}
		elseif (strpos($src, '//') === 0)
		{
			$url = Uri::getInstance()->getScheme() . ':' . $src;
		}
		else
		{
			$path = ltrim($base !== '' && strpos($src, $base . '/') === 0 ? substr($src, strlen($base)) : $src, '/');
			$url  = $root . $path;
		}

		if (!array_key_exists($url, $this->imageSizes))
		{
			$this->imageSizes[$url] = false;

			if (strpos($url, $root) === 0)
			{
				$file = realpath(JPATH_ROOT . '/' . rawurldecode(substr($url, strlen($root))));

				if ($file !== false && strpos($file, JPATH_ROOT . DIRECTORY_SEPARATOR) === 0 && is_file($file))
				{
					$size = @getimagesize($file);

					$this->imageSizes[$url] = $size ? array((int) $size[0], (int) $size[1]) : false;
				}
			}
		}

		$size = $this->imageSizes[$url];

		return array('url' => $url, 'width' => $size ? $size[0] : 0, 'height' => $size ? $size[1] : 0, 'alt' => trim((string) $alt));
	}

	/**
	 * Whether the page has a tag of a family already (another extension set it): in its metadata or custom tags.
	 *
	 * @param   JDocumentHtml  $doc     The page
	 * @param   string         $prefix  og: or twitter:
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	protected function hasTags($doc, $prefix)
	{
		foreach ($doc->_metaTags as $type => $tags)
		{
			if ($type === 'http-equiv')
			{
				continue;
			}

			foreach (array_keys($tags) as $name)
			{
				if (stripos((string) $name, $prefix) === 0)
				{
					return true;
				}
			}
		}

		foreach ($doc->_custom as $custom)
		{
			if (preg_match('/<meta\s[^>]*(?:property|name)\s*=\s*["\']' . preg_quote($prefix, '/') . '/i', (string) $custom))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param   JDocumentHtml  $doc  The page
	 *
	 * @return  boolean  Whether the page has JSON-LD already
	 *
	 * @since   3.18.0
	 */
	protected function hasJsonLd($doc)
	{
		if (!empty($doc->_script['application/ld+json']))
		{
			return true;
		}

		foreach ($doc->_custom as $custom)
		{
			if (stripos((string) $custom, 'application/ld+json') !== false)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return  string  The name of the site (the option, else the site's)
	 *
	 * @since   3.18.0
	 */
	protected function siteName()
	{
		return trim((string) $this->params->get('site_name')) ?: (string) Factory::getConfig()->get('sitename');
	}

	/**
	 * @return  string  The description of the site (the option, else the site's meta description)
	 *
	 * @since   3.18.0
	 */
	protected function siteDescription()
	{
		return trim((string) $this->params->get('site_description')) ?: trim((string) Factory::getConfig()->get('MetaDesc'));
	}

	/**
	 * @return  array|null  The site's preview image, else its logo
	 *
	 * @since   3.18.0
	 */
	protected function defaultImage()
	{
		return $this->image($this->params->get('image'), (string) $this->params->get('image_alt') ?: $this->siteName())
			?: $this->image($this->params->get('logo'), $this->siteName());
	}

	/**
	 * @param   stdClass  $page  describePage()
	 *
	 * @return  string  The page's language tag, e.g. en-GB
	 *
	 * @since   3.18.0
	 */
	protected function languageTag($page)
	{
		$tag = $page->article && $page->article->language !== '' ? $page->article->language : Factory::getLanguage()->getTag();

		return preg_match('/^[a-z]{2,3}-[A-Za-z]{2,4}$/', $tag) ? $tag : '';
	}

	/**
	 * @param   string  $url  A non-SEF URL
	 *
	 * @return  string  The absolute SEF URL
	 *
	 * @since   3.18.0
	 */
	protected function route($url)
	{
		return JRoute::_($url, false, JRoute::TLS_IGNORE, true);
	}

	/**
	 * @return  string  The page's address, without its fragment
	 *
	 * @since   3.18.0
	 */
	protected function currentUrl()
	{
		return Uri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'query'));
	}

	/**
	 * @param   string  $value  A date in UTC, as stored
	 *
	 * @return  string  ISO 8601, e.g. 2026-10-17T09:30:00+00:00
	 *
	 * @since   3.18.0
	 */
	protected function date($value)
	{
		return $value ? Factory::getDate($value)->toISO8601() : '';
	}

	/**
	 * @param   string  $handle  An X username, with or without @, or a profile URL
	 *
	 * @return  string  @username, or empty
	 *
	 * @since   3.18.0
	 */
	protected function handle($handle)
	{
		$handle = trim((string) $handle);

		if (preg_match('#^https?://(?:www\.)?(?:x|twitter)\.com/([A-Za-z0-9_]{1,15})#i', $handle, $match))
		{
			$handle = $match[1];
		}

		$handle = ltrim($handle, '@');

		return preg_match('/^[A-Za-z0-9_]{1,15}$/', $handle) ? '@' . $handle : '';
	}

	/**
	 * @param   string  $html  HTML
	 *
	 * @return  string  Its text: tags, plugin codes ({loadposition ...}) and spare whitespace removed
	 *
	 * @since   3.18.0
	 */
	protected function plainText($html)
	{
		$text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $html);
		$text = preg_replace('/\{[a-z][^{}]*\}/i', ' ', strip_tags(str_replace('<', ' <', $text)));

		return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
	}

	/**
	 * @param   string  $html  HTML
	 *
	 * @return  string  The start of its text, for a description (at most 200 characters, ending at a word)
	 *
	 * @since   3.18.0
	 */
	protected function excerpt($html)
	{
		return $this->truncate($this->plainText($html), 200);
	}

	/**
	 * @param   string   $text    Text
	 * @param   integer  $length  The most characters
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function truncate($text, $length)
	{
		$text = trim((string) $text);

		if (mb_strlen($text, 'UTF-8') <= $length)
		{
			return $text;
		}

		$cut  = mb_substr($text, 0, $length - 1, 'UTF-8');
		$word = preg_replace('/\s+\S*$/u', '', $cut);

		return rtrim($word !== '' ? $word : $cut, " ,.;:-") . '…';
	}
}
