<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Blocks;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;

/**
 * Turns blocks (Editor.js' JSON: a list of {type, data}) into HTML, one layout per block type: layouts/joomla/blocks/<type>.php,
 * which a site template overrides as html/layouts/joomla/blocks/<type>.php. A type without a layout writes nothing.
 *
 * Layouts get the block's data and these helpers: inline() for text which may hold inline formatting (bold, italic, links...),
 * url() for addresses, e() for plain text. Values are never written without one of them.
 *
 * @since  3.18.0
 */
class BlockRenderer
{
	/**
	 * Inline elements text may keep, with the attributes they may keep
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const INLINE = array(
		'a'      => array('href'),
		'b'      => array(),
		'strong' => array(),
		'i'      => array(),
		'em'     => array(),
		'u'      => array('class'),
		's'      => array(),
		'mark'   => array('class'),
		'code'   => array('class'),
		'sub'    => array(),
		'sup'    => array(),
		'br'     => array(),
	);

	/**
	 * The site template whose overrides apply
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	protected $template;

	/**
	 * @param   string  $template  The site template (its folder name); the site's default template when empty
	 *
	 * @since   3.18.0
	 */
	public function __construct($template = '')
	{
		$this->template = $template !== '' ? $template : static::getSiteTemplate();
	}

	/**
	 * The HTML of a list of blocks.
	 *
	 * @param   array  $blocks  The blocks: arrays with "type" and "data"
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function render(array $blocks)
	{
		$html = array();

		foreach ($blocks as $block)
		{
			$block = (array) $block;
			$type  = isset($block['type']) ? (string) $block['type'] : '';

			if (!preg_match('/^[a-z][a-z0-9_-]{0,49}$/', $type))
			{
				continue;
			}

			$output = $this->renderBlock($type, isset($block['data']) && is_array($block['data']) ? $block['data'] : array(), $block);

			if ($output !== '')
			{
				$html[] = $output;
			}
		}

		return implode("\n", $html);
	}

	/**
	 * The HTML of one block, through its layout.
	 *
	 * @param   string  $type   The block type
	 * @param   array   $data   Its data
	 * @param   array   $block  The whole block (id, tunes)
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function renderBlock($type, array $data, array $block)
	{
		$layout = new FileLayout('joomla.blocks.' . $type, null, array('client' => 0, 'component' => 'none'));
		$paths  = array(JPATH_ROOT . '/layouts');

		if ($this->template !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $this->template))
		{
			array_unshift($paths, JPATH_ROOT . '/templates/' . $this->template . '/html/layouts');
		}

		$layout->setIncludePaths($paths);

		// Nothing when no layout of that name exists
		return trim($layout->render(array('type' => $type, 'data' => $data, 'block' => $block, 'renderer' => $this)));
	}

	/**
	 * Text which may hold inline formatting, with nothing but the elements and attributes of INLINE left, and links only to
	 * http(s), mailto, tel, the site's own pages or anchors.
	 *
	 * @param   mixed  $html  The text
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function inline($html)
	{
		$html = (string) $html;

		if ($html === '')
		{
			return '';
		}

		$attributes = array();

		foreach (static::INLINE as $names)
		{
			$attributes = array_merge($attributes, $names);
		}

		$filter = InputFilter::getInstance(array_keys(static::INLINE), array_unique($attributes), 0, 0);
		$html   = $filter->clean($html, 'html');

		// Links to other schemes lose their address
		return preg_replace_callback('/\shref="([^"]*)"/i', function ($match)
		{
			$url = $this->url(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));

			return $url === '' ? '' : ' href="' . $this->e($url) . '"';
		}, $html);
	}

	/**
	 * An address which may be written: http(s), mailto and tel addresses, paths of the site and anchors. Anything else is
	 * empty.
	 *
	 * @param   mixed  $url  The address
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function url($url)
	{
		$url = trim((string) $url);

		if ($url === '' || preg_match('/[\x00-\x1f\x7f]/', $url))
		{
			return '';
		}

		if (preg_match('#^(https?:)?//#i', $url) || preg_match('#^(mailto|tel):#i', $url))
		{
			return $url;
		}

		// A scheme of any other kind (javascript:, data:...)
		if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url))
		{
			return '';
		}

		return $url;
	}

	/**
	 * An address of the site written as editors store it: images/x.jpg rather than /subfolder/images/x.jpg.
	 *
	 * @param   mixed  $url  The address
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function siteUrl($url)
	{
		$url  = $this->url($url);
		$root = rtrim(Uri::root(true), '/') . '/';

		// /subfolder/images/x.jpg (or /images/x.jpg on a site at the domain's root), never //host/...
		if ($url !== '' && strpos($url, '//') !== 0 && strpos($url, $root) === 0)
		{
			return substr($url, strlen($root));
		}

		return $url;
	}

	/**
	 * Plain text, escaped.
	 *
	 * @param   mixed  $text  The text
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function e($text)
	{
		return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * The plain text of text which may hold inline formatting (for alt and title attributes).
	 *
	 * @param   mixed  $html  The text
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function plain($html)
	{
		return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', (string) $html)), ENT_QUOTES, 'UTF-8')));
	}

	/**
	 * The site's default template (its folder name).
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public static function getSiteTemplate()
	{
		try
		{
			$db = Factory::getDbo();

			return (string) $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('template'))
					->from($db->quoteName('#__template_styles'))
					->where($db->quoteName('client_id') . ' = 0')
					->where($db->quoteName('home') . ' = ' . $db->quote('1'))
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			return '';
		}
	}
}
