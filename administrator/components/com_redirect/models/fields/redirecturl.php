<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('text');

/**
 * An address on this site: a text field after the site's root address, which entries store their addresses relative to.
 *
 * @since  3.18.0
 */
class JFormFieldRedirectUrl extends JFormFieldText
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.18.0
     */
    protected $type = 'RedirectUrl';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   3.18.0
     */
    protected function getInput()
    {
        return '<div class="input-prepend redirect-url"><span class="add-on">' . htmlspecialchars(JUri::root(), ENT_COMPAT, 'UTF-8')
            . '</span>' . parent::getInput() . '</div>';
    }
}
