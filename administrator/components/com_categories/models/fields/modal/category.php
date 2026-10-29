<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;

/**
 * Supports a modal category picker.
 *
 * @since  3.1
 */
class JFormFieldModal_Category extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   1.6
     */
    protected $type = 'Modal_Category';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.6
     */
    protected function getInput()
    {
        if ($this->element['extension']) {
            $extension = (string) $this->element['extension'];
        } else {
            $extension = (string) JFactory::getApplication()->input->get('extension', 'com_content');
        }

        $allowNew       = ((string) $this->element['new'] == 'true');
        $allowEdit      = ((string) $this->element['edit'] == 'true');
        $allowClear     = ((string) $this->element['clear'] != 'false');
        $allowSelect    = ((string) $this->element['select'] != 'false');
        $allowPropagate = ((string) $this->element['propagate'] == 'true');

        $languages = LanguageHelper::getContentLanguages(array(0, 1));

        // Load language.
        JFactory::getLanguage()->load('com_categories', JPATH_ADMINISTRATOR);

        // The active category id field.
        $value = (int) $this->value > 0 ? (int) $this->value : '';

        // Setup variables for display.
        $linkItems  = 'index.php?option=com_categories&view=categories&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1'
            . '&extension=' . $extension;
        $linkItem   = 'index.php?option=com_categories&view=category&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1'
            . '&extension=' . $extension;
        $modalTitle = JText::_('COM_CATEGORIES_CHANGE_CATEGORY');

        if (isset($this->element['language'])) {
            $linkItems  .= '&forcedLanguage=' . $this->element['language'];
            $linkItem   .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle .= " \u{2014} " . $this->element['label'];
        }

        if ($value) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = ' . (int) $value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        $title = empty($title) ? JText::_('COM_CATEGORIES_SELECT_A_CATEGORY') : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return JLayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => JText::_('COM_CATEGORIES_SELECT_A_CATEGORY'),
            'function'   => 'jSelectCategory_' . $this->id,
            'urls'       => array(
                'select' => $linkItems . '&function=jSelectCategory_' . $this->id,
                'new'    => $linkItem . '&task=category.add',
                'edit'   => $linkItem . '&task=category.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => JText::_('COM_CATEGORIES_NEW_CATEGORY'),
                'edit'   => JText::_('COM_CATEGORIES_EDIT_CATEGORY'),
            ),
            'tips'       => array(
                'select' => 'COM_CATEGORIES_CHANGE_CATEGORY',
                'new'    => 'COM_CATEGORIES_NEW_CATEGORY',
                'edit'   => 'COM_CATEGORIES_EDIT_CATEGORY',
            ),
            'allow'      => array(
                'select'    => $allowSelect,
                'new'       => $allowNew,
                'edit'      => $allowEdit,
                'clear'     => $allowClear,
                'propagate' => $allowPropagate && count($languages) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr('jSelectCategory_' . $this->id, 0, -strlen((string) $this->element['language'])),
            'task'       => 'category',
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
