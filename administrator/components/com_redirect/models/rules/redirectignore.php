<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * The ignore list's regular expressions (lines written as /.../) must compile.
 *
 * @since  3.18.0
 */
class JFormRuleRedirectignore extends JFormRule
{
    /**
     * Method to test the value.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value.
     * @param   Registry          $input    An optional Registry object with the entire data set to validate against the entire form.
     * @param   JForm             $form     The form object for which the field is being tested.
     *
     * @return  boolean|UnexpectedValueException
     *
     * @since   3.18.0
     */
    public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
    {
        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        $invalid = array();

        foreach (RedirectHelper::getIgnoreList((string) $value) as $line) {
            if (RedirectHelper::isPattern($line, $valid) && !$valid) {
                $invalid[] = $line;
            }
        }

        if ($invalid) {
            return new UnexpectedValueException(JText::sprintf('COM_REDIRECT_IGNORE_INVALID', implode(', ', $invalid)));
        }

        return true;
    }
}
