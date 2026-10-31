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
 * Bulk import of redirects, for Super Users of the component.
 *
 * @since  3.18.0
 */
class RedirectControllerImport extends JControllerLegacy
{
    /**
     * Imports the posted lines and goes back to the page with a report; skipped lines stay in the text area.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function import()
    {
        $this->checkToken();
        $this->setRedirect(JRoute::_('index.php?option=com_redirect&view=import', false));

        if (!JFactory::getUser()->authorise('core.admin', 'com_redirect')) {
            $this->setMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

            return;
        }

        $report = $this->getModel('Import')->import($this->input->post->get('redirects', '', 'raw'), $this->input->post->getInt('state', 1));
        $app    = JFactory::getApplication();

        $app->setUserState('com_redirect.import.report', $report);
        $app->setUserState('com_redirect.import.text', implode("\n", array_column($report['skipped'], 'line')));
        $app->setUserState('com_redirect.import.state', $this->input->post->getInt('state', 1) === 1 ? 1 : 0);

        $this->setMessage(
            JText::sprintf('COM_REDIRECT_IMPORT_RESULT', $report['imported'], count($report['skipped'])),
            $report['skipped'] ? 'warning' : 'message'
        );
    }
}
