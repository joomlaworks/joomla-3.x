<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Bulk import of redirects.
 *
 * @since  3.18.0
 */
class RedirectViewImport extends JViewLegacy
{
    protected $separator;

    protected $state;

    protected $report;

    protected $text;

    protected $sidebar;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  False if unsuccessful, otherwise void.
     *
     * @since   3.18.0
     */
    public function display($tpl = null)
    {
        if (!JFactory::getUser()->authorise('core.admin', 'com_redirect')) {
            throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $app             = JFactory::getApplication();
        $this->separator = $this->getModel()->getSeparator();
        $this->state     = (int) JComponentHelper::getParams('com_redirect')->get('defaultImportState', 0) === 1;
        $this->report    = $app->getUserState('com_redirect.import.report');
        $this->text      = (string) $app->getUserState('com_redirect.import.text', '');

        // The report belongs to the import just made
        $app->setUserState('com_redirect.import.report', null);
        $app->setUserState('com_redirect.import.text', null);

        RedirectHelper::warnIfPluginDisabled();

        JToolbarHelper::title(JText::_('COM_REDIRECT_MANAGER_IMPORT'), 'upload redirect');
        JToolbarHelper::custom('import.import', 'upload', 'upload', 'COM_REDIRECT_TOOLBAR_IMPORT', false);
        JToolbarHelper::divider();
        JToolbarHelper::preferences('com_redirect');
        JToolbarHelper::divider();
        JToolbarHelper::help('JHELP_COMPONENTS_REDIRECT_MANAGER');

        $this->sidebar = JHtmlSidebar::render();

        return parent::display($tpl);
    }
}
