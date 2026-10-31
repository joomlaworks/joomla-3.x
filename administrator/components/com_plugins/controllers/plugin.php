<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_plugins
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Plugin controller class.
 *
 * @since  1.6
 */
class PluginsControllerPlugin extends JControllerForm
{
    /**
     * Save & Close and Close: back to the page that sent the user to the plugin's options (a component asking for its plugin,
     * a post-installation message), when it passed one as "return" (base64 of an address on this site; the edit page's
     * address keeps it, JControllerForm::getRedirectToItemAppend())
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.18.0
     */
    public function save($key = null, $urlVar = null)
    {
        $result = parent::save($key, $urlVar);
        $return = $this->getReturnPage();

        if ($result && $this->getTask() === 'save' && $return !== null) {
            $this->setRedirect($return);
        }

        return $result;
    }

    /**
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since   3.18.0
     */
    public function cancel($key = null)
    {
        $result = parent::cancel($key);
        $return = $this->getReturnPage();

        if ($result && $return !== null) {
            $this->setRedirect($return);
        }

        return $result;
    }

    /**
     * The return page: an address on this site, base64 encoded in the request's "return"
     *
     * @return  string|null
     *
     * @since   3.18.0
     */
    protected function getReturnPage()
    {
        $return = base64_decode((string) $this->input->get('return', '', 'base64'), true);

        // Internal, and an address (http, https, a path or index.php): JUri::isInternal() also takes javascript: (no host)
        if ($return === false || !preg_match('#^(?:https?://|/(?!/)|index\.php)#i', $return) || !JUri::isInternal($return)) {
            return null;
        }

        return $return;
    }
}
