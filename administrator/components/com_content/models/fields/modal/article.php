<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;

/**
 * Supports a modal article picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Article extends JFormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    protected $type = 'Modal_Article';

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
        JFactory::getLanguage()->load('com_content', JPATH_ADMINISTRATOR);

        // The active article id field.
        $value = (int) $this->value > 0 ? (int) $this->value : '';

        // Setup variables for display.
        $linkArticles = 'index.php?option=com_content&view=articles&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $linkArticle  = 'index.php?option=com_content&view=article&layout=modal&tmpl=component&' . JSession::getFormToken() . '=1';
        $modalTitle   = JText::_('COM_CONTENT_CHANGE_ARTICLE');

        if (isset($this->element['language'])) {
            $linkArticles .= '&forcedLanguage=' . $this->element['language'];
            $linkArticle  .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle   .= " \u{2014} " . $this->element['label'];
        }

        if ($value) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = ' . (int) $value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (RuntimeException $e) {
                JError::raiseWarning(500, $e->getMessage());
            }
        }

        $title = empty($title) ? JText::_('COM_CONTENT_SELECT_AN_ARTICLE') : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return JLayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => JText::_('COM_CONTENT_SELECT_AN_ARTICLE'),
            'function'   => 'jSelectArticle_' . $this->id,
            'urls'       => array(
                'select' => $linkArticles . '&function=jSelectArticle_' . $this->id,
                'new'    => $linkArticle . '&task=article.add',
                'edit'   => $linkArticle . '&task=article.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => JText::_('COM_CONTENT_NEW_ARTICLE'),
                'edit'   => JText::_('COM_CONTENT_EDIT_ARTICLE'),
            ),
            'tips'       => array(
                'select' => 'COM_CONTENT_CHANGE_ARTICLE',
                'new'    => 'COM_CONTENT_NEW_ARTICLE',
                'edit'   => 'COM_CONTENT_EDIT_ARTICLE',
            ),
            'allow'      => array(
                'select'    => $allowSelect,
                'new'       => $allowNew,
                'edit'      => $allowEdit,
                'clear'     => $allowClear,
                'propagate' => $allowPropagate && count($languages) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr('jSelectArticle_' . $this->id, 0, -strlen((string) $this->element['language'])),
            'task'       => 'article',
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
     * @since   3.4
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }
}
