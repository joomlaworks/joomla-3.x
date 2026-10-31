<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_fields
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * Fields Section
 *
 * @since  3.7.0
 */
class JFormFieldSection extends JFormFieldList
{
    public $type = 'Section';

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as an array container for the field.
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @since   3.7.0
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        // Choosing a context opens the list of its fields (media/com_fields/js/fields.js), whatever the XML's onchange says
        $this->onchange = '';
        JFactory::getDocument()->addScriptOptions('com_fields.context', $this->id);
        JHtml::_('script', 'com_fields/fields.min.js', array('version' => 'auto', 'relative' => true, 'position' => 'footer'));

        return $return;
    }
}
