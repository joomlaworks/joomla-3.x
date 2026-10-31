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
 * The addresses which gave an error.
 *
 * @since  3.18.0
 */
class RedirectViewErrors extends JViewLegacy
{
    protected $items;

    protected $pagination;

    protected $state;

    protected $sidebar;

    public $filterForm;

    public $activeFilters;

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
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        RedirectHelper::warnIfPluginDisabled();

        if (!JComponentHelper::getParams('com_redirect')->get('collect_errors', 0)) {
            $options = JText::_('COM_REDIRECT_OPTIONS');

            if (JFactory::getUser()->authorise('core.admin', 'com_redirect') || JFactory::getUser()->authorise('core.options', 'com_redirect')) {
                $return  = urlencode(base64_encode('index.php?option=com_redirect&view=errors'));
                $options = '<a class="alert-link" href="' . JRoute::_('index.php?option=com_config&view=component&component=com_redirect&return=' . $return) . '">' . $options . '</a>';
            }

            JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_REDIRECT_COLLECT_ERRORS_DISABLED', $options), 'notice');
        }

        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    protected function addToolbar()
    {
        $canDo = JHelperContent::getActions('com_redirect');

        JToolbarHelper::title(JText::_('COM_REDIRECT_MANAGER_ERRORS'), 'warning redirect');

        if ($canDo->get('core.delete')) {
            JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'errors.delete', 'JTOOLBAR_DELETE');
            JToolbarHelper::custom('errors.purge', 'trash', 'trash', 'COM_REDIRECT_TOOLBAR_PURGE_ERRORS', false);
            JToolbarHelper::divider();
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            JToolbarHelper::preferences('com_redirect');
            JToolbarHelper::divider();
        }

        JToolbarHelper::help('JHELP_COMPONENTS_REDIRECT_MANAGER');
    }
}
