<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\ModalItemField;

/**
 * Supports a modal newsfeed picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Newsfeed extends ModalItemField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    protected $type = 'Modal_Newsfeed';

    /**
     * What the picker opens and shows.
     *
     * @var    array
     * @since  3.19.0
     */
    protected $picker = array(
        'component'   => 'com_newsfeeds',
        'listView'    => 'newsfeeds',
        'itemView'    => 'newsfeed',
        'task'        => 'newsfeed',
        'table'       => '#__newsfeeds',
        'titleColumn' => 'name',
        'callback'    => 'jSelectNewsfeed',
        'form'        => 'newsfeed-form',
        'titleField'  => 'jform_name',
        'strings'     => array(
            'change' => 'COM_NEWSFEEDS_CHANGE_FEED',
            'select' => 'COM_NEWSFEEDS_SELECT_A_FEED',
            'new'    => 'COM_NEWSFEEDS_NEW_NEWSFEED',
            'edit'   => 'COM_NEWSFEEDS_EDIT_NEWSFEED',
        ),
    );
}
