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
 * Media Manager Component Controller
 *
 * @since  1.5
 */
class MediaController extends JControllerLegacy
{
    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  JController		This object to support chaining.
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = false)
    {
        JPluginHelper::importPlugin('content');

        $vType    = JFactory::getDocument()->getType();
        $vName    = $this->input->get('view', 'media');

        // The picker's old addresses (the editors' Image and PDF buttons, image fields, extensions): the Media Manager, picking
        if ($vName === 'images' || $vName === 'imagesList') {
            $this->input->set('pick', MediaHelper::pickType());
            $vName = $vName === 'images' ? 'media' : 'mediaList';
        }

        switch ($vName) {
            case 'mediaList':
                $app     = JFactory::getApplication();
                $mName   = 'list';
                $vLayout = $app->getUserStateFromRequest('media.list.layout', 'layout', 'thumbs', 'word');

                break;

            case 'media':
            default:
                $vName   = 'media';
                $vLayout = $this->input->get('layout', 'default', 'string');
                $mName   = 'manager';

                break;
        }

        // Get/Create the view
        $view = $this->getView($vName, $vType, '', array('base_path' => JPATH_COMPONENT_ADMINISTRATOR));

        // Get/Create the model
        if ($model = $this->getModel($mName)) {
            // Push the model into the view (as default)
            $view->setModel($model, true);
        }

        // Set the layout
        $view->setLayout($vLayout);

        // Display the view
        $view->display();

        return $this;
    }

    /**
     * Validate FTP credentials
     *
     * @return  void
     *
     * @since   1.5
     */
    public function ftpValidate()
    {
        // Set FTP credentials, if given
        JClientHelper::setCredentialsFromRequest('ftp');
    }
}
