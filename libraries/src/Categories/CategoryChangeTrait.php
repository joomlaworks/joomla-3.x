<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Categories;

defined('JPATH_PLATFORM') or die;

/**
 * Checks of the category a form controller's record is saved in: creating it there, or moving it there, needs the create
 * permission in that category. The category field only lists such categories, but the posted value is what's stored.
 *
 * @since  3.19.0
 */
trait CategoryChangeTrait
{
    /**
     * The posted category as the database stores it: an integer cast, so "300abc" or "0.300e3" is checked as category 300
     * (JControllerForm::allowAdd() read the first digits, 0)
     *
     * @param   array  $data  The posted data
     *
     * @return  integer|null  The category ID, 0 for none, null for a value that isn't a scalar
     *
     * @since   3.19.0
     */
    private function getTargetCategoryId(array $data)
    {
        $catid = isset($data['catid']) ? $data['catid'] : null;

        if ($catid !== null && !is_scalar($catid)) {
            return null;
        }

        return (int) $catid;
    }

    /**
     * Whether a new record may be created in the posted category
     *
     * @param   array  $data  The posted data
     *
     * @return  boolean
     *
     * @since   3.19.0
     */
    protected function allowCategoryAdd(array $data)
    {
        $catid = $this->getTargetCategoryId($data);

        if ($catid === null) {
            return false;
        }

        if (!$catid) {
            return true;
        }

        return \JFactory::getUser()->authorise('core.create', $this->option . '.category.' . $catid);
    }

    /**
     * Whether a record may be saved in the posted category: a new one as allowCategoryAdd(), an existing one moved to
     * another category needs the create permission there
     *
     * @param   \JTable  $table     A table of the record type
     * @param   integer  $recordId  The record's ID
     * @param   array    $data      The posted data
     *
     * @return  boolean
     *
     * @since   3.19.0
     */
    protected function allowCategoryChange($table, $recordId, array $data)
    {
        if (!$recordId) {
            return $this->allowCategoryAdd($data);
        }

        $catid = $this->getTargetCategoryId($data);

        if ($catid === null) {
            return false;
        }

        if (!$catid || !$table->load((int) $recordId) || (int) $table->catid === $catid) {
            return true;
        }

        return \JFactory::getUser()->authorise('core.create', $this->option . '.category.' . $catid);
    }
}
