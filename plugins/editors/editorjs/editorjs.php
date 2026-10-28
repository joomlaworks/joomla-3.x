<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.editorjs
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Blocks\BlockRenderer;

/**
 * Blocks (Editor.js): content written as blocks (paragraphs, headings, lists, images, embeds, tables...). The form still gets
 * HTML, which the site, modules, feeds and search read: when the content is saved, the blocks are rendered on the server
 * through layouts (layouts/joomla/blocks/<type>.php, which the site template may override), and the blocks themselves go
 * with it, base64-encoded in a hidden element, so the editor opens them as blocks again.
 *
 * @since  3.18.0
 */
class PlgEditorEditorjs extends JPlugin
{
	/**
	 * Where Editor.js, its tools and the plugin's scripts are
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const MEDIA = 'plugins/editors/editorjs';

	/**
	 * Editor.js and the tools, in the order they're loaded
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const VENDOR = array('editorjs', 'header', 'list', 'image', 'embed', 'quote', 'table', 'code', 'raw', 'delimiter', 'warning', 'marker',
		'inline-code', 'underline');

	/**
	 * The largest content the server renders, in bytes of JSON
	 *
	 * @var    integer
	 * @since  3.18.0
	 */
	const MAX_JSON = 8388608;

	/**
	 * @var    boolean
	 * @since  3.18.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var    JApplicationCms
	 * @since  3.18.0
	 */
	protected $app = null;

	/**
	 * Loads Editor.js, its tools and the Joomla integration.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onInit()
	{
		$min = JDEBUG ? '' : '.min';

		JHtml::_('behavior.core');

		foreach (static::VENDOR as $name)
		{
			JHtml::_('script', self::MEDIA . '/vendor/' . $name . '/' . $name . '.umd.js', array('version' => 'auto'));
		}

		JHtml::_('stylesheet', self::MEDIA . '/css/editorjs' . $min . '.css', array('version' => 'auto'));
		JHtml::_('script', self::MEDIA . '/js/editorjs' . $min . '.js', array('version' => 'auto'));

		foreach (array('READMORE', 'READMORE_HINT', 'READMORE_ONE', 'RENDER_FAILED', 'UPLOAD_FAILED', 'REBUILT', 'PLACEHOLDER', 'IMAGE_ALT', 'IMAGE_CHOOSE',
			'IMAGE_REMOVE', 'REMOVE', 'PREVIEW', 'PREVIEW_TITLE', 'PREVIEW_DESKTOP', 'PREVIEW_TABLET', 'PREVIEW_PHONE', 'CLOSE', 'FULLSCREEN',
			'FULLSCREEN_EXIT') as $key)
		{
			JText::script('PLG_EDITORS_EDITORJS_' . $key);
		}
	}

	/**
	 * @param   string  $id  The editor's id
	 *
	 * @return  string  JavaScript which gets the content (HTML)
	 *
	 * @since   3.18.0
	 */
	public function onGetContent($id)
	{
		return 'Joomla.editors.instances[' . json_encode($id) . '].getValue();';
	}

	/**
	 * @param   string  $id    The editor's id
	 * @param   string  $html  The content (HTML)
	 *
	 * @return  string  JavaScript which sets the content
	 *
	 * @since   3.18.0
	 */
	public function onSetContent($id, $html)
	{
		return 'Joomla.editors.instances[' . json_encode($id) . '].setValue(' . json_encode($html) . ');';
	}

	/**
	 * Views call this before submitting; the editor brings the form's field up to date itself, before the form is sent.
	 *
	 * @param   string  $id  The editor's id
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function onSave($id)
	{
		return '';
	}

	/**
	 * @param   string  $name  The editor's name
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onGetInsertMethod($name)
	{
	}

	/**
	 * Display the editor: the form's textarea (which keeps the HTML the form posts), hidden, the editor's area and the editor
	 * buttons below it.
	 *
	 * @param   string   $name     The name of the editor area
	 * @param   string   $content  The content
	 * @param   string   $width    The width of the editor area
	 * @param   string   $height   The height of the editor area
	 * @param   int      $col      The number of columns
	 * @param   int      $row      The number of rows
	 * @param   boolean  $buttons  True, false or a list of editor buttons to leave out
	 * @param   string   $id       The textarea's id; the name when empty
	 * @param   string   $asset    The asset of the item
	 * @param   object   $author   The author of the item
	 * @param   array    $params   Editor parameters (e.g. readonly)
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public function onDisplay(
		$name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		$id      = preg_replace('/(\s|[^A-Za-z0-9_])+/', '_', empty($id) ? $name : $id);
		$hidden  = is_array($buttons) ? $buttons : array();
		$options = array(
			'height'   => $this->cssSize($this->params->get('height', '500px'), '500px'),
			'width'    => $this->cssSize($this->params->get('width', '800px'), '800px'),
			'readonly' => !empty($params['readonly']),
			'readmore' => !in_array('readmore', $hidden, true),
			'render'   => array(
				'url'   => JUri::base(true) . '/index.php?option=com_ajax&group=editors&plugin=editorjs&format=json',
				'token' => JSession::getFormToken(),
			),
			'root'     => JUri::root(true),
			'embeds'   => array('youtube', 'vimeo', 'twitter', 'instagram', 'codepen'),
			// Templates' blocks: the site template's in the block menu, other installed templates' so their blocks open
			'blocks'   => array_values(BlockRenderer::getDefinitions()),
		);

		// Images dropped, pasted or chosen in an image block go to the images folder through the Media Manager, optimised as its
		// options say (and with a srcset when it saved a copy for small screens)
		if ($this->params->get('upload', 1) && JFactory::getUser()->authorise('core.create', 'com_media'))
		{
			$folder = trim(str_replace('\\', '/', (string) $this->params->get('folder', 'uploads')), '/');

			$options['upload'] = array(
				'url'    => JUri::base(true) . '/index.php?option=com_media&task=file.upload&format=json',
				'token'  => JSession::getFormToken(),
				'folder' => preg_match('#(^|/)\.\.(/|$)|[^A-Za-z0-9_/-]#', $folder) ? '' : $folder,
			);
		}

		// The editor buttons (editors-xtd plugins) below the editor, as with Joomla's other editors, except Page Break: a page
		// break means nothing between blocks (page breaks already in the content stay, as Raw HTML blocks)
		$xtd = is_array($buttons) || $buttons === true
			? JLayoutHelper::render('joomla.editors.buttons', $this->_subject->getButtons($id, array_merge($hidden, array('pagebreak')), $asset, $author))
			: '';

		return '<div class="js-editor-editorjs">'
			. '<textarea name="' . htmlspecialchars($name, ENT_COMPAT, 'UTF-8') . '" id="' . $id . '" cols="' . (int) $col . '" rows="' . (int) $row
			. '" class="joomla-editor-editorjs" style="display:none;"'
			. ' data-editorjs="' . htmlspecialchars(json_encode($options), ENT_COMPAT, 'UTF-8') . '"'
			. (!empty($params['readonly']) ? ' readonly="readonly"' : '') . '>'
			// Joomla's editor field passes the content escaped already
			. $this->checkBlocks($content)
			. '</textarea>'
			. '<div class="editorjs-bar">'
			. '<button type="button" class="btn btn-small editorjs-preview" data-editorjs-action="preview">' . JText::_('PLG_EDITORS_EDITORJS_PREVIEW') . '</button>'
			. '<button type="button" class="btn btn-small editorjs-fullscreen" data-editorjs-action="fullscreen" aria-pressed="false">'
			. JText::_('PLG_EDITORS_EDITORJS_FULLSCREEN') . '</button>'
			. '</div>'
			. '<div class="editorjs-holder" id="' . $id . '_editorjs" style="--editorjs-height:' . htmlspecialchars($options['height'], ENT_COMPAT, 'UTF-8')
			. ';--editorjs-width:' . htmlspecialchars($options['width'], ENT_COMPAT, 'UTF-8') . ';"></div>'
			. $xtd
			. '</div>';
	}

	/**
	 * Renders blocks for the editor (through com_ajax, see PlgEditorsEditorjs): "blocks", the editor's JSON, and the form token,
	 * posted by a user who is logged in. Returns the HTML, which the editor puts in the form. Nothing is saved here: the form
	 * is, with the text filters of Global Configuration, as with any editor.
	 *
	 * @param   JInput  $input  The request
	 *
	 * @return  array  html
	 *
	 * @since   3.18.0
	 */
	public static function renderRequest($input)
	{
		JFactory::getLanguage()->load('plg_editors_editorjs', JPATH_ADMINISTRATOR);

		if (!JSession::checkToken('post') || JFactory::getUser()->guest)
		{
			throw new RuntimeException(JText::_('JINVALID_TOKEN'), 403);
		}

		$json = $input->post->get('blocks', '', 'raw');

		if (!is_string($json) || $json === '' || strlen($json) > static::MAX_JSON)
		{
			throw new RuntimeException(JText::_('PLG_EDITORS_EDITORJS_RENDER_FAILED'), 400);
		}

		$data = json_decode($json, true);

		if (!is_array($data) || !isset($data['blocks']) || !is_array($data['blocks']))
		{
			throw new RuntimeException(JText::_('PLG_EDITORS_EDITORJS_RENDER_FAILED'), 400);
		}

		// Raw HTML goes through the saving user's text filter, as the form's HTML does, so blocks can't keep what the filters
		// remove (they're signed below and opened again later, perhaps by someone whose filter keeps more)
		$blocks      = array();
		$definitions = BlockRenderer::getDefinitions();

		foreach ($data['blocks'] as $block)
		{
			if (!is_array($block) || !isset($block['type']) || !is_string($block['type']))
			{
				continue;
			}

			$block = array('type' => $block['type'], 'data' => isset($block['data']) && is_array($block['data']) ? $block['data'] : array());

			if ($block['type'] === 'raw')
			{
				$block['data']['html'] = JComponentHelper::filterText(isset($block['data']['html']) ? (string) $block['data']['html'] : '');
			}

			// Templates' blocks: every value too, so a layout which writes one as it is still can't write what the filters remove;
			// by the definition's field types when the block's template is installed
			if ($block['type'] === 'template')
			{
				$key = (isset($block['data']['template']) ? (string) $block['data']['template'] : '') . '/'
					. (isset($block['data']['block']) ? (string) $block['data']['block'] : '');
				$fields = isset($block['data']['fields']) && is_array($block['data']['fields']) ? $block['data']['fields'] : array();

				$block['data'] = array(
					'template' => isset($block['data']['template']) ? (string) $block['data']['template'] : '',
					'block'    => isset($block['data']['block']) ? (string) $block['data']['block'] : '',
					'fields'   => isset($definitions[$key]) ? static::filterFields($definitions[$key]['fields'], $fields) : static::filterValues($fields),
				);
			}

			$blocks[] = $block;
		}

		$renderer = new BlockRenderer;
		$encoded  = base64_encode(json_encode(array('blocks' => $blocks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		$html   = $renderer->render($blocks);
		$result = array('html' => $html, 'blocks' => $encoded, 'signature' => static::sign($encoded));

		// The preview: a page showing the blocks as the site template shows content
		if ($input->post->getInt('preview'))
		{
			$result['document'] = $renderer->preview($html);
		}

		return $result;
	}

	/**
	 * A template block's values, by its definition's fields (others are left out), through the user's text filter: formatted
	 * text as HTML; plain values (text, links, lists, alt text), which layouts escape, filtered and then unescaped again, so
	 * "a & b" stays "a & b" rather than "a &amp;amp; b" once written.
	 *
	 * @param   array    $definition  The fields of the definition (or of a group)
	 * @param   array    $values      The values
	 * @param   integer  $depth       How deep this is (groups hold no groups)
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected static function filterFields(array $definition, array $values, $depth = 0)
	{
		$plain  = function ($value)
		{
			return is_string($value) ? html_entity_decode(JComponentHelper::filterText($value), ENT_QUOTES, 'UTF-8') : '';
		};
		$result = array();

		foreach ($definition as $field)
		{
			$value = isset($values[$field['name']]) ? $values[$field['name']] : null;

			switch ($field['type'])
			{
				case 'richtext':
					$result[$field['name']] = is_string($value) ? JComponentHelper::filterText($value) : '';
					break;

				case 'checkbox':
					$result[$field['name']] = (bool) $value;
					break;

				case 'image':
					$result[$field['name']] = !is_array($value) ? null : array(
						'url'    => $plain(isset($value['url']) ? $value['url'] : ''),
						'alt'    => $plain(isset($value['alt']) ? $value['alt'] : ''),
						'width'  => isset($value['width']) ? (int) $value['width'] : 0,
						'height' => isset($value['height']) ? (int) $value['height'] : 0,
						'srcset' => array_values(array_map(function ($source) use ($plain)
						{
							return array('location' => $plain(isset($source['location']) ? $source['location'] : ''), 'width' => isset($source['width']) ? (int) $source['width'] : 0);
						}, array_filter(isset($value['srcset']) && is_array($value['srcset']) ? $value['srcset'] : array(), 'is_array'))),
					);
					break;

				case 'group':
					$entries = $depth === 0 && is_array($value) ? array_slice(array_values(array_filter($value, 'is_array')), 0, (int) $field['max']) : array();

					$result[$field['name']] = array_map(function ($entry) use ($field, $depth)
					{
						return static::filterFields($field['fields'], $entry, $depth + 1);
					}, $entries);
					break;

				default:
					$result[$field['name']] = $plain($value);
			}
		}

		return $result;
	}

	/**
	 * Every string of a value (arrays walked, to a depth of 5) through the user's text filter.
	 *
	 * @param   mixed    $value  The value
	 * @param   integer  $depth  How deep this is
	 *
	 * @return  mixed
	 *
	 * @since   3.18.0
	 */
	protected static function filterValues($value, $depth = 0)
	{
		if (is_string($value))
		{
			return JComponentHelper::filterText($value);
		}

		if (!is_array($value))
		{
			return is_int($value) || is_float($value) || is_bool($value) ? $value : '';
		}

		if ($depth > 5)
		{
			return array();
		}

		foreach ($value as $key => $item)
		{
			$value[$key] = static::filterValues($item, $depth + 1);
		}

		return $value;
	}

	/**
	 * The signature of encoded blocks, with the site's secret: only blocks this site rendered (and filtered) open as blocks.
	 *
	 * @param   string  $encoded  The base64 JSON
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected static function sign($encoded)
	{
		return hash_hmac('sha256', 'editorjs:' . $encoded, (string) JFactory::getConfig()->get('secret'));
	}

	/**
	 * Content as the editor gets it: the hidden blocks element is kept only in the exact form the editor writes (other
	 * editors may leave a space in it) and with a valid signature; any other data-blocks attribute (written by hand, changed, or reordered by another editor) is disarmed,
	 * so its blocks are made from the HTML, which the text filters checked. The content is escaped, as Joomla's editor field
	 * passes it.
	 *
	 * @param   string  $content  The content, escaped
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function checkBlocks($content)
	{
		$content = (string) $content;

		if (stripos($content, 'data-blocks') === false)
		{
			return $content;
		}

		$kept    = array();
		$content = preg_replace_callback(
			'#&lt;div class=&quot;joomla-blocks&quot; hidden=&quot;hidden&quot; data-version=&quot;1&quot; data-hash=&quot;[0-9a-f]{1,8}&quot;'
			. ' data-signature=&quot;([0-9a-f]{64})&quot; data-blocks=&quot;([A-Za-z0-9+/=]+)&quot;&gt;'
			// Other editors write an empty element with a (non-breaking) space in it: TinyMCE, EasyMDE
			. '(?:[ \t\r\n]|&amp;nbsp;|&amp;\#160;|\xC2\xA0)*&lt;/div&gt;#',
			function ($match) use (&$kept)
			{
				if (!$kept && hash_equals(static::sign($match[2]), $match[1]))
				{
					$kept[] = $match[0];

					return "\x01blocks\x01";
				}

				return '';
			},
			$content
		);

		return str_replace("\x01blocks\x01", $kept ? $kept[0] : '', preg_replace('#data-blocks#i', 'data-blocks-unsigned', $content));
	}

	/**
	 * A CSS size from the options: a number (pixels) or a number with px, em, rem, vh or %.
	 *
	 * @param   string  $value     The option
	 * @param   string  $fallback  When it's neither
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function cssSize($value, $fallback)
	{
		$value = trim((string) $value);

		if (preg_match('/^\d+(\.\d+)?$/', $value))
		{
			return $value . 'px';
		}

		return preg_match('/^\d+(\.\d+)?(px|em|rem|vh|%)$/', $value) ? $value : $fallback;
	}
}

/**
 * The plugin as com_ajax loads it (option=com_ajax&group=editors&plugin=editorjs&format=json): JPluginHelper names plugin
 * classes Plg<group><name>, while editors are PlgEditor<name>. It only answers the editor's rendering requests.
 *
 * @since  3.18.0
 */
class PlgEditorsEditorjs extends JPlugin
{
	/**
	 * @return  array  html
	 *
	 * @since   3.18.0
	 */
	public function onAjaxEditorjs()
	{
		return PlgEditorEditorjs::renderRequest(JFactory::getApplication()->input);
	}
}
