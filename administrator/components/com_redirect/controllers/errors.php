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
 * The collected errors: deleting them, and making a redirect of one.
 *
 * @since  3.18.0
 */
class RedirectControllerErrors extends JControllerLegacy
{
    /**
     * Deletes the selected errors.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function delete()
    {
        $this->checkToken();
        $this->setRedirect(JRoute::_('index.php?option=com_redirect&view=errors', false));

        if (!JFactory::getUser()->authorise('core.delete', 'com_redirect')) {
            $this->setMessage(JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');

            return;
        }

        $ids = array_filter(array_map('intval', (array) $this->input->post->get('cid', array(), 'array')));

        if (!$ids) {
            $this->setMessage(JText::_('COM_REDIRECT_NO_ITEM_SELECTED'), 'warning');

            return;
        }

        $count = $this->getModel('Errors')->delete($ids);

        $this->setMessage(JText::plural('COM_REDIRECT_N_ERRORS_DELETED', $count));
    }

    /**
     * Deletes the errors the component's options don't keep (last hit too long ago, more than the number kept).
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function purge()
    {
        $this->checkToken();
        $this->setRedirect(JRoute::_('index.php?option=com_redirect&view=errors', false));

        if (!JFactory::getUser()->authorise('core.delete', 'com_redirect')) {
            $this->setMessage(JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');

            return;
        }

        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');
        $this->setMessage(JText::plural('COM_REDIRECT_N_ERRORS_DELETED', RedirectHelper::purgeErrors()));
    }

    /**
     * Makes a redirect of an error's address; the error is deleted once the redirect is saved.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function createRedirect()
    {
        $this->checkToken();
        $this->setRedirect(JRoute::_('index.php?option=com_redirect&view=errors', false));

        if (!JFactory::getUser()->authorise('core.create', 'com_redirect')) {
            $this->setMessage(JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');

            return;
        }

        $model = $this->getModel('Errors');
        $error = $model->getEntry($this->input->post->getInt('error_id'));

        if (!$error) {
            $this->setMessage(JText::_('COM_REDIRECT_NO_ITEM_SELECTED'), 'warning');

            return;
        }

        $input = $this->input->post;
        $data  = array(
            'id'               => 0,
            'old_url'          => $error->url,
            'target_type'      => $input->get('target_type', 'internal', 'cmd'),
            'new_url'          => $input->getString('new_url', ''),
            'new_url_external' => $input->getString('new_url_external', ''),
            'header'           => $input->getInt('header', 0),
            'comment'          => '',
            'published'        => JFactory::getUser()->authorise('core.edit.state', 'com_redirect') ? 1 : 0,
        );

        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_redirect/models');
        $link = JModelLegacy::getInstance('Link', 'RedirectModel', array('ignore_request' => true));

        if (!$link->save($data)) {
            $this->setMessage(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $link->getError()), 'error');

            return;
        }

        $model->delete(array((int) $error->id));

        $this->setMessage(JText::sprintf('COM_REDIRECT_ERROR_REDIRECTED', '/' . $error->url));
    }
}
