<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

/**
 * Shared helpers of the Hammond template: one way to show an article's image, category, author, date and excerpt in
 * every module layout and list view.
 *
 * @since  3.17.0
 */
abstract class HammondHelper
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
	 * @return  stdClass  params, siteName; for index also isHome, isPage, isList, hasSidebar, hasMenu, bodyClass
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
		$document->setMetaData('theme-color', '#ffffff');

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

		// The home page is made of the frontpage module grid, whatever its menu item shows (without modules there, e.g. on a new
		// site without sample data, it shows the component). Joomla also makes the home item active on pages without a menu item
		// of their own (tags, search), so the request must be the home item's own link.
		$home         = $active ? $active->query : array();
		$page->isHome = $active && $active->home && $document->countModules('frontpage')
			&& (isset($home['option']) ? $home['option'] : '') === $option && (isset($home['view']) ? $home['view'] : '') === $view
			&& (!isset($home['id']) || (int) $home['id'] === $input->getInt('id'));
		$page->isPage = static::isPage();
		$page->isList = !$page->isHome && in_array($option . '.' . $view,
			array('com_content.category', 'com_content.archive', 'com_content.featured', 'com_tags.tag', 'com_search.search'), true);
		$page->hasSidebar = !$page->isHome && !$page->isPage && $document->countModules('sidebar');
		$page->hasMenu    = $document->countModules('search') || $document->countModules('megamenu') || $document->countModules('megamenu-aside');
		$page->bodyClass  = implode(' ', array_filter(array(
			'site', $page->isHome ? 'isFrontpage' : 'isInner', $page->isPage ? 'isPage' : '', $page->isList ? 'isList' : '',
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
	 * The logo: the mark and the site's name, its first word in bold.
	 *
	 * @param   string  $class  Classes, e.g. "logo logoSmall"
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
			. '<svg class="logoMark" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="9" fill="currentColor"/>'
			. '<path d="M12 10.5v19M28 10.5v19M12 20h16" fill="none" stroke="#fff" stroke-width="4.6" stroke-linecap="round"/>'
			. '<circle cx="31.5" cy="8.5" r="2.6" fill="var(--c-accent)"/></svg>'
			. '<span class="logoText"><strong>' . static::e($space === false ? $name : substr($name, 0, $space)) . '</strong>'
			. ($space !== false ? ' <span>' . static::e(substr($name, $space + 1)) . '</span>' : '') . '</span></a>';
	}

	/**
	 * The links to the site's social profiles (the template's options), and its feed.
	 *
	 * @param   string  $class  Classes of the list
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function socialLinks($class = 'socialLinks')
	{
		$html = '<ul class="' . $class . '">';

		foreach (array('facebook' => 'Facebook', 'x' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'rss' => 'RSS') as $network => $label)
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

		return $html . '</ul>';
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
		return '<svg class="icon icon-' . $name . ($class ? ' ' . $class : '') . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"></use></svg>';
	}

	/**
	 * The article's image: the intro image, else the full article image, else the first image of the text.
	 *
	 * @param   object   $item  The article
	 * @param   boolean  $full  Prefer the full article image
	 *
	 * @return  array  src, alt, caption (empty src when there is none)
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
					'alt'     => $images->{'image_' . $type . '_alt'} ?? '',
					'caption' => $images->{'image_' . $type . '_caption'} ?? '',
				);
			}
		}

		if (preg_match('/<img[^>]+src="([^"]+)"/i', (string) ($item->introtext ?? ''), $match))
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
		$src = (string) $src;

		// Images of the media manager carry their size after a #
		$src = preg_replace('/#.*$/', '', $src);

		return preg_match('#^(https?:)?//#i', $src) ? $src : JUri::root(true) . '/' . ltrim($src, '/');
	}

	/**
	 * The image as a picture element (or nothing), linked to the article.
	 *
	 * @param   object   $item     The article
	 * @param   string   $link     The article's link
	 * @param   string   $class    Classes of the wrapper
	 * @param   boolean  $lazy     Load lazily
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function figure($item, $link, $class = 'itemImage', $lazy = true)
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
	 * The category label, linked to the category.
	 *
	 * @param   object  $item  The article
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function category($item)
	{
		$title = $item->category_title ?? '';

		if ($title === '')
		{
			return '';
		}

		$alias = $item->category_alias ?? JApplicationHelper::stringURLSafe($title);

		return '<a class="itemCategory cat-' . static::e($alias) . '" href="' . JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid, $item->language ?? '*')) . '">'
			. static::e($title) . '</a>';
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
	 * The publishing date: relative for today ("3 hours ago"), else the date.
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

		$date    = JFactory::getDate($value);
		$seconds = time() - $date->toUnix();

		if ($seconds >= 0 && $seconds < 86400)
		{
			$text = $seconds < 3600 ? JText::plural('TPL_HAMMOND_MINUTES_AGO', max(1, (int) floor($seconds / 60)))
				: JText::plural('TPL_HAMMOND_HOURS_AGO', (int) floor($seconds / 3600));
		}
		else
		{
			$text = JHtml::_('date', $value, JText::_('DATE_FORMAT_LC3'));
		}

		return '<time class="itemDate" datetime="' . $date->format('c') . '">' . static::e($text) . '</time>';
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
	 * How many items a module skips: a "skip-N" class (e.g. a row of featured articles after the main story, which shows
	 * the first one).
	 *
	 * @param   \Joomla\Registry\Registry  $params  The module's parameters
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public static function skip($params)
	{
		return preg_match('/\bskip-(\d+)\b/', (string) $params->get('moduleclass_sfx'), $match) ? (int) $match[1] : 0;
	}

	/**
	 * The page is one of the info pages (the "company" menu): centered, without sidebar.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isPage()
	{
		$active = JFactory::getApplication()->getMenu()->getActive();

		return $active && $active->menutype === static::params()->get('pagesMenu', 'companymenu')
			&& isset($active->query['option']) && JFactory::getApplication()->input->get('option') === $active->query['option'];
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
		$article->category_alias = $category ? $category->alias : '';

		return $article;
	}

	/**
	 * An article in a list view (category, archive, tag): image, category, title, excerpt, author and date.
	 *
	 * @param   object   $item   The article
	 * @param   boolean  $lead   The first, larger item
	 * @param   boolean  $eager  Load its image at once (the top of the page; lazily otherwise)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function listItem($item, $lead = false, $eager = false)
	{
		$link   = static::link($item);
		$author = static::author($item);

		return '<article class="item' . ($lead ? ' itemLead' : '') . '">'
			. static::figure($item, $link, 'itemImage', !$lead && !$eager)
			. '<div class="itemBody">' . static::category($item)
			. '<h' . ($lead ? 2 : 3) . ' class="itemTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h' . ($lead ? 2 : 3) . '>'
			. '<p class="itemIntroText">' . static::excerpt($item, $lead ? 260 : 170) . '</p>'
			. '<div class="itemMeta">' . ($author !== '' ? '<span class="itemAuthor">' . static::e($author) . '</span>' : '') . static::date($item) . '</div>'
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
