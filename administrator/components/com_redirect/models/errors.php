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
 * The addresses which gave an error, as the plugin collected them.
 *
 * @since  3.18.0
 */
class RedirectModelErrors extends JModelList
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   3.18.0
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'url', 'a.url',
                'code', 'a.code',
                'referer', 'a.referer',
                'hits', 'a.hits',
                'created_date', 'a.created_date',
                'last_hit', 'a.last_hit',
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   3.18.0
     */
    protected function populateState($ordering = 'a.last_hit', $direction = 'desc')
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.code', $this->getUserStateFromRequest($this->context . '.filter.code', 'filter_code', '', 'cmd'));

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   3.18.0
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.code');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  JDatabaseQuery
     *
     * @since   3.18.0
     */
    protected function getListQuery()
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__redirect_errors', 'a'));

        $code = $this->getState('filter.code');

        if (is_numeric($code)) {
            $query->where($db->quoteName('a.code') . ' = ' . (int) $code);
        }

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(' . $db->quoteName('a.url') . ' LIKE ' . $search . ' OR ' . $db->quoteName('a.referer') . ' LIKE ' . $search . ')');
            }
        }

        $listOrdering = $this->getState('list.ordering', 'a.last_hit');
        $listDirn     = $db->escape($this->getState('list.direction', 'DESC'));
        $query->order($db->escape($listOrdering) . ' ' . $listDirn);

        // A unique last column (never twice: SQL Server refuses it), or rows sharing a value come back in any order and pages repeat or skip them.
        if (!in_array($listOrdering, array('a.id', 'id'), true)) {
            $query->order($db->quoteName('a.id') . ' ' . $listDirn);
        }

        return $query;
    }

    /**
     * One collected error.
     *
     * @param   integer  $id  The entry's ID
     *
     * @return  object|null
     *
     * @since   3.18.0
     */
    public function getEntry($id)
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__redirect_errors'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Deletes collected errors.
     *
     * @param   array  $ids  Their IDs
     *
     * @return  integer  The number deleted
     *
     * @since   3.18.0
     */
    public function delete(array $ids)
    {
        $ids = array_filter(array_map('intval', $ids));

        if (!$ids) {
            return 0;
        }

        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__redirect_errors'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $db->setQuery($query)->execute();

        return (int) $db->getAffectedRows();
    }
}
