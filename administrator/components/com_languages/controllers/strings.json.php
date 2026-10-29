<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Languages Strings JSON Controller
 *
 * @since  2.5
 */
class LanguagesControllerStrings extends JControllerAdmin
{
    /**
     * Method for refreshing the cache in the database with the known language strings
     *
     * @return  void
     *
     * @since   2.5
     */
    public function refresh()
    {
        // The form token (the override form's script sends it as X-CSRF-Token)
        if (!JSession::checkToken('post')) {
            echo new JResponseJson(null, JText::_('JINVALID_TOKEN'), true);

            return;
        }

        echo new JResponseJson($this->getModel('strings')->refresh());
    }

    /**
     * Method for searching language strings
     *
     * @return  void
     *
     * @since   2.5
     */
    public function search()
    {
        // The form token (the override form's script sends it as X-CSRF-Token)
        if (!JSession::checkToken('post')) {
            echo new JResponseJson(null, JText::_('JINVALID_TOKEN'), true);

            return;
        }

        echo new JResponseJson($this->getModel('strings')->search());
    }
}
