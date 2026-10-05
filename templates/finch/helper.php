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
	 * A post as a card of a list: image, category, title, excerpt, date and reading time.
	 *
	 * @param   object   $item  The post
	 * @param   boolean  $lead  The first, large post
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function card($item, $lead = false)
	{
		$link    = static::link($item);
		$minutes = isset($item->fulltext) || isset($item->text) ? JText::sprintf('TPL_FINCH_READING_TIME', static::readingTime($item)) : '';

		return '<article class="post' . ($lead ? ' postLead' : '') . '">'
			. static::figure($item, $link, 'postImage', !$lead)
			. '<div class="postBody">' . static::category($item)
			. '<h' . ($lead ? 2 : 3) . ' class="postTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h' . ($lead ? 2 : 3) . '>'
			. '<p class="postExcerpt">' . static::excerpt($item, $lead ? 300 : 180) . '</p>'
			. '<div class="postMeta">' . static::date($item) . ($minutes !== '' ? '<span class="postReading">' . $minutes . '</span>' : '') . '</div>'
			. '</div></article>';
	}

	/**
	 * A list of posts: the first one large (on the first page of a list), the others as cards in a grid.
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
		$items = array_values($items);
		$html  = '';

		if ($lead && $items)
		{
			$html .= static::card(array_shift($items), true);
		}

		if ($items)
		{
			$html .= '<div class="postGrid">';

			foreach ($items as $item)
			{
				$html .= static::card($item);
			}

			$html .= '</div>';
		}

		return '<div class="postList">' . $html . '</div>';
	}
}
