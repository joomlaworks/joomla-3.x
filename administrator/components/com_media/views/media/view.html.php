<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * HTML View class for the Media component
 *
 * @since  1.0
 */
class MediaViewMedia extends JViewLegacy
{
    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   1.0
     */
    public function display($tpl = null)
    {
        $app    = JFactory::getApplication();
        $config = JComponentHelper::getParams('com_media');

        // What it picks for (the editors' Image and PDF buttons, image fields), '' when managing: picking also runs on the site
        // (front-end editing)
        $this->pick = MediaHelper::pickType();

        if (!$app->isClient('administrator') && $this->pick === '') {
            return $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
        }

        /*
         * Display form for FTP credentials?
         * Don't set them here, as there are other functions called before this one if there is any file write operation
         */
        $ftp = !JClientHelper::hasCredentials('ftp');

        $session           = JFactory::getSession();
        $state             = $this->get('state');
        $this->session     = $session;
        $this->config      = &$config;
        $this->state       = &$state;
        $this->require_ftp = $ftp;
        $this->folders_id  = ' id="media-tree"';
        $this->folders     = $this->get('folderTree');

        if ($this->pick === '') {
            $this->sidebar = JHtmlSidebar::render();

            // Set the toolbar
            $this->addToolbar();
        } else {
            $this->sidebar = '';
            $this->toolbar = $this->getPickToolbar();
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
        // Get the toolbar object instance
        $bar  = JToolbar::getInstance('toolbar');
        $user = JFactory::getUser();

        // Set the titlebar text
        JToolbarHelper::title(JText::_('COM_MEDIA'), 'images mediamanager');

        // Add an upload button
        if ($user->authorise('core.create', 'com_media')) {
            // Instantiate a new JLayoutFile instance and render the layout
            $layout = new JLayoutFile('toolbar.uploadmedia');

            $bar->appendButton('Custom', $layout->render(array()), 'upload');
            JToolbarHelper::divider();
        }

        // Add a create folder button
        if ($user->authorise('core.create', 'com_media')) {
            // Instantiate a new JLayoutFile instance and render the layout
            $layout = new JLayoutFile('toolbar.newfolder');

            $bar->appendButton('Custom', $layout->render(array()), 'create');
            JToolbarHelper::divider();
        }

        // A crop button: images are chosen with the list's checkboxes, which only users who may delete have
        if ($user->authorise('core.create', 'com_media') && $user->authorise('core.delete', 'com_media')) {
            $layout = new JLayoutFile('toolbar.cropmedia');

            $bar->appendButton('Custom', $layout->render(array()), 'crop');
            JToolbarHelper::divider();
        }

        // Add a delete button
        if ($user->authorise('core.delete', 'com_media')) {
            // Instantiate a new JLayoutFile instance and render the layout
            $layout = new JLayoutFile('toolbar.deletemedia');

            $bar->appendButton('Custom', $layout->render(array()), 'delete');
            JToolbarHelper::divider();
        }

        // Add a preferences button
        if ($user->authorise('core.admin', 'com_media') || $user->authorise('core.options', 'com_media')) {
            JToolbarHelper::preferences('com_media');
            JToolbarHelper::divider();
        }

        JToolbarHelper::help('JHELP_CONTENT_MEDIA_MANAGER');
    }

    /**
     * The picker's toolbar, drawn in its page (pages in a modal have no toolbar of the template's): the Media Manager's
     * buttons the user may use, without Options and Help. JToolbarHelper is the administrator's only.
     *
     * @return  string
     *
     * @since   3.19.0
     */
    protected function getPickToolbar()
    {
        $user    = JFactory::getUser();
        $bar     = new JToolbar('media-pick');
        $buttons = array();

        if ($user->authorise('core.create', 'com_media')) {
            $buttons['upload'] = 'toolbar.uploadmedia';
            $buttons['create'] = 'toolbar.newfolder';
        }

        if ($user->authorise('core.create', 'com_media') && $user->authorise('core.delete', 'com_media')) {
            $buttons['crop'] = 'toolbar.cropmedia';
        }

        if ($user->authorise('core.delete', 'com_media')) {
            $buttons['delete'] = 'toolbar.deletemedia';
        }

        foreach ($buttons as $name => $layout) {
            $bar->appendButton('Custom', JLayoutHelper::render($layout, array(), JPATH_ADMINISTRATOR . '/components/com_media/layouts'), $name);
        }

        return $buttons ? $bar->render() : '';
    }

    /**
     * Display a folder level
     *
     * @param   array  $folder  Array with folder data
     *
     * @return  string
     *
     * @since   1.0
     */
    protected function getFolderLevel($folder)
    {
        $this->folders_id = null;
        $txt              = null;

        if (isset($folder['children']) && count($folder['children'])) {
            $tmp           = $this->folders;
            $this->folders = $folder;
            $txt           = $this->loadTemplate('folders');
            $this->folders = $tmp;
        }

        return $txt;
    }
}
