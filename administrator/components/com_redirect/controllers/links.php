<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Redirect link list controller class.
 *
 * @since  1.6
 */
class RedirectControllerLinks extends JControllerAdmin
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The name of the model.
     * @param   string  $prefix  The prefix of the model.
     * @param   array   $config  An array of settings.
     *
     * @return  JModel instance
     *
     * @since   1.6
     */
    public function getModel($name = 'Link', $prefix = 'RedirectModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}
