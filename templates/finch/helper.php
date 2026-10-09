<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

/**
 * Shared helpers of the Finch template: one way to show a post's image, category, author, date and excerpt in every list,
 * module layout and post.
 *
 * @since  3.17.0
 */
abstract class FinchHelper
{
	/**
	 * The template's parameters
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  3.17.0
	 */
	protected static $params;

	/**
	 * @return  \Joomla\Registry\Registry
	 *
	 * @since   3.17.0
	 */
	public static function params()
	{
		if (static::$params === null)
		{
			static::$params = JFactory::getApplication()->getTemplate(true)->params;
		}

		return static::$params;
	}

	/**
	 * Prepare a page of the template and tell its markup what it needs to know. Sets up the document's head (HTML5, meta
	 * tags, the stylesheets, the icon) for index.php, component.php (print and popup views) and offline.php alike; for
	 * index.php also the script and the kind of page.
	 *
	 * @param   JDocumentHtml  $document  The document ($this in the template's files)
	 * @param   string         $layout    index, component or offline
	 *
	 * @return  stdClass  params, siteName; for index also isHome, isPost, isPage, isList, hasSidebar, hasSearch, bodyClass
	 *
	 * @since   3.17.0
	 */
	public static function prepare($document, $layout = 'index')
	{
		$base           = JUri::root(true) . '/templates/' . basename(__DIR__);
		$page           = new stdClass;
		$page->params   = static::params();
		$page->siteName = static::siteName();

		$document->setHtml5(true);
		$document->setGenerator('');
		$document->setMetaData('viewport', 'width=device-width, initial-scale=1');
		$page->scheme   = static::scheme();

		// The browser's own parts (scroll bars, fields) and its bar match the page's colours
		$document->setMetaData('color-scheme', $page->scheme === 'auto' ? 'light dark' : $page->scheme);

		if ($page->scheme === 'auto')
		{
			$document->addCustomTag('<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />');
			$document->addCustomTag('<meta name="theme-color" content="#1b1022" media="(prefers-color-scheme: dark)" />');
		}
		else
		{
			$document->setMetaData('theme-color', $page->scheme === 'dark' ? '#1b1022' : '#ffffff');
		}

		foreach (static::stylesheets() as $stylesheet)
		{
			$document->addStyleSheet($stylesheet);
		}

		$document->addHeadLink($base . '/images/favicon.svg', 'icon', 'rel', array('type' => 'image/svg+xml'));

		if ($layout !== 'index')
		{
			return $page;
		}

		$document->addScript($base . '/js/template.js?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/js/template.js')), array(), array('defer' => true));

		$app    = JFactory::getApplication();
		$input  = $app->input;
		$active = $app->getMenu()->getActive();
		$option = $input->getCmd('option', '');
		$view   = $input->getCmd('view', '');

		// Joomla makes the home item active on pages without a menu item of their own (tags, search): the home page is its own link
		$home             = $active ? $active->query : array();
		$page->isHome     = $active && $active->home && (isset($home['option']) ? $home['option'] : '') === $option
			&& (isset($home['view']) ? $home['view'] : '') === $view && (!isset($home['id']) || (int) $home['id'] === $input->getInt('id'));
		$page->isPage     = static::isPage();
		$page->isPost     = $option === 'com_content' && $view === 'article' && !$page->isPage;
		$page->isList     = in_array($option . '.' . $view, array('com_content.featured', 'com_content.category', 'com_content.archive', 'com_tags.tag', 'com_search.search'), true);
		$page->hasSidebar = $page->isList && $document->countModules('sidebar');
		$page->hasSearch  = (bool) $document->countModules('search');
		$page->bodyClass  = implode(' ', array_filter(array(
			'site', $page->isHome ? 'isHome' : '', $page->isPost ? 'isPost' : '', $page->isPage ? 'isPage' : '', $page->isList ? 'isList' : '',
			$page->hasSidebar ? 'hasSidebar' : '', 'option-' . str_replace('com_', '', $option), 'view-' . $view,
			$active ? 'itemid-' . (int) $active->id : '', $active ? trim((string) $active->getParams()->get('pageclass_sfx')) : '',
		)));

		return $page;
	}

	/**
	 * The site's name as the template shows it: the "Name in the Logo" option, else the site's name.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function siteName()
	{
		return static::params()->get('siteName') ?: JFactory::getApplication()->get('sitename');
	}

	/**
	 * The links to the blog's social profiles (the template's options), and its feed.
	 *
	 * @param   string  $class  Classes of the list
	 *
	 * @return  string  Empty when there are none
	 *
	 * @since   3.17.0
	 */
	public static function socialLinks($class = 'socialLinks')
	{
		$items = '';

		foreach (array('x' => 'X', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'rss' => 'RSS') as $network => $label)
		{
			$url = trim((string) static::params()->get('social_' . $network, ''));

			// The site's feed unless another one is set
			if ($network === 'rss' && $url === '')
			{
				$url = JUri::base(true) . '/index.php?format=feed&type=rss';
			}

			// Web addresses (or paths) only: never javascript: and the like, however it's spelt (browsers ignore tabs and newlines in it)
			if ($url !== '' && (!preg_match('/^[a-z][a-z0-9+.\-]*:/i', preg_replace('/[\x00-\x20]+/', '', $url), $scheme)
				|| preg_match('/^https?:$/i', $scheme[0])))
			{
				$items .= '<li><a href="' . static::e($url) . '" aria-label="' . static::e($label) . '"' . ($network !== 'rss' ? ' rel="noopener" target="_blank"' : '')
					. '>' . static::icon($network) . '</a></li>';
			}
		}

		return $items === '' ? '' : '<ul class="' . $class . '">' . $items . '</ul>';
	}

	/**
	 * The template's SVG icons, once at the top of the page; icon() refers to them.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function sprite()
	{
		return (string) file_get_contents(__DIR__ . '/images/icons.svg');
	}

	/**
	 * The offline page's message, as Global Configuration says: its own text, the language's standard one, or none.
	 *
	 * @return  string  HTML (Global Configuration's text is the administrator's)
	 *
	 * @since   3.17.0
	 */
	public static function offlineMessage()
	{
		$app  = JFactory::getApplication();
		$mode = (int) $app->get('display_offline_message', 1);

		if ($mode === 1 && trim((string) $app->get('offline_message')) !== '')
		{
			return (string) $app->get('offline_message');
		}

		return $mode === 2 ? JText::_('JOFFLINE_MESSAGE') : '';
	}

	/**
	 * The offline page's image (Global Configuration), when the file exists.
	 *
	 * @return  string  Its URL, or empty
	 *
	 * @since   3.17.0
	 */
	public static function offlineImage()
	{
		$image = preg_replace('/#.*$/', '', (string) JFactory::getApplication()->get('offline_image'));

		return $image !== '' && is_file(JPATH_ROOT . '/' . ltrim($image, '/')) ? JUri::root(true) . '/' . ltrim($image, '/') : '';
	}

	/**
	 * The template's stylesheets: css/template.css, then css/custom.css when the site has one. custom.css is the site's own CSS:
	 * it isn't one of the template's files, so no update (Joomla Update, an upload of the files or the command line) touches it.
	 * Each URL carries the file's modification time, so browsers fetch a changed file.
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	public static function stylesheets()
	{
		$base = JUri::root(true) . '/templates/' . basename(__DIR__) . '/css/';
		$urls = array();

		foreach (array('template.css', 'custom.css') as $file)
		{
			if (is_file(__DIR__ . '/css/' . $file))
			{
				$urls[] = $base . $file . '?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/css/' . $file));
			}
		}

		return $urls;
	}

	/**
	 * Escape for HTML.
	 *
	 * @param   string  $value  The text
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function e($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * An SVG icon from the sprite in index.php.
	 *
	 * @param   string  $name   The icon
	 * @param   string  $class  Extra classes
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function icon($name, $class = '')
	{
		return '<svg class="icon icon-' . $name . ($class ? ' ' . $class : '') . '" aria-hidden="true" focusable="false"><use href="#fi-' . $name . '"></use></svg>';
	}

	/**
	 * The logo: the finch and the site's name.
	 *
	 * @param   string  $name   The site's name
	 * @param   string  $class  Classes of the link
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function logo($name, $class = 'logo')
	{
		return '<a class="' . $class . '" href="' . JUri::base(true) . '/">'
			. '<svg class="logoMark" viewBox="0 0 48 48" aria-hidden="true"><use href="#fi-finch"></use></svg>'
			. '<span class="logoText">' . static::e($name) . '</span></a>';
	}

	/**
	 * The post's image: the intro image, else the full article image, else the first image of the text.
	 *
	 * @param   object   $item  The post
	 * @param   boolean  $full  Prefer the full article image
	 *
	 * @return  array  src, alt, caption (empty src when there is none)
	 *
	 * @since   3.17.0
	 */
	public static function image($item, $full = false)
	{
		$images = isset($item->images) && is_string($item->images) ? json_decode($item->images) : (isset($item->images) ? (object) $item->images : null);

		foreach ($full ? array('fulltext', 'intro') : array('intro', 'fulltext') as $type)
		{
			$src = isset($images->{'image_' . $type}) ? (string) $images->{'image_' . $type} : '';

			if ($src !== '')
			{
				return array(
					'src'     => static::url($src),
					'alt'     => isset($images->{'image_' . $type . '_alt'}) ? $images->{'image_' . $type . '_alt'} : '',
					'caption' => isset($images->{'image_' . $type . '_caption'}) ? $images->{'image_' . $type . '_caption'} : '',
				);
			}
		}

		if (preg_match('/<img[^>]+src="([^"]+)"/i', (string) (isset($item->introtext) ? $item->introtext : ''), $match))
		{
			return array('src' => static::url($match[1]), 'alt' => '', 'caption' => '');
		}

		return array('src' => '', 'alt' => '', 'caption' => '');
	}

	/**
	 * An image URL: absolute ones as they are, others from the site's root.
	 *
	 * @param   string  $src  The image
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function url($src)
	{
		// Images of the media manager carry their size after a #
		$src = preg_replace('/#.*$/', '', (string) $src);

		return preg_match('#^(https?:)?//#i', $src) ? $src : JUri::root(true) . '/' . ltrim($src, '/');
	}

	/**
	 * The image, linked to the post (or nothing).
	 *
	 * @param   object   $item   The post
	 * @param   string   $link   The post's link
	 * @param   string   $class  Classes of the wrapper
	 * @param   boolean  $lazy   Load lazily
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function figure($item, $link, $class = 'postImage', $lazy = true)
	{
		$image = static::image($item);

		if ($image['src'] === '')
		{
			return '';
		}

		return '<div class="' . $class . '"><a href="' . $link . '" tabindex="-1" aria-hidden="true"><img src="' . static::e($image['src'])
			. '" alt="' . static::e($image['alt']) . '" width="1280" height="720"' . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"')
			. ' /></a></div>';
	}

	/**
	 * The post's link.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function link($item)
	{
		if (!empty($item->link))
		{
			return $item->link;
		}

		$slug    = isset($item->slug) ? $item->slug : $item->id . ':' . (isset($item->alias) ? $item->alias : '');
		$catslug = isset($item->catslug) ? $item->catslug : $item->catid;

		return JRoute::_(ContentHelperRoute::getArticleRoute($slug, $catslug, isset($item->language) ? $item->language : '*'));
	}

	/**
	 * The category, linked to its page.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function category($item)
	{
		$title = isset($item->category_title) ? (string) $item->category_title : '';

		if ($title === '')
		{
			return '';
		}

		return '<a class="postCategory" href="' . JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid, isset($item->language) ? $item->language : '*')) . '">'
			. static::e($title) . '</a>';
	}

	/**
	 * The author's name: the alias, else the user's name.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function author($item)
	{
		foreach (array('created_by_alias', 'author', 'displayAuthorName') as $field)
		{
			if (!empty($item->$field))
			{
				return (string) $item->$field;
			}
		}

		return '';
	}

	/**
	 * Two initials for an author's avatar.
	 *
	 * @param   string  $name  The name
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function initials($name)
	{
		$parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

		if (!$parts)
		{
			return '';
		}

		return mb_strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
	}

	/**
	 * The publishing date.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  string  A time element
	 *
	 * @since   3.17.0
	 */
	public static function date($item)
	{
		$value = isset($item->publish_up) ? $item->publish_up : (isset($item->created) ? $item->created : '');

		if (!$value || $value === JFactory::getDbo()->getNullDate())
		{
			return '';
		}

		return '<time class="postDate" datetime="' . JFactory::getDate($value)->format('c') . '">' . static::e(JHtml::_('date', $value, JText::_('DATE_FORMAT_LC3'))) . '</time>';
	}

	/**
	 * Plain text of the intro, cut at a word.
	 *
	 * @param   object   $item    The post
	 * @param   integer  $length  At most this many characters (0: all)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function excerpt($item, $length = 0)
	{
		$text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) (isset($item->introtext) ? $item->introtext : '')), ENT_QUOTES, 'UTF-8')));

		if ($length && mb_strlen($text) > $length)
		{
			$text = rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $length), ' ') ?: $length), ' ,.;:') . '…';
		}

		return static::e($text);
	}

	/**
	 * Minutes to read the post.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public static function readingTime($item)
	{
		$text = (isset($item->introtext) ? $item->introtext : '') . ' ' . (isset($item->fulltext) ? $item->fulltext : (isset($item->text) ? $item->text : ''));

		return max(1, (int) round(str_word_count(strip_tags($text)) / 230));
	}

	/**
	 * A standalone page (e.g. About): an article shown through a menu item of its own, rather than a post opened from a list.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isPage()
	{
		$app    = JFactory::getApplication();
		$active = $app->getMenu()->getActive();

		return $active && isset($active->query['option'], $active->query['view'], $active->query['id'])
			&& $active->query['option'] === 'com_content' && $active->query['view'] === 'article'
			&& $app->input->get('option') === 'com_content' && $app->input->get('view') === 'article'
			&& (int) $active->query['id'] === $app->input->getInt('id');
	}

	/**
	 * A tag view's item (com_tags, core_* fields) as a post-like object for the helpers.
	 *
	 * @param   object  $item  The tagged item
	 *
	 * @return  object
	 *
	 * @since   3.17.0
	 */
	public static function fromTagItem($item)
	{
		JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

		$post                 = new stdClass;
		$post->id             = (int) $item->content_item_id;
		$post->title          = $item->core_title;
		$post->introtext      = $item->core_body;
		$post->images         = $item->core_images;
		$post->publish_up     = $item->core_publish_up;
		$post->catid          = (int) $item->core_catid;
		$post->language       = $item->core_language;
		$post->author         = isset($item->author) ? $item->author : '';
		$post->link           = JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid,
			$item->core_language, $item->type_alias, $item->router));
		$category             = $post->catid ? JCategories::getInstance('Content')->get($post->catid) : null;
		$post->category_title = $category ? $category->title : '';

		return $post;
	}

	/**
	 * The template's five tones, by name: a topic's colour, given as a class in its menu item's Page Class (e.g. tone-mint)
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const TONES = array('lavender', 'sunflower', 'coral', 'mint', 'sky');

	/**
	 * The colour of a topic: the tone in the Page Class of the menu item showing the category (Category Blog or List), else
	 * one picked by the category's ID (consecutive IDs get different tones). A post keeps its topic's colour everywhere: lists,
	 * its page, "Read next".
	 *
	 * @param   object  $item  The post (its catid) or the category (its id)
	 *
	 * @return  string  A class: tone-lavender, tone-sunflower, tone-coral, tone-mint or tone-sky
	 *
	 * @since   3.17.0
	 */
	public static function tone($item)
	{
		static $chosen = null;

		if ($chosen === null)
		{
			$chosen = array();

			foreach ((array) JFactory::getApplication()->getMenu()->getItems('component', 'com_content') as $menuItem)
			{
				if (isset($menuItem->query['view'], $menuItem->query['id']) && $menuItem->query['view'] === 'category'
					&& preg_match('/(?:^|\s)tone-(' . implode('|', static::TONES) . ')(?:\s|$)/', (string) $menuItem->getParams()->get('pageclass_sfx'), $match))
				{
					$chosen[(int) $menuItem->query['id']] = 'tone-' . $match[1];
				}
			}
		}

		$id = isset($item->catid) ? (int) $item->catid : (isset($item->id) ? (int) $item->id : 0);

		return isset($chosen[$id]) ? $chosen[$id] : 'tone-' . static::TONES[$id % 5];
	}

	/**
	 * The colour scheme: the template's option, light, dark, or the device's setting (auto).
	 *
	 * @return  string  auto, light or dark
	 *
	 * @since   3.17.0
	 */
	public static function scheme()
	{
		// The error page calls it too, when something may have failed already: never let the option break that page
		try
		{
			$scheme = (string) static::params()->get('colorScheme', 'auto');
		}
		catch (Exception $e)
		{
			$scheme = 'auto';
		}

		return in_array($scheme, array('light', 'dark'), true) ? $scheme : 'auto';
	}

	/**
	 * The publishing date as a stamp: the day large, the month and year under it.
	 *
	 * @param   object  $item  The post
	 *
	 * @return  string  Empty without a date
	 *
	 * @since   3.17.0
	 */
	public static function stamp($item)
	{
		$value = isset($item->publish_up) ? $item->publish_up : (isset($item->created) ? $item->created : '');

		if (!$value || $value === JFactory::getDbo()->getNullDate())
		{
			return '';
		}

		return '<time class="postStamp" datetime="' . JFactory::getDate($value)->format('c') . '"><span class="stampDay">'
			. static::e(JHtml::_('date', $value, 'j')) . '</span><span class="stampMonth">' . static::e(JHtml::_('date', $value, 'M Y')) . '</span></time>';
	}

	/**
	 * A post in a list: the date stamp, its topic, title, excerpt and reading time, and its image on a block of the topic's
	 * colour. The lead post (the newest, on a list's first page) is a band of that colour across the page.
	 *
	 * @param   object   $item   The post
	 * @param   boolean  $lead   The lead post
	 * @param   boolean  $eager  Its image is at the top of the page: load it at once
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function card($item, $lead = false, $eager = false)
	{
		$link    = static::link($item);
		$minutes = isset($item->fulltext) || isset($item->text) ? JText::sprintf('TPL_FINCH_READING_TIME', static::readingTime($item)) : '';
		$h       = $lead ? 2 : 3;

		return '<article class="post ' . static::tone($item) . ($lead ? ' postLead' : '') . '"><div class="postInner">'
			. static::stamp($item)
			. '<div class="postBody">' . static::category($item)
			. '<h' . $h . ' class="postTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h' . $h . '>'
			. '<p class="postExcerpt">' . static::excerpt($item, $lead ? 260 : 190) . '</p>'
			. ($minutes !== '' ? '<p class="postMeta"><span class="postReading">' . $minutes . '</span></p>' : '')
			. '</div>'
			. static::figure($item, $link, 'postImage', !$lead && !$eager)
			. '</div></article>';
	}

	/**
	 * A list of posts, one after the other: the newest with a wide image on the first page of a list, the others with a small
	 * one beside their text. The first two images load at once, the others lazily.
	 *
	 * @param   object[]  $items  The posts
	 * @param   boolean   $lead   Show the first post large
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function postList(array $items, $lead = true)
	{
		$html = '';

		foreach (array_values($items) as $i => $item)
		{
			$html .= static::card($item, $lead && !$i, $i < 2);
		}

		return '<div class="postList">' . $html . '</div>';
	}

	/**
	 * An HTML attribute as browsers read it in a tag: its name (group 1), then optionally "=" and a double-quoted,
	 * single-quoted or unquoted value
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	const HTML_ATTRIBUTE = '([^\t\n\f\r \/>][^\t\n\f\r \/>=]*)(?>[\t\n\f\r ]*=[\t\n\f\r ]*(?>"[^"]*"|\'[^\']*\'|[^\t\n\f\r >]+)?)?';

	/**
	 * Load the images and embedded frames of some HTML (an article's text, a module) only when they come near the screen:
	 * loading=lazy for those which don't say how to load (an image with fetchpriority is meant to load at once).
	 *
	 * The HTML has been filtered already, so this must never change what it means: it reads the tags from the start, each
	 * whole (a quoted attribute value may hold "<" and ">", e.g. an "<img" inside a title), leaves comments and the content
	 * of the elements browsers don't read as HTML alone (script, style, iframe, textarea...), stops at the first tag it
	 * can't read, and adds its attributes without quotes, so that even a misread could never close an attribute value.
	 *
	 * @param   string   $html        The HTML
	 * @param   boolean  $eagerFirst  Leave the first image as it is (when it's the top of the page)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function lazyImages($html, $eagerFirst = false)
	{
		$html   = (string) $html;
		$output = '';
		$offset = 0;

		while (($start = strpos($html, '<', $offset)) !== false)
		{
			$output .= substr($html, $offset, $start - $offset);

			// A comment, as browsers end it ("<!-->" and "<!--->" at once, otherwise at "-->" or "--!>"), or what they read as one
			// ("<!...>", "<?...>", "</" not followed by a letter): as it is
			if (preg_match('/\G(?:<!--(?:-?>|.*?--!?>)|<!(?!--)[^>]*>|<\?[^>]*>|<\/(?![A-Za-z])[^>]*>)/s', $html, $match, 0, $start))
			{
				$output .= $match[0];
				$offset  = $start + strlen($match[0]);

				continue;
			}

			// A tag, read whole as browsers read it: its name runs up to a space, "/" or ">"; a value is quoted only when the quote
			// comes right after "=" (an unquoted value may hold quotes, and a quoted one "<" and ">")
			if (preg_match('/\G<(\/?[A-Za-z][^\t\n\f\r \/>]*)((?>[\t\n\f\r \/]+|' . self::HTML_ATTRIBUTE . ')*)>/', $html, $match, 0, $start))
			{
				$tag  = $match[0];
				$name = strtolower($match[1]);

				preg_match_all('/' . self::HTML_ATTRIBUTE . '/', $match[2], $attributes);

				if (($name === 'img' || $name === 'iframe') && !array_intersect(array_map('strtolower', $attributes[1]), array('loading', 'fetchpriority')))
				{
					if ($name === 'img' && $eagerFirst)
					{
						$eagerFirst = false;
					}
					else
					{
						$tag = '<' . $match[1] . ' loading=lazy' . ($name === 'img' ? ' decoding=async' : '') . ' ' . ltrim($match[2]) . '>';
					}
				}

				$output .= $tag;
				$offset  = $start + strlen($match[0]);

				// Elements whose content browsers read as text, not HTML: as it is, up to their end tag (all the rest without one)
				if (in_array($name, array('script', 'style', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'textarea', 'title', 'plaintext'), true))
				{
					// They end only at "</name" followed by a space, "/" or ">"
					if ($name === 'plaintext' || !preg_match('/<\/' . $name . '(?=[\t\n\f\r \/>])/i', $html, $end, PREG_OFFSET_CAPTURE, $offset))
					{
						return $output . substr($html, $offset);
					}

					$output .= substr($html, $offset, $end[0][1] - $offset);
					$offset  = $end[0][1];
				}

				continue;
			}

			// The start of a tag which can't be read (e.g. an unterminated quote): the rest stays as it is
			if (preg_match('/\G<[A-Za-z\/!?]/', $html, $match, 0, $start))
			{
				return $output . substr($html, $start);
			}

			// A "<" in the text
			$output .= '<';
			$offset  = $start + 1;
		}

		return $output . substr($html, $offset);
	}

	/**
	 * The page links of a list, from the pagination's data: getPagesLinks() would load Bootstrap's tooltips, and with them
	 * jQuery, for the Start/Prev/Next/End links' titles.
	 *
	 * @param   JPagination  $pagination  The list's pagination
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function pagination($pagination)
	{
		$data  = $pagination->getData();
		$items = array(array($data->start, 'pagination-start'), array($data->previous, 'pagination-prev'));

		foreach ($data->pages as $page)
		{
			$items[] = array($page, '');
		}

		$items[] = array($data->next, 'pagination-next');
		$items[] = array($data->end, 'pagination-end');
		$html    = '<ul>';

		foreach ($items as $entry)
		{
			list($item, $class) = $entry;

			if ($item->link !== null)
			{
				$html .= '<li' . ($class ? ' class="' . $class . '"' : '') . '><a href="' . $item->link . '" class="pagenav">' . $item->text . '</a></li>';
			}
			elseif (!empty($item->active))
			{
				$html .= '<li class="' . trim($class . ' active') . '"><span class="pagenav" aria-current="page">' . $item->text . '</span></li>';
			}
			else
			{
				$html .= '<li class="' . trim($class . ' disabled') . '"><span class="pagenav">' . $item->text . '</span></li>';
			}
		}

		return $html . '</ul>';
	}
}
