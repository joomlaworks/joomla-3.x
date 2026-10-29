<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;

/**
 * Supports a modal newsfeeds picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Newsfeed extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     * @since   1.6
     */
    protected $type = 'Modal_Newsfeed';

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
        JFactory::getLanguage()->load('com_newsfeeds', JPATH_ADMINISTRATOR);

        // The active newsfeed id field.
        $value = (int) $this->value > 0 ? (int) $this->value : '';

        // Setup variables for display.
        $linkItems  = 'index.php?option=com_newsfeeds&view=newsfeeds&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $linkItem   = 'index.php?option=com_newsfeeds&view=newsfeed&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $modalTitle = JText::_('COM_NEWSFEEDS_CHANGE_FEED');

        if (isset($this->element['language'])) {
            $linkItems  .= '&forcedLanguage=' . $this->element['language'];
            $linkItem   .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle .= " \u{2014} " . $this->element['label'];
        }

        if ($value) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('name'))
                ->from($db->quoteName('#__newsfeeds'))
                ->where($db->quoteName('id') . ' = ' . (int) $value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        $title = empty($title) ? JText::_('COM_NEWSFEEDS_SELECT_A_FEED') : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return JLayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => JText::_('COM_NEWSFEEDS_SELECT_A_FEED'),
            'function'   => 'jSelectNewsfeed_' . $this->id,
            'urls'       => array(
                'select' => $linkItems . '&function=jSelectNewsfeed_' . $this->id,
                'new'    => $linkItem . '&task=newsfeed.add',
                'edit'   => $linkItem . '&task=newsfeed.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => JText::_('COM_NEWSFEEDS_NEW_NEWSFEED'),
                'edit'   => JText::_('COM_NEWSFEEDS_EDIT_NEWSFEED'),
            ),
            'tips'       => array(
                'select' => 'COM_NEWSFEEDS_CHANGE_FEED',
                'new'    => 'COM_NEWSFEEDS_NEW_NEWSFEED',
                'edit'   => 'COM_NEWSFEEDS_EDIT_NEWSFEED',
            ),
            'allow'      => array(
                'select'    => $allowSelect,
                'new'       => $allowNew,
                'edit'      => $allowEdit,
                'clear'     => $allowClear,
                'propagate' => $allowPropagate && count($languages) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr('jSelectNewsfeed_' . $this->id, 0, -strlen((string) $this->element['language'])),
            'task'       => 'newsfeed',
            'form'       => 'newsfeed-form',
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
