<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  Form
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Form;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;

/**
 * The item pickers' fields: modal_article, modal_category, modal_contact, modal_menu, modal_newsfeed.
 * Outside the Field namespace, so no field type resolves to it.
 *
 * @since  3.19.0
 */
abstract class ModalItemField extends FormField
{
    /**
     * What the picker opens and shows, set by each field:
     * component, listView, itemView, task (the item controller), table, titleColumn,
     * callback (followed by _<field id>), form and titleField (the item's edit form),
     * strings: change (the Select button and modal), select (no item), new, edit
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array();

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   3.19.0
     */
    protected function getInput()
    {
        $picker = array_merge(
            array('titleColumn' => 'title', 'form' => 'item-form', 'titleField' => 'jform_title'),
            $this->picker
        );
        $strings  = $picker['strings'];
        $callback = $picker['callback'] . '_' . $this->id;

        Factory::getLanguage()->load($picker['component'], JPATH_ADMINISTRATOR);

        $value = (int) $this->value > 0 ? (int) $this->value : '';
        $base  = 'index.php?option=' . $picker['component'] . '&view=%s&layout=modal&tmpl=component&' . Session::getFormToken() . '=1'
            . $this->getLinkQuery();

        $linkItems  = sprintf($base, $picker['listView']);
        $linkItem   = sprintf($base, $picker['itemView']);
        $modalTitle = Text::_($strings['change']);

        if (isset($this->element['language'])) {
            $linkItems  .= '&forcedLanguage=' . $this->element['language'];
            $linkItem   .= '&forcedLanguage=' . $this->element['language'];
            $modalTitle .= " \u{2014} " . $this->element['label'];
        }

        $title = '';

        if ($value) {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName($picker['titleColumn']))
                ->from($db->quoteName($picker['table']))
                ->where($db->quoteName('id') . ' = ' . (int) $value);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (\RuntimeException $e) {
                \JError::raiseWarning(500, $e->getMessage());
            }
        }

        $empty = $this->getEmptyText();
        $title = empty($title) ? $empty : htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
        $allow = $this->getAllowed();

        // The field's markup and script are shared by the item pickers (global.js: CMS.modalField)
        return LayoutHelper::render('joomla.form.field.modal', array(
            'id'         => $this->id,
            'name'       => $this->name,
            'value'      => $value,
            'required'   => $this->required,
            'title'      => $title,
            'empty'      => $empty,
            'function'   => $callback,
            'urls'       => array(
                'select' => $linkItems . '&function=' . $callback,
                'new'    => $linkItem . '&task=' . $picker['task'] . '.add',
                'edit'   => $linkItem . '&task=' . $picker['task'] . '.edit&id={id}',
            ),
            'titles'     => array(
                'select' => $modalTitle,
                'new'    => Text::_($strings['new']),
                'edit'   => Text::_($strings['edit']),
            ),
            'tips'       => array(
                'select' => $strings['change'],
                'new'    => $strings['new'],
                'edit'   => $strings['edit'],
            ),
            'allow'      => array(
                'select'    => $allow['select'],
                'new'       => $allow['new'],
                'edit'      => $allow['edit'],
                'clear'     => $allow['clear'],
                'propagate' => $allow['propagate'] && count(LanguageHelper::getContentLanguages(array(0, 1))) > 2,
            ),
            // The callbacks of the other languages' fields: this one's without its language tag
            'propagate'  => substr($callback, 0, -strlen((string) $this->element['language'])),
            'task'       => $picker['task'],
            'form'       => $picker['form'],
            'idField'    => 'jform_id',
            'titleField' => $picker['titleField'],
        ));
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   3.19.0
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }

    /**
     * What the picker's addresses add to their query (e.g. the categories' extension)
     *
     * @return  string  Query parts, each starting with "&"
     *
     * @since   3.19.0
     */
    protected function getLinkQuery()
    {
        return '';
    }

    /**
     * Which of the buttons the field shows, from its attributes
     *
     * @return  array  select, new, edit, clear, propagate
     *
     * @since   3.19.0
     */
    protected function getAllowed()
    {
        return array(
            'select'    => (string) $this->element['select'] != 'false',
            'new'       => (string) $this->element['new'] == 'true',
            'edit'      => (string) $this->element['edit'] == 'true',
            'clear'     => (string) $this->element['clear'] != 'false',
            'propagate' => (string) $this->element['propagate'] == 'true',
        );
    }

    /**
     * The text shown while no item is chosen
     *
     * @return  string
     *
     * @since   3.19.0
     */
    protected function getEmptyText()
    {
        return Text::_($this->picker['strings']['select']);
    }
}
