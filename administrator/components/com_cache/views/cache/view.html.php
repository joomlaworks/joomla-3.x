<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_cache
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * HTML View class for the Cache component
 *
 * @since  1.6
 */
class CacheViewCache extends JViewLegacy
{
    /**
     * @var object client object.
     * @deprecated 4.0
     */
    protected $client;

    protected $data;

    protected $pagination;

    protected $state;

    /**
     * The handler's name when it keeps one cache for the site and the administrator
     *
     * @var    string
     * @since  3.18.0
     */
    protected $sharedHandler = '';

    /**
     * Display a view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $this->data          = $this->get('Data');
        $this->pagination    = $this->get('Pagination');
        $this->total         = $this->get('Total');
        $this->state         = $this->get('State');
        $this->sharedHandler = $this->get('SharedHandler');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            // A cache that can't be reached is no reason to lose the page which says so
            JFactory::getApplication()->enqueueMessage(implode("\n", array_unique($errors)), 'error');
        }

        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
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
        $state = $this->get('State');

        if ($this->sharedHandler) {
            JToolbarHelper::title(JText::_('COM_CACHE_CLEAR_CACHE'), 'lightning clear');
        } elseif ($state->get('client_id') == 1) {
            JToolbarHelper::title(JText::_('COM_CACHE_CLEAR_CACHE_ADMIN_TITLE'), 'lightning clear');
        } else {
            JToolbarHelper::title(JText::_('COM_CACHE_CLEAR_CACHE_SITE_TITLE'), 'lightning clear');
        }

        JToolbarHelper::custom('delete', 'delete.png', 'delete_f2.png', 'JTOOLBAR_DELETE', true);
        JToolbarHelper::custom('deleteAll', 'remove.png', 'delete_f2.png', 'JTOOLBAR_DELETE_ALL', false);

        if ($this->getModel()->canPurgeExpired()) {
            JToolbar::getInstance('toolbar')->appendButton('Confirm', 'COM_CACHE_PURGE_CONFIRM', 'clock', 'COM_CACHE_PURGE_EXPIRED', 'purge', false);
        }

        JToolbarHelper::divider();

        if (JFactory::getUser()->authorise('core.admin', 'com_cache')) {
            JToolbarHelper::preferences('com_cache');
        }

        JToolbarHelper::divider();
        JToolbarHelper::help('JHELP_SITE_MAINTENANCE_CLEAR_CACHE');

        JHtmlSidebar::setAction('index.php?option=com_cache');
    }
}
