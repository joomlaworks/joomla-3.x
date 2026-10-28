<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Editor;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\Registry\Registry;

/**
 * Editor class to handle WYSIWYG editors
 *
 * @since  1.5
 */
class Editor extends \JObject
{
	/**
	 * An array of Observer objects to notify
	 *
	 * @var    array
	 * @since  1.5
	 */
	protected $_observers = array();

	/**
	 * The state of the observable object
	 *
	 * @var    mixed
	 * @since  1.5
	 */
	protected $_state = null;

	/**
	 * A multi dimensional array of [function][] = key for observers
	 *
	 * @var    array
	 * @since  1.5
	 */
	protected $_methods = array();

	/**
	 * Editor Plugin object
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $_editor = null;

	/**
	 * Editor Plugin name
	 *
	 * @var    string
	 * @since  1.5
	 */
	protected $_name = null;

	/**
	 * Object asset
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $asset = null;

	/**
	 * Object author
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $author = null;

	/**
	 * Editor instances container.
	 *
	 * @var    Editor[]
	 * @since  2.5
	 */
	protected static $instances = array();

	/**
	 * The content editors which can be switched between, with their names' language strings
	 *
	 * @var    array
	 * @since  3.18.0
	 */
	const SWITCHABLE_EDITORS = array(
		'tinymce_latest' => 'JLIB_EDITOR_SWITCH_TINYMCE_LATEST',
		'tinymce'        => 'JLIB_EDITOR_SWITCH_TINYMCE',
		'easymde'        => 'JLIB_EDITOR_SWITCH_EASYMDE',
		'editorjs'       => 'JLIB_EDITOR_SWITCH_EDITORJS',
	);

	/**
	 * The session namespace of the editor chosen with the switcher
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const SWITCH_NAMESPACE = 'editorswitch';

	/**
	 * Constructor
	 *
	 * @param   string  $editor  The editor name
	 */
	public function __construct($editor = 'none')
	{
		$this->_name = $editor;
	}

	/**
	 * Returns the global Editor object, only creating it
	 * if it doesn't already exist.
	 *
	 * @param   string  $editor  The editor to use.
	 *
	 * @return  Editor The Editor object.
	 *
	 * @since   1.5
	 */
	public static function getInstance($editor = 'none')
	{
		$signature = serialize($editor);

		if (empty(self::$instances[$signature]))
		{
			self::$instances[$signature] = new Editor($editor);
		}

		return self::$instances[$signature];
	}

	/**
	 * Get the state of the Editor object
	 *
	 * @return  mixed    The state of the object.
	 *
	 * @since   1.5
	 */
	public function getState()
	{
		return $this->_state;
	}

	/**
	 * Attach an observer object
	 *
	 * @param   array|object  $observer  An observer object to attach or an array with handler and event keys
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function attach($observer)
	{
		if (is_array($observer))
		{
			if (!isset($observer['handler']) || !isset($observer['event']) || !is_callable($observer['handler']))
			{
				return;
			}

			// Make sure we haven't already attached this array as an observer
			foreach ($this->_observers as $check)
			{
				if (is_array($check) && $check['event'] == $observer['event'] && $check['handler'] == $observer['handler'])
				{
					return;
				}
			}

			$this->_observers[] = $observer;
			end($this->_observers);
			$methods = array($observer['event']);
		}
		else
		{
			if (!($observer instanceof Editor))
			{
				return;
			}

			// Make sure we haven't already attached this object as an observer
			$class = get_class($observer);

			foreach ($this->_observers as $check)
			{
				if ($check instanceof $class)
				{
					return;
				}
			}

			$this->_observers[] = $observer;

			// @todo We require an Editor object above but get the methods from \JPlugin - something isn't right here!
			$methods = array_diff(get_class_methods($observer), get_class_methods('\JPlugin'));
		}

		$key = key($this->_observers);

		foreach ($methods as $method)
		{
			$method = strtolower((string) $method);

			if (!isset($this->_methods[$method]))
			{
				$this->_methods[$method] = array();
			}

			$this->_methods[$method][] = $key;
		}
	}

	/**
	 * Detach an observer object
	 *
	 * @param   object  $observer  An observer object to detach.
	 *
	 * @return  boolean  True if the observer object was detached.
	 *
	 * @since   1.5
	 */
	public function detach($observer)
	{
		$retval = false;

		$key = array_search($observer, $this->_observers);

		if ($key !== false)
		{
			unset($this->_observers[$key]);
			$retval = true;

			foreach ($this->_methods as &$method)
			{
				$k = array_search($key, $method);

				if ($k !== false)
				{
					unset($method[$k]);
				}
			}
		}

		return $retval;
	}

	/**
	 * Initialise the editor
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @deprecated 4.0 This function will not load any custom tag from 4.0 forward, use JHtml::script
	 */
	public function initialise()
	{
		// Check if editor is already loaded
		if ($this->_editor === null)
		{
			return;
		}

		$args['event'] = 'onInit';

		$return    = '';
		$results[] = $this->_editor->update($args);

		foreach ($results as $result)
		{
			if (!is_null($result) && trim($result))
			{
				// @todo remove code: $return .= $result;
				$return = $result;
			}
		}

		$document = \JFactory::getDocument();

		if (!empty($return) && method_exists($document, 'addCustomTag'))
		{
			$document->addCustomTag($return);
		}
	}

	/**
	 * Display the editor area.
	 *
	 * @param   string   $name     The control name.
	 * @param   string   $html     The contents of the text area.
	 * @param   string   $width    The width of the text area (px or %).
	 * @param   string   $height   The height of the text area (px or %).
	 * @param   integer  $col      The number of columns for the textarea.
	 * @param   integer  $row      The number of rows for the textarea.
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $id       An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param   string   $asset    The object asset
	 * @param   object   $author   The author.
	 * @param   array    $params   Associative array of editor parameters.
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	public function display($name, $html, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		$this->asset = $asset;
		$this->author = $author;
		$this->_loadEditor($params);

		// Check whether editor is already loaded
		if ($this->_editor === null)
		{
			\JFactory::getApplication()->enqueueMessage(\JText::_('JLIB_NO_EDITOR_PLUGIN_PUBLISHED'), 'error');

			return;
		}

		// Backwards compatibility. Width and height should be passed without a semicolon from now on.
		// If editor plugins need a unit like "px" for CSS styling, they need to take care of that
		$width = str_replace(';', '', $width);
		$height = str_replace(';', '', $height);

		$return = null;

		$args['name'] = $name;
		$args['content'] = $html;
		$args['width'] = $width;
		$args['height'] = $height;
		$args['col'] = $col;
		$args['row'] = $row;
		$args['buttons'] = $buttons;
		$args['id'] = $id ?: $name;
		$args['asset'] = $asset;
		$args['author'] = $author;
		$args['params'] = $params;
		$args['event'] = 'onDisplay';

		$results[] = $this->_editor->update($args);

		foreach ($results as $result)
		{
			if (trim((string) $result))
			{
				$return .= $result;
			}
		}

		if ($return !== null && $this->canSwitch($params))
		{
			$return = $this->getSwitcher($args['id']) . $return;
		}

		return $return;
	}

	/**
	 * The content editors the user may switch between: enabled, and of an access level the user has.
	 *
	 * @return  array  Editor (plugin element) => its translated name
	 *
	 * @since   3.18.0
	 */
	public static function getSwitchableEditors()
	{
		$editors = array();

		foreach (self::SWITCHABLE_EDITORS as $name => $key)
		{
			if (\JPluginHelper::getPlugin('editors', $name))
			{
				$editors[$name] = \JText::_($key);
			}
		}

		return $editors;
	}

	/**
	 * Records the editor a logged-in user chose with the switcher (posted as editor_switch, with the form token) for the
	 * rest of the session. A switch posted on its own (editor_switch_return) is answered with a redirect to the same page.
	 * Called by the application before it decides the user's editor.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public static function switchFromRequest()
	{
		$app    = \JFactory::getApplication();
		$editor = $app->input->post->get('editor_switch', '', 'cmd');

		if ($editor === '' || \JFactory::getUser()->guest || !\JSession::checkToken('post'))
		{
			return;
		}

		$editors = self::getSwitchableEditors();

		if (isset($editors[$editor]))
		{
			\JFactory::getSession()->set('editor', $editor, self::SWITCH_NAMESPACE);
		}

		if ($app->input->post->getInt('editor_switch_return'))
		{
			$app->redirect(\JUri::getInstance()->toString(), 303);
		}
	}

	/**
	 * @return  string|null  The editor the user chose with the switcher in this session, while it may still be used
	 *
	 * @since   3.18.0
	 */
	public static function getSessionEditor()
	{
		$editor  = (string) \JFactory::getSession()->get('editor', '', self::SWITCH_NAMESPACE);
		$editors = $editor !== '' ? self::getSwitchableEditors() : array();

		return isset($editors[$editor]) ? $editor : null;
	}

	/**
	 * Whether this editor gets the switcher: in the administrator (the site keeps the user's or the site's editor), a content
	 * editor which follows the user's choice (one the caller named, e.g. with a field's editor attribute, stays as it is),
	 * editable, for a user with another editor to choose.
	 *
	 * @param   array  $params  The display parameters ('switcher' => false leaves it out)
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	protected function canSwitch(array $params)
	{
		$app = \JFactory::getApplication();

		if ((isset($params['switcher']) && !$params['switcher']) || !empty($params['readonly'])
			|| !isset(self::SWITCHABLE_EDITORS[$this->_name])
			|| !($app instanceof \JApplicationCms) || !$app->isClient('administrator')
			|| $this->_name !== $app->get('editor') || \JFactory::getUser()->guest)
		{
			return false;
		}

		return count(self::getSwitchableEditors()) > 1;
	}

	/**
	 * @param   string  $id  The editor's id
	 *
	 * @return  string  The switcher's markup (layout joomla.editors.switcher)
	 *
	 * @since   3.18.0
	 */
	protected function getSwitcher($id)
	{
		static $assets = false;

		if (!$assets)
		{
			$assets = true;

			// CMS.editorSwitch (global.js) and its styles (global.css)
			\JHtml::_('behavior.cms');
		}

		$option = \JFactory::getApplication()->input->get('option', '', 'cmd');

		return \JLayoutHelper::render('joomla.editors.switcher', array(
			'id'      => $id,
			'current' => $this->_name,
			'editors' => self::getSwitchableEditors(),
			'token'   => \JSession::getFormToken(),
			// Core components' edit forms have a reload task (FormController::reload()), which keeps what wasn't saved
			'core'    => $option !== '' && ExtensionHelper::checkIfCoreExtension('component', $option, 1),
		));
	}

	/**
	 * Save the editor content
	 *
	 * @param   string  $editor  The name of the editor control
	 *
	 * @return  string
	 *
	 * @since   1.5
	 *
	 * @deprecated 4.0 Bind functionality to form submit through javascript
	 */
	public function save($editor)
	{
		$this->_loadEditor();

		// Check whether editor is already loaded
		if ($this->_editor === null)
		{
			return;
		}

		$args[] = $editor;
		$args['event'] = 'onSave';

		$return = '';
		$results[] = $this->_editor->update($args);

		foreach ($results as $result)
		{
			if (trim((string) $result))
			{
				$return .= $result;
			}
		}

		return $return;
	}

	/**
	 * Get the editor contents
	 *
	 * @param   string  $editor  The name of the editor control
	 *
	 * @return  string
	 *
	 * @since   1.5
	 *
	 * @deprecated 4.0 Use Joomla.editors API, see core.js
	 */
	public function getContent($editor)
	{
		$this->_loadEditor();

		$args['name'] = $editor;
		$args['event'] = 'onGetContent';

		$return = '';
		$results[] = $this->_editor->update($args);

		foreach ($results as $result)
		{
			if (trim((string) $result))
			{
				$return .= $result;
			}
		}

		return $return;
	}

	/**
	 * Set the editor contents
	 *
	 * @param   string  $editor  The name of the editor control
	 * @param   string  $html    The contents of the text area
	 *
	 * @return  string
	 *
	 * @since   1.5
	 *
	 * @deprecated 4.0 Use Joomla.editors API, see core.js
	 */
	public function setContent($editor, $html)
	{
		$this->_loadEditor();

		$args['name'] = $editor;
		$args['html'] = $html;
		$args['event'] = 'onSetContent';

		$return = '';
		$results[] = $this->_editor->update($args);

		foreach ($results as $result)
		{
			if (trim((string) $result))
			{
				$return .= $result;
			}
		}

		return $return;
	}

	/**
	 * Get the editor extended buttons (usually from plugins)
	 *
	 * @param   string  $editor   The name of the editor.
	 * @param   mixed   $buttons  Can be boolean or array, if boolean defines if the buttons are
	 *                            displayed, if array defines a list of buttons not to show.
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public function getButtons($editor, $buttons = true)
	{
		$result = array();

		if (is_bool($buttons) && !$buttons)
		{
			return $result;
		}

		// Get plugins
		$plugins = \JPluginHelper::getPlugin('editors-xtd');

		foreach ($plugins as $plugin)
		{
			if (is_array($buttons) && in_array($plugin->name, $buttons))
			{
				continue;
			}

			\JPluginHelper::importPlugin('editors-xtd', $plugin->name, false);
			$className = 'PlgEditorsXtd' . $plugin->name;

			if (!class_exists($className))
			{
				$className = 'PlgButton' . $plugin->name;
			}

			if (class_exists($className))
			{
				$plugin = new $className($this, (array) $plugin);
			}

			// Try to authenticate
			if (!method_exists($plugin, 'onDisplay'))
			{
				continue;
			}

			$button = $plugin->onDisplay($editor, $this->asset, $this->author);

			if (empty($button))
			{
				continue;
			}

			if (is_array($button))
			{
				$result = array_merge($result, $button);
				continue;
			}

			$result[] = $button;
		}

		return $result;
	}

	/**
	 * Load the editor
	 *
	 * @param   array  $config  Associative array of editor config parameters
	 *
	 * @return  mixed
	 *
	 * @since   1.5
	 */
	protected function _loadEditor($config = array())
	{
		// Check whether editor is already loaded
		if ($this->_editor !== null)
		{
			return;
		}

		// Build the path to the needed editor plugin
		$name = \JFilterInput::getInstance()->clean($this->_name, 'cmd');
		$path = JPATH_PLUGINS . '/editors/' . $name . '/' . $name . '.php';

		if (!is_file($path))
		{
			\JLog::add(\JText::_('JLIB_HTML_EDITOR_CANNOT_LOAD'), \JLog::WARNING, 'jerror');

			return false;
		}

		// Require plugin file
		require_once $path;

		// Get the plugin
		$plugin = \JPluginHelper::getPlugin('editors', $this->_name);

		// If no plugin is published we get an empty array and there not so much to do with it
		if (empty($plugin))
		{
			return false;
		}

		$params = new Registry($plugin->params);
		$params->loadArray($config);
		$plugin->params = $params;

		// Build editor plugin classname
		$name = 'PlgEditor' . $this->_name;

		if ($this->_editor = new $name($this, (array) $plugin))
		{
			// Load plugin parameters
			$this->initialise();
			\JPluginHelper::importPlugin('editors-xtd');
		}
	}
}
