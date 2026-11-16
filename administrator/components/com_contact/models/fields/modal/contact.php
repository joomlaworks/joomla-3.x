<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\ModalItemField;

/**
 * Supports a modal contact picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Contact extends ModalItemField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    protected $type = 'Modal_Contact';

    /**
     * What the picker opens and shows.
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array(
        'component'   => 'com_contact',
        'listView'    => 'contacts',
        'itemView'    => 'contact',
        'task'        => 'contact',
        'table'       => '#__contact_details',
        'titleColumn' => 'name',
        'callback'    => 'jSelectContact',
        'form'        => 'contact-form',
        'titleField'  => 'jform_name',
        'strings'     => array(
            'change' => 'COM_CONTACT_CHANGE_CONTACT',
            'select' => 'COM_CONTACT_SELECT_A_CONTACT',
            'new'    => 'COM_CONTACT_NEW_CONTACT',
            'edit'   => 'COM_CONTACT_EDIT_CONTACT',
        ),
    );
}
