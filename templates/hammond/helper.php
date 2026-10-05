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
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function listItem($item, $lead = false)
	{
		$link   = static::link($item);
		$author = static::author($item);

		return '<article class="item' . ($lead ? ' itemLead' : '') . '">'
			. static::figure($item, $link, 'itemImage', !$lead)
			. '<div class="itemBody">' . static::category($item)
			. '<h' . ($lead ? 2 : 3) . ' class="itemTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h' . ($lead ? 2 : 3) . '>'
			. '<p class="itemIntroText">' . static::excerpt($item, $lead ? 260 : 170) . '</p>'
			. '<div class="itemMeta">' . ($author !== '' ? '<span class="itemAuthor">' . static::e($author) . '</span>' : '') . static::date($item) . '</div>'
			. '</div></article>';
	}
}
