<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ModulesHelper', JPATH_ADMINISTRATOR . '/components/com_modules/helpers/modules.php');

JFormHelper::loadFieldClass('list');

/**
 * The installed templates of the client the module positions picker shows.
 *
 * @since  3.19.0
 */
class JFormFieldModulesTemplate extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.19.0
     */
    protected $type = 'ModulesTemplate';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   3.19.0
     */
    public function getOptions()
    {
        $clientId = (int) JFactory::getApplication()->getUserState('com_modules.positions.client_id', 0);
        $options  = array();

        foreach (ModulesHelper::getTemplates($clientId) as $template) {
            $options[] = JHtml::_('select.option', $template->element, JText::_($template->name));
        }

        return array_merge(parent::getOptions(), $options);
    }
}
