<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Menu table
 *
 * @since  1.6
 */
class MenusTableMenu extends JTableMenu
{
    /**
     * Method to delete a node and, optionally, its child nodes from the table.
     *
     * @param   integer  $pk        The primary key of the node to delete.
     * @param   boolean  $children  True to delete child nodes, false to move them up a level.
     *
     * @return  boolean  True on success.
     *
     * @since   2.5
     */
    public function delete($pk = null, $children = false)
    {
        // Joomla 3.x UTD: the item's own ID when none is given (the query had none, failing after the item was deleted)
        $id     = (int) ($pk !== null ? $pk : $this->id);
        $return = parent::delete($pk, $children);

        if ($return) {
            // Its module assignments; never menuid 0 ("on all pages"), and modules shown on all pages except it stay on all pages
            JModuleHelper::removeMenuAssignments(array($id));
        }

        return $return;
    }
}
