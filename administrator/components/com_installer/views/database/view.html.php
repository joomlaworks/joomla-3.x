<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('InstallerViewDefault', dirname(__DIR__) . '/default/view.php');

/**
 * Extension Manager Database View
 *
 * @since  1.6
 */
class InstallerViewDatabase extends InstallerViewDefault
{
	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		// Set variables
		$app = JFactory::getApplication();

		// Get data from the model.
		$this->state = $this->get('State');
		$this->changeSet = $this->get('Items');
		$this->errors = $this->changeSet->check();
		$this->results = $this->changeSet->getStatus();
		$this->schemaVersion = $this->get('SchemaVersion');
		$this->updateVersion = $this->get('UpdateVersion');
		$this->filterParams  = $this->get('DefaultTextFilters');
		$this->schemaVersion = $this->schemaVersion ?: JText::_('JNONE');
		$this->updateVersion = $this->updateVersion ?: JText::_('JNONE');
		$this->pagination = $this->get('Pagination');
		$this->errorCount = count($this->errors);

		// Read the authoritative CMS version from joomla.xml rather than the JVERSION
		// constant: OPcache can serve stale bytecode for Version.php across PHP-FPM
		// workers even after opcache_reset() was called during the upgrade, causing
		// JVERSION to still evaluate to the pre-upgrade value on subsequent requests.
		// XML files are never bytecode-cached, so simplexml_load_file() always returns
		// the value that is actually on disk.
		$manifestFile = JPATH_ADMINISTRATOR . '/manifests/files/joomla.xml';
		$this->cmsVersion = JVERSION;

		if (is_readable($manifestFile))
		{
			$xml = @simplexml_load_file($manifestFile);

			if ($xml !== false && !empty((string) $xml->version))
			{
				$this->cmsVersion = (string) $xml->version;
			}
		}

		if ($this->schemaVersion != $this->changeSet->getSchema())
		{
			$this->errorCount++;
		}

		if (!$this->filterParams)
		{
			$this->errorCount++;
		}

		if (version_compare($this->updateVersion, $this->cmsVersion) != 0)
		{
			$this->errorCount++;
		}

		if ($this->errorCount === 0)
		{
			$app->enqueueMessage(JText::_('COM_INSTALLER_MSG_DATABASE_OK'), 'notice');
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_INSTALLER_MSG_DATABASE_ERRORS'), 'warning');
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
		/*
		 * Set toolbar items for the page.
		 */
		JToolbarHelper::custom('database.fix', 'refresh', 'refresh', 'COM_INSTALLER_TOOLBAR_DATABASE_FIX', false);
		JToolbarHelper::divider();
		parent::addToolbar();
		JToolbarHelper::help('JHELP_EXTENSIONS_EXTENSION_MANAGER_DATABASE');
	}
}
