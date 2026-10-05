<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('InstallerViewDefault', dirname(__DIR__) . '/default/view.php');

/**
 * Extension Manager Install View
 *
 * @since  1.5
 */
class InstallerViewInstall extends InstallerViewDefault
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function display($tpl = null)
	{
		if (!JFactory::getUser()->authorise('core.admin'))
		{
			throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$paths = new stdClass;
		$paths->first = '';
		$state = $this->get('state');

		$this->paths = &$paths;
		$this->state = &$state;

		$this->showJedAndWebInstaller = JComponentHelper::getParams('com_installer')->get('show_jed_info', 1);

		JPluginHelper::importPlugin('installer');

		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onInstallerBeforeDisplay', array(&$this->showJedAndWebInstaller, $this));

		/*
		 * The Install from Web plugin is bundled (and hides this message while enabled). It used to be installed from a JED feed
		 * which offers no version for Joomla 3.11 and later, so the message's button now leads to the plugin in the Plugin
		 * Manager to enable it; when the plugin was uninstalled, there is nothing to enable.
		 */
		if ($this->showJedAndWebInstaller)
		{
			$db = JFactory::getDbo();
			$this->showJedAndWebInstaller = (bool) $db->setQuery(
				$db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('folder') . ' = ' . $db->quote('installer'))
					->where($db->quoteName('element') . ' = ' . $db->quote('webinstaller'))
					->where($db->quoteName('state') . ' >= 0')
			)->loadResult();
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		parent::addToolbar();
		JToolbarHelper::help('JHELP_EXTENSIONS_EXTENSION_MANAGER_INSTALL');
	}
}
