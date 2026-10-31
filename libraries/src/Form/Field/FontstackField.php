<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Form\Field;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\FontStackHelper;

FormHelper::loadFieldClass('list');

/**
 * A font stack of fonts already on visitors' devices (FontStackHelper::STACKS, from Modern Font Stacks): each option shows
 * the stack's name and its fonts, written in the stack itself, so the searchable list previews them. The value is the
 * stack's key; FontStackHelper::get() gives its CSS. Options of the field's XML (e.g. an empty one for the template's own
 * fonts) come first.
 *
 * @since  3.18.0
 */
class FontstackField extends \JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.18.0
     */
    protected $type = 'Fontstack';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   3.18.0
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        foreach (FontStackHelper::STACKS as $key => $stack) {
            $option             = \JHtml::_('select.option', $key, $stack[0] . ': ' . $stack[1]);
            $option->optionattr = ' style="font-family:' . htmlspecialchars($stack[1], ENT_QUOTES, 'UTF-8') . '"';
            $options[]          = $option;
        }

        return $options;
    }
}
