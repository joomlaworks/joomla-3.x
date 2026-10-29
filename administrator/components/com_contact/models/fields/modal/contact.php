<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;

/**
 * Supports a modal contact picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Contact extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   1.6
     */
    protected $type = 'Modal_Contact';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.6
     */
    protected function getInput()
    {
        $allowNew       = ((string) $this->element['new'] == 'true');
        $allowEdit      = ((string) $this->element['edit'] == 'true');
        $allowClear     = ((string) $this->element['clear'] != 'false');
        $allowSelect    = ((string) $this->element['select'] != 'false');
        $allowPropagate = ((string) $this->element['propagate'] == 'true');

        $languages = LanguageHelper::getContentLanguages(array(0, 1));

        // Load language
        JFactory::getLanguage()->load('com_contact', JPATH_ADMINISTRATOR);

        // The active contact id field.
        $value = (int) $this->value > 0 ? (int) $this->value : '';

        // Setup variables for display.
        $linkItems  = 'index.php?option=com_contact&view=contacts&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $linkItem   = 'index.php?option=com_contact&view=contact&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $modalTitle = JText::_('COM_CONTACT_CHANGE_CONTACT');

        if (isset($this->element['language'])) {
            $linkItems  .= '&forcedLanguage=' . $this->element['language'];
            $linkItem   .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle .= " \u{2014} " . $this->element['label'];
        }

        if ($value) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('name'))
                ->from($db->quoteName('#__contact_details'))
                ->where($db->quoteName('id') . ' = ' . (int) $value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        $title = empty($title) ? JText::_('COM_CONTACT_SELECT_A_CONTACT') : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return JLayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => JText::_('COM_CONTACT_SELECT_A_CONTACT'),
            'function'   => 'jSelectContact_' . $this->id,
            'urls'       => array(
                'select' => $linkItems . '&function=jSelectContact_' . $this->id,
                'new'    => $linkItem . '&task=contact.add',
                'edit'   => $linkItem . '&task=contact.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => JText::_('COM_CONTACT_NEW_CONTACT'),
                'edit'   => JText::_('COM_CONTACT_EDIT_CONTACT'),
            ),
            'tips'       => array(
                'select' => 'COM_CONTACT_CHANGE_CONTACT',
                'new'    => 'COM_CONTACT_NEW_CONTACT',
                'edit'   => 'COM_CONTACT_EDIT_CONTACT',
            ),
            'allow'      => array(
                'select'    => $allowSelect,
                'new'       => $allowNew,
                'edit'      => $allowEdit,
                'clear'     => $allowClear,
                'propagate' => $allowPropagate && count($languages) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr('jSelectContact_' . $this->id, 0, -strlen((string) $this->element['language'])),
            'task'       => 'contact',
            'form'       => 'contact-form',
            'idField'    => 'jform_id',
            'titleField' => 'jform_name',
        ));
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   3.4
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }
}
