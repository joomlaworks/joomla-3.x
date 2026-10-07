<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.tinymce_latest
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * TinyMCE 8 editor. Shipped next to the TinyMCE 4 editor (plg_editors_tinymce, "TinyMCE (legacy)"), whose toolbars,
 * third-party TinyMCE plugins and content handling it doesn't share; sites choose either in Global Configuration.
 *
 * @since  3.17.0
 */
class PlgEditorTinymce_latest extends JPlugin
{
	/**
	 * Where TinyMCE and the plugin's scripts are
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	const MEDIA = 'media/editors/tinymce_latest';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var    JApplicationCms
	 * @since  3.17.0
	 */
	protected $app = null;

	/**
	 * Loads TinyMCE and the Joomla integration.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function onInit()
	{
		JHtml::_('behavior.core');
		JHtml::_('script', self::MEDIA . '/tinymce.min.js', array('version' => 'auto'));
		JHtml::_('script', self::MEDIA . '/js/tinymce.min.js', array('version' => 'auto'));
	}

	/**
	 * @param   string  $id  The editor's id
	 *
	 * @return  string  JavaScript which gets the content
	 *
	 * @since   3.17.0
	 */
	public function onGetContent($id)
	{
		return 'Joomla.editors.instances[' . json_encode($id) . '].getValue();';
	}

	/**
	 * @param   string  $id    The editor's id
	 * @param   string  $html  The content
	 *
	 * @return  string  JavaScript which sets the content
	 *
	 * @since   3.17.0
	 */
	public function onSetContent($id, $html)
	{
		return 'Joomla.editors.instances[' . json_encode($id) . '].setValue(' . json_encode($html) . ');';
	}

	/**
	 * TinyMCE copies the content to the textarea itself when the form is submitted.
	 *
	 * @param   string  $id  The editor's id
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function onSave($id)
	{
	}

	/**
	 * @param   string  $name  The editor's name
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function onGetInsertMethod($name)
	{
	}

	/**
	 * Display the editor.
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
	 * @since   3.17.0
	 */
	public function onDisplay(
		$name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		$id        = preg_replace('/(\s|[^A-Za-z0-9_])+/', '_', empty($id) ? $name : $id);
		$nameGroup = explode('[', (string) preg_replace('/\[\]|\]/', '', $name));
		$fieldName = end($nameGroup);
		$doc       = JFactory::getDocument();
		$options   = $doc->getScriptOptions('plg_editor_tinymce_latest');

		$textarea           = new stdClass;
		$textarea->name     = $name;
		$textarea->id       = $id;
		$textarea->class    = 'mce_editable joomla-editor-tinymce-latest';
		$textarea->cols     = $col;
		$textarea->rows     = $row;
		$textarea->width    = is_numeric($width) ? $width . 'px' : $width;
		$textarea->height   = is_numeric($height) ? $height . 'px' : $height;
		$textarea->content  = $content;
		$textarea->readonly = !empty($params['readonly']);

		// The editor buttons (editors-xtd plugins) below the editor, as with Joomla's other editors
		$xtd = is_array($buttons) || $buttons === true
			? JLayoutHelper::render('joomla.editors.buttons', $this->_subject->getButtons($id, $buttons, $asset, $author)) : '';

		$editor = '<div class="js-editor-tinymce-latest">'
			. JLayoutHelper::render('joomla.tinymce.textarea', $textarea)
			. $xtd
			. JLayoutHelper::render('plugins.editors.tinymce_latest.togglebutton', $id)
			. '</div>';

		// Per editor: its read-only state, by the field's name
		if (!isset($options['tinyMCE'][$fieldName]['joomla']['readonly']))
		{
			$options['tinyMCE'][$fieldName]['joomla']['readonly'] = !empty($params['readonly']);
			$options['tinyMCE'][$fieldName]['joomlaMergeDefaults'] = true;

			$doc->addScriptOptions('plg_editor_tinymce_latest', $options, false);
		}

		if (empty($options['tinyMCE']['default']))
		{
			$options['tinyMCE']['default'] = $this->getDefaultOptions();

			if ($options['tinyMCE']['default'] === null)
			{
				return '';
			}

			$doc->addScriptOptions('plg_editor_tinymce_latest', $options);
		}

		return $editor;
	}

	/**
	 * The TinyMCE options common to every editor on the page, from the set of the user's groups.
	 *
	 * @return  array|null
	 *
	 * @since   3.17.0
	 */
	protected function getDefaultOptions()
	{
		$app         = $this->app;
		$user        = JFactory::getUser();
		$language    = JFactory::getLanguage();
		$levelParams = $this->getSetParams($user);
		$joomla      = array();

		// Skin, as named in skins/ui
		$skin = (string) $levelParams->get($app->isClient('administrator') ? 'skin_admin' : 'skin', 'oxide');

		if (!preg_match('/^[a-z0-9_-]+$/i', $skin) || !is_dir(JPATH_ROOT . '/' . self::MEDIA . '/skins/ui/' . $skin))
		{
			$skin = 'oxide';
		}

		// Language: the user's (or its main language) when there's a translation, otherwise the chosen one
		$langCode = 'en';

		if ($levelParams->get('lang_mode', 1))
		{
			$tag = $language->getTag();

			foreach (array($tag, str_replace('-', '_', $tag), strtok($tag, '-')) as $candidate)
			{
				if (is_file(JPATH_ROOT . '/' . self::MEDIA . '/langs/' . $candidate . '.js'))
				{
					$langCode = $candidate;
					break;
				}
			}
		}
		elseif (preg_match('/^[a-zA-Z_-]+$/', (string) $levelParams->get('lang_code', 'en')))
		{
			$langCode = (string) $levelParams->get('lang_code', 'en');
		}

		$contentCss = $this->getContentCss($levelParams);

		if ($contentCss === false)
		{
			return null;
		}

		// Text filtering
		$ignoreFilter = false;

		if ($levelParams->get('use_config_textfilters', 0))
		{
			$filter        = static::getGlobalFilters();
			$ignoreFilter  = $filter === false;
			$tagBlacklist  = !empty($filter->tagBlacklist) ? $filter->tagBlacklist : array();
			$attrBlacklist = !empty($filter->attrBlacklist) ? $filter->attrBlacklist : array();

			// Blocked tags and attributes only (plg_editors_tinymce also read "tagArray"/"attrArray", which don't exist, so never more)
			$invalidElements  = implode(',', array_merge($tagBlacklist, $attrBlacklist));
			$validElements    = implode(',', array_diff(JFilterInput::getInstance()->tagBlacklist, $tagBlacklist));
			$extendedElements = '';
		}
		else
		{
			$invalidElements  = trim((string) $levelParams->get('invalid_elements', 'script,applet,iframe'));
			$extendedElements = trim((string) $levelParams->get('extended_elements', ''));
			$validElements    = trim((string) $levelParams->get('valid_elements', ''));
		}

		$elements = array('hr[id|title|alt|class|width|size|noshade]');

		if ($extendedElements !== '')
		{
			$elements = array_merge($elements, explode(',', $extendedElements));
		}

		$resizing = (bool) $levelParams->get('resizing', true);

		if ($resizing && $levelParams->get('resize_horizontal', true))
		{
			$resizing = 'both';
		}

		// Toolbars, from the set or else the preset for the user's groups
		if (!$levelParams->get('menu') && !$levelParams->get('toolbar1') && !$levelParams->get('toolbar2'))
		{
			$levelParams->loadArray($this->getPresetForUser($user));
		}

		$knownButtons = static::getKnownButtons();
		$menubar      = (array) $levelParams->get('menu', array());
		$toolbar      = array_merge((array) $levelParams->get('toolbar1', array()), (array) $levelParams->get('toolbar2', array()));
		$plugins      = array('autolink', 'lists', 'importcss');

		foreach ($toolbar as $button)
		{
			if (!empty($knownButtons[$button]['plugin']))
			{
				$plugins[] = $knownButtons[$button]['plugin'];
			}
		}

		foreach (array('wordcount' => 1, 'advlist' => 1, 'autosave' => 1) as $plugin => $default)
		{
			if ($levelParams->get($plugin, $default))
			{
				$plugins[] = $plugin;
			}
		}

		// Plugins the menus offer
		$menuPlugins = array(
			'insert' => array('link', 'image', 'media', 'anchor', 'charmap', 'pagebreak', 'insertdatetime', 'nonbreaking', 'codesample', 'accordion'),
			'view'   => array('code', 'visualblocks', 'visualchars', 'preview', 'fullscreen'),
			'table'  => array('table'),
			'tools'  => array('code', 'searchreplace'),
			'format' => array(),
			'help'   => array('help'),
		);

		foreach ($menubar as $menu)
		{
			if (isset($menuPlugins[$menu]))
			{
				$plugins = array_merge($plugins, $menuPlugins[$menu]);
			}
		}

		$customPlugin = trim((string) $levelParams->get('custom_plugin', ''));
		$customButton = trim((string) $levelParams->get('custom_button', ''));

		if ($customPlugin !== '')
		{
			$plugins = array_merge($plugins, preg_split('/[\s,]+/', $customPlugin, -1, PREG_SPLIT_NO_EMPTY));
		}

		if ($customButton !== '')
		{
			$toolbar = array_merge($toolbar, preg_split('/[\s,]+/', $customButton, -1, PREG_SPLIT_NO_EMPTY));
		}

		// Sets saved when the editor buttons were a toolbar menu ("CMS Content") may still have it: they're below the editor now
		$toolbar = array_values(array_diff($toolbar, array('jxtdbuttons')));

		// Templates (TinyMCE dropped its template plugin; the Joomla one inserts the HTML files of the templates folder)
		if (in_array('jtemplate', $toolbar, true))
		{
			$joomla['templates'] = $this->getTemplates();
			JText::script('PLG_TINYMCE_LATEST_TEMPLATE_DIALOG_TITLE');
			JText::script('PLG_TINYMCE_LATEST_TEMPLATE_LABEL');
			JText::script('PLG_TINYMCE_LATEST_TEMPLATE_PREVIEW');
			JText::script('PLG_TINYMCE_LATEST_TEMPLATE_NONE');
		}

		// Uploading images: dropped, pasted or chosen in the image dialog, into the images folder through com_media
		$upload = $levelParams->get('drag_drop', 1) && $user->authorise('core.create', 'com_media');

		if ($upload)
		{
			$path = trim(str_replace('\\', '/', (string) $levelParams->get('path', '')), '/');

			$joomla['upload'] = array(
				'url'    => JUri::base(true) . '/index.php?option=com_media&task=file.upload&format=json',
				'token'  => JSession::getFormToken(),
				'folder' => preg_match('#(^|/)\.\.(/|$)#', $path) ? '' : $path,
				'root'   => JUri::root(true),
			);

			JText::script('PLG_TINYMCE_LATEST_UPLOAD_FAILED');
		}

		JText::script('PLG_TINYMCE_LATEST_BUTTON_TOGGLE_EDITOR');

		$joomla['baseURL'] = JUri::root(true) . '/' . self::MEDIA;

		// Iframes keep working from TinyMCE's trusted domains, this site and the set's extra domains; others are sandboxed
		$trusted = array(
			'youtube.com', 'youtu.be', 'vimeo.com', 'player.vimeo.com', 'dailymotion.com', 'embed.music.apple.com', 'open.spotify.com',
			'giphy.com', 'dai.ly', 'codepen.io', JUri::getInstance()->getHost(),
		);

		foreach (preg_split('/[\s,]+/', (string) $levelParams->get('sandbox_iframes_exclusions', ''), -1, PREG_SPLIT_NO_EMPTY) as $domain)
		{
			if (preg_match('/^[a-z0-9.-]+$/i', $domain))
			{
				$trusted[] = strtolower($domain);
			}
		}

		$options = array(
			'joomla'          => $joomla,
			'license_key'     => 'gpl',
			'promotion'       => false,
			'branding'        => false,
			'language'        => $langCode,
			'directionality'  => $language->isRtl() ? 'rtl' : 'ltr',
			'skin'            => $skin,
			'menubar'         => $menubar ? implode(' ', array_unique($menubar)) : false,
			'toolbar'         => implode(' ', $toolbar),
			'toolbar_mode'    => in_array($levelParams->get('toolbar_mode'), array('floating', 'sliding', 'scrolling', 'wrap'), true)
				? $levelParams->get('toolbar_mode') : 'sliding',
			'toolbar_sticky'  => true,
			'plugins'         => implode(' ', array_values(array_unique($plugins))),
			'autosave_restore_when_empty' => false,

			// Output
			'browser_spellcheck'      => true,
			'entity_encoding'         => in_array($levelParams->get('entity_encoding'), array('named', 'numeric', 'raw'), true)
				? $levelParams->get('entity_encoding') : 'raw',
			'verify_html'             => !$ignoreFilter,
			'valid_elements'          => $validElements,
			'extended_valid_elements' => implode(',', $elements),
			'invalid_elements'        => $invalidElements,
			'paste_as_text'           => (bool) $levelParams->get('paste_as_text', 0),
			'end_container_on_empty_block' => true,

			'sandbox_iframes'            => (bool) $levelParams->get('sandbox_iframes', 1),
			'sandbox_iframes_exclusions' => array_values(array_unique($trusted)),
			'convert_unsafe_embeds'      => true,

			// URLs
			'relative_urls'      => (bool) $levelParams->get('relative_urls', 1),
			'remove_script_host' => false,
			'document_base_url'  => JUri::root(true) . '/',

			// Layout
			'content_css'      => $contentCss,
			// Large images and embeds stay within the editing area (no sideways scrolling); only in the editor, not on the site
			'content_style'    => 'img, video { max-width: 100%; height: auto; } iframe { max-width: 100%; }',
			'importcss_append' => true,
			'height'           => $this->params->get('html_height', '550px') ?: '550px',
			'width'            => $this->params->get('html_width', '') ?: null,
			'resize'           => $resizing,
			'elementpath'      => (bool) $levelParams->get('element_path', 1),
			'contextmenu'      => $levelParams->get('contextmenu', 1) ? null : false,

			// Images
			'image_advtab'      => (bool) $levelParams->get('image_advtab', 1),
			'image_title'       => true,
			'image_caption'     => true,
			'paste_data_images' => $upload,
			'automatic_uploads' => $upload,
			'images_reuse_filename' => true,

			'link_rel_list' => array(
				array('title' => 'None', 'value' => ''),
				array('title' => 'Alternate', 'value' => 'alternate'),
				array('title' => 'Author', 'value' => 'author'),
				array('title' => 'Bookmark', 'value' => 'bookmark'),
				array('title' => 'Help', 'value' => 'help'),
				array('title' => 'License', 'value' => 'license'),
				array('title' => 'Lightbox', 'value' => 'lightbox'),
				array('title' => 'Next', 'value' => 'next'),
				array('title' => 'No Follow', 'value' => 'nofollow'),
				array('title' => 'No Referrer', 'value' => 'noreferrer'),
				array('title' => 'Prefetch', 'value' => 'prefetch'),
				array('title' => 'Prev', 'value' => 'prev'),
				array('title' => 'Search', 'value' => 'search'),
				array('title' => 'Tag', 'value' => 'tag'),
			),
		);

		// New lines: paragraphs, or line breaks (Shift+Enter then starts a paragraph)
		$options['newline_behavior'] = $levelParams->get('newlines') ? 'invert' : 'default';

		return $options;
	}

	/**
	 * The options of the set assigned to one of the user's groups (sets are checked from the last, so the first set wins).
	 *
	 * @param   JUser  $user  The user
	 *
	 * @return  Registry
	 *
	 * @since   3.17.0
	 */
	protected function getSetParams($user)
	{
		$groups      = array_flip($user->getAuthorisedGroups());
		$levelParams = new Registry;
		$setOptions  = (array) $this->params->get('configuration.setoptions', array());
		$toolbars    = (array) $this->params->get('configuration.toolbars', array());
		$chosen      = null;

		krsort($setOptions);

		foreach ($setOptions as $set => $values)
		{
			foreach ((array) (isset($values->access) ? $values->access : array()) as $group)
			{
				if (isset($groups[$group]))
				{
					$chosen = $set;
				}
			}
		}

		if ($chosen !== null)
		{
			$levelParams->loadObject(isset($toolbars[$chosen]) ? (object) $toolbars[$chosen] : new stdClass);
			$levelParams->loadObject((object) $setOptions[$chosen]);
		}

		return $levelParams;
	}

	/**
	 * The toolbar preset for a user without a configured set: advanced for Administrator, Editor and Super Users,
	 * medium for Registered and Manager, simple for the others.
	 *
	 * @param   JUser  $user  The user
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getPresetForUser($user)
	{
		$groups  = array_flip($user->getAuthorisedGroups());
		$presets = static::getToolbarPreset();

		if (isset($groups[4]) || isset($groups[7]) || isset($groups[8]))
		{
			return $presets['advanced'];
		}

		return isset($groups[2]) || isset($groups[6]) ? $presets['medium'] : $presets['simple'];
	}

	/**
	 * The stylesheet for the content: the custom one, the default site template's editor.css or the system template's.
	 *
	 * @param   Registry  $levelParams  The set's options
	 *
	 * @return  string|null|false  The URL, null for none, false when the template couldn't be read
	 *
	 * @since   3.17.0
	 */
	protected function getContentCss(Registry $levelParams)
	{
		$custom = trim((string) $levelParams->get('content_css_custom', ''));

		if ($custom === '' && !$levelParams->get('content_css', 1))
		{
			return null;
		}

		$db = JFactory::getDbo();

		try
		{
			$template = (string) $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('template'))
					->from($db->quoteName('#__template_styles'))
					->where($db->quoteName('client_id') . ' = 0')
					->where($db->quoteName('home') . ' = ' . $db->quote('1'))
			)->loadResult();
		}
		catch (RuntimeException $e)
		{
			$this->app->enqueueMessage(JText::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}

		if ($custom !== '')
		{
			if (preg_match('#^https?://#i', $custom))
			{
				return $custom;
			}

			if (!is_file(JPATH_SITE . '/templates/' . $template . '/css/' . $custom))
			{
				JLog::add(JText::sprintf('PLG_TINYMCE_LATEST_ERR_CUSTOMCSSFILENOTPRESENT', $custom), JLog::WARNING, 'jerror');

				return JUri::root(true) . '/templates/' . $template . '/css/' . $custom;
			}

			return $this->versionedUrl('templates/' . $template . '/css/' . $custom);
		}

		foreach (array($template, 'system') as $folder)
		{
			if ($folder !== '' && is_file(JPATH_SITE . '/templates/' . $folder . '/css/editor.css'))
			{
				return $this->versionedUrl('templates/' . $folder . '/css/editor.css');
			}
		}

		JLog::add(JText::_('PLG_TINYMCE_LATEST_ERR_EDITORCSSFILENOTPRESENT'), JLog::WARNING, 'jerror');

		return null;
	}

	/**
	 * The URL of a file of the site with its modification time (?t=YYYYMMDD_HHii, as the templates' stylesheets), so that
	 * browsers fetch a changed editor.css instead of the copy they keep
	 *
	 * @param   string  $path  The file, relative to the site's root
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function versionedUrl($path)
	{
		return JUri::root(true) . '/' . $path . '?t=' . date('Ymd_Hi', filemtime(JPATH_SITE . '/' . $path));
	}

	/**
	 * The HTML templates (templates/*.html in the media folder), with their names from the language strings
	 * PLG_TINYMCE_LATEST_TEMPLATE_<NAME>_TITLE and _DESC.
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getTemplates()
	{
		$language  = JFactory::getLanguage();
		$templates = array();

		foreach (glob(JPATH_ROOT . '/' . self::MEDIA . '/templates/*.html') ?: array() as $file)
		{
			$name = basename($file, '.html');

			if ($name === 'index')
			{
				continue;
			}

			$key = 'PLG_TINYMCE_LATEST_TEMPLATE_' . strtoupper($name);

			$templates[] = array(
				'title'       => $language->hasKey($key . '_TITLE') ? JText::_($key . '_TITLE') : $name,
				'description' => $language->hasKey($key . '_DESC') ? JText::_($key . '_DESC') : '',
				'url'         => JUri::root(true) . '/' . self::MEDIA . '/templates/' . rawurlencode($name) . '.html',
			);
		}

		return $templates;
	}

	/**
	 * The global text filters for the user's groups (as in plg_editors_tinymce).
	 *
	 * @return  JFilterInput|false  False when the user's content isn't filtered
	 *
	 * @since   3.17.0
	 */
	protected static function getGlobalFilters()
	{
		$filters = JComponentHelper::getParams('com_config')->get('filters');
		$groups  = JAccess::getGroupsByUser(JFactory::getUser()->get('id'));

		$blackTags = $blackAttrs = $customTags = $customAttrs = $whiteTags = $whiteAttrs = array();
		$blackList = $customList = $whiteList = false;

		foreach ($groups as $groupId)
		{
			if (!isset($filters->$groupId))
			{
				continue;
			}

			$filterData = $filters->$groupId;
			$type       = strtoupper((string) $filterData->filter_type);

			if ($type === 'NONE')
			{
				return false;
			}

			if ($type === 'NH')
			{
				continue;
			}

			$tags  = array_filter(array_map('trim', explode(',', (string) $filterData->filter_tags)));
			$attrs = array_filter(array_map('trim', explode(',', (string) $filterData->filter_attributes)));

			if ($type === 'BL')
			{
				$blackList  = true;
				$blackTags  = array_merge($blackTags, $tags);
				$blackAttrs = array_merge($blackAttrs, $attrs);
			}
			elseif ($type === 'CBL' && ($tags || $attrs))
			{
				$customList  = true;
				$customTags  = array_merge($customTags, $tags);
				$customAttrs = array_merge($customAttrs, $attrs);
			}
			elseif ($type === 'WL')
			{
				$whiteList  = true;
				$whiteTags  = array_merge($whiteTags, $tags);
				$whiteAttrs = array_merge($whiteAttrs, $attrs);
			}
		}

		if ($customList)
		{
			$filter = JFilterInput::getInstance(array(), array(), 1, 1);

			if ($customTags)
			{
				$filter->tagBlacklist = array_unique($customTags);
			}

			if ($customAttrs)
			{
				$filter->attrBlacklist = array_unique($customAttrs);
			}

			return $filter;
		}

		if ($blackList)
		{
			$filter = JFilterInput::getInstance(array_diff(array_unique($blackTags), $whiteTags), array_diff(array_unique($blackAttrs), $whiteAttrs), 1, 1);

			if ($whiteTags)
			{
				$filter->tagBlacklist = array_diff($filter->tagBlacklist, $whiteTags);
			}

			if ($whiteAttrs)
			{
				$filter->attrBlacklist = array_diff($filter->attrBlacklist, $whiteAttrs);
			}

			return $filter;
		}

		if ($whiteList)
		{
			return JFilterInput::getInstance(array_unique($whiteTags), array_unique($whiteAttrs), 0, 0, 0);
		}

		return JFilterInput::getInstance();
	}

	/**
	 * The toolbar buttons the sets can use, by TinyMCE 8 name.
	 *
	 * @return  array  name => array(label[, text][, plugin])
	 *
	 * @since   3.17.0
	 */
	public static function getKnownButtons()
	{
		return array(
			'|'             => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_SEPARATOR'), 'text' => '|'),
			'undo'          => array('label' => 'Undo'),
			'redo'          => array('label' => 'Redo'),
			'bold'          => array('label' => 'Bold'),
			'italic'        => array('label' => 'Italic'),
			'underline'     => array('label' => 'Underline'),
			'strikethrough' => array('label' => 'Strikethrough'),
			'styles'        => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_STYLES'), 'text' => 'Formats'),
			'blocks'        => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_BLOCKS'), 'text' => 'Paragraph'),
			'fontfamily'    => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_FONTFAMILY'), 'text' => 'Fonts'),
			'fontsize'      => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_FONTSIZE'), 'text' => 'Font sizes'),
			'alignleft'     => array('label' => 'Align left'),
			'aligncenter'   => array('label' => 'Align center'),
			'alignright'    => array('label' => 'Align right'),
			'alignjustify'  => array('label' => 'Justify'),
			'lineheight'    => array('label' => 'Line height'),
			'outdent'       => array('label' => 'Decrease indent'),
			'indent'        => array('label' => 'Increase indent'),
			'forecolor'     => array('label' => 'Text color'),
			'backcolor'     => array('label' => 'Background color'),
			'bullist'       => array('label' => 'Bullet list'),
			'numlist'       => array('label' => 'Numbered list'),
			'link'          => array('label' => 'Insert/edit link', 'plugin' => 'link'),
			'unlink'        => array('label' => 'Remove link', 'plugin' => 'link'),
			'subscript'     => array('label' => 'Subscript'),
			'superscript'   => array('label' => 'Superscript'),
			'blockquote'    => array('label' => 'Blockquote'),
			'cut'           => array('label' => 'Cut'),
			'copy'          => array('label' => 'Copy'),
			'paste'         => array('label' => 'Paste'),
			'pastetext'     => array('label' => 'Paste as text'),
			'removeformat'  => array('label' => 'Clear formatting'),

			'accordion'      => array('label' => 'Accordion', 'plugin' => 'accordion'),
			'anchor'         => array('label' => 'Anchor', 'plugin' => 'anchor'),
			'hr'             => array('label' => 'Horizontal line'),
			'ltr'            => array('label' => 'Left to right', 'plugin' => 'directionality'),
			'rtl'            => array('label' => 'Right to left', 'plugin' => 'directionality'),
			'code'           => array('label' => 'Source code', 'plugin' => 'code'),
			'codesample'     => array('label' => 'Insert/edit code sample', 'plugin' => 'codesample'),
			'table'          => array('label' => 'Table', 'plugin' => 'table'),
			'charmap'        => array('label' => 'Special character', 'plugin' => 'charmap'),
			'visualchars'    => array('label' => 'Show invisible characters', 'plugin' => 'visualchars'),
			'visualblocks'   => array('label' => 'Show blocks', 'plugin' => 'visualblocks'),
			'nonbreaking'    => array('label' => 'Nonbreaking space', 'plugin' => 'nonbreaking'),
			'emoticons'      => array('label' => 'Emojis', 'plugin' => 'emoticons'),
			'image'          => array('label' => 'Insert/edit image', 'plugin' => 'image'),
			'media'          => array('label' => 'Insert/edit media', 'plugin' => 'media'),
			'pagebreak'      => array('label' => 'Page break', 'plugin' => 'pagebreak'),
			'print'          => array('label' => 'Print'),
			'preview'        => array('label' => 'Preview', 'plugin' => 'preview'),
			'fullscreen'     => array('label' => 'Fullscreen', 'plugin' => 'fullscreen'),
			'searchreplace'  => array('label' => 'Find and replace', 'plugin' => 'searchreplace'),
			'insertdatetime' => array('label' => 'Insert date/time', 'plugin' => 'insertdatetime'),
			'help'           => array('label' => 'Help', 'plugin' => 'help'),
			'jtemplate'      => array('label' => JText::_('PLG_TINYMCE_LATEST_TOOLBAR_BUTTON_TEMPLATE')),
		);
	}

	/**
	 * The toolbar presets.
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public static function getToolbarPreset()
	{
		return array(
			'simple' => array(
				'menu'     => array(),
				'toolbar1' => array(
					'bold', 'underline', 'strikethrough', '|',
					'undo', 'redo', '|',
					'bullist', 'numlist', '|',
					'pastetext',
				),
				'toolbar2' => array(),
			),
			'medium' => array(
				'menu'     => array('edit', 'insert', 'view', 'format', 'table', 'tools', 'help'),
				'toolbar1' => array(
					'bold', 'italic', 'underline', 'strikethrough', '|',
					'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
					'blocks', '|',
					'bullist', 'numlist', '|',
					'outdent', 'indent', '|',
					'undo', 'redo', '|',
					'link', 'unlink', 'anchor', 'code', '|',
					'hr', 'table', '|',
					'subscript', 'superscript', '|',
					'charmap', 'pastetext', 'preview',
				),
				'toolbar2' => array(),
			),
			'advanced' => array(
				'menu'     => array('edit', 'insert', 'view', 'format', 'table', 'tools', 'help'),
				'toolbar1' => array(
					'bold', 'italic', 'underline', 'strikethrough', '|',
					'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
					'lineheight', '|',
					'styles', '|',
					'blocks', 'fontfamily', 'fontsize', '|',
					'searchreplace', '|',
					'bullist', 'numlist', 'accordion', '|',
					'outdent', 'indent', '|',
					'undo', 'redo', '|',
					'link', 'unlink', 'anchor', 'image', '|',
					'code', '|',
					'forecolor', 'backcolor', '|',
					'fullscreen', '|',
					'table', '|',
					'subscript', 'superscript', '|',
					'charmap', 'emoticons', 'media', 'hr', 'ltr', 'rtl', '|',
					'cut', 'copy', 'paste', 'pastetext', '|',
					'visualchars', 'visualblocks', 'nonbreaking', 'blockquote', 'jtemplate', '|',
					'print', 'preview', 'codesample', 'insertdatetime', 'removeformat',
				),
				'toolbar2' => array(),
			),
		);
	}
}
