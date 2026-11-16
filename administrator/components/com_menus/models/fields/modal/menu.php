<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\ModalItemField;

/**
 * Supports a modal menu item picker.
 *
 * @since  3.7.0
 */
class JFormFieldModal_Menu extends ModalItemField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   3.7.0
     */
    protected $type = 'Modal_Menu';

    /**
     * What the picker opens and shows.
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array(
        'component'   => 'com_menus',
        'listView'    => 'items',
        'itemView'    => 'item',
        'task'        => 'item',
        'table'       => '#__menu',
        'titleColumn' => 'title',
        'callback'    => 'jSelectMenu',
        'form'        => 'item-form',
        'titleField'  => 'jform_title',
        'strings'     => array(
            'change' => 'COM_MENUS_CHANGE_MENUITEM',
            'select' => 'COM_MENUS_SELECT_A_MENUITEM',
            'new'    => 'COM_MENUS_NEW_MENUITEM',
            'edit'   => 'COM_MENUS_EDIT_MENUITEM',
        ),
    );

    /**
     * Determinate, if the select button is shown
     *
     * @var     boolean
     * @since   3.7.0
     */
    protected $allowSelect = true;

    /**
     * Determinate, if the clear button is shown
     *
     * @var     boolean
     * @since   3.7.0
     */
    protected $allowClear = true;

    /**
     * Determinate, if the create button is shown
     *
     * @var     boolean
     * @since   3.7.0
     */
    protected $allowNew = false;

    /**
     * Determinate, if the edit button is shown
     *
     * @var     boolean
     * @since   3.7.0
     */
    protected $allowEdit = false;

    /**
     * Determinate, if the propagate button is shown
     *
     * @var     boolean
     * @since   3.9.0
     */
    protected $allowPropagate = false;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     *
     * @since   3.7.0
     */
    public function __get($name)
    {
        switch ($name) {
            case 'allowSelect':
            case 'allowClear':
            case 'allowNew':
            case 'allowEdit':
            case 'allowPropagate':
                return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     *
     * @since   3.7.0
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'allowSelect':
            case 'allowClear':
            case 'allowNew':
            case 'allowEdit':
            case 'allowPropagate':
                $value = (string) $value;
                $this->$name = !($value === 'false' || $value === 'off' || $value === '0');
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as an array container for the field.
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     JFormField::setup()
     * @since   3.7.0
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->allowSelect = ((string) $this->element['select']) !== 'false';
            $this->allowClear = ((string) $this->element['clear']) !== 'false';
            $this->allowPropagate = ((string) $this->element['propagate']) === 'true';

            // Creating/editing menu items is not supported in frontend.
            $isAdministrator = JFactory::getApplication()->isClient('administrator');
            $this->allowNew = $isAdministrator ? ((string) $this->element['new']) === 'true' : false;
            $this->allowEdit = $isAdministrator ? ((string) $this->element['edit']) === 'true' : false;
        }

        return $return;
    }

    /**
     * The menu items' client
     *
     * @return  string
     *
     * @since   3.19.0
     */
    protected function getLinkQuery()
    {
        return '&client_id=' . (int) $this->element['clientid'];
    }

    /**
     * Which of the buttons the field shows, from its properties
     *
     * @return  array  select, new, edit, clear, propagate
     *
     * @since   3.19.0
     */
    protected function getAllowed()
    {
        return array(
            'select'    => $this->allowSelect,
            'new'       => $this->allowNew,
            'edit'      => $this->allowEdit,
            'clear'     => $this->allowClear,
            'propagate' => $this->allowPropagate,
        );
    }

    /**
     * The text shown while no item is chosen: the field's empty option, if it has one
     *
     * @return  string
     *
     * @since   3.19.0
     */
    protected function getEmptyText()
    {
        if ($this->element->option && (string) $this->element->option['value'] == '') {
            return JText::_($this->element->option);
        }

        return parent::getEmptyText();
    }
}
