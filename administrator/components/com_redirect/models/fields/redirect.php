<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * The code a redirect is sent with: the component's default, 301 or 302.
 *
 * @since  3.4
 */
class JFormFieldRedirect extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.4
     */
    protected $type = 'Redirect';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   3.4
     */
    protected function getOptions()
    {
        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        $options = RedirectHelper::getCodeOptions();

        return array_merge(parent::getOptions(), $options);
    }
}
