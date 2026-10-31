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
 * Link Table for Redirect.
 *
 * @since  1.6
 */
class RedirectTableLink extends JTable
{
    /**
     * Set by check() when the target is itself the source of another entry.
     *
     * @var    boolean
     * @since  3.18.0
     */
    public $_chain = false;

    /**
     * Constructor
     *
     * @param   JDatabaseDriver  $db  Database object.
     *
     * @since   1.6
     */
    public function __construct($db)
    {
        parent::__construct('#__redirect_links', 'id', $db);
    }

    /**
     * Overloaded check function: the source relative to the site and unique, the target on this site or an http(s) address
     * elsewhere, no loop.
     *
     * @return  boolean
     *
     * @since   1.6
     */
    public function check()
    {
        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        $source = RedirectHelper::normaliseSource($this->old_url);

        if ($source === false) {
            $this->setError(JText::_('COM_REDIRECT_ERROR_SOURCE_URL_OTHER_SITE'));

            return false;
        }

        if ($source === '') {
            $this->setError(JText::_('COM_REDIRECT_ERROR_SOURCE_URL_REQUIRED'));

            return false;
        }

        if (trim((string) $this->new_url) === '') {
            $this->setError(JText::_('COM_REDIRECT_ERROR_DESTINATION_URL_REQUIRED'));

            return false;
        }

        $target = RedirectHelper::normaliseTarget($this->new_url);

        if ($target === false) {
            $this->setError(JText::_('COM_REDIRECT_ERROR_DESTINATION_URL_INVALID'));

            return false;
        }

        $this->old_url      = $source;
        $this->old_url_hash = RedirectHelper::hash($source);
        $this->new_url      = $target;
        $this->header       = in_array((int) $this->header, array(301, 302), true) ? (int) $this->header : 0;

        // Check for NOT NULL.
        if (empty($this->referer)) {
            $this->referer = '';
        }

        if ($this->comment === null) {
            $this->comment = '';
        }

        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__redirect_links'))
            ->where($db->quoteName('old_url_hash') . ' = ' . $db->quote($this->old_url_hash))
            ->where($db->quoteName('published') . ' <> -2')
            ->where($db->quoteName('id') . ' <> ' . (int) $this->id);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(JText::_('COM_REDIRECT_ERROR_DUPLICATE_OLD_URL'));

            return false;
        }

        $loop = RedirectHelper::checkLoop($source, $target, (int) $this->id);

        if ($loop === 'loop') {
            $this->setError(JText::_($this->leadsElsewhere($source, $target) ? 'COM_REDIRECT_ERROR_LOOP' : 'COM_REDIRECT_ERROR_DUPLICATE_URLS'));

            return false;
        }

        $this->_chain = $loop === 'chain';

        return true;
    }

    /**
     * Whether the target goes somewhere other than the source itself (a loop through other entries rather than to itself).
     *
     * @param   string  $source  The normalised source
     * @param   string  $target  The normalised target
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function leadsElsewhere($source, $target)
    {
        return !isset(RedirectHelper::getCandidates($target)[RedirectHelper::hash($source)]);
    }

    /**
     * Overriden store method to set dates.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   1.6
     */
    public function store($updateNulls = false)
    {
        $date = JFactory::getDate()->toSql();

        $this->modified_date = $date;

        if (!$this->id) {
            // New record.
            $this->created_date = $date;
        }

        $result = parent::store($updateNulls);

        if ($result) {
            RedirectHelper::cleanCache();
        }

        return $result;
    }

    /**
     * Changes the state of entries; the site's flag of enabled redirects is cleared.
     *
     * @param   mixed    $pks     An array of primary key values, or a single value
     * @param   integer  $state   The state
     * @param   integer  $userId  The user changing the state
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        $result = parent::publish($pks, $state, $userId);

        RedirectHelper::cleanCache();

        return $result;
    }

    /**
     * Deletes an entry; the site's flag of enabled redirects is cleared.
     *
     * @param   mixed  $pk  The primary key
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function delete($pk = null)
    {
        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        $result = parent::delete($pk);

        RedirectHelper::cleanCache();

        return $result;
    }
}
