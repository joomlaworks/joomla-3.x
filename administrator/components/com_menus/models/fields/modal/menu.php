<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;

JHtml::_('bootstrap.tooltip', '.hasTooltip');

/**
 * Supports a modal menu item picker.
 *
 * @since  3.7.0
 */
class JFormFieldModal_Menu extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   3.7.0
     */
    protected $type = 'Modal_Menu';

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
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   3.7.0
     */
    protected function getInput()
    {
        $clientId    = (int) $this->element['clientid'];
        $languages   = LanguageHelper::getContentLanguages(array(0, 1));

        // Load language
        JFactory::getLanguage()->load('com_menus', JPATH_ADMINISTRATOR);

        // The active article id field.
        $value = (int) $this->value > 0 ? (int) $this->value : '';

        // Setup variables for display.
        $linkSuffix = '&layout=modal&client_id=' . $clientId . '&tmpl=component&' . JSession::getFormToken() . '=1';
        $linkItems  = 'index.php?option=com_menus&view=items' . $linkSuffix;
        $linkItem   = 'index.php?option=com_menus&view=item' . $linkSuffix;
        $modalTitle = JText::_('COM_MENUS_CHANGE_MENUITEM');

        if (isset($this->element['language'])) {
            $linkItems  .= '&forcedLanguage=' . $this->element['language'];
            $linkItem   .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle .= " \u{2014} " . $this->element['label'];
        }

        if ($value) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('id') . ' = ' . (int) $value);

            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        // Placeholder if option is present or not
        if ($this->element->option && (string) $this->element->option['value'] == '') {
            $titleHolder = JText::_($this->element->option);
        } else {
            $titleHolder = JText::_('COM_MENUS_SELECT_A_MENUITEM');
        }

        $title = empty($title) ? $titleHolder : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return JLayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => $titleHolder,
            'function'   => 'jSelectMenu_' . $this->id,
            'urls'       => array(
                'select' => $linkItems . '&function=jSelectMenu_' . $this->id,
                'new'    => $linkItem . '&task=item.add',
                'edit'   => $linkItem . '&task=item.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => JText::_('COM_MENUS_NEW_MENUITEM'),
                'edit'   => JText::_('COM_MENUS_EDIT_MENUITEM'),
            ),
            'tips'       => array(
                'select' => 'COM_MENUS_CHANGE_MENUITEM',
                'new'    => 'COM_MENUS_NEW_MENUITEM',
                'edit'   => 'COM_MENUS_EDIT_MENUITEM',
            ),
            'allow'      => array(
                'select'    => $this->allowSelect,
                'new'       => $this->allowNew,
                'edit'      => $this->allowEdit,
                'clear'     => $this->allowClear,
                'propagate' => $this->allowPropagate && count($languages) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr('jSelectMenu_' . $this->id, 0, -strlen((string) $this->element['language'])),
            'task'       => 'item',
            'form'       => 'item-form',
            'idField'    => 'jform_id',
            'titleField' => 'jform_title',
        ));
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   3.7.0
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }
}
