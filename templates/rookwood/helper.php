<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

/**
 * Shared helpers of the Rookwood template: the page set-up, the theme, the logo, and one way to show an article's image,
 * category, author, date and excerpt in every list and module layout.
 *
 * @since  3.17.0
 */
abstract class RookwoodHelper
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
	 * tags, the theme, the stylesheets, the icon) for index.php, component.php and offline.php alike; for index.php also the
	 * script and the kind of page.
	 *
	 * @param   JDocumentHtml  $document  The document ($this in the template's files)
	 * @param   string         $layout    index, component or offline
	 *
	 * @return  stdClass  params, siteName, theme; for index also isHome, isPage, isList, ownLayout (an override draws the page), bodyClass
	 *
	 * @since   3.17.0
	 */
	public static function prepare($document, $layout = 'index')
	{
		$base           = JUri::root(true) . '/templates/' . basename(__DIR__);
		$page           = new stdClass;
		$page->params   = static::params();
		$page->siteName = static::siteName();
		$page->theme    = static::defaultTheme();

		$document->setHtml5(true);
		$document->setGenerator('');
		$document->setMetaData('viewport', 'width=device-width, initial-scale=1');
		$document->setMetaData('color-scheme', 'dark light');

		// The visitor's choice (or the template's default) before anything is drawn, so the page never flashes the other theme
		$document->addScriptDeclaration(static::themeScript());

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

		// The home page is made of the frontpage modules, whatever its menu item shows (without modules there, e.g. on a new site
		// without sample data, it shows the component). Joomla also makes the home item active on pages without a menu item of
		// their own (tags, search), so the request must be the home item's own link.
		$home         = $active ? $active->query : array();
		$page->isHome = $active && $active->home && $document->countModules('frontpage')
			&& (isset($home['option']) ? $home['option'] : '') === $option && (isset($home['view']) ? $home['view'] : '') === $view
			&& (!isset($home['id']) || (int) $home['id'] === $input->getInt('id'));
		$page->isPage    = static::isPage();
		$page->isList    = !$page->isHome && in_array($option . '.' . $view,
			array('com_content.category', 'com_content.archive', 'com_content.featured', 'com_tags.tag', 'com_tags.tags', 'com_search.search'), true);
		$page->ownLayout = in_array($option . '.' . $view, array('com_content.article', 'com_content.category', 'com_content.featured',
			'com_content.archive', 'com_tags.tag', 'com_tags.tags'), true) && $input->getCmd('layout') !== 'edit';
		$page->bodyClass = implode(' ', array_filter(array(
			'site', $page->isHome ? 'isFrontpage' : 'isInner', $page->isPage ? 'isPage' : '', $page->isList ? 'isList' : '',
			'option-' . str_replace('com_', '', $option), 'view-' . $view, $active ? 'itemid-' . (int) $active->id : '',
			$active ? trim((string) $active->getParams()->get('pageclass_sfx')) : '',
		)));

		return $page;
	}

	/**
	 * The theme a page starts with: the template's option (dark, light, or the visitor's system setting).
	 *
	 * @return  string  dark, light or system
	 *
	 * @since   3.17.0
	 */
	public static function defaultTheme()
	{
		$theme = (string) static::params()->get('defaultTheme', 'dark');

		return in_array($theme, array('dark', 'light', 'system'), true) ? $theme : 'dark';
	}

	/**
	 * The script which sets the theme on the html element: the visitor's choice (kept in the browser by the theme switch), else
	 * the template's default. It runs in the head, before the page is drawn.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function themeScript()
	{
		return "(function(d){var t;try{t=localStorage.getItem('rookwood-theme');}catch(e){}"
			. "if(t!=='dark'&&t!=='light'){t=" . json_encode(static::defaultTheme()) . ";}"
			. "if(t==='system'){t=window.matchMedia&&matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';}"
			. "d.documentElement.setAttribute('data-theme',t);})(document);";
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
	 * The logo: the mark and the site's name, its first word in bold.
	 *
	 * @param   string  $class  Classes
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function logo($class = 'logo')
	{
		$name  = static::siteName();
		$space = strpos($name, ' ');

		return '<a class="' . $class . '" href="' . JUri::base(true) . '/" aria-label="' . static::e($name) . '">'
			. '<svg class="logoMark" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="currentColor"/>'
			. '<path d="M13 30V10h8.5a5.75 5.75 0 0 1 0 11.5H13M20.5 21.5 28 30" fill="none" stroke="var(--c-bg)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>'
			. '<circle cx="30" cy="10.5" r="3.2" fill="var(--c-lime)"/></svg>'
			. '<span class="logoText"><strong>' . static::e($space === false ? $name : substr($name, 0, $space)) . '</strong>'
			. ($space !== false ? ' <span>' . static::e(substr($name, $space + 1)) . '</span>' : '') . '</span></a>';
	}

	/**
	 * The links to the site's social profiles (the template's options), and its feed.
	 *
	 * @param   string  $class  Classes of the list
	 *
	 * @return  string  Empty when there are none
	 *
	 * @since   3.17.0
	 */
	public static function socialLinks($class = 'socialLinks')
	{
		$html = '';

		foreach (array('x' => 'X', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'github' => 'GitHub', 'dribbble' => 'Dribbble', 'rss' => 'RSS') as $network => $label)
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
				$html .= '<li><a href="' . static::e($url) . '" aria-label="' . static::e($label) . '"' . ($network !== 'rss' ? ' rel="noopener" target="_blank"' : '')
					. '>' . static::icon($network) . '</a></li>';
			}
		}

		return $html === '' ? '' : '<ul class="' . $class . '">' . $html . '</ul>';
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
	 * An SVG icon from the sprite at the top of the page.
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
		return '<svg class="icon icon-' . $name . ($class ? ' ' . $class : '') . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"></use></svg>';
	}

	/**
	 * The article's image: the intro image, else the full article image, else the first image of the text.
	 *
	 * @param   object   $item  The article
	 * @param   boolean  $full  Prefer the full article image
	 *
	 * @return  array  src, alt, caption, path (empty src when there is none; path: the site-relative file, for its sizes)
	 *
	 * @since   3.17.0
	 */
	public static function image($item, $full = false)
	{
		$images = is_string($item->images ?? null) ? json_decode($item->images) : ($item->images ?? null);
		$order  = $full ? array('fulltext', 'intro') : array('intro', 'fulltext');

		foreach ($order as $type)
		{
			$src = $images->{'image_' . $type} ?? '';

			if ($src !== '')
			{
				return array(
					'src'     => static::url($src),
					'path'    => preg_replace('/#.*$/', '', $src),
					'alt'     => $images->{'image_' . $type . '_alt'} ?? '',
					'caption' => $images->{'image_' . $type . '_caption'} ?? '',
				);
			}
		}

		if (preg_match('/<img[^>]+src="([^"]+)"/i', (string) ($item->introtext ?? ''), $match))
		{
			return array('src' => static::url($match[1]), 'path' => $match[1], 'alt' => '', 'caption' => '');
		}

		return array('src' => '', 'path' => '', 'alt' => '', 'caption' => '');
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
		$src = (string) $src;

		// Images of the media manager carry their size after a #
		$src = preg_replace('/#.*$/', '', $src);

		return preg_match('#^(https?:)?//#i', $src) ? $src : JUri::root(true) . '/' . ltrim($src, '/');
	}

	/**
	 * An img element for an image of the site: when a smaller copy sits next to it (photo-960.webp beside photo.webp), the
	 * browser picks the size the layout needs (srcset and sizes).
	 *
	 * @param   array    $image  image()'s result
	 * @param   string   $sizes  The sizes attribute: how wide the image is shown
	 * @param   boolean  $eager  The top of the page: load at once, with priority (lazily otherwise)
	 * @param   string   $class  Its class
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function img(array $image, $sizes = '100vw', $eager = false, $class = '')
	{
		$path   = ltrim((string) $image['path'], '/');
		$srcset = '';

		if ($path !== '' && !preg_match('#^(https?:)?//#i', $path) && preg_match('/^(.+)\.(webp|jpe?g|png|avif)$/i', $path, $parts)
			&& is_file(JPATH_ROOT . '/' . $parts[1] . '-960.' . $parts[2]))
		{
			$srcset = ' srcset="' . static::e(static::url($parts[1] . '-960.' . $parts[2])) . ' 960w, ' . static::e($image['src']) . ' 1920w" sizes="' . static::e($sizes) . '"';
		}

		return '<img' . ($class ? ' class="' . $class . '"' : '') . ' src="' . static::e($image['src']) . '"' . $srcset . ' alt="' . static::e($image['alt'])
			. '" width="1920" height="1080"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"') . ' />';
	}

	/**
	 * The article's link.
	 *
	 * @param   object  $item  The article
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

		$slug    = $item->slug ?? ($item->id . ':' . ($item->alias ?? ''));
		$catslug = $item->catslug ?? $item->catid;

		return JRoute::_(ContentHelperRoute::getArticleRoute($slug, $catslug, $item->language ?? '*'));
	}

	/**
	 * The category's title, as a label.
	 *
	 * @param   object  $item  The article
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function category($item)
	{
		$title = $item->category_title ?? ($item->displayCategoryTitle ?? '');

		return $title === '' ? '' : '<span class="itemCategory">' . static::e(strip_tags($title)) . '</span>';
	}

	/**
	 * The article's first tags (the services of a case study, the topics of a post), as labels.
	 *
	 * @param   object   $item  The article
	 * @param   integer  $max   At most this many
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function tags($item, $max = 2)
	{
		// The lists load their articles' tags already; modules don't
		if (!isset($item->tagLabels))
		{
			$tags = isset($item->tags->itemTags) ? $item->tags : new JHelperTags;

			if (!isset($item->tags->itemTags))
			{
				$tags->getItemTags('com_content.article', (int) $item->id);
			}

			$item->tagLabels = array_map(function ($tag) { return $tag->title; }, (array) $tags->itemTags);
		}

		$html = '';

		foreach (array_slice($item->tagLabels, 0, $max) as $title)
		{
			$html .= '<li>' . static::e($title) . '</li>';
		}

		return $html === '' ? '' : '<ul class="itemTags">' . $html . '</ul>';
	}

	/**
	 * The author's name: the alias, else the user's name.
	 *
	 * @param   object  $item  The article
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
		$parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);

		if (!$parts)
		{
			return '';
		}

		$first = mb_substr($parts[0], 0, 1);
		$last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

		return mb_strtoupper($first . $last);
	}

	/**
	 * The publishing date.
	 *
	 * @param   object  $item  The article
	 *
	 * @return  string  A time element
	 *
	 * @since   3.17.0
	 */
	public static function date($item)
	{
		$value = $item->publish_up ?? ($item->created ?? '');

		if (!$value || $value === JFactory::getDbo()->getNullDate())
		{
			return '';
		}

		return '<time class="itemDate" datetime="' . JFactory::getDate($value)->format('c') . '">' . static::e(JHtml::_('date', $value, JText::_('DATE_FORMAT_LC3'))) . '</time>';
	}

	/**
	 * Plain text of the intro, cut at a word.
	 *
	 * @param   object   $item    The article
	 * @param   integer  $length  At most this many characters (0: all)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function excerpt($item, $length = 0)
	{
		$text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($item->introtext ?? '')), ENT_QUOTES, 'UTF-8')));

		if ($length && mb_strlen($text) > $length)
		{
			$text = rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $length), ' ') ?: $length), ' ,.;:') . '…';
		}

		return static::e($text);
	}

	/**
	 * Minutes to read the article.
	 *
	 * @param   object  $item  The article
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public static function readingTime($item)
	{
		$words = str_word_count(strip_tags(($item->introtext ?? '') . ' ' . ($item->fulltext ?? ($item->text ?? ''))));

		return max(1, (int) round($words / 230));
	}

	/**
	 * The page shows one article through a menu item of its own (e.g. Studio, Contact): a page, not a post.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isPage()
	{
		$app    = JFactory::getApplication();
		$active = $app->getMenu()->getActive();
		$input  = $app->input;

		return $active && $input->get('option') === 'com_content' && $input->get('view') === 'article'
			&& ($active->query['option'] ?? '') === 'com_content' && ($active->query['view'] ?? '') === 'article'
			&& (int) ($active->query['id'] ?? 0) === $input->getInt('id');
	}

	/**
	 * A tag view's item (com_tags, core_* fields) as an article-like object for the helpers.
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

		$article                 = new stdClass;
		$article->id             = (int) $item->content_item_id;
		$article->title          = $item->core_title;
		$article->introtext      = $item->core_body;
		$article->images         = $item->core_images;
		$article->publish_up     = $item->core_publish_up;
		$article->catid          = (int) $item->core_catid;
		$article->language       = $item->core_language;
		$article->author         = $item->author ?? '';
		$article->link           = JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid,
			$item->core_language, $item->type_alias, $item->router));
		$category                = $article->catid ? JCategories::getInstance('Content')->get($article->catid) : null;
		$article->category_title = $category ? $category->title : '';
		$article->tagLabels      = array();

		return $article;
	}

	/**
	 * An article as a card: image, labels, title, excerpt and byline. The lists, the journal's mosaic and the modules share it.
	 *
	 * @param   object  $item     The article
	 * @param   array   $options  class (extra classes), heading (h2/h3), excerpt (characters, 0: none), eager (the top of the
	 *                            page), sizes (how wide its image is shown), labels (tags or category)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function card($item, array $options = array())
	{
		$options += array('class' => '', 'heading' => 'h3', 'excerpt' => 140, 'eager' => false, 'sizes' => '(min-width: 1100px) 33vw, (min-width: 700px) 50vw, 100vw', 'labels' => 'tags');
		$link    = static::link($item);
		$image   = static::image($item);
		$author  = static::author($item);
		$h       = $options['heading'];
		$labels  = $options['labels'] === 'tags' ? static::tags($item) : static::category($item);

		return '<article class="card' . ($options['class'] ? ' ' . $options['class'] : '') . '">'
			. ($image['src'] !== '' ? '<div class="cardImage">' . static::img($image, $options['sizes'], $options['eager']) . '</div>' : '')
			. '<div class="cardBody">' . $labels
			. '<' . $h . ' class="cardTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></' . $h . '>'
			. ($options['excerpt'] ? '<p class="cardText">' . static::excerpt($item, $options['excerpt']) . '</p>' : '')
			. '<p class="cardMeta">' . ($author !== '' ? '<span class="itemAuthor">' . static::e($author) . '</span>' : '') . static::date($item) . '</p>'
			. '</div></article>';
	}

	/**
	 * The header of a list (a category, the featured articles, the archive, a tag): its title and description, in a band as
	 * wide as the page.
	 *
	 * @param   string  $title        The title
	 * @param   string  $description  Its description (HTML, already prepared), or empty
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function listHeader($title, $description = '')
	{
		return '<header class="listHeader"><div class="container"><h1 class="listTitle">' . static::e($title) . '</h1>'
			. (trim(strip_tags($description, '<img>')) !== '' ? '<div class="listDescription">' . static::lazyImages($description) . '</div>' : '')
			. '</div></header>';
	}

	/**
	 * The title of a category layout: the menu item's page heading when it shows one, else the category's title.
	 *
	 * @param   Joomla\Registry\Registry  $params    The view's parameters
	 * @param   JCategoryNode              $category  The category
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function categoryTitle($params, $category)
	{
		return $params->get('show_page_heading') && $params->get('page_heading') ? $params->get('page_heading') : $category->title;
	}

	/**
	 * The description of a category layout, prepared by the content plugins, when the menu item shows it.
	 *
	 * @param   Joomla\Registry\Registry  $params    The view's parameters
	 * @param   JCategoryNode              $category  The category
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function categoryDescription($params, $category)
	{
		return $params->get('show_description', 1) && $category->description
			? JHtml::_('content.prepare', $category->description, '', 'com_content.category') : '';
	}

	/**
	 * A case study as a panel: the image as its canvas, the text over it.
	 *
	 * @param   object   $item   The article
	 * @param   boolean  $eager  The top of the page
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function panel($item, $eager = false)
	{
		$link  = static::link($item);
		$image = static::image($item, true);

		return '<article class="panel">'
			. ($image['src'] !== '' ? '<div class="panelImage">' . static::img($image, '100vw', $eager) . '</div>' : '')
			. '<div class="panelBody">' . static::tags($item, 3)
			. '<h2 class="panelTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h2>'
			. '<p class="panelText">' . static::excerpt($item, 200) . '</p>'
			. '<a class="btn btnLight" href="' . $link . '" tabindex="-1" aria-hidden="true">' . JText::_('TPL_ROOKWOOD_VIEW_PROJECT') . static::icon('arrow-right') . '</a>'
			. '</div></article>';
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
