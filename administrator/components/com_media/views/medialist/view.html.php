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
class MediaViewMediaList extends JViewLegacy
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
        $app = JFactory::getApplication();

        if (!$app->isClient('administrator')) {
            return $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
        }

        // Do not allow cache
        $app->allowCache(false);

        $this->images    = $this->get('images');
        $this->documents = $this->get('documents');

        // PDF files: their first pages as the tiles' pictures, a click shows them (JHtml behavior.pdf)
        foreach ($this->documents as $document) {
            if (strtolower(JFile::getExt($document->name)) === 'pdf') {
                JHtml::_('behavior.pdf');

                break;
            }
        }
        $this->folders   = $this->get('folders');
        $this->videos    = $this->get('videos');
        $this->audios    = $this->get('audios');
        $this->state     = $this->get('state');

        // Check for invalid folder name
        if (empty($this->state->folder)) {
            $dirname = JFactory::getApplication()->input->getPath('folder', '');

            if (!empty($dirname)) {
                $dirname = htmlspecialchars((string) $dirname, ENT_COMPAT, 'UTF-8');
                JError::raiseWarning(100, JText::sprintf('COM_MEDIA_ERROR_UNABLE_TO_BROWSE_FOLDER_WARNDIRNAME', $dirname));
            }
        }

        $user = JFactory::getUser();
        $this->canDelete = $user->authorise('core.delete', 'com_media');

        // Files dropped on the list upload into its folder
        MediaHelper::dropUpload((string) $this->state->folder);

        // A pencil next to each name renames the file or folder
        MediaHelper::renaming((string) $this->state->folder);

        // The thumbnails' size chosen in this session (media.js), set before they're drawn
        JFactory::getDocument()->addScriptDeclaration(
            "try { var size = parseInt(sessionStorage.getItem('com_media.tileSize'), 10);"
            . " if (size > 0) { document.documentElement.style.setProperty('--media-tile', size + 'px'); } } catch (error) {}"
        );

        parent::display($tpl);
    }
}
