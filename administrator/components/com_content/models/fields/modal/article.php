<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\ModalItemField;

/**
 * Supports a modal article picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Article extends ModalItemField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    protected $type = 'Modal_Article';

    /**
     * What the picker opens and shows.
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array(
        'component'   => 'com_content',
        'listView'    => 'articles',
        'itemView'    => 'article',
        'task'        => 'article',
        'table'       => '#__content',
        'titleColumn' => 'title',
        'callback'    => 'jSelectArticle',
        'form'        => 'item-form',
        'titleField'  => 'jform_title',
        'strings'     => array(
            'change' => 'COM_CONTENT_CHANGE_ARTICLE',
            'select' => 'COM_CONTENT_SELECT_AN_ARTICLE',
            'new'    => 'COM_CONTENT_NEW_ARTICLE',
            'edit'   => 'COM_CONTENT_EDIT_ARTICLE',
        ),
    );
}
