<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\ModalItemField;

/**
 * Supports a modal category picker.
 *
 * @since  3.1
 */
class JFormFieldModal_Category extends ModalItemField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   1.6
     */
    protected $type = 'Modal_Category';

    /**
     * What the picker opens and shows.
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array(
        'component'   => 'com_categories',
        'listView'    => 'categories',
        'itemView'    => 'category',
        'task'        => 'category',
        'table'       => '#__categories',
        'titleColumn' => 'title',
        'callback'    => 'jSelectCategory',
        'form'        => 'item-form',
        'titleField'  => 'jform_title',
        'strings'     => array(
            'change' => 'COM_CATEGORIES_CHANGE_CATEGORY',
            'select' => 'COM_CATEGORIES_SELECT_A_CATEGORY',
            'new'    => 'COM_CATEGORIES_NEW_CATEGORY',
            'edit'   => 'COM_CATEGORIES_EDIT_CATEGORY',
        ),
    );

    /**
     * The categories' extension: the field's, else the request's
     *
     * @return  string
     *
     * @since   3.19.0
     */
    protected function getLinkQuery()
    {
        if ($this->element['extension']) {
            $extension = (string) $this->element['extension'];
        } else {
            $extension = (string) JFactory::getApplication()->input->get('extension', 'com_content');
        }

        return '&extension=' . $extension;
    }
}
