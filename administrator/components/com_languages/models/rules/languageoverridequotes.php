<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Form rule to block quote characters in language override text for non-Super Users (CVE-2026-48954).
 *
 * Override text is rendered unescaped almost everywhere in Joomla core and third-party extensions via
 * JText::_(), including inside hardcoded HTML attributes (e.g. title="<?php echo JText::_('KEY'); ?>").
 * A quote character in an override value breaks out of that attribute and injects a new one, bypassing
 * InputFilter entirely because InputFilter only sanitises HTML tags found *inside* the override text —
 * it has nothing to inspect when the payload is a bare quote with no angle brackets. Rejecting quotes at
 * the point of entry (for anyone who isn't already fully trusted) closes the whole class of sink, since
 * auditing every echo of JText::_() across core and every installed extension is not feasible.
 *
 * @since  3.15.0
 */
class JFormRuleLanguageoverridequotes extends JFormRule
{
	/**
	 * Method to test if the override text contains a quote character.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value.
	 * @param   Registry          $input    An optional Registry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 *
	 * @since   3.15.0
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?JForm $form = null)
	{
		// Super Users are already fully trusted; only restrict quotes for everyone else.
		if (JFactory::getUser()->authorise('core.admin'))
		{
			return true;
		}

		return strpos((string) $value, '"') === false && strpos((string) $value, "'") === false;
	}
}
